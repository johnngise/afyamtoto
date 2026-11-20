import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";
import type { Prisma } from "@prisma/client";

export const runtime = "nodejs";

export async function GET() {
  const access = await authorize({ permission: "REFERRAL_VIEW", allowedRoles: ["community_health_worker", "health_professional"] });
  if (!access.allowed) return access.response;
  const user = access.user;
  let where: Prisma.HealthReferralWhereInput;
  if (user.roleCode === "community_health_worker") {
    where = { agentUserId: BigInt(user.id) };
  } else if (user.roleCode === "health_professional") {
    const centers = await prisma.userHealthCenter.findMany({ where: { userId: BigInt(user.id) }, select: { healthCenterId: true } });
    where = { healthCenterId: { in: centers.map((center) => center.healthCenterId) } };
  } else {
    return jsonError("Accès réservé au personnel de santé.", 403);
  }
  try {
    const referrals = await prisma.healthReferral.findMany({ where: { ...where, child: { guardians: { some: { consentStatus: "granted" } } } }, include: { child: { select: { firstName: true, lastName: true } }, center: { select: { name: true } } }, orderBy: { referredAt: "desc" }, take: 200 });
    return Response.json({ referrals: referrals.map((item) => ({ ...item, id: item.id.toString(), childId: item.childId.toString(), agentUserId: item.agentUserId.toString(), healthCenterId: item.healthCenterId.toString(), communityVisitId: item.communityVisitId?.toString() ?? null })) });
  } catch (error) {
    console.error("Referral listing failed", error);
    return jsonError("Impossible de charger les orientations.", 503);
  }
}

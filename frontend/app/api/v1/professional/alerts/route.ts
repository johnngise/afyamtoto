import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function GET() {
  const access = await authorize({ permission: "HEALTH_ALERT_VIEW", allowedRoles: ["health_professional"] });
  if (!access.allowed) return access.response;
  const professional = access.user;
  const centers = await prisma.userHealthCenter.findMany({ where: { userId: BigInt(professional.id) }, select: { healthCenterId: true } });
  const alerts = await prisma.healthAlert.findMany({ where: { healthCenterId: { in: centers.map((item) => item.healthCenterId) }, child: { guardians: { some: { consentStatus: "granted" } } } }, orderBy: { createdAt: "desc" }, take: 200 });
  return Response.json({ alerts: alerts.map((alert) => ({ ...alert, id: alert.id.toString(), childId: alert.childId.toString(), createdByUserId: alert.createdByUserId?.toString() ?? null, reviewedByUserId: alert.reviewedByUserId?.toString() ?? null, healthCenterId: alert.healthCenterId?.toString() ?? null })) });
}

import { prisma } from "@/lib/db";
import type { PublicUser } from "@/lib/auth";

export async function communityWorkerCanAccessChild(user: PublicUser, childId: bigint) {
  if (user.roleCode !== "community_health_worker") return false;
  const child = await prisma.child.findUnique({ where: { id: childId }, include: { guardians: { select: { consentStatus: true } } } });
  if (!child || !child.guardians.some((guardian) => guardian.consentStatus === "granted")) return false;
  if (child.createdByUserId === BigInt(user.id)) return true;
  const userId = BigInt(user.id);
  const [growth, nutrition, visit, referral] = await Promise.all([
    prisma.growthMeasurement.count({ where: { childId, recordedByUserId: userId } }),
    prisma.nutritionRecord.count({ where: { childId, healthWorkerId: userId } }),
    prisma.communityVisit.count({ where: { childId, agentUserId: userId } }),
    prisma.healthReferral.count({ where: { childId, agentUserId: userId } }),
  ]);
  return growth + nutrition + visit + referral > 0;
}

export async function professionalCanAccessChild(user: PublicUser, childId: bigint, centerId: bigint) {
  if (user.roleCode !== "health_professional") return false;
  const [assignment, consent, linkedAlert] = await Promise.all([
    prisma.userHealthCenter.findUnique({ where: { userId_healthCenterId: { userId: BigInt(user.id), healthCenterId: centerId } } }),
    prisma.childGuardian.findFirst({ where: { childId, consentStatus: "granted" } }),
    prisma.healthAlert.findFirst({ where: { childId, healthCenterId: centerId } }),
  ]);
  return Boolean(assignment && consent && linkedAlert);
}

export async function parentCanAccessChild(user: PublicUser, childId: bigint, requireConsent = false) {
  if (user.roleCode !== "parent") return false;
  const guardian = await prisma.childGuardian.findUnique({ where: { childId_userId: { childId, userId: BigInt(user.id) } } });
  return Boolean(guardian && (!requireConsent || guardian.consentStatus === "granted"));
}

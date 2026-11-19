import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { communityWorkerCanAccessChild } from "@/lib/health-access";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
const prioritySeverity = { routine: "low", prompt: "medium", emergency: "high" } as const;

export async function POST(request: Request) {
  const access = await authorize({ permission: "REFERRAL_CREATE", allowedRoles: ["community_health_worker"] });
  if (!access.allowed) return access.response;
  const agent = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  if (typeof data.childId !== "string" || !/^\d+$/u.test(data.childId) || typeof data.healthCenterId !== "string" || !/^\d+$/u.test(data.healthCenterId)) return jsonError("Enfant ou centre invalide.", 400);
  const priority = data.priority;
  if (priority !== "routine" && priority !== "prompt" && priority !== "emergency") return jsonError("Priorité d’orientation invalide.", 400);
  const concern = typeof data.concernDescription === "string" ? data.concernDescription.trim() : "";
  if (!concern || concern.length > 1000) return jsonError("La situation à signaler est obligatoire (1 000 caractères maximum).", 400);
  const childId = BigInt(data.childId);
  const centerId = BigInt(data.healthCenterId);
  const childAccess = await authorize({ permission: "CHILD_VIEW", allowedRoles: ["community_health_worker"], resource: { type: "child", id: childId.toString(), medicalData: true } });
  if (!childAccess.allowed) return childAccess.response;
  const centerAccess = await authorize({ permission: "REFERRAL_CREATE", allowedRoles: ["community_health_worker"], resource: { type: "health_center", id: centerId.toString() } });
  if (!centerAccess.allowed) return centerAccess.response;
  if (!(await communityWorkerCanAccessChild(agent, childId))) return jsonError("Ce dossier n’est pas accessible ou le consentement est inactif.", 403);
  const center = await prisma.healthCenter.findUnique({ where: { id: centerId } });
  if (!center) return jsonError("Centre de santé introuvable.", 404);
  const created = await prisma.$transaction(async (tx) => {
    const referral = await tx.healthReferral.create({ data: { childId, agentUserId: BigInt(agent.id), healthCenterId: centerId, concernDescription: concern, priority } });
    await tx.healthAlert.create({ data: { childId, alertType: "community_referral", severity: prioritySeverity[priority], description: concern, createdByUserId: BigInt(agent.id), healthCenterId: centerId } });
    const professionals = await tx.userHealthCenter.findMany({ where: { healthCenterId: centerId, user: { isActive: true, roles: { some: { roleCode: "health_professional" } } } }, select: { userId: true } });
    if (professionals.length) await tx.notification.createMany({ data: professionals.map(({ userId }) => ({ userId, title: "Orientation communautaire à examiner", message: "Une orientation nécessite une évaluation professionnelle.", type: "community_referral" })) });
    const guardians = await tx.childGuardian.findMany({ where: { childId, consentStatus: "granted" }, select: { userId: true } });
    if (guardians.length) await tx.notification.createMany({ data: guardians.map(({ userId }) => ({ userId, title: "Orientation vers un centre de santé", message: "Un agent a créé une orientation pour le suivi de votre enfant.", type: "community_referral" })) });
    await tx.activityLog.create({ data: { userId: BigInt(agent.id), action: "community_referral_created", description: "Orientation communautaire créée." } });
    return referral;
  });
  return Response.json({ referral: { ...created, id: created.id.toString(), childId: created.childId.toString(), agentUserId: created.agentUserId.toString(), healthCenterId: created.healthCenterId.toString() } }, { status: 201 });
}

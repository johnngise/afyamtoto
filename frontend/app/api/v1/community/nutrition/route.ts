import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { communityWorkerCanAccessChild } from "@/lib/health-access";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function POST(request: Request) {
  const access = await authorize({ permission: "NUTRITION_RECORD_CREATE", allowedRoles: ["community_health_worker"] });
  if (!access.allowed) return access.response;
  const agent = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  if (typeof data.childId !== "string" || !/^\d+$/u.test(data.childId) || typeof data.recordDate !== "string" || !/^\d{4}-\d{2}-\d{2}$/u.test(data.recordDate)) return jsonError("Profil ou date invalide.", 400);
  const date = new Date(`${data.recordDate}T12:00:00.000Z`);
  const childId = BigInt(data.childId);
  const childAccess = await authorize({ permission: "CHILD_VIEW", allowedRoles: ["community_health_worker"], resource: { type: "child", id: childId.toString(), medicalData: true } });
  if (!childAccess.allowed) return childAccess.response;
  const child = await prisma.child.findUnique({ where: { id: childId } });
  if (!child) return jsonError("Profil enfant introuvable.", 404);
  if (Number.isNaN(date.getTime()) || date.toISOString().slice(0, 10) !== data.recordDate || date > new Date() || date < child.dateOfBirth) return jsonError("La date de dépistage est hors limites.", 400);
  const feedingType = typeof data.feedingType === "string" ? data.feedingType.trim() : "";
  const observations = typeof data.observations === "string" ? data.observations.trim() : "";
  if (feedingType.length > 64 || !observations || observations.length > 4000) return jsonError("Type d’alimentation ou observations invalides.", 400);
  if (!(await communityWorkerCanAccessChild(agent, childId))) return jsonError("Ce dossier n’est pas accessible ou le consentement est inactif.", 403);
  const record = await prisma.$transaction(async (tx) => {
    const created = await tx.nutritionRecord.create({ data: { childId, recordDate: date, feedingType: feedingType || null, observations, healthWorkerId: BigInt(agent.id) } });
    await tx.activityLog.create({ data: { userId: BigInt(agent.id), action: "community_nutrition_screening_recorded", description: "Observations de dépistage nutritionnel enregistrées." } });
    return created;
  });
  return Response.json({ record: { ...record, id: record.id.toString(), childId: record.childId.toString(), healthWorkerId: record.healthWorkerId.toString() } }, { status: 201 });
}

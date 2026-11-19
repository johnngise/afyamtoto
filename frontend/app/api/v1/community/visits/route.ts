import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { communityWorkerCanAccessChild } from "@/lib/health-access";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
const dateOnly = /^\d{4}-\d{2}-\d{2}$/u;

export async function POST(request: Request) {
  const access = await authorize({ permission: "COMMUNITY_VISIT_CREATE", allowedRoles: ["community_health_worker"] });
  if (!access.allowed) return access.response;
  const agent = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  if (typeof data.childId !== "string" || !/^\d+$/u.test(data.childId)) return jsonError("Profil enfant invalide.", 400);
  if (data.visitType !== "home" && data.visitType !== "community_outreach" && data.visitType !== "follow_up") return jsonError("Type de visite invalide.", 400);
  const visitedOn = typeof data.visitedOn === "string" ? data.visitedOn : "";
  const purpose = typeof data.purpose === "string" ? data.purpose.trim() : "";
  const observations = typeof data.observations === "string" ? data.observations.trim() : "";
  if (!dateOnly.test(visitedOn)) return jsonError("Date de visite invalide.", 400);
  const visitedAt = new Date(`${visitedOn}T12:00:00.000Z`);
  const childId = BigInt(data.childId);
  const childAccess = await authorize({ permission: "CHILD_VIEW", allowedRoles: ["community_health_worker"], resource: { type: "child", id: childId.toString(), medicalData: true } });
  if (!childAccess.allowed) return childAccess.response;
  const child = await prisma.child.findUnique({ where: { id: childId } });
  if (!child) return jsonError("Profil enfant introuvable.", 404);
  if (Number.isNaN(visitedAt.getTime()) || visitedAt.toISOString().slice(0, 10) !== visitedOn || visitedAt > new Date() || visitedAt < child.dateOfBirth) return jsonError("La date de visite est hors limites.", 400);
  if (!purpose || purpose.length > 160 || observations.length > 4000) return jsonError("Motif ou observations invalides.", 400);
  if (!(await communityWorkerCanAccessChild(agent, childId))) return jsonError("Ce dossier n’est pas accessible ou le consentement est inactif.", 403);
  const visit = await prisma.$transaction(async (tx) => {
    const created = await tx.communityVisit.create({ data: { childId, agentUserId: BigInt(agent.id), visitType: data.visitType as string, visitedAt, purpose, observations: observations || null } });
    await tx.activityLog.create({ data: { userId: BigInt(agent.id), action: "community_visit_recorded", description: "Activité communautaire enregistrée." } });
    return created;
  });
  return Response.json({ visit: { ...visit, id: visit.id.toString(), childId: visit.childId.toString(), agentUserId: visit.agentUserId.toString() } }, { status: 201 });
}

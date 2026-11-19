import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
type Context = { params: Promise<{ id: string }> };
const statuses = ["under_review", "referred", "resolved", "dismissed"];

export async function PATCH(request: Request, context: Context) {
  const access = await authorize({ permission: "HEALTH_ALERT_RESOLVE", allowedRoles: ["health_professional"] });
  if (!access.allowed) return access.response;
  const professional = access.user;
  const { id } = await context.params;
  if (!/^\d+$/u.test(id)) return jsonError("Alerte introuvable.", 404);
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  if (typeof data.status !== "string" || !statuses.includes(data.status)) return jsonError("Statut d’alerte invalide.", 400);
  const notes = typeof data.notes === "string" ? data.notes.trim() : "";
  if (notes.length > 1000) return jsonError("Les notes ne peuvent pas dépasser 1 000 caractères.", 400);
  try {
    const result = await prisma.$transaction(async (tx) => {
      const alert = await tx.healthAlert.findUnique({ where: { id: BigInt(id) } });
      if (!alert || !alert.healthCenterId || !["pending_review", "under_review"].includes(alert.status)) throw new Error("NOT_AVAILABLE");
      const assigned = await tx.userHealthCenter.findUnique({ where: { userId_healthCenterId: { userId: BigInt(professional.id), healthCenterId: alert.healthCenterId } } });
      const guardians = await tx.childGuardian.findMany({ where: { childId: alert.childId, consentStatus: "granted" }, select: { userId: true } });
      if (!assigned || !guardians.length) throw new Error("FORBIDDEN");
      const updated = await tx.healthAlert.update({ where: { id: alert.id }, data: { status: data.status as string, reviewedByUserId: BigInt(professional.id), reviewedAt: new Date(), reviewNotes: notes || null, resolvedAt: data.status === "resolved" ? new Date() : null } });
      await tx.notification.createMany({ data: guardians.map(({ userId }) => ({ userId, title: "Mise à jour du suivi de santé", message: "Un professionnel a mis à jour le suivi. Consultez votre espace parent.", type: "health_alert_review" })) });
      await tx.activityLog.create({ data: { userId: BigInt(professional.id), action: "professional_alert_reviewed", description: `Revue professionnelle de l’alerte ${id}.` } });
      return updated;
    });
    return Response.json({ alert: { ...result, id: result.id.toString(), childId: result.childId.toString(), createdByUserId: result.createdByUserId?.toString() ?? null, reviewedByUserId: result.reviewedByUserId?.toString() ?? null, healthCenterId: result.healthCenterId?.toString() ?? null } });
  } catch (error) {
    if (error instanceof Error && error.message === "FORBIDDEN") return jsonError("Cette alerte n’est pas accessible ou le consentement est inactif.", 403);
    if (error instanceof Error && error.message === "NOT_AVAILABLE") return jsonError("Alerte introuvable ou déjà close.", 409);
    console.error("Alert review failed", error);
    return jsonError("La mise à jour de l’alerte a échoué.", 503);
  }
}

import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
type Context = { params: Promise<{ id: string }> };

export async function PATCH(request: Request, context: Context) {
  const access = await authorize({ permission: "REFERRAL_FOLLOW_UP", allowedRoles: ["community_health_worker", "health_center_manager", "health_facility_administrator"] });
  if (!access.allowed) return access.response;
  const actor = access.user;
  const { id } = await context.params;
  if (!/^\d+$/u.test(id)) return jsonError("Orientation introuvable.", 404);
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  if (data.outcome !== "attended" && data.outcome !== "not_attended") return jsonError("Résultat de suivi invalide.", 400);
  const notes = typeof data.notes === "string" ? data.notes.trim() : "";
  if (notes.length > 500) return jsonError("Les notes ne peuvent pas dépasser 500 caractères.", 400);
  try {
    const referral = await prisma.$transaction(async (tx) => {
      const current = await tx.healthReferral.findUnique({ where: { id: BigInt(id) } });
      if (!current || !["awaiting_follow_up", "not_attended"].includes(current.status)) throw new Error("NOT_AVAILABLE");
      if (actor.roleCode === "community_health_worker" && current.agentUserId !== BigInt(actor.id)) throw new Error("FORBIDDEN");
      if (actor.roleCode === "health_center_manager" && !(await tx.userHealthCenter.findUnique({ where: { userId_healthCenterId: { userId: BigInt(actor.id), healthCenterId: current.healthCenterId } } }))) throw new Error("FORBIDDEN");
      const guardians = await tx.childGuardian.findMany({ where: { childId: current.childId, consentStatus: "granted" }, select: { userId: true } });
      if (!guardians.length) throw new Error("CONSENT_REVOKED");
      const updated = await tx.healthReferral.update({ where: { id: current.id }, data: { status: data.outcome as string, followedUpAt: new Date(), followUpNotes: notes || null } });
      await tx.notification.createMany({ data: guardians.map(({ userId }) => ({ userId, title: "Mise à jour de la prise en charge", message: "Le centre a enregistré une mise à jour de l’orientation de votre enfant.", type: "referral_update" })) });
      await tx.activityLog.create({ data: { userId: BigInt(actor.id), action: actor.roleCode === "community_health_worker" ? "community_referral_followed_up" : "facility_referral_updated", description: `Suivi de l’orientation ${id} mis à jour.` } });
      return updated;
    });
    return Response.json({ referral: { ...referral, id: referral.id.toString(), childId: referral.childId.toString(), agentUserId: referral.agentUserId.toString(), healthCenterId: referral.healthCenterId.toString(), communityVisitId: referral.communityVisitId?.toString() ?? null } });
  } catch (error) {
    if (error instanceof Error && error.message === "FORBIDDEN") return jsonError("Cette orientation n’est pas affectée à votre activité.", 403);
    if (error instanceof Error && error.message === "CONSENT_REVOKED") return jsonError("Le consentement au suivi de santé n’est plus actif.", 409);
    if (error instanceof Error && error.message === "NOT_AVAILABLE") return jsonError("Orientation introuvable ou déjà suivie.", 409);
    console.error("Referral follow-up failed", error);
    return jsonError("La mise à jour de l’orientation a échoué.", 503);
  }
}

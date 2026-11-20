import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
type Context = { params: Promise<{ id: string }> };

export async function PATCH(request: Request, context: Context) {
  const access = await authorize({ permission: "REFERRAL_FOLLOW_UP", allowedRoles: ["health_professional"] });
  if (!access.allowed) return access.response;
  const professional = access.user;
  const { id } = await context.params;
  if (!/^\d+$/u.test(id)) return jsonError("Orientation introuvable.", 404);
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  if (data.outcome !== "attended" && data.outcome !== "not_attended") return jsonError("Résultat de prise en charge invalide.", 400);
  const notes = typeof data.notes === "string" ? data.notes.trim() : "";
  if (notes.length > 1000) return jsonError("Les observations ne peuvent pas dépasser 1 000 caractères.", 400);
  try {
    const referral = await prisma.$transaction(async (tx) => {
      const current = await tx.healthReferral.findUnique({ where: { id: BigInt(id) } });
      if (!current || !["awaiting_follow_up", "not_attended"].includes(current.status)) throw new Error("NOT_AVAILABLE");
      const assignment = await tx.userHealthCenter.findUnique({ where: { userId_healthCenterId: { userId: BigInt(professional.id), healthCenterId: current.healthCenterId } } });
      const guardians = await tx.childGuardian.findMany({ where: { childId: current.childId, consentStatus: "granted" }, select: { userId: true } });
      if (!assignment || !guardians.length) throw new Error("FORBIDDEN");
      const updated = await tx.healthReferral.update({ where: { id: current.id }, data: { status: data.outcome as string, followedUpAt: new Date(), followUpNotes: notes || null } });
      if (notes) await tx.professionalObservation.create({ data: { childId: current.childId, healthCenterId: current.healthCenterId, healthProfessionalId: BigInt(professional.id), healthReferralId: current.id, observationType: "clinical_observation", content: notes } });
      await tx.notification.createMany({ data: guardians.map(({ userId }) => ({ userId, title: "Mise à jour de la prise en charge", message: "Un professionnel a mis à jour le suivi de l’orientation de votre enfant.", type: "referral_update" })) });
      await tx.activityLog.create({ data: { userId: BigInt(professional.id), action: "professional_referral_reviewed", description: `Suivi professionnel de l’orientation ${id}.` } });
      return updated;
    });
    return Response.json({ referral: { ...referral, id: referral.id.toString(), childId: referral.childId.toString(), agentUserId: referral.agentUserId.toString(), healthCenterId: referral.healthCenterId.toString(), communityVisitId: referral.communityVisitId?.toString() ?? null } });
  } catch (error) {
    if (error instanceof Error && error.message === "FORBIDDEN") return jsonError("Cette orientation n’est pas accessible ou le consentement est inactif.", 403);
    if (error instanceof Error && error.message === "NOT_AVAILABLE") return jsonError("Orientation introuvable ou déjà suivie.", 409);
    console.error("Professional referral review failed", error);
    return jsonError("La mise à jour de l’orientation a échoué.", 503);
  }
}

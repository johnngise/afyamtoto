import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
type Context = { params: Promise<{ id: string }> };

export async function PATCH(request: Request, context: Context) {
  const access = await authorize({ permission: "APPOINTMENT_MANAGE", allowedRoles: ["health_center_manager", "health_facility_administrator"] });
  if (!access.allowed) return access.response;
  const manager = access.user;
  const { id } = await context.params;
  if (!/^\d+$/u.test(id)) return jsonError("Demande introuvable.", 404);
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const status = data.status;
  if (status !== "confirmed" && status !== "declined") return jsonError("La réponse doit confirmer ou refuser la demande.", 400);
  const scheduledAt = typeof data.scheduledAt === "string" && data.scheduledAt ? new Date(data.scheduledAt) : null;
  if (status === "confirmed" && (!scheduledAt || Number.isNaN(scheduledAt.getTime()) || scheduledAt <= new Date())) return jsonError("Une date future valide est requise pour confirmer le rendez-vous.", 400);
  if (status === "declined" && scheduledAt) return jsonError("Une date ne peut être fournie que pour confirmer le rendez-vous.", 400);
  const responseMessage = typeof data.responseMessage === "string" ? data.responseMessage.trim() : "";
  if (responseMessage.length > 500) return jsonError("Le message ne peut pas dépasser 500 caractères.", 400);
  try {
    const result = await prisma.$transaction(async (tx) => {
      const appointment = await tx.appointmentRequest.findUnique({ where: { id: BigInt(id) } });
      if (!appointment || appointment.status !== "pending") throw new Error("NOT_AVAILABLE");
      const assigned = await tx.userHealthCenter.findUnique({ where: { userId_healthCenterId: { userId: BigInt(manager.id), healthCenterId: appointment.healthCenterId } } });
      if (!assigned) throw new Error("FORBIDDEN");
      const guardian = await tx.childGuardian.findUnique({ where: { childId_userId: { childId: appointment.childId, userId: appointment.parentUserId } } });
      if (!guardian || guardian.consentStatus !== "granted") throw new Error("CONSENT_REVOKED");
      const updated = await tx.appointmentRequest.updateMany({ where: { id: appointment.id, status: "pending" }, data: { status, scheduledAt, responseMessage: responseMessage || null } });
      if (updated.count !== 1) throw new Error("NOT_AVAILABLE");
      await tx.notification.create({ data: { userId: appointment.parentUserId, title: status === "confirmed" ? "Rendez-vous confirmé" : "Réponse du centre de santé", message: status === "confirmed" ? "Le centre a confirmé votre rendez-vous. Consultez votre espace parent pour la date." : responseMessage || "Le centre ne peut pas accepter cette demande.", type: "appointment_response" } });
      await tx.activityLog.create({ data: { userId: BigInt(manager.id), action: "appointment_request_responded", description: `Réponse enregistrée pour la demande ${id}.` } });
      return tx.appointmentRequest.findUnique({ where: { id: appointment.id } });
    });
    return Response.json({ appointment: result ? { ...result, id: result.id.toString(), childId: result.childId.toString(), parentUserId: result.parentUserId.toString(), healthCenterId: result.healthCenterId.toString() } : null });
  } catch (error) {
    if (error instanceof Error && error.message === "FORBIDDEN") return jsonError("Cette demande n’est pas affectée à votre centre.", 403);
    if (error instanceof Error && error.message === "CONSENT_REVOKED") return jsonError("Le consentement au suivi de santé n’est plus actif.", 409);
    if (error instanceof Error && error.message === "NOT_AVAILABLE") return jsonError("Cette demande est introuvable ou a déjà été traitée.", 409);
    console.error("Appointment response failed", error);
    return jsonError("La réponse à la demande a échoué.", 503);
  }
}

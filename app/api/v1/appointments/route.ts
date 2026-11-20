import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
const requestTypes = ["consultation", "vaccination_follow_up", "growth_follow_up", "nutrition_consultation"];

export async function GET() {
  const access = await authorize({ permission: "APPOINTMENT_VIEW", allowedRoles: ["parent", "health_facility_administrator", "health_center_manager"] });
  if (!access.allowed) return access.response;
  const user = access.user;
  try {
    const where = user.roleCode === "parent"
      ? { parentUserId: BigInt(user.id) }
      : (user.roleCode === "health_center_manager" || user.roleCode === "health_facility_administrator")
        ? { healthCenterId: { in: (await prisma.userHealthCenter.findMany({ where: { userId: BigInt(user.id) }, select: { healthCenterId: true } })).map((assignment) => assignment.healthCenterId) } }
        : null;
    if (!where) return jsonError("Accès réservé aux parents et gestionnaires de centres.", 403);
    const appointments = await prisma.appointmentRequest.findMany({ where, orderBy: { createdAt: "desc" }, take: 100 });
    return Response.json({ appointments: appointments.map((item) => ({ ...item, id: item.id.toString(), childId: item.childId.toString(), parentUserId: item.parentUserId.toString(), healthCenterId: item.healthCenterId.toString() })) });
  } catch (error) {
    console.error("Appointment listing failed", error);
    return jsonError("Impossible de charger les demandes.", 503);
  }
}

export async function POST(request: Request) {
  const access = await authorize({ permission: "APPOINTMENT_CREATE", allowedRoles: ["parent"] });
  if (!access.allowed) return access.response;
  const user = access.user;
  if (user.roleCode !== "parent") return jsonError("Accès réservé aux parents.", 403);
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  if (typeof data.childId !== "string" || !/^\d+$/u.test(data.childId) || typeof data.healthCenterId !== "string" || !/^\d+$/u.test(data.healthCenterId)) return jsonError("Enfant ou centre invalide.", 400);
  const childAccess = await authorize({ permission: "APPOINTMENT_CREATE", allowedRoles: ["parent"], resource: { type: "child", id: data.childId, medicalData: true } });
  if (!childAccess.allowed) return childAccess.response;
  const requestType = typeof data.requestType === "string" ? data.requestType : "consultation";
  if (!requestTypes.includes(requestType)) return jsonError("Type de demande invalide.", 400);
  const preferredDate = typeof data.preferredDate === "string" && data.preferredDate ? new Date(`${data.preferredDate}T00:00:00.000Z`) : null;
  if (preferredDate && (Number.isNaN(preferredDate.getTime()) || preferredDate.toISOString().slice(0, 10) !== data.preferredDate)) return jsonError("Date souhaitée invalide.", 400);
  if (preferredDate && preferredDate < new Date(new Date().toISOString().slice(0, 10))) return jsonError("La date souhaitée doit être aujourd’hui ou dans le futur.", 400);
  try {
    const guardian = await prisma.childGuardian.findUnique({ where: { childId_userId: { childId: BigInt(data.childId), userId: BigInt(user.id) } } });
    if (!guardian) return jsonError("Profil enfant introuvable.", 404);
    if (guardian.consentStatus !== "granted") return jsonError("Le consentement au suivi de santé n’est pas actif.", 403);
    const center = await prisma.healthCenter.findUnique({ where: { id: BigInt(data.healthCenterId) } });
    if (!center) return jsonError("Centre de santé introuvable.", 404);
    const appointment = await prisma.appointmentRequest.create({ data: { childId: BigInt(data.childId), parentUserId: BigInt(user.id), healthCenterId: BigInt(data.healthCenterId), requestType, preferredDate } });
    await prisma.activityLog.create({ data: { userId: BigInt(user.id), action: "appointment_requested", description: "Demande de rendez-vous créée." } });
    return Response.json({ appointment: { ...appointment, id: appointment.id.toString(), childId: appointment.childId.toString(), parentUserId: appointment.parentUserId.toString(), healthCenterId: appointment.healthCenterId.toString() } }, { status: 201 });
  } catch (error) {
    console.error("Appointment creation failed", error);
    return jsonError("La demande de rendez-vous a échoué.", 503);
  }
}

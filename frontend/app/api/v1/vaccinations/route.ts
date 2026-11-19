import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
const datePattern = /^\d{4}-\d{2}-\d{2}$/u;

export async function GET(request: Request) {
  const childId = new URL(request.url).searchParams.get("childId");
  if (!childId || !/^\d+$/u.test(childId)) return jsonError("Identifiant enfant invalide.", 400);
  const access = await authorize({ permission: "VACCINATION_VIEW", allowedRoles: ["parent"], resource: { type: "child", id: childId, medicalData: true } });
  if (!access.allowed) return access.response;
  const user = access.user;
  const guardian = await prisma.childGuardian.findUnique({ where: { childId_userId: { childId: BigInt(childId), userId: BigInt(user.id) } } });
  if (!guardian) return jsonError("Profil enfant introuvable.", 404);
  if (guardian.consentStatus !== "granted") return jsonError("Le consentement au suivi de santé n’est pas actif.", 403);
  const records = await prisma.vaccinationRecord.findMany({ where: { childId: BigInt(childId), status: "administered" }, include: { dose: { include: { vaccine: true } } }, orderBy: { administeredAt: "desc" } });
  return Response.json({ records: records.map((record) => ({ id: record.id.toString(), childId: record.childId.toString(), vaccineDoseId: record.vaccineDoseId.toString(), vaccine: record.dose.vaccine.displayName, dose: record.dose.displayName, administeredAt: record.administeredAt, nextDoseDate: record.nextDoseDate, notes: record.notes })) });
}

export async function POST(request: Request) {
  const access = await authorize({ permission: "VACCINATION_CREATE", allowedRoles: ["community_health_worker", "health_professional"] });
  if (!access.allowed) return access.response;
  const user = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  if (typeof data.childId !== "string" || !/^\d+$/u.test(data.childId) || typeof data.vaccineDoseId !== "string" || !/^\d+$/u.test(data.vaccineDoseId)) return jsonError("Enfant ou dose invalide.", 400);
  if (typeof data.administeredDate !== "string" || !datePattern.test(data.administeredDate)) return jsonError("Date d’administration invalide.", 400);
  const administeredAt = new Date(`${data.administeredDate}T12:00:00.000Z`);
  const today = new Date();
  today.setUTCHours(23, 59, 59, 999);
  if (Number.isNaN(administeredAt.getTime()) || administeredAt.toISOString().slice(0, 10) !== data.administeredDate || administeredAt > today) return jsonError("La date d’administration ne peut pas être future.", 400);
  let nextDoseDate: Date | null = null;
  if (typeof data.nextDoseDate === "string" && data.nextDoseDate) {
    if (!datePattern.test(data.nextDoseDate)) return jsonError("Date de prochaine dose invalide.", 400);
    nextDoseDate = new Date(`${data.nextDoseDate}T00:00:00.000Z`);
    if (Number.isNaN(nextDoseDate.getTime()) || nextDoseDate.toISOString().slice(0, 10) !== data.nextDoseDate || nextDoseDate <= administeredAt) return jsonError("La prochaine dose doit être postérieure à cette administration.", 400);
  }
  const notes = typeof data.notes === "string" ? data.notes.trim() : "";
  if (notes.length > 500) return jsonError("Les notes ne peuvent pas dépasser 500 caractères.", 400);
  if (data.healthCenterId !== undefined && (typeof data.healthCenterId !== "string" || !/^\d+$/u.test(data.healthCenterId))) return jsonError("Centre de sant? invalide.", 400);
  const centerId = typeof data.healthCenterId === "string" ? BigInt(data.healthCenterId) : null;
  const recordAccess = await authorize({ permission: "VACCINATION_CREATE", allowedRoles: ["community_health_worker", "health_professional"], resource: { type: "child", id: data.childId as string, healthCenterId: centerId?.toString(), medicalData: true } });
  if (!recordAccess.allowed) return recordAccess.response;
  if (user.roleCode === "health_professional" && centerId === null) return jsonError("Un centre associé au dossier doit être sélectionné.", 400);
  const guardian = await prisma.childGuardian.findFirst({ where: { childId: BigInt(data.childId), consentStatus: "granted" } });
  if (!guardian) return jsonError("Le consentement au suivi de santé n’est pas actif.", 403);
  const child = await prisma.child.findUnique({ where: { id: BigInt(data.childId) } });
  if (!child) return jsonError("Profil enfant introuvable.", 404);
  const assignedCenters = await prisma.userHealthCenter.findMany({ where: { userId: BigInt(user.id) }, select: { healthCenterId: true } });
  if (centerId !== null && !assignedCenters.some((center) => center.healthCenterId === centerId)) return jsonError("Vous n’êtes pas affecté à ce centre.", 403);
  if (user.roleCode === "community_health_worker") {
    const hasAccess = child.createdByUserId === BigInt(user.id) || await prisma.growthMeasurement.count({ where: { childId: child.id, recordedByUserId: BigInt(user.id) } }) > 0 || await prisma.nutritionRecord.count({ where: { childId: child.id, healthWorkerId: BigInt(user.id) } }) > 0 || await prisma.communityVisit.count({ where: { childId: child.id, agentUserId: BigInt(user.id) } }) > 0 || await prisma.healthReferral.count({ where: { childId: child.id, agentUserId: BigInt(user.id) } }) > 0;
    if (!hasAccess) return jsonError("Cet enfant n’est pas associé à votre activité.", 403);
  } else {
    const centerLink = await prisma.healthAlert.findFirst({ where: { childId: child.id, healthCenterId: centerId! } });
    if (!centerLink) return jsonError("Ce dossier n’est pas associé à ce centre de santé.", 403);
  }
  const dose = await prisma.vaccineDose.findFirst({ where: { id: BigInt(data.vaccineDoseId), vaccine: { isActive: true } } });
  if (!dose) return jsonError("Dose vaccinale indisponible.", 404);
  try {
    const record = await prisma.$transaction(async (tx) => {
      const created = await tx.vaccinationRecord.create({ data: { childId: child.id, vaccineDoseId: dose.id, administeredAt, nextDoseDate, notes: notes || null, recordedByUserId: BigInt(user.id), healthCenterId: centerId } });
      await tx.activityLog.create({ data: { userId: BigInt(user.id), action: "vaccination_recorded", description: `Dose de vaccin enregistrée pour le profil ${child.id}.` } });
      return created;
    });
    return Response.json({ record: { ...record, id: record.id.toString(), childId: record.childId.toString(), vaccineDoseId: record.vaccineDoseId.toString(), recordedByUserId: record.recordedByUserId.toString(), healthCenterId: record.healthCenterId?.toString() ?? null } }, { status: 201 });
  } catch (error) {
    console.error("Vaccination record creation failed", error);
    return jsonError("L’enregistrement de la vaccination a échoué.", 503);
  }
}

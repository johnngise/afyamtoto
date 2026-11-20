import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { professionalCanAccessChild } from "@/lib/health-access";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
const observationTypes = ["growth_review", "nutrition_review", "clinical_observation", "care_plan"];

export async function POST(request: Request) {
  const access = await authorize({ permission: "MEDICAL_RECORD_CREATE", allowedRoles: ["health_professional"] });
  if (!access.allowed) return access.response;
  const professional = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  if (typeof data.childId !== "string" || !/^\d+$/u.test(data.childId) || typeof data.healthCenterId !== "string" || !/^\d+$/u.test(data.healthCenterId)) return jsonError("Enfant ou centre invalide.", 400);
  const recordAccess = await authorize({ permission: "MEDICAL_RECORD_CREATE", allowedRoles: ["health_professional"], resource: { type: "child", id: data.childId, healthCenterId: data.healthCenterId, medicalData: true } });
  if (!recordAccess.allowed) return recordAccess.response;
  if (typeof data.observationType !== "string" || !observationTypes.includes(data.observationType)) return jsonError("Type d’observation invalide.", 400);
  const content = typeof data.content === "string" ? data.content.trim() : "";
  if (!content || content.length > 8000) return jsonError("Le contenu de l’observation est obligatoire (8 000 caractères maximum).", 400);
  const childId = BigInt(data.childId);
  const centerId = BigInt(data.healthCenterId);
  if (!(await professionalCanAccessChild(professional, childId, centerId))) return jsonError("Ce dossier n’est pas associé à votre centre ou le consentement est inactif.", 403);
  const healthAlertId = typeof data.healthAlertId === "string" && /^\d+$/u.test(data.healthAlertId) ? BigInt(data.healthAlertId) : null;
  const healthReferralId = typeof data.healthReferralId === "string" && /^\d+$/u.test(data.healthReferralId) ? BigInt(data.healthReferralId) : null;
  if (healthAlertId && !(await prisma.healthAlert.findFirst({ where: { id: healthAlertId, childId, healthCenterId: centerId } }))) return jsonError("L’alerte ne correspond pas à ce dossier et à ce centre.", 404);
  if (healthReferralId && !(await prisma.healthReferral.findFirst({ where: { id: healthReferralId, childId, healthCenterId: centerId } }))) return jsonError("L’orientation ne correspond pas à ce dossier et à ce centre.", 404);
  const observation = await prisma.professionalObservation.create({ data: { childId, healthCenterId: centerId, healthProfessionalId: BigInt(professional.id), healthAlertId, healthReferralId, observationType: data.observationType, content } });
  await prisma.activityLog.create({ data: { userId: BigInt(professional.id), action: "professional_observation_added", description: `Observation professionnelle ajoutée au profil ${childId}.` } });
  return Response.json({ observation: { ...observation, id: observation.id.toString(), childId: observation.childId.toString(), healthCenterId: observation.healthCenterId.toString(), healthProfessionalId: observation.healthProfessionalId.toString(), healthAlertId: observation.healthAlertId?.toString() ?? null, healthReferralId: observation.healthReferralId?.toString() ?? null } }, { status: 201 });
}

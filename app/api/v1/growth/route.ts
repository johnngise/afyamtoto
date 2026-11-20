import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { communityWorkerCanAccessChild } from "@/lib/health-access";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function GET(request: Request) {
  const childId = new URL(request.url).searchParams.get("childId");
  if (!childId || !/^\d+$/u.test(childId)) return jsonError("Identifiant enfant invalide.", 400);
  const access = await authorize({ permission: "GROWTH_VIEW", allowedRoles: ["parent", "community_health_worker"], resource: { type: "child", id: childId, medicalData: true } });
  if (!access.allowed) return access.response;
  const user = access.user;
  if (user.roleCode === "parent") {
    const guardian = await prisma.childGuardian.findUnique({ where: { childId_userId: { childId: BigInt(childId), userId: BigInt(user.id) } } });
    if (!guardian) return jsonError("Profil enfant introuvable.", 404);
    if (guardian.consentStatus !== "granted") return jsonError("Le consentement au suivi de santé n’est pas actif.", 403);
  } else if (user.roleCode === "community_health_worker") {
    if (!(await communityWorkerCanAccessChild(user, BigInt(childId)))) return jsonError("Ce dossier n’est pas accessible ou le consentement est inactif.", 403);
  } else return jsonError("Accès réservé aux parents et agents communautaires.", 403);
  const measurements = await prisma.growthMeasurement.findMany({ where: { childId: BigInt(childId) }, orderBy: { measuredAt: "desc" }, take: 100 });
  return Response.json({ measurements: measurements.map((item) => ({ ...item, id: item.id.toString(), childId: item.childId.toString(), recordedByUserId: item.recordedByUserId.toString(), weightKg: item.weightKg?.toString() ?? null, lengthHeightCm: item.lengthHeightCm?.toString() ?? null, muacCm: item.muacCm?.toString() ?? null })) });
}

export async function POST(request: Request) {
  const access = await authorize({ permission: "GROWTH_CREATE", allowedRoles: ["parent", "community_health_worker"] });
  if (!access.allowed) return access.response;
  const user = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  if (typeof data.childId !== "string" || !/^\d+$/u.test(data.childId)) return jsonError("Profil enfant invalide.", 400);
  const recordAccess = await authorize({ permission: "GROWTH_CREATE", allowedRoles: ["parent", "community_health_worker"], resource: { type: "child", id: data.childId, medicalData: true } });
  if (!recordAccess.allowed) return recordAccess.response;
  if (user.roleCode === "parent") {
    const guardian = await prisma.childGuardian.findUnique({ where: { childId_userId: { childId: BigInt(data.childId), userId: BigInt(user.id) } } });
    if (!guardian) return jsonError("Profil enfant introuvable.", 404);
    if (guardian.consentStatus !== "granted") return jsonError("Le consentement au suivi de santé n’est pas actif.", 403);
  } else if (!(await communityWorkerCanAccessChild(user, BigInt(data.childId)))) return jsonError("Ce dossier n’est pas accessible ou le consentement est inactif.", 403);
  const parsePositive = (value: unknown, scale: number, max: number) => {
    if (value === null || value === undefined || value === "") return null;
    if (typeof value !== "string" && typeof value !== "number") return NaN;
    const text = String(value).trim();
    const precision = new RegExp(`^\\d+(?:\\.\\d{1,${scale}})?$`, "u");
    const number = Number(text);
    return precision.test(text) && Number.isFinite(number) && number > 0 && number <= max ? text : NaN;
  };
  const weightKg = parsePositive(data.weightKg, 3, 999.999);
  const lengthHeightCm = parsePositive(data.lengthHeightCm, 2, 9999.99);
  const muacCm = parsePositive(data.muacCm, 2, 9999.99);
  if ([weightKg, lengthHeightCm, muacCm].some((value) => Number.isNaN(value)) || [weightKg, lengthHeightCm, muacCm].every((value) => value === null)) return jsonError("Saisissez au moins une mesure strictement positive.", 400);
  const lengthHeightType = data.lengthHeightType === "length" || data.lengthHeightType === "height" ? data.lengthHeightType : null;
  if ((lengthHeightCm !== null) !== (lengthHeightType !== null)) return jsonError("Précisez s’il s’agit d’une longueur ou d’une taille.", 400);
  const measuredAt = typeof data.measuredAt === "string" ? new Date(data.measuredAt) : new Date();
  if (Number.isNaN(measuredAt.getTime()) || measuredAt > new Date()) return jsonError("Date de mesure invalide.", 400);
  const child = await prisma.child.findUnique({ where: { id: BigInt(data.childId) }, select: { dateOfBirth: true } });
  if (!child || measuredAt < child.dateOfBirth) return jsonError("La date de mesure ne peut pas précéder la naissance de l’enfant.", 400);
  const notes = typeof data.notes === "string" ? data.notes.trim() : "";
  if (notes.length > 500) return jsonError("Les notes ne peuvent pas dépasser 500 caractères.", 400);
  try {
    const measurement = await prisma.growthMeasurement.create({ data: { childId: BigInt(data.childId), recordedByUserId: BigInt(user.id), measuredAt, weightKg, lengthHeightCm, lengthHeightType, muacCm, notes: notes || null } });
    await prisma.activityLog.create({ data: { userId: BigInt(user.id), action: user.roleCode === "parent" ? "parent_growth_measurement_recorded" : "community_growth_recorded", description: "Mesure de croissance enregistrée." } });
    return Response.json({ measurement: { ...measurement, id: measurement.id.toString(), childId: measurement.childId.toString(), recordedByUserId: measurement.recordedByUserId.toString(), weightKg: measurement.weightKg?.toString() ?? null, lengthHeightCm: measurement.lengthHeightCm?.toString() ?? null, muacCm: measurement.muacCm?.toString() ?? null } }, { status: 201 });
  } catch (error) {
    console.error("Growth measurement creation failed", error);
    return jsonError("L’enregistrement de la mesure a échoué.", 503);
  }
}

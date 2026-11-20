import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function GET() {
  try {
    const centers = await prisma.healthCenter.findMany({ where: { NOT: { name: { startsWith: "DEMO UNIQUEMENT" } } }, orderBy: [{ province: "asc" }, { city: "asc" }, { name: "asc" }] });
    return Response.json({ centers: centers.map((center) => ({ ...center, id: center.id.toString(), latitude: center.latitude?.toString() ?? null, longitude: center.longitude?.toString() ?? null })) });
  } catch (error) {
    console.error("Health center listing failed", error);
    return jsonError("Impossible de charger les centres.", 503);
  }
}

export async function POST(request: Request) {
  const access = await authorize({ permission: "FACILITY_CREATE", allowedRoles: ["super_administrator", "administrator", "national_administrator", "provincial_administrator", "health_zone_administrator"] });
  if (!access.allowed) return access.response;
  const user = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const name = typeof data.name === "string" ? data.name.trim() : "";
  const phone = typeof data.phoneE164 === "string" ? data.phoneE164.trim() : "";
  const address = typeof data.addressText === "string" ? data.addressText.trim() : "";
  const city = typeof data.city === "string" ? data.city.trim() : "";
  const province = typeof data.province === "string" ? data.province.trim() : "";
  let provinceId = typeof data.provinceId === "string" && /^\d+$/u.test(data.provinceId) ? BigInt(data.provinceId) : null;
  const healthZoneId = typeof data.healthZoneId === "string" && /^\d+$/u.test(data.healthZoneId) ? BigInt(data.healthZoneId) : null;
  if ((data.provinceId !== undefined && provinceId === null) || (data.healthZoneId !== undefined && healthZoneId === null)) return jsonError("Périmètre géographique invalide.", 400);
  if (healthZoneId) {
    const zone = await prisma.healthZone.findUnique({ where: { id: healthZoneId }, select: { provinceId: true } });
    if (!zone || (provinceId && zone.provinceId !== provinceId)) return jsonError("La zone de santé ne correspond pas à la province.", 400);
    provinceId = zone.provinceId;
  }
  if (user.roleCode === "provincial_administrator" && !provinceId) return jsonError("Sélectionne une province de ton périmètre.", 400);
  if (user.roleCode === "health_zone_administrator" && !healthZoneId) return jsonError("Sélectionne une zone de santé de ton périmètre.", 400);
  if (provinceId) {
    const scopeAccess = await authorize({ permission: "FACILITY_CREATE", allowedRoles: ["super_administrator", "administrator", "national_administrator", "provincial_administrator", "health_zone_administrator"], resource: { type: "province", id: provinceId.toString() } });
    if (!scopeAccess.allowed) return scopeAccess.response;
  }
  if (healthZoneId) {
    const scopeAccess = await authorize({ permission: "FACILITY_CREATE", allowedRoles: ["super_administrator", "administrator", "national_administrator", "provincial_administrator", "health_zone_administrator"], resource: { type: "health_zone", id: healthZoneId.toString() } });
    if (!scopeAccess.allowed) return scopeAccess.response;
  }
  if (!name || name.length > 180) return jsonError("Le nom du centre est obligatoire (180 caractères maximum).", 400);
  if (phone && !/^\+[1-9][0-9]{1,14}$/u.test(phone)) return jsonError("Numéro de téléphone invalide.", 400);
  if (address.length > 500 || city.length > 120 || province.length > 120) return jsonError("Une adresse ou une localité dépasse sa longueur maximale.", 400);
  const parseCoordinate = (value: unknown, max: number) => {
    if (value === null || value === undefined || value === "") return null;
    const parsed = Number(value);
    return Number.isFinite(parsed) && parsed >= -max && parsed <= max ? parsed : NaN;
  };
  const latitude = parseCoordinate(data.latitude, 90);
  const longitude = parseCoordinate(data.longitude, 180);
  if (Number.isNaN(latitude) || Number.isNaN(longitude) || ((latitude === null) !== (longitude === null))) return jsonError("Les coordonnées doivent être valides et fournies ensemble.", 400);
  try {
    const provinceName = province || (provinceId ? (await prisma.province.findUnique({ where: { id: provinceId }, select: { name: true } }))?.name : null) || null;
    const center = await prisma.healthCenter.create({ data: { name, phoneE164: phone || null, addressText: address || null, province: provinceName, provinceId, healthZoneId, city: city || null, latitude, longitude } });
    await prisma.activityLog.create({ data: { userId: BigInt(user.id), action: "health_center_created", description: "Centre de santé créé." } });
    return Response.json({ center: { ...center, id: center.id.toString(), latitude: center.latitude?.toString() ?? null, longitude: center.longitude?.toString() ?? null } }, { status: 201 });
  } catch (error) {
    console.error("Health center creation failed", error);
    return jsonError("La création du centre a échoué.", 503);
  }
}

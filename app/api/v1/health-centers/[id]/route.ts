import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
type Context = { params: Promise<{ id: string }> };

export async function PATCH(request: Request, context: Context) {
  const { id } = await context.params;
  if (!/^\d+$/u.test(id)) return jsonError("Centre introuvable.", 404);
  const access = await authorize({ permission: "FACILITY_UPDATE", allowedRoles: ["super_administrator", "administrator", "national_administrator", "provincial_administrator", "health_zone_administrator"], resource: { type: "health_center", id } });
  if (!access.allowed) return access.response;
  const actor = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const existing = await prisma.healthCenter.findUnique({ where: { id: BigInt(id) } });
  if (!existing) return jsonError("Centre introuvable.", 404);
  const name = typeof data.name === "string" ? data.name.trim() : existing.name;
  const phoneE164 = typeof data.phoneE164 === "string" ? data.phoneE164.trim() || null : existing.phoneE164;
  const addressText = typeof data.addressText === "string" ? data.addressText.trim() || null : existing.addressText;
  const city = typeof data.city === "string" ? data.city.trim() || null : existing.city;
  const province = typeof data.province === "string" ? data.province.trim() || null : existing.province;
  let provinceId = existing.provinceId;
  let healthZoneId = existing.healthZoneId;
  if (data.provinceId !== undefined) {
    if (data.provinceId === null || data.provinceId === "") provinceId = null;
    else if (typeof data.provinceId === "string" && /^\d+$/u.test(data.provinceId)) provinceId = BigInt(data.provinceId);
    else return jsonError("Province invalide.", 400);
  }
  if (data.healthZoneId !== undefined) {
    if (data.healthZoneId === null || data.healthZoneId === "") healthZoneId = null;
    else if (typeof data.healthZoneId === "string" && /^\d+$/u.test(data.healthZoneId)) healthZoneId = BigInt(data.healthZoneId);
    else return jsonError("Zone de santé invalide.", 400);
  }
  if (healthZoneId) {
    const zone = await prisma.healthZone.findUnique({ where: { id: healthZoneId }, select: { provinceId: true } });
    if (!zone || (provinceId && zone.provinceId !== provinceId)) return jsonError("La zone de santé ne correspond pas à la province.", 400);
    provinceId = zone.provinceId;
  }
  if (provinceId && provinceId !== existing.provinceId) {
    const scopeAccess = await authorize({ permission: "FACILITY_UPDATE", allowedRoles: ["super_administrator", "administrator", "national_administrator", "provincial_administrator", "health_zone_administrator"], resource: { type: "province", id: provinceId.toString() } });
    if (!scopeAccess.allowed) return scopeAccess.response;
  }
  if (healthZoneId && healthZoneId !== existing.healthZoneId) {
    const scopeAccess = await authorize({ permission: "FACILITY_UPDATE", allowedRoles: ["super_administrator", "administrator", "national_administrator", "provincial_administrator", "health_zone_administrator"], resource: { type: "health_zone", id: healthZoneId.toString() } });
    if (!scopeAccess.allowed) return scopeAccess.response;
  }
  if (!name || name.length > 180) return jsonError("Nom de centre invalide.", 400);
  if (phoneE164 && !/^\+[1-9][0-9]{1,14}$/u.test(phoneE164)) return jsonError("Numéro de téléphone invalide.", 400);
  if ((addressText?.length ?? 0) > 500 || (city?.length ?? 0) > 120 || (province?.length ?? 0) > 120) return jsonError("Adresse ou localité trop longue.", 400);
  const parse = (value: unknown, old: unknown, max: number): number | null => {
    if (value === undefined) return old === null ? null : Number(old);
    if (value === null || value === "") return null;
    const number = Number(value);
    return Number.isFinite(number) && number >= -max && number <= max ? number : NaN;
  };
  const latitude = parse(data.latitude, existing.latitude, 90);
  const longitude = parse(data.longitude, existing.longitude, 180);
  if (Number.isNaN(latitude) || Number.isNaN(longitude) || ((latitude === null) !== (longitude === null))) return jsonError("Coordonnées invalides; latitude et longitude doivent être fournies ensemble.", 400);
  const updated = await prisma.healthCenter.update({ where: { id: existing.id }, data: { name, phoneE164, addressText, city, province, provinceId, healthZoneId, latitude, longitude } });
  await prisma.activityLog.create({ data: { userId: BigInt(actor.id), action: "health_center_updated", description: `Centre ${id} modifié.` } });
  return Response.json({ center: { ...updated, id: updated.id.toString(), latitude: updated.latitude?.toString() ?? null, longitude: updated.longitude?.toString() ?? null } });
}

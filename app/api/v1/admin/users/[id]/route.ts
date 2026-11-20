import { randomUUID } from "node:crypto";
import { Prisma } from "@prisma/client";
import { authorize, userScopeWhere } from "@/lib/authorization";
import { isStrongPassword, jsonError, makePasswordHash } from "@/lib/auth";
import { mayAssignRole, ROLE_PERMISSION_GRANTS } from "@/lib/rbac-catalog";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
type Context = { params: Promise<{ id: string }> };
const validRoles = Object.keys(ROLE_PERMISSION_GRANTS).filter((role) => !["administrator", "health_center_manager", "health_supervisor"].includes(role));
const specialties = ["physician", "nurse", "midwife", "nutritionist", "other"];
const managementRoles = ["super_administrator", "administrator", "national_administrator", "provincial_administrator", "health_zone_administrator", "health_facility_administrator", "health_center_manager"];

function parseIds(value: unknown): bigint[] | null {
  if (!Array.isArray(value) || value.some((id) => typeof id !== "string" || !/^\d{1,19}$/u.test(id))) return null;
  try {
    const ids = (value as string[]).map(BigInt);
    if (ids.some((id) => id <= BigInt(0) || id > BigInt("9223372036854775807"))) return null;
    return [...new Set(ids)];
  } catch { return null; }
}

async function validateScope(roleCode: string, ids: { provinces: bigint[]; zones: bigint[]; centers: bigint[] }) {
  const scopeRequirements: Record<string, keyof typeof ids> = {
    provincial_administrator: "provinces", health_zone_administrator: "zones", health_facility_administrator: "centers",
    health_professional: "centers", community_health_worker: "centers",
  };
  const required = scopeRequirements[roleCode];
  if (required && ids[required].length === 0) return "Ce rôle exige une affectation géographique.";
  const results = await Promise.all([
    ...ids.provinces.map((id) => authorize({ permission: "FACILITY_ASSIGN", allowedRoles: managementRoles, resource: { type: "province", id: id.toString() } })),
    ...ids.zones.map((id) => authorize({ permission: "FACILITY_ASSIGN", allowedRoles: managementRoles, resource: { type: "health_zone", id: id.toString() } })),
    ...ids.centers.map((id) => authorize({ permission: "FACILITY_ASSIGN", allowedRoles: managementRoles, resource: { type: "health_center", id: id.toString() } })),
  ]);
  return results.some((result) => !result.allowed) ? "Une affectation demandée est hors de ton périmètre." : null;
}

export async function PATCH(request: Request, context: Context) {
  const access = await authorize({ permission: "USER_UPDATE", allowedRoles: managementRoles });
  if (!access.allowed) return access.response;
  const { id } = await context.params;
  if (!/^\d+$/u.test(id) || BigInt(id) <= BigInt(0)) return jsonError("Compte introuvable.", 404);
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const targetId = BigInt(id);
  const scope = await userScopeWhere(access.user);
  const target = await prisma.user.findFirst({ where: { AND: [{ id: targetId }, scope] }, include: { roles: true } });
  if (!target) return jsonError("Compte introuvable.", 404);
  const displayName = typeof data.displayName === "string" ? data.displayName.trim() : target.displayName;
  const username = typeof data.username === "string" ? data.username.trim().toLowerCase() || null : target.username;
  const email = typeof data.email === "string" ? data.email.trim().toLowerCase() || null : target.email;
  const phone = typeof data.phoneE164 === "string" ? data.phoneE164.trim() || null : target.phoneE164;
  const isActive = typeof data.isActive === "boolean" ? data.isActive : target.isActive;
  const password = typeof data.password === "string" ? data.password : "";
  const oldRoleCodes = target.roles.map((role) => role.roleCode);
  const roleCodesRaw = Array.isArray(data.roleCodes) ? data.roleCodes : typeof data.roleCode === "string" ? [data.roleCode] : oldRoleCodes.filter((role) => validRoles.includes(role));
  if (!roleCodesRaw.length || roleCodesRaw.some((role) => typeof role !== "string" || !validRoles.includes(role))) return jsonError("Liste de rôles invalide.", 400);
  const roleCodes = [...new Set(roleCodesRaw as string[])];
  const changedRoles = [...new Set([...oldRoleCodes.filter((role) => validRoles.includes(role)), ...roleCodes])].filter((role) => oldRoleCodes.includes(role) !== roleCodes.includes(role));
  if (changedRoles.some((role) => role === "administrator" ? !(access.user.roleCode === "super_administrator" && oldRoleCodes.includes("super_administrator")) : !mayAssignRole([access.user.roleCode], role))) return jsonError("Tu n’es pas autorisé à modifier cette attribution de rôle.", 403);
  const wasSuper = oldRoleCodes.includes("super_administrator") && target.isActive;
  if (wasSuper && !roleCodes.includes("super_administrator")) {
    const otherSuperAdmins = await prisma.user.count({ where: { id: { not: targetId }, isActive: true, roles: { some: { roleCode: "super_administrator" } } } });
    if (otherSuperAdmins === 0) return jsonError("Le dernier Super Admin actif ne peut pas être rétrogradé.", 409);
  }
  if (!displayName || Array.from(displayName).length > 160) return jsonError("Nom invalide.", 400);
  if (username && !/^[a-z0-9._-]{3,64}$/u.test(username)) return jsonError("Nom d’utilisateur invalide.", 400);
  if (email && (email.length > 254 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(email))) return jsonError("Adresse e-mail invalide.", 400);
  if (phone && !/^\+[1-9][0-9]{1,14}$/u.test(phone)) return jsonError("Numéro de téléphone invalide.", 400);
  if (!email && !phone) return jsonError("Une adresse e-mail ou un numéro est obligatoire.", 400);
  if (password && !isStrongPassword(password)) return jsonError("Mot de passe trop faible.", 400);
  const specialtyCode = typeof data.specialtyCode === "string" ? data.specialtyCode : "";
  if (roleCodes.includes("health_professional") && !specialties.includes(specialtyCode) && !data.specialtyCode) {
    const existingProfile = await prisma.professionalProfile.findUnique({ where: { userId: targetId }, select: { specialtyCode: true } });
    if (!existingProfile) return jsonError("La spécialité du professionnel doit être renseignée.", 400);
  } else if (roleCodes.includes("health_professional") && !specialties.includes(specialtyCode)) return jsonError("Spécialité professionnelle invalide.", 400);

  const hasScopeInput = data.scopeAssignmentsProvided === true || data.scopeAssignmentsProvided === "true" || ["provinceIds", "healthZoneIds", "healthCenterIds"].some((key) => data[key] !== undefined);
  const provinces = data.provinceIds === undefined ? null : parseIds(data.provinceIds);
  const zones = data.healthZoneIds === undefined ? null : parseIds(data.healthZoneIds);
  const centers = data.healthCenterIds === undefined ? null : parseIds(data.healthCenterIds);
  if (provinces === null && data.provinceIds !== undefined) return jsonError("Les identifiants des provinces doivent être des nombres entiers positifs séparés par des virgules (ex. 2, 4).", 400);
  if (zones === null && data.healthZoneIds !== undefined) return jsonError("Les identifiants des zones de santé doivent être des nombres entiers positifs séparés par des virgules (ex. 3, 7).", 400);
  if (centers === null && data.healthCenterIds !== undefined) return jsonError("Les identifiants des centres de santé doivent être des nombres entiers positifs séparés par des virgules (ex. 1, 5).", 400);
  if (hasScopeInput) {
    const scopeError = await validateScope(roleCodes[0], { provinces: provinces ?? [], zones: zones ?? [], centers: centers ?? [] });
    if (scopeError) return jsonError(scopeError, 403);
  }

  try {
    const updated = await prisma.$transaction(async (tx) => {
      const account = await tx.user.update({ where: { id: targetId }, data: {
        displayName, username, email, phoneE164: phone, isActive,
        ...(password ? { passwordHash: await makePasswordHash(password) } : {}),
      } });
      await tx.userRole.deleteMany({ where: { userId: targetId } });
      await tx.userRole.createMany({ data: roleCodes.map((roleCode) => ({ userId: targetId, roleCode })) });
      const removedRoles = oldRoleCodes.filter((role) => !roleCodes.includes(role));
      if (removedRoles.length) {
        await tx.userAssignment.deleteMany({ where: { userId: targetId, roleCode: { in: removedRoles } } });
        await tx.userCapability.deleteMany({ where: { userId: targetId, roleCode: { in: removedRoles } } });
        await tx.userHealthCenter.deleteMany({ where: { userId: targetId } });
      }
      if (hasScopeInput) {
        await tx.userAssignment.deleteMany({ where: { userId: targetId, roleCode: { in: roleCodes } } });
        await tx.userHealthCenter.deleteMany({ where: { userId: targetId } });
        if (centers?.length) await tx.userHealthCenter.createMany({ data: centers.map((healthCenterId) => ({ userId: targetId, healthCenterId })) });
        const assignments = [
          ...(provinces ?? []).map((provinceId) => ({ userId: targetId, roleCode: roleCodes[0], scopeType: "province", provinceId, assignedByUserId: BigInt(access.user.id) })),
          ...(zones ?? []).map((healthZoneId) => ({ userId: targetId, roleCode: roleCodes[0], scopeType: "health_zone", healthZoneId, assignedByUserId: BigInt(access.user.id) })),
          ...(centers ?? []).map((healthCenterId) => ({ userId: targetId, roleCode: roleCodes[0], scopeType: "health_center", healthCenterId, assignedByUserId: BigInt(access.user.id) })),
        ];
        if (assignments.length) await tx.userAssignment.createMany({ data: assignments });
      }
      if (roleCodes.includes("health_professional")) {
        if (specialties.includes(specialtyCode)) await tx.professionalProfile.upsert({ where: { userId: targetId }, create: { userId: targetId, specialtyCode }, update: { specialtyCode } });
      } else await tx.professionalProfile.deleteMany({ where: { userId: targetId } });
      await tx.userCapability.deleteMany({ where: { userId: targetId, roleCode: { notIn: roleCodes } } });
      await tx.session.deleteMany({ where: { userId: targetId } });
      await tx.activityLog.create({ data: { userId: BigInt(access.user.id), action: "user_updated", description: `Compte ${targetId} modifié.` } });
      await tx.auditEvent.create({ data: { actorUserId: BigInt(access.user.id), eventCode: "USER_ACCESS_UPDATED", subjectType: "user", subjectId: targetId, requestId: randomUUID() } });
      return account;
    });
    return Response.json({ user: { id: updated.id.toString(), displayName: updated.displayName, username: updated.username, email: updated.email, phoneE164: updated.phoneE164, isActive: updated.isActive, roleCodes } });
  } catch (error) {
    if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === "P2002") return jsonError("Cette adresse, ce téléphone ou ce nom est déjà associé à un compte.", 409);
    console.error("User update failed", error);
    return jsonError("La modification du compte a échoué.", 503);
  }
}

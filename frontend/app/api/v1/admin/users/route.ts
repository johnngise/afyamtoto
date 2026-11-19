import { randomUUID } from "node:crypto";
import { Prisma } from "@prisma/client";
import { authorize, userScopeWhere } from "@/lib/authorization";
import { isStrongPassword, jsonError, makePasswordHash } from "@/lib/auth";
import { mayAssignRole, ROLE_PERMISSION_GRANTS } from "@/lib/rbac-catalog";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
const assignableRoles = Object.keys(ROLE_PERMISSION_GRANTS).filter((role) => !["administrator", "health_center_manager", "health_supervisor"].includes(role));
const specialties = ["physician", "nurse", "midwife", "nutritionist", "other"];
const managementRoles = ["super_administrator", "administrator", "national_administrator", "provincial_administrator", "health_zone_administrator", "health_facility_administrator", "health_center_manager"];

function parseIds(value: unknown): bigint[] | null {
  if (value === undefined) return [];
  if (!Array.isArray(value) || value.some((id) => typeof id !== "string" || !/^\d{1,19}$/u.test(id))) return null;
  try {
    const ids = (value as string[]).map(BigInt);
    if (ids.some((id) => id <= BigInt(0) || id > BigInt("9223372036854775807"))) return null;
    return [...new Set(ids)];
  } catch { return null; }
}

async function validateScope(roleCode: string, ids: { provinces: bigint[]; zones: bigint[]; centers: bigint[] }) {
  const scopeRequirements: Record<string, keyof typeof ids> = {
    provincial_administrator: "provinces",
    health_zone_administrator: "zones",
    health_facility_administrator: "centers",
    health_professional: "centers",
    community_health_worker: "centers",
  };
  const required = scopeRequirements[roleCode];
  if (required && ids[required].length === 0) return "Ce rôle exige une affectation géographique.";
  if (ids[required ?? "centers"].length && !required && roleCode !== "health_data_analyst") return "Affectation géographique non applicable à ce rôle.";
  if (roleCode === "health_data_analyst" && !ids.provinces.length && !ids.zones.length && !ids.centers.length) return "L’analyste doit recevoir un périmètre de rapports.";

  const checks = [
    ...ids.provinces.map((id) => authorize({ permission: "FACILITY_ASSIGN", allowedRoles: managementRoles, resource: { type: "province", id: id.toString() } })),
    ...ids.zones.map((id) => authorize({ permission: "FACILITY_ASSIGN", allowedRoles: managementRoles, resource: { type: "health_zone", id: id.toString() } })),
    ...ids.centers.map((id) => authorize({ permission: "FACILITY_ASSIGN", allowedRoles: managementRoles, resource: { type: "health_center", id: id.toString() } })),
  ];
  const results = await Promise.all(checks);
  if (results.some((result) => !result.allowed)) return "Une affectation demandée est hors de ton périmètre.";
  return null;
}

export async function GET() {
  const access = await authorize({ permission: "USER_VIEW", allowedRoles: managementRoles });
  if (!access.allowed) return access.response;
  const scope = await userScopeWhere(access.user);
  const users = await prisma.user.findMany({ where: scope, orderBy: { createdAt: "desc" }, include: { roles: true, professionalProfile: { select: { specialtyCode: true } }, scopedAssignments: { select: { roleCode: true, scopeType: true, provinceId: true, healthZoneId: true, healthCenterId: true } } } });
  const userIds = users.map((entry) => entry.id);
  const centers = await prisma.userHealthCenter.findMany({ where: { userId: { in: userIds } }, select: { userId: true, healthCenterId: true } });
  return Response.json({ users: users.map((entry) => ({
    id: entry.id.toString(), displayName: entry.displayName, username: entry.username, email: entry.email,
    phoneE164: entry.phoneE164, isActive: entry.isActive, roles: entry.roles.map((role) => role.roleCode), specialtyCode: entry.professionalProfile?.specialtyCode ?? null,
    healthCenterIds: centers.filter((center) => center.userId === entry.id).map((center) => center.healthCenterId.toString()),
    assignments: entry.scopedAssignments.map((item) => ({ roleCode: item.roleCode, scopeType: item.scopeType, provinceId: item.provinceId?.toString() ?? null, healthZoneId: item.healthZoneId?.toString() ?? null, healthCenterId: item.healthCenterId?.toString() ?? null })),
    createdAt: entry.createdAt,
  })) });
}

export async function POST(request: Request) {
  const access = await authorize({ permission: "USER_CREATE", allowedRoles: managementRoles });
  if (!access.allowed) return access.response;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const displayName = typeof data.displayName === "string" ? data.displayName.trim() : "";
  const username = typeof data.username === "string" ? data.username.trim().toLowerCase() : "";
  const email = typeof data.email === "string" ? data.email.trim().toLowerCase() : "";
  const phone = typeof data.phoneE164 === "string" ? data.phoneE164.trim() : "";
  const password = typeof data.password === "string" ? data.password : "";
  const roleCode = typeof data.roleCode === "string" ? data.roleCode : "";
  const specialtyCode = typeof data.specialtyCode === "string" ? data.specialtyCode : "";
  if (!displayName || Array.from(displayName).length > 160) return jsonError("Nom invalide.", 400);
  if (username && !/^[a-z0-9._-]{3,64}$/u.test(username)) return jsonError("Nom d’utilisateur invalide.", 400);
  if (email.length > 254 || (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(email))) return jsonError("Adresse e-mail invalide.", 400);
  if (phone && !/^\+[1-9][0-9]{1,14}$/u.test(phone)) return jsonError("Numéro de téléphone invalide.", 400);
  if (!email && !phone) return jsonError("Une adresse e-mail ou un numéro est obligatoire.", 400);
  if (!assignableRoles.includes(roleCode) || !mayAssignRole([access.user.roleCode], roleCode)) return jsonError("Tu n’es pas autorisé à attribuer ce rôle.", 403);
  if (roleCode === "health_professional" && !specialties.includes(specialtyCode)) return jsonError("La spécialité du professionnel est obligatoire.", 400);
  if (!isStrongPassword(password)) return jsonError("Mot de passe trop faible.", 400);
  const provinces = parseIds(data.provinceIds), zones = parseIds(data.healthZoneIds), centers = parseIds(data.healthCenterIds);
  if (!provinces) return jsonError("Les identifiants des provinces doivent être des nombres entiers positifs séparés par des virgules (ex. 2, 4).", 400);
  if (!zones) return jsonError("Les identifiants des zones de santé doivent être des nombres entiers positifs séparés par des virgules (ex. 3, 7).", 400);
  if (!centers) return jsonError("Les identifiants des centres de santé doivent être des nombres entiers positifs séparés par des virgules (ex. 1, 5).", 400);
  const scopeError = await validateScope(roleCode, { provinces, zones, centers });
  if (scopeError) return jsonError(scopeError, 403);

  try {
    const created = await prisma.$transaction(async (tx) => {
      const account = await tx.user.create({ data: {
        displayName, username: username || null, email: email || null, phoneE164: phone || null,
        passwordHash: await makePasswordHash(password), roles: { create: { roleCode } },
      } });
      const assignments = [
        ...provinces.map((provinceId) => ({ userId: account.id, roleCode, scopeType: "province", provinceId, assignedByUserId: BigInt(access.user.id) })),
        ...zones.map((healthZoneId) => ({ userId: account.id, roleCode, scopeType: "health_zone", healthZoneId, assignedByUserId: BigInt(access.user.id) })),
        ...centers.map((healthCenterId) => ({ userId: account.id, roleCode, scopeType: "health_center", healthCenterId, assignedByUserId: BigInt(access.user.id) })),
      ];
      if (assignments.length) await tx.userAssignment.createMany({ data: assignments });
      if (centers.length) await tx.userHealthCenter.createMany({ data: centers.map((healthCenterId) => ({ userId: account.id, healthCenterId })) });
      if (roleCode === "health_professional") await tx.professionalProfile.create({ data: { userId: account.id, specialtyCode } });
      await tx.activityLog.create({ data: { userId: BigInt(access.user.id), action: "user_created", description: `Compte créé avec le rôle ${roleCode}.` } });
      await tx.auditEvent.create({ data: { actorUserId: BigInt(access.user.id), eventCode: "USER_ROLE_ASSIGNED", subjectType: "user", subjectId: account.id, requestId: randomUUID() } });
      return account;
    });
    return Response.json({ user: { id: created.id.toString(), displayName, username: username || null, email: email || null, phoneE164: phone || null, roleCode, assignments: { provinceIds: provinces.map(String), healthZoneIds: zones.map(String), healthCenterIds: centers.map(String) } } }, { status: 201 });
  } catch (error) {
    if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === "P2002") return jsonError("Cette adresse, ce téléphone ou ce nom est déjà associé à un compte.", 409);
    console.error("User creation failed", error);
    return jsonError("La création du compte a échoué.", 503);
  }
}

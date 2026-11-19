import { randomUUID } from "node:crypto";
import type { Prisma } from "@prisma/client";
import { getCurrentUser, jsonError, type PublicUser } from "@/lib/auth";
import { communityWorkerCanAccessChild, professionalCanAccessChild } from "@/lib/health-access";
import { type PermissionCode, type ResourceScopeType } from "@/lib/rbac-catalog";
import { decideAccess, requiredCapability, specialtyAllows } from "@/lib/rbac-policy";
import { prisma } from "@/lib/db";

export type AuthorizationResource =
  | { type: "child"; id: string; healthCenterId?: string; medicalData?: boolean }
  | { type: "health_center" | "health_zone" | "province"; id: string };

type AuthorizationResult = { allowed: true; user: PublicUser } | { allowed: false; response: Response };

function validId(value: string) {
  return /^\d+$/u.test(value) && BigInt(value) > BigInt(0);
}

export async function authorize(input: {
  permission: PermissionCode;
  allowedRoles?: readonly string[];
  resource?: AuthorizationResource;
}): Promise<AuthorizationResult> {
  const user = await getCurrentUser();
  if (!user) return { allowed: false, response: jsonError("Authentification requise.", 401) };

  let roleCodes = [user.roleCode].filter((code) => !input.allowedRoles || input.allowedRoles.includes(code));
  if (roleCodes.length === 0) return { allowed: false, response: jsonError("Accès interdit.", 403) };
  if (roleCodes.includes("health_professional") && isClinicalPermission(input.permission)) {
    const profile = await prisma.professionalProfile.findUnique({ where: { userId: BigInt(user.id) }, select: { specialtyCode: true } });
    if (!profile || !specialtyAllows(profile.specialtyCode, input.permission)) roleCodes = roleCodes.filter((code) => code !== "health_professional");
  }
  if (roleCodes.includes("community_health_worker")) {
    const capability = requiredCapability("community_health_worker", input.permission);
    if (capability) {
      const now = new Date();
      const qualification = await prisma.userCapability.findUnique({
        where: { userId_roleCode_capabilityCode: { userId: BigInt(user.id), roleCode: "community_health_worker", capabilityCode: capability } },
        select: { expiresAt: true },
      });
      if (!qualification || (qualification.expiresAt && qualification.expiresAt <= now)) roleCodes = roleCodes.filter((code) => code !== "community_health_worker");
    }
  }
  if (roleCodes.length === 0) return { allowed: false, response: jsonError("La qualification requise n’est pas valide pour cette opération.", 403) };
  const grants = await prisma.rolePermission.findMany({
    where: { roleCode: { in: roleCodes }, permissionCode: input.permission },
    select: { roleCode: true, permissionCode: true },
  });
  if (grants.length === 0) return { allowed: false, response: jsonError("Permission insuffisante.", 403) };

  let resourceFacts: Parameters<typeof decideAccess>[1]["resource"];
  if (input.resource) {
    const resource = input.resource;
    if (!validId(resource.id)) return { allowed: false, response: jsonError("Ressource introuvable.", 404) };
    const id = BigInt(resource.id);
    if (resource.type === "child") {
      const child = await prisma.child.findUnique({ where: { id }, select: { id: true } });
      if (!child) return { allowed: false, response: jsonError("Ressource introuvable.", 404) };
      const guardian = await prisma.childGuardian.findUnique({
        where: { childId_userId: { childId: id, userId: BigInt(user.id) } },
        select: { consentStatus: true },
      });
      const consentGranted = await prisma.childGuardian.count({ where: { childId: id, consentStatus: "granted" } }) > 0;
      let childAssigned = false;
      let centerAssigned = false;
      if (resource.healthCenterId && validId(resource.healthCenterId)) {
        const centerId = BigInt(resource.healthCenterId);
        centerAssigned = await hasCenterAssignment(user, roleCodes, centerId);
        if (roleCodes.includes("health_professional")) {
          childAssigned = await professionalCanAccessChild({ ...user, roleCode: "health_professional" }, id, centerId);
        }
      }
      if (roleCodes.includes("community_health_worker")) {
        childAssigned = await communityWorkerCanAccessChild({ ...user, roleCode: "community_health_worker" }, id);
      }
      resourceFacts = {
        type: "child",
        isOwnGuardian: Boolean(guardian),
        consentGranted: roleCodes.includes("parent") ? guardian?.consentStatus === "granted" : consentGranted,
        childAssigned,
        centerAssigned,
        medicalData: resource.medicalData ?? isMedicalPermission(input.permission),
      };
    } else {
      const target = await resolveScopeFacts(user, roleCodes, resource.type, id);
      if (!target.exists) return { allowed: false, response: jsonError("Ressource introuvable.", 404) };
      resourceFacts = { type: resource.type, ...target };
    }
  }

  const decision = decideAccess(input.permission, {
    authenticated: true,
    active: true,
    roleCodes,
    grantedPermissions: grants.map((grant) => `${grant.roleCode}:${grant.permissionCode}`),
    resource: resourceFacts,
  });
  if (!decision.allowed) return { allowed: false, response: jsonError("Accès interdit.", decision.status) };

  if (input.resource?.type === "child" && resourceFacts?.medicalData) {
    await prisma.auditEvent.create({
      data: {
        actorUserId: BigInt(user.id),
        eventCode: "MEDICAL_DATA_ACCESS",
        subjectType: "child",
        subjectId: BigInt(input.resource.id),
        requestId: randomUUID(),
      },
    });
  }
  return { allowed: true, user };
}

export async function userScopeWhere(user: PublicUser): Promise<Prisma.UserWhereInput> {
  if (["super_administrator", "administrator", "national_administrator"].includes(user.roleCode)) return {};
  const now = new Date();
  const [assignments, legacyCenters] = await Promise.all([
    prisma.userAssignment.findMany({
      where: { userId: BigInt(user.id), roleCode: user.roleCode, OR: [{ expiresAt: null }, { expiresAt: { gt: now } }] },
      select: { provinceId: true, healthZoneId: true, healthCenterId: true },
    }),
    prisma.userHealthCenter.findMany({ where: { userId: BigInt(user.id) }, select: { healthCenterId: true } }),
  ]);
  const provinceIds = assignments.flatMap((entry) => entry.provinceId ? [entry.provinceId] : []);
  const zoneIds = assignments.flatMap((entry) => entry.healthZoneId ? [entry.healthZoneId] : []);
  const centerIds = [...new Set([...assignments.flatMap((entry) => entry.healthCenterId ? [entry.healthCenterId] : []), ...legacyCenters.map((entry) => entry.healthCenterId)])];
  const scopeConditions: Prisma.UserWhereInput[] = [];
  if (provinceIds.length || zoneIds.length || centerIds.length) {
    const assignmentConditions: Prisma.UserAssignmentWhereInput[] = [];
    if (provinceIds.length) assignmentConditions.push(
      { provinceId: { in: provinceIds } },
      { healthZone: { provinceId: { in: provinceIds } } },
      { healthCenter: { provinceId: { in: provinceIds } } },
    );
    if (zoneIds.length) assignmentConditions.push(
      { healthZoneId: { in: zoneIds } },
      { healthCenter: { healthZoneId: { in: zoneIds } } },
    );
    if (centerIds.length) assignmentConditions.push({ healthCenterId: { in: centerIds } });
    scopeConditions.push({ scopedAssignments: { some: { OR: assignmentConditions } } });
    if (centerIds.length) scopeConditions.push({ centerAssignments: { some: { healthCenterId: { in: centerIds } } } });
  }
  return scopeConditions.length ? { OR: scopeConditions } : { id: { in: [] } };
}

function isMedicalPermission(permission: PermissionCode) {
  return /^(GROWTH|VACCINATION|MEDICAL_RECORD|HEALTH_ALERT|REFERRAL|NUTRITION_RECORD)_/u.test(permission);
}

function isClinicalPermission(permission: PermissionCode) {
  return isMedicalPermission(permission) || permission === "CHILD_VIEW" || permission === "APPOINTMENT_MANAGE";
}

async function hasCenterAssignment(user: PublicUser, roleCodes: string[], healthCenterId: bigint) {
  const now = new Date();
  const [scoped, legacy] = await Promise.all([
    prisma.userAssignment.findFirst({
      where: {
        userId: BigInt(user.id),
        roleCode: { in: roleCodes },
        healthCenterId,
        scopeType: "health_center",
        OR: [{ expiresAt: null }, { expiresAt: { gt: now } }],
      },
      select: { id: true },
    }),
    prisma.userHealthCenter.findUnique({ where: { userId_healthCenterId: { userId: BigInt(user.id), healthCenterId } }, select: { userId: true } }),
  ]);
  return Boolean(scoped || legacy);
}

async function resolveScopeFacts(user: PublicUser, roleCodes: string[], type: Exclude<ResourceScopeType, "child">, id: bigint) {
  const now = new Date();
  const query = { userId: BigInt(user.id), roleCode: { in: roleCodes }, OR: [{ expiresAt: null }, { expiresAt: { gt: now } }] };
  if (type === "health_center") {
    const center = await prisma.healthCenter.findUnique({ where: { id }, select: { id: true, healthZoneId: true, provinceId: true } });
    if (!center) return { exists: false, centerAssigned: false, healthZoneAssigned: false, provinceAssigned: false };
    const [direct, legacy, zone, province] = await Promise.all([
      prisma.userAssignment.findFirst({ where: { ...query, scopeType: "health_center", healthCenterId: id }, select: { id: true } }),
      prisma.userHealthCenter.findUnique({ where: { userId_healthCenterId: { userId: BigInt(user.id), healthCenterId: id } }, select: { userId: true } }),
      center.healthZoneId ? prisma.userAssignment.findFirst({ where: { ...query, scopeType: "health_zone", healthZoneId: center.healthZoneId }, select: { id: true } }) : null,
      center.provinceId ? prisma.userAssignment.findFirst({ where: { ...query, scopeType: "province", provinceId: center.provinceId }, select: { id: true } }) : null,
    ]);
    return { exists: true, centerAssigned: Boolean(direct || legacy), healthZoneAssigned: Boolean(zone), provinceAssigned: Boolean(province) };
  }
  if (type === "health_zone") {
    const zone = await prisma.healthZone.findUnique({ where: { id }, select: { id: true, provinceId: true } });
    if (!zone) return { exists: false, centerAssigned: false, healthZoneAssigned: false, provinceAssigned: false };
    const [direct, province] = await Promise.all([
      prisma.userAssignment.findFirst({ where: { ...query, scopeType: "health_zone", healthZoneId: id }, select: { id: true } }),
      prisma.userAssignment.findFirst({ where: { ...query, scopeType: "province", provinceId: zone.provinceId }, select: { id: true } }),
    ]);
    return { exists: true, centerAssigned: false, healthZoneAssigned: Boolean(direct), provinceAssigned: Boolean(province) };
  }
  const province = await prisma.province.findUnique({ where: { id }, select: { id: true } });
  if (!province) return { exists: false, centerAssigned: false, healthZoneAssigned: false, provinceAssigned: false };
  const direct = await prisma.userAssignment.findFirst({ where: { ...query, scopeType: "province", provinceId: id }, select: { id: true } });
  return { exists: true, centerAssigned: false, healthZoneAssigned: false, provinceAssigned: Boolean(direct) };
}

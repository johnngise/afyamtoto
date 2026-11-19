import type { PermissionCode, ResourceScopeType } from "./rbac-catalog";

export type AccessFacts = {
  authenticated: boolean;
  active: boolean;
  roleCodes: readonly string[];
  grantedPermissions: readonly string[];
  resource?: {
    type: ResourceScopeType;
    isOwnGuardian?: boolean;
    consentGranted?: boolean;
    childAssigned?: boolean;
    centerAssigned?: boolean;
    healthZoneAssigned?: boolean;
    provinceAssigned?: boolean;
    medicalData?: boolean;
  };
};

export type AccessDecision = { allowed: true } | { allowed: false; status: 401 | 403; reason: string };

const COMMUNITY_CAPABILITIES: Record<string, string> = {
  GROWTH_CREATE: "GROWTH_MEASUREMENT",
  VACCINATION_CREATE: "VACCINATION_ADMINISTER",
  NUTRITION_RECORD_CREATE: "NUTRITION_SCREENING",
};

const PROFESSIONAL_SPECIALTY_PERMISSIONS: Record<string, readonly string[]> = {
  physician: ["CHILD_VIEW", "GROWTH_VIEW", "GROWTH_CREATE", "GROWTH_UPDATE", "GROWTH_VALIDATE", "VACCINATION_VIEW", "VACCINATION_CREATE", "VACCINATION_UPDATE", "VACCINATION_VALIDATE", "MEDICAL_RECORD_VIEW", "MEDICAL_RECORD_CREATE", "MEDICAL_RECORD_UPDATE", "HEALTH_ALERT_VIEW", "HEALTH_ALERT_CREATE", "HEALTH_ALERT_RESOLVE", "APPOINTMENT_VIEW", "APPOINTMENT_MANAGE", "REFERRAL_VIEW", "REFERRAL_FOLLOW_UP", "NUTRITION_RECORD_CREATE"],
  nurse: ["CHILD_VIEW", "GROWTH_VIEW", "GROWTH_CREATE", "GROWTH_UPDATE", "GROWTH_VALIDATE", "VACCINATION_VIEW", "VACCINATION_CREATE", "VACCINATION_UPDATE", "VACCINATION_VALIDATE", "MEDICAL_RECORD_VIEW", "MEDICAL_RECORD_CREATE", "MEDICAL_RECORD_UPDATE", "HEALTH_ALERT_VIEW", "HEALTH_ALERT_CREATE", "HEALTH_ALERT_RESOLVE", "APPOINTMENT_VIEW", "APPOINTMENT_MANAGE", "REFERRAL_VIEW", "REFERRAL_FOLLOW_UP"],
  midwife: ["CHILD_VIEW", "GROWTH_VIEW", "GROWTH_CREATE", "GROWTH_UPDATE", "GROWTH_VALIDATE", "VACCINATION_VIEW", "VACCINATION_CREATE", "VACCINATION_UPDATE", "VACCINATION_VALIDATE", "HEALTH_ALERT_VIEW", "HEALTH_ALERT_CREATE", "HEALTH_ALERT_RESOLVE", "APPOINTMENT_VIEW", "APPOINTMENT_MANAGE", "REFERRAL_VIEW", "REFERRAL_FOLLOW_UP"],
  nutritionist: ["CHILD_VIEW", "GROWTH_VIEW", "GROWTH_CREATE", "GROWTH_UPDATE", "GROWTH_VALIDATE", "NUTRITION_RECORD_CREATE", "HEALTH_ALERT_VIEW", "REFERRAL_VIEW"],
  other: ["CHILD_VIEW", "GROWTH_VIEW", "HEALTH_ALERT_VIEW", "REFERRAL_VIEW"],
};

export function requiredCapability(roleCode: string, permission: string) {
  return roleCode === "community_health_worker" ? COMMUNITY_CAPABILITIES[permission] ?? null : null;
}

export function specialtyAllows(specialtyCode: string, permission: string) {
  return PROFESSIONAL_SPECIALTY_PERMISSIONS[specialtyCode]?.includes(permission) ?? false;
}

export function decideAccess(permission: PermissionCode, facts: AccessFacts): AccessDecision {
  if (!facts.authenticated) return { allowed: false, status: 401, reason: "UNAUTHENTICATED" };
  if (!facts.active) return { allowed: false, status: 401, reason: "ACCOUNT_INACTIVE" };
  if (!facts.roleCodes.some((role) => facts.grantedPermissions.includes(`${role}:${permission}`))) {
    return { allowed: false, status: 403, reason: "PERMISSION_DENIED" };
  }

  const resource = facts.resource;
  if (!resource) return { allowed: true };
  const globalAdmin = facts.roleCodes.some((role) => role === "super_administrator" || role === "administrator");

  if (resource.type === "child") {
    if (resource.medicalData && globalAdmin) return { allowed: false, status: 403, reason: "MEDICAL_ACCESS_REQUIRES_CLINICAL_ROLE" };
    const allowed = facts.roleCodes.some((role) => {
      if (role === "parent") return resource.isOwnGuardian === true && (!resource.medicalData || resource.consentGranted === true);
      if (role === "community_health_worker") return resource.childAssigned === true && resource.consentGranted === true;
      if (role === "health_professional") return resource.childAssigned === true && resource.centerAssigned === true && resource.consentGranted === true;
      return false;
    });
    return allowed ? { allowed: true } : { allowed: false, status: 403, reason: "CHILD_SCOPE_DENIED" };
  }
  if (globalAdmin) return { allowed: true };

  const scopeMatch = facts.roleCodes.some((role) => {
    if (role === "national_administrator") return true;
    if (resource.type === "health_center") {
      if (["health_facility_administrator", "health_center_manager", "health_professional", "community_health_worker", "health_supervisor"].includes(role)) return resource.centerAssigned === true;
      if (role === "health_zone_administrator") return resource.healthZoneAssigned === true;
      if (role === "provincial_administrator") return resource.provinceAssigned === true;
      return false;
    }
    if (resource.type === "health_zone") return role === "health_zone_administrator" ? resource.healthZoneAssigned === true : role === "provincial_administrator" && resource.provinceAssigned === true;
    if (resource.type === "province") return role === "provincial_administrator" && resource.provinceAssigned === true;
    return false;
  });
  return scopeMatch ? { allowed: true } : { allowed: false, status: 403, reason: "RESOURCE_SCOPE_DENIED" };
}

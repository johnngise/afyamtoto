import test from "node:test";
import assert from "node:assert/strict";
import { decideAccess } from "../../lib/rbac-policy.ts";
import { mayAssignRole, PERMISSION_CATALOG, ROLE_PERMISSION_GRANTS } from "../../lib/rbac-catalog.ts";

function evaluate(role, permission, resource, overrides = {}) {
  return decideAccess(permission, {
    authenticated: true,
    active: true,
    roleCodes: [role],
    grantedPermissions: [`${role}:${permission}`],
    ...(resource ? { resource } : {}),
    ...overrides,
  });
}

test("catalogue permissions and role grants only reference defined values", () => {
  const permissions = new Set(Object.keys(PERMISSION_CATALOG));
  for (const grants of Object.values(ROLE_PERMISSION_GRANTS)) {
    for (const permission of grants) assert.ok(permissions.has(permission), `Unknown permission ${permission}`);
  }
});

test("unauthenticated and suspended accounts are denied", () => {
  assert.equal(decideAccess("CHILD_VIEW", { authenticated: false, active: true, roleCodes: ["parent"], grantedPermissions: ["parent:CHILD_VIEW"] }).status, 401);
  assert.equal(decideAccess("CHILD_VIEW", { authenticated: true, active: false, roleCodes: ["parent"], grantedPermissions: ["parent:CHILD_VIEW"] }).status, 401);
});

test("an ungranted permission is denied by default", () => {
  const result = decideAccess("CHILD_VIEW", { authenticated: true, active: true, roleCodes: ["health_data_analyst"], grantedPermissions: [] });
  assert.equal(result.allowed, false);
  assert.equal(result.reason, "PERMISSION_DENIED");
});

test("a parent can view a linked child's basic profile", () => {
  assert.deepEqual(evaluate("parent", "CHILD_VIEW", { type: "child", isOwnGuardian: true }), { allowed: true });
});

test("a parent cannot view another family's child", () => {
  assert.equal(evaluate("parent", "CHILD_VIEW", { type: "child", isOwnGuardian: false }).allowed, false);
});

test("a parent cannot view clinical data after consent is withdrawn", () => {
  assert.equal(evaluate("parent", "GROWTH_VIEW", { type: "child", isOwnGuardian: true, medicalData: true, consentGranted: false }).allowed, false);
});

test("a parent can view own clinical data while consent is active", () => {
  assert.equal(evaluate("parent", "GROWTH_VIEW", { type: "child", isOwnGuardian: true, medicalData: true, consentGranted: true }).allowed, true);
});

test("a community worker cannot access a child outside assigned work", () => {
  assert.equal(evaluate("community_health_worker", "GROWTH_VIEW", { type: "child", childAssigned: false, consentGranted: true, medicalData: true }).allowed, false);
});

test("a community worker cannot access a child without active consent", () => {
  assert.equal(evaluate("community_health_worker", "GROWTH_VIEW", { type: "child", childAssigned: true, consentGranted: false, medicalData: true }).allowed, false);
});

test("a community worker cannot diagnose, prescribe, or validate clinical data", () => {
  for (const permission of ["MEDICAL_RECORD_CREATE", "GROWTH_VALIDATE", "VACCINATION_VALIDATE"]) {
    assert.equal(ROLE_PERMISSION_GRANTS.community_health_worker.includes(permission), false);
  }
});

test("a professional without the assigned centre is denied a child record", () => {
  assert.equal(evaluate("health_professional", "MEDICAL_RECORD_VIEW", { type: "child", childAssigned: true, centerAssigned: false, consentGranted: true, medicalData: true }).allowed, false);
});

test("a professional with case, centre, and consent can access the authorized record", () => {
  assert.equal(evaluate("health_professional", "MEDICAL_RECORD_VIEW", { type: "child", childAssigned: true, centerAssigned: true, consentGranted: true, medicalData: true }).allowed, true);
});

test("a provincial administrator cannot administer another province", () => {
  assert.equal(evaluate("provincial_administrator", "FACILITY_UPDATE", { type: "health_center", provinceAssigned: false }).allowed, false);
});

test("a provincial administrator can administer a center in the assigned province", () => {
  assert.equal(evaluate("provincial_administrator", "FACILITY_UPDATE", { type: "health_center", provinceAssigned: true }).allowed, true);
});

test("a facility administrator cannot administer another facility", () => {
  assert.equal(evaluate("health_facility_administrator", "USER_UPDATE", { type: "health_center", centerAssigned: false }).allowed, false);
});

test("an analyst cannot view identifiable child records", () => {
  assert.equal(evaluate("health_data_analyst", "CHILD_VIEW", { type: "child", isOwnGuardian: false }).allowed, false);
  assert.equal(ROLE_PERMISSION_GRANTS.health_data_analyst.includes("MEDICAL_RECORD_VIEW"), false);
});

test("a Super Admin does not receive medical chart access by virtue of platform role", () => {
  const result = decideAccess("MEDICAL_RECORD_VIEW", { authenticated: true, active: true, roleCodes: ["super_administrator"], grantedPermissions: ["super_administrator:MEDICAL_RECORD_VIEW"], resource: { type: "child", medicalData: true } });
  assert.equal(result.allowed, false);
});

test("only a Super Admin can grant roles and cannot create a legacy administrator account", () => {
  assert.equal(mayAssignRole(["super_administrator"], "health_professional"), true);
  assert.equal(mayAssignRole(["health_facility_administrator"], "super_administrator"), false);
  assert.equal(mayAssignRole(["health_facility_administrator"], "administrator"), false);
});

test("an exceptional medical access request does not itself grant record access", () => {
  assert.equal(ROLE_PERMISSION_GRANTS.super_administrator.includes("MEDICAL_RECORD_VIEW"), false);
  assert.equal(ROLE_PERMISSION_GRANTS.national_administrator.includes("MEDICAL_RECORD_VIEW"), false);
  assert.equal(ROLE_PERMISSION_GRANTS.health_data_analyst.includes("MEDICAL_RECORD_VIEW"), false);
});

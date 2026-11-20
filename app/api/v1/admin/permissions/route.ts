import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { PERMISSION_CATALOG, ROLE_PERMISSION_GRANTS } from "@/lib/rbac-catalog";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function GET() {
  const access = await authorize({ permission: "PERMISSION_MANAGE", allowedRoles: ["super_administrator"] });
  if (!access.allowed) return access.response;
  const [roles, permissions, grants] = await Promise.all([
    prisma.role.findMany({ where: { code: { in: Object.keys(ROLE_PERMISSION_GRANTS) } }, select: { code: true, displayName: true }, orderBy: { code: "asc" } }),
    prisma.permission.findMany({ where: { code: { in: Object.keys(PERMISSION_CATALOG) } }, select: { code: true, description: true }, orderBy: { code: "asc" } }),
    prisma.rolePermission.findMany({ select: { roleCode: true, permissionCode: true } }),
  ]);
  return Response.json({ roles, permissions, grants: grants.map(({ roleCode, permissionCode }) => ({ roleCode, permissionCode })) });
}

export async function PUT(request: Request) {
  const access = await authorize({ permission: "PERMISSION_MANAGE", allowedRoles: ["super_administrator"] });
  if (!access.allowed) return access.response;
  let input: unknown;
  try { input = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!input || typeof input !== "object") return jsonError("Données invalides.", 400);
  const body = input as { roleCode?: unknown; permissionCodes?: unknown };
  if (typeof body.roleCode !== "string" || !Object.hasOwn(ROLE_PERMISSION_GRANTS, body.roleCode) || body.roleCode === "super_administrator") return jsonError("Ce rôle ne peut pas être modifié depuis cette interface.", 400);
  if (!Array.isArray(body.permissionCodes) || body.permissionCodes.some((code) => typeof code !== "string" || !Object.hasOwn(PERMISSION_CATALOG, code))) return jsonError("Liste de permissions invalide.", 400);
  const permissions = [...new Set(body.permissionCodes as string[])];
  if (permissions.includes("PERMISSION_MANAGE") || permissions.includes("ROLE_MANAGE")) return jsonError("La gestion des privilèges reste réservée au Super Administrateur.", 403);
  const actor = BigInt(access.user.id);
  await prisma.$transaction(async (tx) => {
    await tx.rolePermission.deleteMany({ where: { roleCode: body.roleCode as string } });
    if (permissions.length) await tx.rolePermission.createMany({ data: permissions.map((permissionCode) => ({ roleCode: body.roleCode as string, permissionCode })) });
    await tx.auditEvent.create({ data: { actorUserId: actor, eventCode: "ROLE_PERMISSIONS_UPDATED", subjectType: "role", subjectId: null } });
    await tx.activityLog.create({ data: { userId: actor, action: "role_permissions_updated", description: `Permissions du rôle ${body.roleCode} mises à jour.` } });
  });
  return Response.json({ updated: true, roleCode: body.roleCode, permissionCodes: permissions });
}

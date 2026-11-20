import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
type Context = { params: Promise<{ id: string }> };

export async function PATCH(request: Request, context: Context) {
  const access = await authorize({ permission: "CONTENT_MANAGE", allowedRoles: ["super_administrator", "administrator"] });
  if (!access.allowed) return access.response;
  const actor = access.user;
  const { id } = await context.params;
  if (!/^\d+$/u.test(id)) return jsonError("Référence introuvable.", 404);
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object" || typeof (body as Record<string, unknown>).isActive !== "boolean") return jsonError("Statut de référence invalide.", 400);
  const vaccine = await prisma.vaccine.update({ where: { id: BigInt(id) }, data: { isActive: (body as { isActive: boolean }).isActive } });
  await prisma.activityLog.create({ data: { userId: BigInt(actor.id), action: vaccine.isActive ? "vaccine_reference_activated" : "vaccine_reference_deactivated", description: `Référence vaccinale ${vaccine.vaccineCode} mise à jour.` } });
  return Response.json({ vaccine: { id: vaccine.id.toString(), vaccineCode: vaccine.vaccineCode, displayName: vaccine.displayName, isActive: vaccine.isActive } });
}

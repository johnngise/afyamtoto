import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function GET() {
  const access = await authorize({ permission: "NOTIFICATION_VIEW" });
  if (!access.allowed) return access.response;
  const user = access.user;
  const notifications = await prisma.notification.findMany({ where: { userId: BigInt(user.id) }, orderBy: { createdAt: "desc" }, take: 100 });
  return Response.json({ notifications: notifications.map((item) => ({ ...item, id: item.id.toString(), userId: item.userId.toString() })) });
}

export async function PATCH(request: Request) {
  const access = await authorize({ permission: "NOTIFICATION_UPDATE" });
  if (!access.allowed) return access.response;
  const user = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object" || !Array.isArray((body as Record<string, unknown>).ids)) return jsonError("Liste de notifications invalide.", 400);
  const rawIds = (body as { ids: unknown[] }).ids;
  if (rawIds.length > 100 || rawIds.some((id) => typeof id !== "string" || !/^\d+$/u.test(id))) return jsonError("Liste de notifications invalide.", 400);
  const ids = [...new Set((rawIds as string[]).map(BigInt))];
  const updated = await prisma.notification.updateMany({ where: { userId: BigInt(user.id), id: { in: ids } }, data: { isRead: true } });
  return Response.json({ updated: updated.count });
}

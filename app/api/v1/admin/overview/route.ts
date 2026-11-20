import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function GET() {
  const access = await authorize({ permission: "ADMIN_DASHBOARD_VIEW", allowedRoles: ["super_administrator", "administrator"] });
  if (!access.allowed) return access.response;

  try {
    const [users, activeUsers, children, centers, pendingAppointments, openAlerts, activeVaccines, activity] = await Promise.all([
      prisma.user.count(),
      prisma.user.count({ where: { isActive: true } }),
      prisma.child.count(),
      prisma.healthCenter.count(),
      prisma.appointmentRequest.count({ where: { status: "pending" } }),
      prisma.healthAlert.count({ where: { status: { in: ["pending_review", "under_review"] } } }),
      prisma.vaccine.count({ where: { isActive: true } }),
      prisma.activityLog.findMany({ take: 8, orderBy: { createdAt: "desc" }, include: { user: { select: { displayName: true } } } }),
    ]);
    return Response.json({
      stats: { users, activeUsers, children, centers, pendingAppointments, openAlerts, activeVaccines },
      activity: activity.map((entry) => ({ id: entry.id.toString(), action: entry.action, description: entry.description, createdAt: entry.createdAt, actor: entry.user?.displayName ?? "Compte supprimé" })),
    });
  } catch (error) {
    console.error("Admin overview failed", error);
    return jsonError("Impossible de charger les indicateurs administratifs.", 503);
  }
}

import { getCurrentUser, jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";
import { authorize } from "@/lib/authorization";

export const runtime = "nodejs";

export async function GET() {
  const user = await getCurrentUser();
  if (!user) return jsonError("Authentification requise.", 401);
  const userId = BigInt(user.id);
  try {
    const requiredPermission = user.roleCode === "parent" ? "CHILD_VIEW"
      : user.roleCode === "community_health_worker" ? "AGGREGATE_REPORT_VIEW"
      : user.roleCode === "health_professional" ? "HEALTH_ALERT_VIEW"
      : user.roleCode === "super_administrator" || user.roleCode === "administrator" ? "ADMIN_DASHBOARD_VIEW"
      : "AGGREGATE_REPORT_VIEW";
    const access = await authorize({ permission: requiredPermission });
    if (!access.allowed) return access.response;

    if (user.roleCode === "super_administrator" || user.roleCode === "administrator") {
      const [activeUsers, childProfiles, healthCenters, pendingAccessRequests] = await Promise.all([
        prisma.user.count({ where: { isActive: true } }),
        prisma.child.count(),
        prisma.healthCenter.count(),
        prisma.accessRequest.count({ where: { status: "pending" } }),
      ]);
      return Response.json({ role: user.roleCode, totals: { activeUsers, childProfiles, healthCenters, pendingAccessRequests } });
    }

    if (["national_administrator", "provincial_administrator", "health_zone_administrator", "health_facility_administrator", "health_center_manager", "health_data_analyst", "health_supervisor"].includes(user.roleCode)) {
      const now = new Date();
      const [assignments, legacyCenters] = await Promise.all([
        prisma.userAssignment.findMany({ where: { userId, roleCode: user.roleCode, OR: [{ expiresAt: null }, { expiresAt: { gt: now } }] }, select: { provinceId: true, healthZoneId: true, healthCenterId: true } }),
        prisma.userHealthCenter.findMany({ where: { userId }, select: { healthCenterId: true } }),
      ]);
      const provinceIds = assignments.flatMap((a) => a.provinceId ? [a.provinceId] : []);
      const zoneIds = assignments.flatMap((a) => a.healthZoneId ? [a.healthZoneId] : []);
      const centerIds = [...new Set([...assignments.flatMap((a) => a.healthCenterId ? [a.healthCenterId] : []), ...legacyCenters.map((a) => a.healthCenterId)])];
      const where = user.roleCode === "national_administrator" ? {} : user.roleCode === "provincial_administrator" ? { provinceId: { in: provinceIds } } : user.roleCode === "health_zone_administrator" ? { healthZoneId: { in: zoneIds } } : { id: { in: centerIds } };
      const centers = await prisma.healthCenter.findMany({ where, select: { id: true, name: true, city: true, province: true } });
      const visibleCenters = await Promise.all(centers.map(async (center) => {
        const consentingChildCount = await prisma.child.count({ where: { guardians: { some: { consentStatus: "granted" } }, healthReferrals: { some: { healthCenterId: center.id } } } });
        if (consentingChildCount < 5) return { id: center.id.toString(), name: center.name, city: center.city, province: center.province, privacySuppressed: true };
        const [vaccinations, measurements, openAlerts, referrals] = await Promise.all([
          prisma.vaccinationRecord.count({ where: { healthCenterId: center.id, status: "administered", child: { guardians: { some: { consentStatus: "granted" } } } } }),
          prisma.growthMeasurement.count({ where: { child: { guardians: { some: { consentStatus: "granted" } }, healthReferrals: { some: { healthCenterId: center.id } } } } }),
          prisma.healthAlert.count({ where: { healthCenterId: center.id, status: { in: ["pending_review", "under_review"] }, child: { guardians: { some: { consentStatus: "granted" } } } } }),
          prisma.healthReferral.count({ where: { healthCenterId: center.id, child: { guardians: { some: { consentStatus: "granted" } } } } }),
        ]);
        return { id: center.id.toString(), name: center.name, city: center.city, province: center.province, privacySuppressed: false, consentingChildCount, vaccinations, measurements, openAlerts, referrals };
      }));
      const reportable = visibleCenters.filter((center) => !center.privacySuppressed);
      const totals = reportable.reduce((value, center) => ({ consentingChildren: value.consentingChildren + ("consentingChildCount" in center ? Number(center.consentingChildCount) : 0), vaccinations: value.vaccinations + ("vaccinations" in center ? Number(center.vaccinations) : 0), measurements: value.measurements + ("measurements" in center ? Number(center.measurements) : 0), openAlerts: value.openAlerts + ("openAlerts" in center ? Number(center.openAlerts) : 0) }), { consentingChildren: 0, vaccinations: 0, measurements: 0, openAlerts: 0 });
      return Response.json({ role: user.roleCode, totals, centers: visibleCenters });
    }
    if (user.roleCode === "parent") {
      const guardians = await prisma.childGuardian.findMany({ where: { userId }, include: { child: { select: { id: true, firstName: true, lastName: true, dateOfBirth: true, sex: true } } }, orderBy: { linkedAt: "desc" } });
      const childIds = guardians.map((item) => item.childId);
      const [measurements, vaccinations, appointments, notifications, centers] = await Promise.all([
        prisma.growthMeasurement.findMany({ where: { childId: { in: childIds }, child: { guardians: { some: { userId, consentStatus: "granted" } } } }, orderBy: { measuredAt: "desc" }, take: 20 }),
        prisma.vaccinationRecord.findMany({ where: { childId: { in: childIds }, status: "administered", child: { guardians: { some: { userId, consentStatus: "granted" } } } }, include: { dose: { include: { vaccine: true } } }, orderBy: { administeredAt: "desc" }, take: 20 }),
        prisma.appointmentRequest.findMany({ where: { parentUserId: userId }, include: { center: { select: { name: true } }, child: { select: { firstName: true, lastName: true } } }, orderBy: { createdAt: "desc" }, take: 20 }),
        prisma.notification.findMany({ where: { userId }, orderBy: { createdAt: "desc" }, take: 20 }),
        prisma.healthCenter.findMany({ select: { id: true, name: true, city: true, province: true, addressText: true, phoneE164: true }, orderBy: { name: "asc" } }),
      ]);
      return Response.json({ role: user.roleCode, children: guardians.map((entry) => ({ ...entry.child, id: entry.child.id.toString(), consentStatus: entry.consentStatus, relationshipType: entry.relationshipType })), measurements: measurements.map((item) => ({ ...item, id: item.id.toString(), childId: item.childId.toString(), recordedByUserId: item.recordedByUserId.toString(), weightKg: item.weightKg?.toString() ?? null, lengthHeightCm: item.lengthHeightCm?.toString() ?? null, muacCm: item.muacCm?.toString() ?? null })), vaccinations: vaccinations.map((item) => ({ id: item.id.toString(), childId: item.childId.toString(), vaccine: item.dose.vaccine.displayName, dose: item.dose.displayName, administeredAt: item.administeredAt, nextDoseDate: item.nextDoseDate })), appointments: appointments.map((item) => ({ ...item, id: item.id.toString(), childId: item.childId.toString(), parentUserId: item.parentUserId.toString(), healthCenterId: item.healthCenterId.toString() })), notifications: notifications.map((item) => ({ ...item, id: item.id.toString(), userId: item.userId.toString() })), centers: centers.map((item) => ({ ...item, id: item.id.toString() })) });
    }

    if (user.roleCode === "community_health_worker") {
      const [children, visits, referrals, measurements] = await Promise.all([
        prisma.child.findMany({ where: { createdByUserId: userId }, include: { guardians: { select: { consentStatus: true } } }, orderBy: { createdAt: "desc" }, take: 100 }),
        prisma.communityVisit.count({ where: { agentUserId: userId } }),
        prisma.healthReferral.count({ where: { agentUserId: userId } }),
        prisma.growthMeasurement.count({ where: { recordedByUserId: userId } }),
      ]);
      return Response.json({ role: user.roleCode, totals: { enrolledChildren: children.length, consentPending: children.filter((child) => child.guardians.some((guardian) => guardian.consentStatus === "pending")).length, visits, referrals, measurements }, children: children.map((child) => ({ id: child.id.toString(), firstName: child.firstName, lastName: child.lastName, dateOfBirth: child.dateOfBirth, consentStatus: child.guardians.some((guardian) => guardian.consentStatus === "granted") ? "granted" : child.guardians.some((guardian) => guardian.consentStatus === "pending") ? "pending" : "withdrawn" })) });
    }

    if (user.roleCode === "health_professional") {
      const centers = await prisma.userHealthCenter.findMany({ where: { userId }, include: { center: { select: { id: true, name: true } } } });
      const centerIds = centers.map((item) => item.healthCenterId);
      const [alerts, referrals, observations] = await Promise.all([
        prisma.healthAlert.count({ where: { healthCenterId: { in: centerIds }, status: { in: ["pending_review", "under_review"] }, child: { guardians: { some: { consentStatus: "granted" } } } } }),
        prisma.healthReferral.count({ where: { healthCenterId: { in: centerIds }, status: { in: ["awaiting_follow_up", "not_attended"] }, child: { guardians: { some: { consentStatus: "granted" } } } } }),
        prisma.professionalObservation.count({ where: { healthProfessionalId: userId } }),
      ]);
      return Response.json({ role: user.roleCode, totals: { openAlerts: alerts, openReferrals: referrals, observations }, centers: centers.map((entry) => ({ ...entry.center, id: entry.center.id.toString() })) });
    }

    if (user.roleCode === "health_center_manager") {
      const centers = await prisma.userHealthCenter.findMany({ where: { userId }, select: { healthCenterId: true } });
      const centerIds = centers.map((item) => item.healthCenterId);
      const [pendingAppointments, openReferrals] = await Promise.all([
        prisma.appointmentRequest.count({ where: { healthCenterId: { in: centerIds }, status: "pending" } }),
        prisma.healthReferral.count({ where: { healthCenterId: { in: centerIds }, status: { in: ["awaiting_follow_up", "not_attended"] }, child: { guardians: { some: { consentStatus: "granted" } } } } }),
      ]);
      return Response.json({ role: user.roleCode, totals: { pendingAppointments, openReferrals }, healthCenterIds: centerIds.map(String) });
    }

    if (user.roleCode === "health_supervisor") {
      const assignedCenters = await prisma.userHealthCenter.findMany({
        where: { userId },
        include: { center: { select: { id: true, name: true, city: true, province: true } } },
        orderBy: { center: { name: "asc" } },
      });
      const centerIds = assignedCenters.map((entry) => entry.healthCenterId);
      const referralGroups = centerIds.length
        ? await prisma.healthReferral.groupBy({
            by: ["healthCenterId", "childId"],
            where: {
              healthCenterId: { in: centerIds },
              child: { guardians: { some: { consentStatus: "granted" } } },
            },
            _count: { _all: true },
          })
        : [];
      const metricsByCenter = new Map<string, { childIds: bigint[]; referrals: number }>();
      for (const group of referralGroups) {
        const centerKey = group.healthCenterId.toString();
        const current = metricsByCenter.get(centerKey) ?? { childIds: [], referrals: 0 };
        current.childIds.push(group.childId);
        current.referrals += group._count._all;
        metricsByCenter.set(centerKey, current);
      }
      const visibleCenters = await Promise.all(assignedCenters.map(async ({ center }) => {
        const aggregate = metricsByCenter.get(center.id.toString()) ?? { childIds: [], referrals: 0 };
        const referredChildren = aggregate.childIds.length;
        if (referredChildren < 5) {
          return { id: center.id.toString(), name: center.name, city: center.city, province: center.province, privacySuppressed: true };
        }
        const childFilter = { in: aggregate.childIds };
        const [openReferrals, vaccinationDoses, growthMeasurements, nutritionScreenings] = await Promise.all([
          prisma.healthReferral.count({ where: { healthCenterId: center.id, childId: childFilter, status: { in: ["awaiting_follow_up", "not_attended"] } } }),
          prisma.vaccinationRecord.count({ where: { healthCenterId: center.id, status: "administered", child: { guardians: { some: { consentStatus: "granted" } } } } }),
          prisma.growthMeasurement.count({ where: { childId: childFilter } }),
          prisma.nutritionRecord.count({ where: { childId: childFilter } }),
        ]);
        return {
          id: center.id.toString(), name: center.name, city: center.city, province: center.province,
          privacySuppressed: false, referredChildren, referrals: aggregate.referrals,
          openReferrals, vaccinationDoses, growthMeasurements, nutritionScreenings,
        };
      }));
      const reportableCenters = visibleCenters.filter((center) => !center.privacySuppressed);
      return Response.json({
        role: user.roleCode,
        totals: {
          assignedCenters: assignedCenters.length,
          reportableCenters: reportableCenters.length,
          openReferrals: reportableCenters.reduce((total, center) => total + ("openReferrals" in center ? Number(center.openReferrals ?? 0) : 0), 0),
          vaccinationDoses: reportableCenters.reduce((total, center) => total + ("vaccinationDoses" in center ? Number(center.vaccinationDoses ?? 0) : 0), 0),
        },
        centers: visibleCenters,
      });
    }

    if (user.roleCode === "administrator") {
      const [users, children, pendingAlerts, pendingAppointments, centers] = await Promise.all([
        prisma.user.count({ where: { isActive: true } }),
        prisma.child.count(),
        prisma.healthAlert.count({ where: { status: { in: ["pending_review", "under_review"] } } }),
        prisma.appointmentRequest.count({ where: { status: "pending" } }),
        prisma.healthCenter.count(),
      ]);
      return Response.json({ role: user.roleCode, totals: { activeUsers: users, childProfiles: children, pendingAlerts, pendingAppointments, healthCenters: centers } });
    }
    return jsonError("Aucun tableau de bord n’est disponible pour ce rôle.", 403);
  } catch (error) {
    console.error("Dashboard query failed", error);
    return jsonError("Impossible de charger le tableau de bord.", 503);
  }
}

import { redirect } from "next/navigation";
import { getCurrentUser } from "@/lib/auth";
import { DashboardWorkspace } from "../workspace";

const rolePath: Record<string, string> = {
  super_administrator: "super-admin", administrator: "super-admin",
  national_administrator: "national-admin", provincial_administrator: "provincial-admin",
  health_zone_administrator: "health-zone-admin", health_facility_administrator: "facility-admin",
  health_center_manager: "facility-admin", health_professional: "health-professional",
  community_health_worker: "community-health-worker", parent: "parent",
  health_data_analyst: "data-analyst", health_supervisor: "data-analyst",
};

export const dynamic = "force-dynamic";

export default async function RoleDashboard({ params }: { params: Promise<{ workspace: string }> }) {
  const [{ workspace }, user] = await Promise.all([params, getCurrentUser()]);
  if (!user) redirect("/login?scope=public");
  const ownWorkspace = rolePath[user.roleCode];
  if (!ownWorkspace) redirect("/login?scope=public");
  if (workspace !== ownWorkspace) redirect(`/dashboard/${ownWorkspace}`);
  return <DashboardWorkspace user={user} />;
}

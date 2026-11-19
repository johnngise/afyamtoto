import { redirect } from "next/navigation";
import { getCurrentUser } from "@/lib/auth";

const rolePath: Record<string, string> = {
  super_administrator: "super-admin", administrator: "super-admin",
  national_administrator: "national-admin", provincial_administrator: "provincial-admin",
  health_zone_administrator: "health-zone-admin", health_facility_administrator: "facility-admin",
  health_center_manager: "facility-admin", health_professional: "health-professional",
  community_health_worker: "community-health-worker", parent: "parent",
  health_data_analyst: "data-analyst", health_supervisor: "data-analyst",
};

export default async function DashboardIndex() {
  const user = await getCurrentUser();
  if (!user) redirect("/login?scope=public");
  redirect(`/dashboard/${rolePath[user.roleCode] ?? "parent"}`);
}

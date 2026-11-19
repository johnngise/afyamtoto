import { redirect } from "next/navigation";
import { authorize } from "@/lib/authorization";
import { AdminShell } from "./shell";

export const dynamic = "force-dynamic";

export default async function AdminLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  const access = await authorize({ permission: "ADMIN_DASHBOARD_VIEW", allowedRoles: ["super_administrator", "administrator"] });
  if (!access.allowed) redirect(access.response.status === 401 ? "/login" : "/dashboard");
  return <AdminShell user={access.user}>{children}</AdminShell>;
}

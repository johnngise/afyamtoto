import { prisma } from "@/lib/db";
import { AdminUsers } from "./users-panel";

export const dynamic = "force-dynamic";

export default async function AdminUsersPage() {
  const [provinces, zones, centers] = await Promise.all([
    prisma.province.findMany({ select: { id: true, name: true }, orderBy: { name: "asc" } }),
    prisma.healthZone.findMany({ select: { id: true, name: true, province: { select: { name: true } } }, orderBy: { name: "asc" } }),
    prisma.healthCenter.findMany({ select: { id: true, name: true, city: true }, orderBy: { name: "asc" } }),
  ]);
  return <AdminUsers geographicOptions={{
    provinces: provinces.map((item) => ({ id: item.id.toString(), label: item.name })),
    zones: zones.map((item) => ({ id: item.id.toString(), label: `${item.name} · ${item.province.name}` })),
    centers: centers.map((item) => ({ id: item.id.toString(), label: `${item.name}${item.city ? ` · ${item.city}` : ""}` })),
  }} />;
}

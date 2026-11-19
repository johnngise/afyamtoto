import { prisma } from "@/lib/db";
import { AdminContent } from "./content-panel";

export const dynamic = "force-dynamic";

export default async function AdminContentPage() {
  const [vaccines, centers] = await Promise.all([
    prisma.vaccine.findMany({ include: { doses: { orderBy: { doseOrder: "asc" } } }, orderBy: { displayName: "asc" } }),
    prisma.healthCenter.findMany({ orderBy: { name: "asc" } }),
  ]);
  return <AdminContent
    vaccines={vaccines.map((vaccine) => ({ ...vaccine, id: vaccine.id.toString(), doses: vaccine.doses.map((dose) => ({ ...dose, id: dose.id.toString(), vaccineId: dose.vaccineId.toString() })) }))}
    centers={centers.map((center) => ({ ...center, id: center.id.toString(), latitude: center.latitude?.toString() ?? null, longitude: center.longitude?.toString() ?? null }))}
  />;
}

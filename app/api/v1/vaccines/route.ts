import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function GET() {
  const access = await authorize({ permission: "VACCINATION_VIEW" });
  if (!access.allowed) return access.response;
  const user = access.user;
  const vaccines = await prisma.vaccine.findMany({ where: { isActive: true }, orderBy: { displayName: "asc" } });
  const doses = await prisma.vaccineDose.findMany({ orderBy: [{ vaccineId: "asc" }, { doseOrder: "asc" }] });
  return Response.json({ vaccines: vaccines.map((vaccine) => ({ ...vaccine, id: vaccine.id.toString(), doses: doses.filter((dose) => dose.vaccineId === vaccine.id).map((dose) => ({ ...dose, id: dose.id.toString(), vaccineId: dose.vaccineId.toString() })) })) });
}

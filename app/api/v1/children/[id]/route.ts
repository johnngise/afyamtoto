import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

type Context = { params: Promise<{ id: string }> };

export async function GET(_request: Request, context: Context) {
  const { id } = await context.params;
  if (!/^\d+$/u.test(id)) return jsonError("Profil introuvable.", 404);
  const authz = await authorize({ permission: "CHILD_VIEW", allowedRoles: ["parent"], resource: { type: "child", id } });
  if (!authz.allowed) return authz.response;
  const user = authz.user;
  const child = await prisma.child.findFirst({
    where: { id: BigInt(id), guardians: { some: { userId: BigInt(user.id) } } },
    select: { id: true, firstName: true, lastName: true, dateOfBirth: true, sex: true, createdAt: true },
  });
  return child ? Response.json({ child: { ...child, id: child.id.toString() } }) : jsonError("Profil introuvable.", 404);
}

export async function PATCH(request: Request, context: Context) {
  const { id } = await context.params;
  if (!/^\d+$/u.test(id)) return jsonError("Profil introuvable.", 404);
  const authz = await authorize({ permission: "CHILD_UPDATE", allowedRoles: ["parent"], resource: { type: "child", id } });
  if (!authz.allowed) return authz.response;
  const user = authz.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const firstName = typeof data.firstName === "string" ? data.firstName.trim() : "";
  const lastName = typeof data.lastName === "string" ? data.lastName.trim() : "";
  const dateOfBirth = typeof data.dateOfBirth === "string" ? data.dateOfBirth : "";
  if (!firstName || Array.from(firstName).length > 100 || !lastName || Array.from(lastName).length > 100) return jsonError("Les prénom et nom sont obligatoires (100 caractères maximum).", 400);
  if (!/^\d{4}-\d{2}-\d{2}$/u.test(dateOfBirth)) return jsonError("Date de naissance invalide (AAAA-MM-JJ attendu).", 400);
  const parsedDate = new Date(`${dateOfBirth}T00:00:00.000Z`);
  if (Number.isNaN(parsedDate.getTime()) || parsedDate.toISOString().slice(0, 10) !== dateOfBirth || parsedDate > new Date()) return jsonError("Date de naissance invalide.", 400);
  const earliestDate = new Date(); earliestDate.setUTCFullYear(earliestDate.getUTCFullYear() - 5);
  if (parsedDate < earliestDate) return jsonError("Le profil doit concerner un enfant de 0 à 5 ans.", 400);
  if (data.sex !== "female" && data.sex !== "male" && data.sex !== "unknown") return jsonError("Valeur de sexe invalide.", 400);
  const idValue = BigInt(id);
  const access = await prisma.childGuardian.findUnique({ where: { childId_userId: { childId: idValue, userId: BigInt(user.id) } } });
  if (!access) return jsonError("Profil introuvable.", 404);
  const child = await prisma.$transaction(async (tx) => {
    const updated = await tx.child.update({
      where: { id: idValue },
      data: { firstName, lastName, dateOfBirth: parsedDate, sex: data.sex as "female" | "male" | "unknown" },
      select: { id: true, firstName: true, lastName: true, dateOfBirth: true, sex: true, updatedAt: true },
    });
    if (typeof data.consentGranted === "boolean" || data.relationshipType === "parent" || data.relationshipType === "guardian") {
      const granting = data.consentGranted === true;
      await tx.childGuardian.update({
        where: { childId_userId: { childId: idValue, userId: BigInt(user.id) } },
        data: {
          ...(data.relationshipType === "parent" || data.relationshipType === "guardian" ? { relationshipType: data.relationshipType } : {}),
          ...(typeof data.consentGranted === "boolean" ? {
            consentStatus: granting ? "granted" : "withdrawn",
            ...(granting ? { consentAt: new Date(), consentWithdrawnAt: null } : { consentWithdrawnAt: new Date() }),
          } : {}),
        },
      });
    }
    await tx.activityLog.create({ data: { userId: BigInt(user.id), action: "child_updated", description: "Profil enfant modifié par son parent." } });
    return updated;
  });
  return Response.json({ child: { ...child, id: child.id.toString() } });
}

export async function DELETE(_request: Request, context: Context) {
  const { id } = await context.params;
  if (!/^\d+$/u.test(id)) return jsonError("Profil introuvable.", 404);
  const authz = await authorize({ permission: "CHILD_ARCHIVE", allowedRoles: ["parent"], resource: { type: "child", id } });
  if (!authz.allowed) return authz.response;
  const user = authz.user;
  const idValue = BigInt(id);
  const access = await prisma.childGuardian.findUnique({ where: { childId_userId: { childId: idValue, userId: BigInt(user.id) } } });
  if (!access) return jsonError("Profil introuvable.", 404);
  // Permit deletion only when this parent is the sole guardian and creator.
  const child = await prisma.child.findFirst({ where: { id: idValue, createdByUserId: BigInt(user.id), guardians: { some: { userId: BigInt(user.id) } } }, include: { guardians: true } });
  if (!child || child.guardians.length !== 1) return jsonError("Ce profil ne peut pas être supprimé car il est partagé avec un autre tuteur.", 409);
  const [growth, vaccinations, appointments, medical, nutrition, alerts, visits, referrals, observations] = await Promise.all([
    prisma.growthMeasurement.count({ where: { childId: idValue } }), prisma.vaccinationRecord.count({ where: { childId: idValue } }),
    prisma.appointmentRequest.count({ where: { childId: idValue } }), prisma.medicalRecord.count({ where: { childId: idValue } }),
    prisma.nutritionRecord.count({ where: { childId: idValue } }), prisma.healthAlert.count({ where: { childId: idValue } }),
    prisma.communityVisit.count({ where: { childId: idValue } }), prisma.healthReferral.count({ where: { childId: idValue } }),
    prisma.professionalObservation.count({ where: { childId: idValue } }),
  ]);
  if (growth + vaccinations + appointments + medical + nutrition + alerts + visits + referrals + observations > 0) return jsonError("Ce dossier contient un historique de suivi et ne peut pas être supprimé.", 409);
  await prisma.child.delete({ where: { id: idValue } });
  await prisma.activityLog.create({ data: { userId: BigInt(user.id), action: "child_deleted", description: "Profil enfant supprimé par son parent." } });
  return new Response(null, { status: 204 });
}

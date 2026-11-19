import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function GET() {
  const access = await authorize({ permission: "CHILD_VIEW", allowedRoles: ["community_health_worker"] });
  if (!access.allowed) return access.response;
  const agent = access.user;
  const children = await prisma.child.findMany({ where: { createdByUserId: BigInt(agent.id) }, include: { guardians: { select: { consentStatus: true, relationshipType: true, user: { select: { displayName: true } } } } }, orderBy: { createdAt: "desc" } });
  return Response.json({ children: children.map((child) => ({ id: child.id.toString(), firstName: child.firstName, lastName: child.lastName, dateOfBirth: child.dateOfBirth, sex: child.sex, guardians: child.guardians.map((guardian) => ({ consentStatus: guardian.consentStatus, relationshipType: guardian.relationshipType, displayName: guardian.user.displayName })) })) });
}

export async function POST(request: Request) {
  const access = await authorize({ permission: "CHILD_CREATE", allowedRoles: ["community_health_worker"] });
  if (!access.allowed) return access.response;
  const agent = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const parentEmail = typeof data.parentEmail === "string" ? data.parentEmail.trim().toLowerCase() : "";
  const firstName = typeof data.firstName === "string" ? data.firstName.trim() : "";
  const lastName = typeof data.lastName === "string" ? data.lastName.trim() : "";
  const relationshipType = data.relationshipType;
  const dob = typeof data.dateOfBirth === "string" ? data.dateOfBirth : "";
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(parentEmail) || parentEmail.length > 254) return jsonError("Adresse e-mail parent invalide.", 400);
  if (!firstName || firstName.length > 100 || !lastName || lastName.length > 100) return jsonError("Prénom et nom obligatoires (100 caractères maximum).", 400);
  if (relationshipType !== "parent" && relationshipType !== "guardian") return jsonError("Lien familial invalide.", 400);
  if (data.sex !== "female" && data.sex !== "male" && data.sex !== "unknown") return jsonError("Valeur de sexe invalide.", 400);
  if (!/^\d{4}-\d{2}-\d{2}$/u.test(dob)) return jsonError("Date de naissance invalide.", 400);
  const dateOfBirth = new Date(`${dob}T00:00:00.000Z`);
  const earliest = new Date(); earliest.setUTCFullYear(earliest.getUTCFullYear() - 5);
  if (Number.isNaN(dateOfBirth.getTime()) || dateOfBirth.toISOString().slice(0, 10) !== dob || dateOfBirth > new Date() || dateOfBirth < earliest) return jsonError("La date doit correspondre à un enfant de 0 à 5 ans.", 400);
  try {
    const parent = await prisma.user.findFirst({ where: { email: parentEmail, isActive: true, roles: { some: { roleCode: "parent" } } } });
    if (!parent) return jsonError("Le parent doit d’abord créer son compte.", 404);
    const created = await prisma.$transaction(async (tx) => {
      const child = await tx.child.create({ data: { firstName, lastName, dateOfBirth, sex: data.sex as "female" | "male" | "unknown", createdByUserId: BigInt(agent.id) } });
      await tx.childGuardian.create({ data: { childId: child.id, userId: parent.id, relationshipType, isPrimary: true, consentStatus: "pending" } });
      await tx.notification.create({ data: { userId: parent.id, title: "Profil enfant à vérifier", message: "Un agent de santé a rattaché un profil à votre compte. Vérifiez-le et confirmez votre consentement avant tout suivi de santé.", type: "child_consent_requested" } });
      await tx.activityLog.create({ data: { userId: BigInt(agent.id), action: "community_child_enrolled", description: "Profil communautaire créé en attente du consentement du tuteur." } });
      return child;
    });
    return Response.json({ child: { ...created, id: created.id.toString(), createdByUserId: created.createdByUserId.toString() } }, { status: 201 });
  } catch (error) {
    console.error("Community child enrollment failed", error);
    return jsonError("Le rattachement du profil a échoué.", 503);
  }
}

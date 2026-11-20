import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function GET() {
  try {
    const access = await authorize({ permission: "CHILD_VIEW", allowedRoles: ["parent"] });
    if (!access.allowed) return access.response;
    const user = access.user;
    const children = await prisma.child.findMany({
      where: { guardians: { some: { userId: BigInt(user.id) } } },
      orderBy: { createdAt: "desc" },
      select: { id: true, firstName: true, lastName: true, dateOfBirth: true, sex: true, createdAt: true },
    });
    return Response.json({ children: children.map((child) => ({ ...child, id: child.id.toString() })) });
  } catch (error) {
    console.error("Child listing failed", error);
    return jsonError("Impossible de charger les profils d’enfants.", 503);
  }
}

export async function POST(request: Request) {
  const access = await authorize({ permission: "CHILD_CREATE", allowedRoles: ["parent"] });
    if (!access.allowed) return access.response;
    const user = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const firstName = typeof data.firstName === "string" ? data.firstName.trim() : "";
  const lastName = typeof data.lastName === "string" ? data.lastName.trim() : "";
  const dateOfBirth = typeof data.dateOfBirth === "string" ? data.dateOfBirth : "";
  const sex = data.sex;
  if (data.consentGranted !== true) return jsonError("Confirmez votre accord pour le suivi du dossier de cet enfant.", 400);
  if (!firstName || Array.from(firstName).length > 100 || !lastName || Array.from(lastName).length > 100) return jsonError("Les prénom et nom sont obligatoires (100 caractères maximum).", 400);
  if (!/^\d{4}-\d{2}-\d{2}$/u.test(dateOfBirth)) return jsonError("Date de naissance invalide (AAAA-MM-JJ attendu).", 400);
  const parsedDate = new Date(`${dateOfBirth}T00:00:00.000Z`);
  if (Number.isNaN(parsedDate.getTime()) || parsedDate.toISOString().slice(0, 10) !== dateOfBirth || parsedDate > new Date()) return jsonError("Date de naissance invalide.", 400);
  const earliestDate = new Date(); earliestDate.setUTCFullYear(earliestDate.getUTCFullYear() - 5);
  if (parsedDate < earliestDate) return jsonError("Le profil doit concerner un enfant de 0 à 5 ans.", 400);
  if (sex !== "female" && sex !== "male" && sex !== "unknown") return jsonError("Valeur de sexe invalide.", 400);
  try {
    const child = await prisma.$transaction(async (tx) => {
      const created = await tx.child.create({
        data: { firstName, lastName, dateOfBirth: parsedDate, sex, createdByUserId: BigInt(user.id) },
      });
      const relationshipType = data.relationshipType === "parent" || data.relationshipType === "guardian" ? data.relationshipType : "guardian";
      await tx.childGuardian.create({ data: { childId: created.id, userId: BigInt(user.id), relationshipType, isPrimary: true, consentStatus: "granted", consentAt: new Date() } });
      await tx.activityLog.create({ data: { userId: BigInt(user.id), action: "child_created", description: "Profil enfant créé par son parent." } });
      return created;
    });
    return Response.json({ child: { id: child.id.toString(), firstName: child.firstName, lastName: child.lastName, dateOfBirth: child.dateOfBirth, sex: child.sex } }, { status: 201 });
  } catch (error) {
    console.error("Child creation failed", error);
    return jsonError("La création du profil a échoué.", 503);
  }
}

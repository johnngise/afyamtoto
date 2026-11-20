import { Prisma } from "@prisma/client";
import { authorize } from "@/lib/authorization";
import { jsonError } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function POST(request: Request) {
  const access = await authorize({ permission: "CONTENT_MANAGE", allowedRoles: ["super_administrator", "administrator"] });
  if (!access.allowed) return access.response;
  const actor = access.user;
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const vaccineCode = typeof data.vaccineCode === "string" ? data.vaccineCode.trim() : "";
  const displayName = typeof data.displayName === "string" ? data.displayName.trim() : "";
  const description = typeof data.description === "string" ? data.description.trim() : "";
  if (!/^[A-Za-z0-9_-]{1,64}$/u.test(vaccineCode)) return jsonError("Le code du vaccin doit contenir 1 à 64 lettres sans accent, chiffres, tirets ou traits de soulignement, sans espace (ex. BCG ou PENTA_1).", 400);
  if (!displayName || displayName.length > 160) return jsonError("Le nom affiché du vaccin est obligatoire et ne peut pas dépasser 160 caractères.", 400);
  if (description.length > 500) return jsonError("La description ne peut pas dépasser 500 caractères.", 400);
  if (!Array.isArray(data.doses) || data.doses.length < 1 || data.doses.length > 30) return jsonError("Ajoutez entre 1 et 30 doses.", 400);
  const doses = data.doses.map((item) => item && typeof item === "object" ? item as Record<string, unknown> : {});
  const normalized = doses.map((dose, index) => ({
    doseCode: typeof dose.doseCode === "string" ? dose.doseCode.trim() : "",
    displayName: typeof dose.displayName === "string" ? dose.displayName.trim() : "",
    doseOrder: typeof dose.doseOrder === "number" ? dose.doseOrder : index + 1,
  }));
  if (normalized.some((dose) => !/^[A-Za-z0-9_-]{1,64}$/u.test(dose.doseCode))) return jsonError("Le code de dose doit contenir 1 à 64 lettres sans accent, chiffres, tirets ou traits de soulignement, sans espace (ex. BCG_1).", 400);
  if (normalized.some((dose) => !dose.displayName || dose.displayName.length > 120)) return jsonError("Le nom de la dose est obligatoire et ne peut pas dépasser 120 caractères.", 400);
  if (normalized.some((dose) => !Number.isInteger(dose.doseOrder) || dose.doseOrder < 1 || dose.doseOrder > 32767)) return jsonError("L’ordre de la dose doit être un entier positif.", 400);
  if (new Set(normalized.map((dose) => dose.doseCode)).size !== normalized.length || new Set(normalized.map((dose) => dose.doseOrder)).size !== normalized.length) return jsonError("Les codes et ordres de dose doivent être uniques.", 400);
  try {
    const vaccine = await prisma.$transaction(async (tx) => {
      const created = await tx.vaccine.create({ data: { vaccineCode, displayName, description: description || null, doses: { create: normalized } }, include: { doses: { orderBy: { doseOrder: "asc" } } } });
      await tx.activityLog.create({ data: { userId: BigInt(actor.id), action: "vaccine_reference_created", description: `Référence vaccinale ${vaccineCode} créée.` } });
      return created;
    });
    return Response.json({ vaccine: { ...vaccine, id: vaccine.id.toString(), doses: vaccine.doses.map((dose) => ({ ...dose, id: dose.id.toString(), vaccineId: dose.vaccineId.toString() })) } }, { status: 201 });
  } catch (error) {
    if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === "P2002") return jsonError("Cette référence ou une de ses doses existe déjà.", 409);
    console.error("Vaccine reference creation failed", error);
    return jsonError("La création de la référence a échoué.", 503);
  }
}

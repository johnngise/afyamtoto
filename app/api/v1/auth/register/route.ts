import { Prisma } from "@prisma/client";
import { createSession, isStrongPassword, jsonError, makePasswordHash } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";

export async function POST(request: Request) {
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const displayName = typeof data.displayName === "string" ? data.displayName.trim() : "";
  const email = typeof data.email === "string" ? data.email.trim().toLowerCase() : "";
  const phone = typeof data.phoneE164 === "string" ? data.phoneE164.trim() : "";
  const password = typeof data.password === "string" ? data.password : "";
  if (!displayName || Array.from(displayName).length > 160) return jsonError("Le nom doit contenir entre 1 et 160 caractères.", 400);
  if (email.length > 254 || (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(email))) return jsonError("Adresse e-mail invalide.", 400);
  if (phone && !/^\+[1-9][0-9]{1,14}$/u.test(phone)) return jsonError("Le numéro doit être au format international, par exemple +243XXXXXXXXX.", 400);
  if (!email && !phone) return jsonError("Une adresse e-mail ou un numéro est obligatoire.", 400);
  if (!isStrongPassword(password)) return jsonError("Utilisez au moins 12 caractères et trois types de caractères, ou une phrase de 20 caractères minimum (72 octets maximum).", 400);

  try {
    const user = await prisma.user.create({
      data: {
        displayName, email: email || null, phoneE164: phone || null,
        passwordHash: await makePasswordHash(password),
        roles: { create: { roleCode: "parent" } },
      },
      select: { id: true, displayName: true, email: true, phoneE164: true, roles: { select: { roleCode: true } } },
    });
    await prisma.activityLog.create({ data: { userId: user.id, action: "parent_registered", description: "Compte parent créé." } });
    await createSession(user.id, "parent");
    return Response.json({ user: { id: user.id.toString(), displayName: user.displayName, email: user.email, phoneE164: user.phoneE164, roleCode: "parent" } }, { status: 201 });
  } catch (error) {
    if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === "P2002") return jsonError("Cette adresse ou ce numéro est déjà associé à un compte.", 409);
    console.error("Parent registration failed", error);
    return jsonError("La création du compte a échoué.", 500);
  }
}

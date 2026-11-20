import { createHash, timingSafeEqual } from "node:crypto";
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
  const username = typeof data.username === "string" ? data.username.trim().toLowerCase() : "";
  const email = typeof data.email === "string" ? data.email.trim().toLowerCase() : "";
  const phone = typeof data.phoneE164 === "string" ? data.phoneE164.trim() : "";
  const password = typeof data.password === "string" ? data.password : "";
  const providedSecret = typeof data.setupSecret === "string" ? data.setupSecret : "";
  const configuredSecret = process.env.AFYA_ADMIN_SETUP_SECRET ?? "";
  if (!configuredSecret || configuredSecret.length < 32 || configuredSecret.length > 256) return jsonError("La configuration initiale n’est pas activée.", 404);
  if (!displayName || Array.from(displayName).length > 160) return jsonError("Nom invalide.", 400);
  if (username && !/^[a-z0-9._-]{3,64}$/u.test(username)) return jsonError("Nom d’utilisateur invalide.", 400);
  if (email.length > 254 || (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(email))) return jsonError("Adresse e-mail invalide.", 400);
  if (phone && !/^\+[1-9][0-9]{1,14}$/u.test(phone)) return jsonError("Numéro de téléphone invalide.", 400);
  if (!email && !phone) return jsonError("Une adresse e-mail ou un numéro est obligatoire.", 400);
  if (!isStrongPassword(password)) return jsonError("Mot de passe trop faible.", 400);
  const attemptKey = createHash("sha256").update(`admin-setup\0${request.headers.get("x-real-ip") ?? "unknown"}`).digest("hex");
  const now = new Date();
  const attempt = await prisma.loginAttempt.findUnique({ where: { key: attemptKey } });
  if (attempt?.lockedUntil && attempt.lockedUntil > now) return jsonError("La configuration initiale est temporairement bloquée.", 429);
  const suppliedHash = createHash("sha256").update(providedSecret).digest();
  const expectedHash = createHash("sha256").update(configuredSecret).digest();
  if (!timingSafeEqual(suppliedHash, expectedHash)) {
    const count = attempt && now.getTime() - attempt.windowStart.getTime() <= 15 * 60 * 1000 ? attempt.count + 1 : 1;
    await prisma.loginAttempt.upsert({
      where: { key: attemptKey },
      create: { key: attemptKey, count, windowStart: now, lockedUntil: count >= 5 ? new Date(now.getTime() + 15 * 60 * 1000) : null },
      update: { count, windowStart: now, lockedUntil: count >= 5 ? new Date(now.getTime() + 15 * 60 * 1000) : null },
    });
    return jsonError("Configuration initiale indisponible.", 403);
  }
  try {
    const userId = await prisma.$transaction(async (tx) => {
      await tx.$executeRaw(Prisma.sql`SELECT pg_advisory_xact_lock(742006110)`);
      const state = await tx.activityLog.count({ where: { action: "administrator_bootstrapped" } });
      const admins = await tx.user.count({ where: { roles: { some: { roleCode: "administrator" } } } });
      if (state || admins) throw new Error("SETUP_CLOSED");
      const user = await tx.user.create({
        data: { displayName, username: username || null, email: email || null, phoneE164: phone || null, passwordHash: await makePasswordHash(password), roles: { create: [{ roleCode: "administrator" }, { roleCode: "super_administrator" }] } },
      });
      await tx.activityLog.create({ data: { userId: user.id, action: "administrator_bootstrapped", description: "Compte administrateur initial créé." } });
      await tx.loginAttempt.deleteMany({ where: { key: attemptKey } });
      return user.id;
    });
    await createSession(userId, "super_administrator");
    return Response.json({ success: true }, { status: 201 });
  } catch (error) {
    if (error instanceof Error && error.message === "SETUP_CLOSED") return jsonError("La configuration initiale n’est plus disponible.", 409);
    if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === "P2002") return jsonError("Cette adresse ou ce numéro est déjà associé à un compte.", 409);
    console.error("Administrator bootstrap failed", error);
    return jsonError("La création du compte a échoué.", 500);
  }
}

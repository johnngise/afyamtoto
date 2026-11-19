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
  const providedSecret = typeof data.setupSecret === "string" ? data.setupSecret.trim().replace(/^['"]|['"]$/g, "") : "";
  const configuredSecret = process.env.AFYA_ADMIN_RECOVERY_SECRET ?? "";

  if (configuredSecret.length < 32 || configuredSecret.length > 256) return jsonError("La récupération administrateur n’est pas activée.", 404);
  if (!displayName || Array.from(displayName).length > 160) return jsonError("Nom invalide.", 400);
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(email) || email.length > 254) return jsonError("Adresse e-mail invalide.", 400);
  if (username && !/^[a-z0-9._-]{3,64}$/u.test(username)) return jsonError("Nom d’utilisateur invalide.", 400);
  if (phone && !/^\+[1-9][0-9]{1,14}$/u.test(phone)) return jsonError("Numéro de téléphone invalide.", 400);
  if (!isStrongPassword(password)) return jsonError("Mot de passe trop faible.", 400);

  const ip = request.headers.get("x-real-ip") ?? "unknown";
  const attemptKey = createHash("sha256").update(`admin-recovery\0${ip}`).digest("hex");
  const now = new Date();
  const attempt = await prisma.loginAttempt.findUnique({ where: { key: attemptKey } });
  if (attempt?.lockedUntil && attempt.lockedUntil > now) return jsonError("La récupération est temporairement bloquée.", 429);
  const suppliedHash = createHash("sha256").update(providedSecret).digest();
  const expectedHash = createHash("sha256").update(configuredSecret).digest();
  if (!timingSafeEqual(suppliedHash, expectedHash)) {
    const inWindow = attempt && now.getTime() - attempt.windowStart.getTime() <= 15 * 60 * 1000;
    const count = inWindow ? attempt.count + 1 : 1;
    await prisma.loginAttempt.upsert({
      where: { key: attemptKey },
      create: { key: attemptKey, count, windowStart: now, lockedUntil: count >= 5 ? new Date(now.getTime() + 15 * 60 * 1000) : null },
      update: { count, windowStart: inWindow ? attempt.windowStart : now, lockedUntil: count >= 5 ? new Date(now.getTime() + 15 * 60 * 1000) : null },
    });
    return jsonError("Code de récupération incorrect. Vérifie AFYA_ADMIN_RECOVERY_SECRET dans frontend/.env.local.", 403);
  }

  try {
    const userId = await prisma.$transaction(async (tx) => {
      await tx.$executeRaw(Prisma.sql`SELECT pg_advisory_xact_lock(742006110)`);
      const [admins, recoveryMarker] = await Promise.all([
        tx.user.findMany({ where: { roles: { some: { roleCode: "administrator" } } }, select: { id: true, username: true } }),
        tx.activityLog.count({ where: { action: "administrator_credentials_recovered" } }),
      ]);
      if (admins.length !== 1 || recoveryMarker > 0) throw new Error("RECOVERY_CLOSED");
      const adminId = admins[0].id;
      await tx.user.update({
        where: { id: adminId },
        data: {
          displayName, username: username || admins[0].username, email, phoneE164: phone || null,
          passwordHash: await makePasswordHash(password), isActive: true,
        },
      });
      await tx.session.deleteMany({ where: { userId: adminId } });
      await tx.activityLog.create({ data: { userId: adminId, action: "administrator_credentials_recovered", description: "Identifiants du compte administrateur récupérés avec le secret local à usage unique." } });
      await tx.loginAttempt.deleteMany({ where: { key: attemptKey } });
      return adminId;
    });
    await createSession(userId, "super_administrator");
    return Response.json({ success: true }, { status: 200 });
  } catch (error) {
    if (error instanceof Error && error.message === "RECOVERY_CLOSED") return jsonError("La récupération n’est plus disponible.", 409);
    if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === "P2002") return jsonError("Cette adresse ou ce nom d’utilisateur est déjà associé à un compte.", 409);
    console.error("Administrator credential recovery failed", error);
    return jsonError("La récupération des identifiants a échoué.", 500);
  }
}

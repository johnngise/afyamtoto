import { createHash } from "node:crypto";
import { isStrongPassword, jsonError, makePasswordHash } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const runtime = "nodejs";
const digest = (value: string) => createHash("sha256").update(value).digest("hex");

export async function POST(request: Request) {
  const origin = request.headers.get("origin");
  if (!origin || origin !== new URL(request.url).origin) return jsonError("Origine de la requête invalide.", 403);
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const token = typeof data.token === "string" ? data.token : "";
  const password = typeof data.password === "string" ? data.password : "";
  if (!/^[a-f0-9]{64}$/u.test(token)) return jsonError("Ce lien est invalide ou expiré. Demandez-en un nouveau.", 400);
  if (!isStrongPassword(password)) return jsonError("Utilisez au moins 12 caractères et trois types de caractères, ou une phrase de passe de 20 caractères minimum (72 octets maximum).", 400);

  try {
    const tokenHash = digest(token);
    const reset = await prisma.passwordResetToken.findFirst({ where: { tokenHash, usedAt: null, expiresAt: { gt: new Date() }, user: { isActive: true, roles: { some: { roleCode: "parent" } } } }, select: { id: true, userId: true } });
    if (!reset) return jsonError("Ce lien est invalide ou expiré. Demandez-en un nouveau.", 400);
    const now = new Date();
    const passwordHash = await makePasswordHash(password);
    const consumed = await prisma.$transaction(async (tx) => {
      const result = await tx.passwordResetToken.updateMany({ where: { id: reset.id, usedAt: null, expiresAt: { gt: now } }, data: { usedAt: now } });
      if (result.count !== 1) return false;
      await tx.user.update({ where: { id: reset.userId }, data: { passwordHash } });
      await tx.session.deleteMany({ where: { userId: reset.userId } });
      await tx.passwordResetToken.updateMany({ where: { userId: reset.userId, id: { not: reset.id }, usedAt: null }, data: { usedAt: now } });
      await tx.activityLog.create({ data: { userId: reset.userId, action: "password_reset", description: "Mot de passe réinitialisé." } });
      return true;
    });
    if (!consumed) return jsonError("Ce lien a déjà été utilisé. Demandez-en un nouveau.", 400);
    return Response.json({ success: true });
  } catch (error) {
    console.error("Password reset failed", error);
    return jsonError("La modification du mot de passe a échoué.", 503);
  }
}

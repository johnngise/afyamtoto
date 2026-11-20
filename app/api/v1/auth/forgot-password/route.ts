import { createHash, randomBytes } from "node:crypto";
import { headers } from "next/headers";
import { prisma } from "@/lib/db";
import { jsonError } from "@/lib/auth";

export const runtime = "nodejs";
const genericMessage = "Si un compte parent correspond à cette adresse, un lien de réinitialisation sera envoyé.";
const WINDOW_MS = 60 * 60 * 1000;
const LIMIT = 3;
const digest = (value: string) => createHash("sha256").update(value).digest("hex");

function sameOrigin(request: Request) {
  const origin = request.headers.get("origin");
  return Boolean(origin && origin === new URL(request.url).origin);
}

export async function POST(request: Request) {
  if (!sameOrigin(request)) return jsonError("Origine de la requête invalide.", 403);
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const email = typeof (body as Record<string, unknown>).email === "string" ? String((body as Record<string, unknown>).email).trim().toLowerCase() : "";
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(email) || email.length > 254) return jsonError("Adresse e-mail invalide.", 400);

  const apiKey = process.env.RESEND_API_KEY;
  const from = process.env.AUTH_EMAIL_FROM;
  const appUrl = process.env.APP_URL;
  if (!apiKey || !from || !appUrl) return jsonError("Le service d’envoi des e-mails n’est pas configuré.", 503);

  try {
    const requestHeaders = await headers();
    const ip = requestHeaders.get("x-real-ip") ?? "unknown";
    const throttleKey = digest(`password-reset\0${email}\0${ip}`);
    const now = new Date();
    const attempt = await prisma.loginAttempt.findUnique({ where: { key: throttleKey } });
    const inWindow = Boolean(attempt && now.getTime() - attempt.windowStart.getTime() <= WINDOW_MS);
    if (inWindow && attempt!.count >= LIMIT) return Response.json({ message: genericMessage });
    await prisma.loginAttempt.upsert({
      where: { key: throttleKey },
      create: { key: throttleKey, count: 1, windowStart: now },
      update: { count: inWindow ? attempt!.count + 1 : 1, windowStart: inWindow ? attempt!.windowStart : now, lockedUntil: null },
    });

    const user = await prisma.user.findFirst({ where: { email, isActive: true, roles: { some: { roleCode: "parent" } } }, select: { id: true, email: true } });
    if (!user?.email) return Response.json({ message: genericMessage });

    const token = randomBytes(32).toString("hex");
    const expiresAt = new Date(now.getTime() + 30 * 60 * 1000);
    await prisma.passwordResetToken.create({ data: { userId: user.id, tokenHash: digest(token), expiresAt } });
    const baseUrl = appUrl.replace(/\/$/u, "");
    const resetUrl = `${baseUrl}/reset-password?token=${token}`;
    const emailResponse = await fetch("https://api.resend.com/emails", {
      method: "POST", headers: { Authorization: `Bearer ${apiKey}`, "Content-Type": "application/json" },
      body: JSON.stringify({ from, to: [user.email], subject: "Réinitialisation de votre mot de passe AFYAMTOTO AI", text: `Pour définir un nouveau mot de passe (lien valide 30 minutes), ouvrez : ${resetUrl}\n\nSi vous n’avez pas demandé cette réinitialisation, ignorez ce message.` }),
      signal: AbortSignal.timeout(10000),
    });
    if (!emailResponse.ok) {
      await prisma.passwordResetToken.deleteMany({ where: { tokenHash: digest(token), usedAt: null } });
      console.error("Password reset email delivery failed", { status: emailResponse.status });
      return Response.json({ message: genericMessage });
    }
    return Response.json({ message: genericMessage });
  } catch (error) {
    console.error("Password reset request failed", error);
    return jsonError("Le service de réinitialisation est momentanément indisponible.", 503);
  }
}

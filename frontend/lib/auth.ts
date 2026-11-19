import { createHash, randomBytes } from "node:crypto";
import { cookies, headers } from "next/headers";
import { compare, hash } from "bcryptjs";
import { prisma } from "@/lib/db";

export const SESSION_COOKIE = "afyamtoto_session";
const IDLE_TIMEOUT_MS = 30 * 60 * 1000;
const SESSION_LIFETIME_MS = 8 * 60 * 60 * 1000;
const REMEMBER_ME_LIFETIME_MS = 30 * 24 * 60 * 60 * 1000;
const LOGIN_WINDOW_MS = 15 * 60 * 1000;
const LOGIN_LIMIT = 5;

export type PublicUser = {
  id: string;
  displayName: string;
  email: string | null;
  phoneE164: string | null;
  roleCode: string;
  roleCodes: string[];
};

export function isStrongPassword(password: string): boolean {
  const chars = Array.from(password);
  if (chars.length < 12 || Buffer.byteLength(password, "utf8") > 72) return false;
  if (chars.length >= 20) return new Set(chars.filter((c) => !/\s/u.test(c))).size >= 8;
  const groups = [/[a-z]/u, /[A-Z]/u, /\p{N}/u, /[^\p{L}\p{N}\s]/u];
  return groups.filter((group) => group.test(password)).length >= 3;
}

export async function makePasswordHash(password: string) {
  return hash(password, 12);
}

function digest(value: string) {
  return createHash("sha256").update(value).digest("hex");
}

function cleanUser(user: { id: bigint; displayName: string; email: string | null; phoneE164: string | null; roles: { roleCode: string }[] }, roleCode?: string): PublicUser {
  return {
    id: user.id.toString(),
    displayName: user.displayName,
    email: user.email,
    phoneE164: user.phoneE164,
    roleCode: roleCode ?? user.roles[0]?.roleCode ?? "",
    roleCodes: user.roles.map((role) => role.roleCode),
  };
}

export async function createSession(userId: bigint, roleCode: string, rememberMe = false) {
  const account = await prisma.user.findFirst({
    where: { id: userId, isActive: true, roles: { some: { roleCode } } },
    select: { id: true },
  });
  if (!account) throw new Error("Cannot create a session for an unassigned role.");
  const token = randomBytes(32).toString("hex");
  const now = new Date();
  const lifetime = rememberMe ? REMEMBER_ME_LIFETIME_MS : SESSION_LIFETIME_MS;
  await prisma.session.create({
    data: { id: digest(token), userId, roleCode, expiresAt: new Date(now.getTime() + lifetime), lastSeen: now },
  });
  const cookieStore = await cookies();
  cookieStore.set(SESSION_COOKIE, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    expires: new Date(now.getTime() + lifetime),
  });
}

export async function getCurrentUser(): Promise<PublicUser | null> {
  const cookieStore = await cookies();
  const token = cookieStore.get(SESSION_COOKIE)?.value;
  if (!token || !/^[a-f0-9]{64}$/u.test(token)) return null;
  const session = await prisma.session.findUnique({
    where: { id: digest(token) },
    include: { user: { include: { roles: true } } },
  });
  const now = new Date();
  if (!session || !session.user.isActive || !session.user.roles.some((role) => role.roleCode === session.roleCode) || session.expiresAt <= now || now.getTime() - session.lastSeen.getTime() > IDLE_TIMEOUT_MS) {
    if (session) await prisma.session.delete({ where: { id: session.id } }).catch(() => undefined);
    return null;
  }
  await prisma.session.update({ where: { id: session.id }, data: { lastSeen: now } });
  return cleanUser(session.user, session.roleCode);
}

export async function destroySession() {
  const cookieStore = await cookies();
  const token = cookieStore.get(SESSION_COOKIE)?.value;
  if (token && /^[a-f0-9]{64}$/u.test(token)) {
    await prisma.session.deleteMany({ where: { id: digest(token) } });
  }
  cookieStore.delete(SESSION_COOKIE);
}

async function attemptKey(identifier: string) {
  const requestHeaders = await headers();
  const ip = requestHeaders.get("x-real-ip") ?? "unknown";
  return digest(`${identifier.toLowerCase()}\0${ip}`);
}

export async function authenticate(identifierValue: string, password: string, allowedRoles: string[]) {
  const identifier = identifierValue.trim().toLowerCase();
  if (!identifier || identifier.length > 254 || !password || password.length > 1024) return null;
  const key = await attemptKey(identifier);
  const now = new Date();
  const attempt = await prisma.loginAttempt.findUnique({ where: { key } });
  if (attempt?.lockedUntil && attempt.lockedUntil > now) return null;
  const user = await prisma.user.findFirst({
    where: { OR: [{ email: identifier }, { phoneE164: identifier }, { username: identifier }], isActive: true },
    include: { roles: true },
  });
  const validRole = allowedRoles.map((role) => user?.roles.find((entry) => entry.roleCode === role)).find(Boolean);
  const validPassword = user ? await compare(password, user.passwordHash) : false;
  if (!user || !validPassword || !validRole) {
    const inWindow = attempt && now.getTime() - attempt.windowStart.getTime() <= LOGIN_WINDOW_MS;
    const count = inWindow ? attempt.count + 1 : 1;
    await prisma.loginAttempt.upsert({
      where: { key },
      create: { key, count, windowStart: now, lockedUntil: count >= LOGIN_LIMIT ? new Date(now.getTime() + LOGIN_WINDOW_MS) : null },
      update: { count, windowStart: inWindow ? attempt.windowStart : now, lockedUntil: count >= LOGIN_LIMIT ? new Date(now.getTime() + LOGIN_WINDOW_MS) : null },
    });
    return null;
  }
  await prisma.loginAttempt.deleteMany({ where: { key } });
  await prisma.activityLog.create({ data: { userId: user.id, action: "user_login", description: "Connexion réussie." } });
  return { user: cleanUser(user, validRole.roleCode), id: user.id };
}

export function jsonError(message: string, status: number) {
  return Response.json({ error: message }, { status });
}

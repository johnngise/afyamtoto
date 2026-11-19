import { getCurrentUser, jsonError } from "@/lib/auth";

export const runtime = "nodejs";

export async function GET() {
  try {
    const user = await getCurrentUser();
    return user ? Response.json({ user }) : jsonError("Authentification requise.", 401);
  } catch (error) {
    console.error("Session lookup failed", error);
    return jsonError("Le service de session est momentanément indisponible.", 503);
  }
}

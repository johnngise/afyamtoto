import { authenticate, createSession, jsonError } from "@/lib/auth";

export const runtime = "nodejs";

export async function POST(request: Request) {
  let body: unknown;
  try { body = await request.json(); } catch { return jsonError("Corps JSON invalide.", 400); }
  if (!body || typeof body !== "object") return jsonError("Données invalides.", 400);
  const data = body as Record<string, unknown>;
  const identifier = typeof data.identifier === "string" ? data.identifier : "";
  const password = typeof data.password === "string" ? data.password : "";
  const scope = data.scope;
  const rememberMe = data.rememberMe === true;
  const roles = scope === "administrator" ? ["super_administrator", "administrator"] : scope === "agent"
    ? ["health_professional", "community_health_worker", "health_facility_administrator", "health_center_manager", "health_zone_administrator", "health_supervisor", "provincial_administrator", "national_administrator", "health_data_analyst"]
    : ["super_administrator", "administrator", "national_administrator", "provincial_administrator", "health_zone_administrator", "health_facility_administrator", "health_professional", "community_health_worker", "health_center_manager", "health_supervisor", "health_data_analyst", "parent"];
  try {
    const result = await authenticate(identifier, password, roles);
    if (!result) return jsonError("Identifiant ou mot de passe incorrect.", 401);
    await createSession(result.id, result.user.roleCode, rememberMe);
    return Response.json({ user: result.user });
  } catch (error) {
    console.error("Login failed", error);
    return jsonError("Le service de connexion est momentanément indisponible.", 503);
  }
}

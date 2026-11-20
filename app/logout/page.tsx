"use client";

import { useRouter } from "next/navigation";

export default function LogoutPage() {
  const router = useRouter();
  async function logout() {
    await fetch("/api/v1/auth/logout", { method: "POST" });
    router.replace("/login?scope=public");
    router.refresh();
  }
  return <main className="auth-page"><section className="auth-card"><h1>Déconnexion</h1><p className="auth-intro">Terminez la session de ce compte sur cet appareil.</p><button className="button button-primary auth-submit" onClick={logout} type="button">Confirmer la déconnexion</button></section></main>;
}

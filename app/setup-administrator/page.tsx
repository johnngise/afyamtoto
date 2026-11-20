import Link from "next/link";
import { prisma } from "@/lib/db";
import { AuthForm } from "@/app/components/auth-form";

export const dynamic = "force-dynamic";

export default async function SetupAdministratorPage() {
  const [admins, setupMarker, recoveryMarker] = await Promise.all([
    prisma.user.count({ where: { roles: { some: { roleCode: "administrator" } } } }),
    prisma.activityLog.count({ where: { action: "administrator_bootstrapped" } }),
    prisma.activityLog.count({ where: { action: "administrator_credentials_recovered" } }),
  ]);
  const setupAvailable = admins === 0 && setupMarker === 0 && (process.env.AFYA_ADMIN_SETUP_SECRET?.length ?? 0) >= 32;
  const recoveryAvailable = admins === 1 && recoveryMarker === 0 && (process.env.AFYA_ADMIN_RECOVERY_SECRET?.length ?? 0) >= 32;
  const mode = setupAvailable ? "setup" : recoveryAvailable ? "recover" : null;

  return (
    <main className="auth-page">
      <section className="auth-card" aria-labelledby="setup-title">
        <Link className="auth-brand" href="/">AFYAMTOTO <span>AI</span></Link>
        <p className="section-kicker">Accès administrateur</p>
        <h1 id="setup-title">{mode === "setup" ? "Créer le premier compte" : mode === "recover" ? "Récupérer l’accès administrateur" : "Initialisation indisponible"}</h1>
        {mode === "setup" ? <>
          <p className="auth-intro">Définis le nom, l’adresse e-mail et le mot de passe du premier administrateur. Cette initialisation se ferme après sa création.</p>
          <AuthForm mode="setup" />
        </> : mode === "recover" ? <>
          <p className="auth-intro">Un compte administrateur existe déjà. Copie le code AFYA_ADMIN_RECOVERY_SECRET depuis frontend/.env.local, puis définis ton nom, ton e-mail et ton nouveau mot de passe. Aucun autre compte ne sera créé.</p>
          <AuthForm mode="recover" />
        </> : <>
          <p className="auth-intro">Aucune initialisation ou récupération n’est actuellement autorisée. Connecte-toi à l’espace administrateur ou demande au responsable du serveur de configurer le mécanisme local.</p>
          <Link className="button button-primary" href="/login">Aller à la connexion</Link>
        </>}
        <Link className="auth-back" href="/">Retour à l’accueil</Link>
      </section>
    </main>
  );
}

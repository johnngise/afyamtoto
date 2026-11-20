import Link from "next/link";
import { AuthForm } from "@/app/components/auth-form";

export default async function LoginPage({ searchParams }: { searchParams: Promise<{ scope?: string }> }) {
  const adminOnly = (await searchParams).scope !== "public";
  return (
    <main className="auth-page" role="main" aria-label="Connexion">
      <div className="auth-layout">
        <div className="auth-branding" aria-hidden="true">
          <div className="auth-brand">AFYAMTOTO <span>AI</span></div>
          <div className="auth-brand-mark">A</div>
          <div className="auth-illustration">
            <strong>Bienvenue sur AFYAMTOTO AI</strong>
            <p>L&rsquo;espace s&eacute;curis&eacute; pour le suivi de sant&eacute; des enfants de 0 &agrave; 5 ans.</p>
            <p>Connectez-vous pour acc&eacute;der &agrave; votre tableau de bord personnalis&eacute; selon votre r&ocirc;le.</p>
          </div>
        </div>
        <div className="auth-form-panel">
          <div className="auth-card" aria-labelledby="login-title">
            <Link className="auth-brand" href="/" aria-label="Accueil AFYAMTOTO AI">AFYAMTOTO <span>AI</span></Link>
            <p className="section-kicker">Votre espace s&eacute;curis&eacute;</p>
            <h1 id="login-title">{adminOnly ? "Connexion administrateur" : "Connexion"}</h1>
            <p className="auth-intro">{adminOnly ? "Saisissez l&rsquo;adresse e-mail ou le nom d&rsquo;utilisateur de votre compte administrateur." : "Connectez-vous avec l&rsquo;adresse e-mail ou le num&eacute;ro associ&eacute; au compte."}</p>
            <AuthForm mode="login" adminOnly={adminOnly} />
            {!adminOnly && <p className="auth-switch"><Link href="/forgot-password">Mot de passe oubli&eacute; ?</Link></p>}
            {!adminOnly && <p className="auth-switch">Pas encore de compte parent ? <Link href="/register">Cr&eacute;er un compte</Link></p>}
            <Link className="auth-back" href="/">Retour &agrave; l&rsquo;accueil</Link>
          </div>
        </div>
      </div>
    </main>
  );
}

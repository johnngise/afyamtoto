import Link from "next/link";
import { ForgotPasswordForm } from "@/app/components/password-reset-form";

export default function ForgotPasswordPage() {
  return <main className="auth-page"><section className="auth-card" aria-labelledby="forgot-title">
    <Link className="auth-brand" href="/">AFYAMTOTO <span>AI</span></Link>
    <p className="section-kicker">Récupération du compte</p>
    <h1 id="forgot-title">Mot de passe oublié ?</h1>
    <p className="auth-intro">Saisissez l’adresse e-mail de votre compte parent. Si elle correspond à un compte, vous recevrez un lien temporaire.</p>
    <ForgotPasswordForm />
    <p className="auth-switch"><Link href="/login?scope=public">Retour à la connexion</Link></p>
  </section></main>;
}

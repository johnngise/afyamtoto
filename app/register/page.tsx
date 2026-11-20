import Link from "next/link";
import { AuthForm } from "@/app/components/auth-form";

export default function RegisterPage() {
  return (
    <main className="auth-page">
      <section className="auth-card" aria-labelledby="register-title">
        <Link className="auth-brand" href="/">AFYAMTOTO <span>AI</span></Link>
        <p className="section-kicker">Espace famille</p>
        <h1 id="register-title">Créer un compte parent</h1>
        <p className="auth-intro">L’inscription publique est réservée aux parents et tuteurs.</p>
        <AuthForm mode="register" />
        <p className="auth-switch">Vous avez déjà un compte ? <Link href="/login?scope=public">Se connecter</Link></p>
      </section>
    </main>
  );
}

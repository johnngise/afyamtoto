import Link from "next/link";
import { ResetPasswordForm } from "@/app/components/password-reset-form";

export default async function ResetPasswordPage({ searchParams }: { searchParams: Promise<{ token?: string }> }) {
  const token = (await searchParams).token ?? "";
  const validTokenShape = /^[a-f0-9]{64}$/u.test(token);
  return <main className="auth-page"><section className="auth-card" aria-labelledby="reset-title">
    <Link className="auth-brand" href="/">AFYAMTOTO <span>AI</span></Link>
    <p className="section-kicker">Sécurité du compte</p>
    <h1 id="reset-title">Créer un nouveau mot de passe</h1>
    <p className="auth-intro">Choisissez un mot de passe fort pour protéger votre compte.</p>
    {validTokenShape ? <ResetPasswordForm token={token} /> : <p className="form-error" role="alert">Ce lien est invalide ou incomplet. Demandez un nouveau lien.</p>}
    <p className="auth-switch"><Link href="/forgot-password">Demander un nouveau lien</Link></p>
  </section></main>;
}

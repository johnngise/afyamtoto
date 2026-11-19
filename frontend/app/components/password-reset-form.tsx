"use client";

import { FormEvent, useState } from "react";

export function ForgotPasswordForm() {
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true); setMessage(""); setError("");
    const formElement = event.currentTarget;
    const form = new FormData(formElement);
    try {
      const response = await fetch("/api/v1/auth/forgot-password", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: form.get("email") }),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "La demande a échoué.");
      setMessage(result.message);
      formElement.reset();
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Une erreur est survenue.");
    } finally { setBusy(false); }
  }

  return <form className="auth-form" onSubmit={submit}>
    <label>Adresse e-mail<input autoComplete="email" maxLength={254} name="email" required type="email" /></label>
    {message && <p className="form-success" role="status">{message}</p>}
    {error && <p className="form-error" role="alert">{error}</p>}
    <button className="button button-primary auth-submit" disabled={busy} type="submit">{busy ? "Envoi en cours…" : "Recevoir un lien de réinitialisation"}</button>
  </form>;
}

export function ResetPasswordForm({ token }: { token: string }) {
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setMessage(""); setError("");
    const form = new FormData(event.currentTarget);
    const password = String(form.get("password") ?? "");
    if (password !== form.get("confirmation")) { setError("Les mots de passe ne correspondent pas."); setBusy(false); return; }
    try {
      const response = await fetch("/api/v1/auth/reset-password", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token, password }),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "La demande a échoué.");
      setMessage("Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.");
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Une erreur est survenue.");
    } finally { setBusy(false); }
  }

  return <form className="auth-form" onSubmit={submit}>
    <label>Nouveau mot de passe<input autoComplete="new-password" maxLength={72} minLength={12} name="password" required type="password" /></label>
    <label>Confirmer le mot de passe<input autoComplete="new-password" maxLength={72} minLength={12} name="confirmation" required type="password" /></label>
    <p className="password-hint">12 caractères avec 3 types de caractères, ou une phrase de passe de 20 caractères minimum.</p>
    {message && <p className="form-success" role="status">{message}</p>}
    {error && <p className="form-error" role="alert">{error}</p>}
    <button className="button button-primary auth-submit" disabled={busy || Boolean(message)} type="submit">{busy ? "Modification en cours…" : "Modifier le mot de passe"}</button>
  </form>;
}

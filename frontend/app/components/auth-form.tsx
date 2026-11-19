"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

export function AuthForm({ mode, adminOnly = false }: { mode: "login" | "register" | "setup" | "recover"; adminOnly?: boolean }) {
  const router = useRouter();
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setBusy(true);
    const values = Object.fromEntries(new FormData(event.currentTarget).entries());
    try {
      if (mode === "login" && !values.password) throw new Error("Mot de passe requis.");
      const endpoint = mode === "login" ? "/api/v1/auth/login" : mode === "setup" ? "/api/v1/auth/setup-administrator" : mode === "recover" ? "/api/v1/auth/recover-administrator" : "/api/v1/auth/register";
      const response = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(mode === "login" ? { identifier: values.identifier, password: values.password, ...(adminOnly ? { scope: "administrator" } : {}), rememberMe } : {
          displayName: values.displayName,
          email: values.email,
          phoneE164: values.phoneE164,
          password: values.password,
          ...((mode === "setup" || mode === "recover") ? { setupSecret: values.setupSecret, username: values.username } : {}),
        }),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "La demande a échoué.");
      router.push(mode === "register" || (mode === "login" && !adminOnly) ? "/dashboard" : "/admin");
      router.refresh();
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Une erreur est survenue.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <form className="auth-form" onSubmit={submit}>
      {mode !== "login" ? <>
        <label>Nom complet<input autoComplete="name" maxLength={160} name="displayName" required /></label>
        <label>Adresse e-mail<input autoComplete="email" maxLength={254} name="email" required={mode === "setup" || mode === "recover"} type="email" /></label>
        <label>Téléphone au format international<input autoComplete="tel" maxLength={16} name="phoneE164" placeholder="+243XXXXXXXXX" /></label>
        {(mode === "setup" || mode === "recover") && <label>Nom d’utilisateur (facultatif)<input autoComplete="username" maxLength={64} name="username" pattern="[a-zA-Z0-9._-]{3,64}" /></label>}
        {(mode === "setup" || mode === "recover") && <label>{mode === "recover" ? "Code de récupération du serveur" : "Code secret temporaire"}<input autoComplete="off" name="setupSecret" required type="password" /></label>}
      </> : <label>{adminOnly ? "Adresse e-mail ou nom d\u2019utilisateur" : "E-mail ou t\u00e9l\u00e9phone"}<input autoComplete="username" maxLength={254} name="identifier" required /></label>}
      <label>Mot de passe<span className="password-control"><input autoComplete={mode === "login" ? "current-password" : "new-password"} maxLength={72} minLength={12} name="password" required type={showPassword ? "text" : "password"} /><button aria-label={showPassword ? "Masquer le mot de passe" : "Afficher le mot de passe"} className="password-toggle" onClick={() => setShowPassword((value) => !value)} type="button">{showPassword ? "Masquer" : "Afficher"}</button></span></label>
      {mode === "login" && <label className="remember-me"><input type="checkbox" checked={rememberMe} onChange={(e) => setRememberMe(e.target.checked)} /><span>Se souvenir de moi</span></label>}
      {mode !== "login" && <label>Confirmer le mot de passe<input autoComplete="new-password" maxLength={72} minLength={12} name="passwordConfirmation" required type="password" /></label>}
      {mode === "register" && <p className="password-hint">12 caractères avec 3 types de caractères, ou une phrase de passe de 20 caractères minimum.</p>}
      {error && <p className="form-error" role="alert">{error}</p>}
      <button className="button button-primary auth-submit" disabled={busy} type="submit">{busy && <span aria-hidden="true" className="loading-spinner" />}{busy ? "Enregistrement en cours\u2026" : mode === "login" ? "Se connecter" : mode === "setup" ? "Cr\u00e9er l?administrateur" : mode === "recover" ? "R\u00e9cup\u00e9rer le compte administrateur" : "Cr\u00e9er mon compte"}</button>
    </form>
  );
}

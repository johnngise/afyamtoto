"use client";

import { FormEvent, useEffect, useState } from "react";

type UserRow = { id: string; displayName: string; username: string | null; email: string | null; phoneE164: string | null; isActive: boolean; roles: string[]; createdAt: string; healthCenterIds?: string[]; specialtyCode?: string | null; assignments?: { scopeType: string; provinceId: string | null; healthZoneId: string | null; healthCenterId: string | null }[] };
type GeographicOption = { id: string; label: string };
const roles = ["super_administrator", "national_administrator", "provincial_administrator", "health_zone_administrator", "health_facility_administrator", "health_professional", "community_health_worker", "parent", "health_data_analyst"];
const editableRoles = roles;
const roleLabels: Record<string, string> = {
  super_administrator: "Super administrateur", national_administrator: "Administrateur national",
  provincial_administrator: "Administrateur provincial", health_zone_administrator: "Administrateur de zone de santé",
  health_facility_administrator: "Administrateur d’établissement", health_professional: "Professionnel de santé",
  community_health_worker: "Agent de santé communautaire", parent: "Parent ou responsable légal",
  health_data_analyst: "Analyste de données sanitaires",
};

function formRole(user: UserRow) {
  return user.roles.includes("super_administrator") ? "super_administrator" : user.roles.find((role) => role !== "administrator") ?? "health_professional";
}

function selectedIds(form: FormData, key: string) {
  return form.getAll(key).filter((value): value is string => typeof value === "string");
}

function ScopeOptions({ name, options, selected = [] }: { name: string; options: GeographicOption[]; selected?: string[] }) {
  return <fieldset className="admin-scope-picker"><legend>{name === "provinceIds" ? "Provinces" : name === "healthZoneIds" ? "Zones de santé" : "Centres de santé"}</legend>
    {options.length ? <div className="admin-scope-options">{options.map((option) => <label key={option.id}><input type={name === "provinceIds" ? "radio" : "checkbox"} name={name} value={option.id} defaultChecked={selected.includes(option.id)} /><span>{option.label}</span><small>ID {option.id}</small></label>)}</div> : <p className="admin-scope-empty">Aucune option enregistrée pour le moment.</p>}
  </fieldset>;
}

export function AdminUsers({ geographicOptions }: { geographicOptions: { provinces: GeographicOption[]; zones: GeographicOption[]; centers: GeographicOption[] } }) {
  const [users, setUsers] = useState<UserRow[]>([]);
  const [query, setQuery] = useState("");
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [editingUser, setEditingUser] = useState<UserRow | null>(null);

  async function loadUsers() {
    const response = await fetch("/api/v1/admin/users", { cache: "no-store" });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Impossible de charger les utilisateurs.");
    setUsers(result.users);
  }
  useEffect(() => { void loadUsers().catch((reason: unknown) => setError(reason instanceof Error ? reason.message : "Erreur de chargement.")); }, []);

  async function createUser(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError(""); setMessage("");
    const form = event.currentTarget;
    const formData = new FormData(form);
    const values = Object.fromEntries(formData.entries());
    try {
      const response = await fetch("/api/v1/admin/users", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...values, provinceIds: selectedIds(formData, "provinceIds"), healthZoneIds: selectedIds(formData, "healthZoneIds"), healthCenterIds: selectedIds(formData, "healthCenterIds") }) });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "Création impossible.");
      form.reset(); setMessage("Le compte a été créé."); await loadUsers();
    } catch (reason) { setError(reason instanceof Error ? reason.message : "Création impossible."); }
    finally { setBusy(false); }
  }

  async function toggleUser(user: UserRow) {
    setBusy(true); setError(""); setMessage("");
    try {
      const response = await fetch(`/api/v1/admin/users/${user.id}`, { method: "PATCH", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ isActive: !user.isActive }) });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "Modification impossible.");
      setMessage(user.isActive ? "Le compte a été désactivé." : "Le compte a été activé."); await loadUsers();
    } catch (reason) { setError(reason instanceof Error ? reason.message : "Modification impossible."); }
    finally { setBusy(false); }
  }

  async function updateUser(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!editingUser) return;
    setBusy(true); setError(""); setMessage("");
    const formData = new FormData(event.currentTarget);
    const values = Object.fromEntries(formData.entries());
    try {
      const hasScopeOptions = formData.has("scopeAssignmentsProvided");
      const scopePayload = hasScopeOptions ? { provinceIds: selectedIds(formData, "provinceIds"), healthZoneIds: selectedIds(formData, "healthZoneIds"), healthCenterIds: selectedIds(formData, "healthCenterIds") } : {};
      const response = await fetch(`/api/v1/admin/users/${editingUser.id}`, { method: "PATCH", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...values, ...scopePayload }) });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "Modification impossible.");
      setEditingUser(null); setMessage("Le compte a été mis à jour."); await loadUsers();
    } catch (reason) { setError(reason instanceof Error ? reason.message : "Modification impossible."); }
    finally { setBusy(false); }
  }

  const filtered = users.filter((user) => `${user.displayName} ${user.username ?? ""} ${user.email ?? ""} ${user.roles.join(" ")}`.toLowerCase().includes(query.toLowerCase()));
  return <>
    <div className="admin-page-heading"><div><p className="admin-eyebrow">ACCÈS ET ÉQUIPES</p><h1>Utilisateurs</h1><p>Consultez et administrez les comptes et les rôles de la plateforme.</p></div><span className="admin-count-chip">{users.length} comptes</span></div>
    {message && <p className="admin-success" role="status">{message}</p>}{error && <p className="admin-alert" role="alert">{error}</p>}
    <section className="admin-panel admin-form-panel"><div className="admin-panel-heading"><div><p className="admin-eyebrow">NOUVEAU COMPTE</p><h2>Ajouter un membre de l’équipe</h2></div></div>
      <form className="admin-form-grid" onSubmit={(event) => void createUser(event)}>
        <label>Nom complet<input name="displayName" maxLength={160} required /></label>
        <label>Nom d’utilisateur<input name="username" autoComplete="username" pattern="[a-zA-Z0-9._-]{3,64}" /></label>
        <label>E-mail<input name="email" type="email" maxLength={254} required /></label>
        <label>Téléphone (facultatif)<input name="phoneE164" placeholder="+243…" /></label>
        <label>Rôle<select name="roleCode" required>{roles.map((role) => <option key={role} value={role}>{roleLabels[role] ?? role}</option>)}</select></label>
        <div className="admin-scope-grid"><ScopeOptions name="provinceIds" options={geographicOptions.provinces} /><ScopeOptions name="healthZoneIds" options={geographicOptions.zones} /><ScopeOptions name="healthCenterIds" options={geographicOptions.centers} /></div>
        <label>Spécialité professionnelle<select name="specialtyCode"><option value="">À renseigner pour un professionnel</option><option value="physician">Médecin</option><option value="nurse">Infirmier ou infirmière</option><option value="midwife">Sage-femme</option><option value="nutritionist">Nutritionniste</option><option value="other">Autre</option></select></label>
        <label>Mot de passe temporaire<input name="password" type="password" autoComplete="new-password" minLength={12} required /></label>
        <div className="admin-form-actions"><small>12 caractères et 3 types de caractères, ou une phrase secrète de 20 caractères.</small><button className="admin-primary-button" disabled={busy} type="submit">{busy ? "Enregistrement…" : "Créer le compte"}</button></div>
      </form>
    </section>
    <section className="admin-panel admin-users-panel"><div className="admin-panel-heading"><div><p className="admin-eyebrow">ANNUAIRE</p><h2>Comptes existants</h2></div><input aria-label="Rechercher un utilisateur" className="admin-search" onChange={(event) => setQuery(event.target.value)} placeholder="Rechercher…" value={query} /></div>
      <div className="admin-table-wrap"><table className="admin-table"><thead><tr><th>Utilisateur</th><th>Identifiant</th><th>Rôle</th><th>Statut</th><th>Création</th><th>Action</th></tr></thead><tbody>
        {filtered.map((user) => <tr key={user.id}><td><strong>{user.displayName}</strong><small>{user.username ? `@${user.username}` : "Nom d’utilisateur non défini"}</small></td><td>{user.email ?? user.phoneE164 ?? "—"}</td><td><span className="admin-role-chip">{user.roles.map((role) => roleLabels[role] ?? role.replaceAll("_", " ")).join(", ") || "—"}</span></td><td><span className={`admin-status ${user.isActive ? "is-active" : "is-inactive"}`}>{user.isActive ? "Actif" : "Désactivé"}</span></td><td>{new Date(user.createdAt).toLocaleDateString("fr-FR")}</td><td><div className="admin-row-actions"><button className="admin-table-action" disabled={busy} onClick={() => setEditingUser(user)} type="button">Modifier</button><button className="admin-table-action" disabled={busy} onClick={() => void toggleUser(user)} type="button">{user.isActive ? "Désactiver" : "Activer"}</button></div></td></tr>)}
        {!filtered.length && <tr><td className="admin-empty-cell" colSpan={6}>Aucun compte ne correspond à cette recherche.</td></tr>}
      </tbody></table></div>
    </section>
    {editingUser && <div className="admin-modal-backdrop" onMouseDown={(event) => { if (event.target === event.currentTarget) setEditingUser(null); }}>
      <section aria-labelledby="edit-user-title" aria-modal="true" className="admin-modal" role="dialog">
        <div className="admin-panel-heading"><div><p className="admin-eyebrow">MODIFICATION DU COMPTE</p><h2 id="edit-user-title">Modifier l’utilisateur</h2></div><button aria-label="Fermer" className="admin-modal-close" onClick={() => setEditingUser(null)} type="button">×</button></div>
        <form className="admin-form-grid" onSubmit={(event) => void updateUser(event)}>
          <input type="hidden" name="scopeAssignmentsProvided" value="true" />
          <label>Nom complet<input defaultValue={editingUser.displayName} maxLength={160} name="displayName" required /></label>
          <label>Nom d’utilisateur<input autoComplete="username" defaultValue={editingUser.username ?? ""} maxLength={64} name="username" pattern="[a-zA-Z0-9._-]{3,64}" /></label>
          <label>Adresse e-mail<input defaultValue={editingUser.email ?? ""} maxLength={254} name="email" type="email" /></label>
          <label>Téléphone<input defaultValue={editingUser.phoneE164 ?? ""} maxLength={16} name="phoneE164" placeholder="+243…" /></label>
          <label>Rôle<select defaultValue={editingUser.roles[0] ?? "health_professional"} name="roleCode">{editableRoles.map((role) => <option key={role} value={role}>{roleLabels[role] ?? role}</option>)}</select></label>
          <div className="admin-scope-grid"><ScopeOptions name="provinceIds" options={geographicOptions.provinces} selected={(editingUser.assignments ?? []).filter((a) => a.scopeType === "province").map((a) => a.provinceId).filter((id): id is string => Boolean(id))} /><ScopeOptions name="healthZoneIds" options={geographicOptions.zones} selected={(editingUser.assignments ?? []).filter((a) => a.scopeType === "health_zone").map((a) => a.healthZoneId).filter((id): id is string => Boolean(id))} /><ScopeOptions name="healthCenterIds" options={geographicOptions.centers} selected={editingUser.healthCenterIds ?? []} /></div>
          <label>Spécialité professionnelle<select defaultValue={editingUser.specialtyCode ?? ""} name="specialtyCode"><option value="">À renseigner pour un professionnel</option><option value="physician">Médecin</option><option value="nurse">Infirmier ou infirmière</option><option value="midwife">Sage-femme</option><option value="nutritionist">Nutritionniste</option><option value="other">Autre</option></select></label>
          <label>Nouveau mot de passe (facultatif)<input autoComplete="new-password" minLength={12} name="password" type="password" /></label>
          <div className="admin-form-actions"><small>Laissez le mot de passe vide pour le conserver. Un changement de rôle renouvelle les sessions du compte.</small><div className="admin-row-actions"><button className="admin-table-action" onClick={() => setEditingUser(null)} type="button">Annuler</button><button className="admin-primary-button" disabled={busy} type="submit">Enregistrer</button></div></div>
        </form>
      </section>
    </div>}
  </>;
}


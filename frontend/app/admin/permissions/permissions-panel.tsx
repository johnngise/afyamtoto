"use client";

import { useEffect, useState } from "react";

type Matrix = { roles: { code: string; displayName: string }[]; permissions: { code: string; description: string }[]; grants: { roleCode: string; permissionCode: string }[] };

export function AdminPermissions() {
  const [matrix, setMatrix] = useState<Matrix | null>(null);
  const [roleCode, setRoleCode] = useState("");
  const [selected, setSelected] = useState<string[]>([]);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  async function load() {
    const response = await fetch("/api/v1/admin/permissions", { cache: "no-store" });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Chargement refusé.");
    setMatrix(result);
    const editable = result.roles.find((role: Matrix["roles"][number]) => role.code !== "super_administrator");
    if (editable) { setRoleCode(editable.code); setSelected(result.grants.filter((grant: Matrix["grants"][number]) => grant.roleCode === editable.code).map((grant: Matrix["grants"][number]) => grant.permissionCode)); }
  }
  useEffect(() => { void load().catch((error: unknown) => setMessage(error instanceof Error ? error.message : "Erreur.")); }, []);
  function chooseRole(value: string) {
    setRoleCode(value);
    setSelected(matrix?.grants.filter((grant) => grant.roleCode === value).map((grant) => grant.permissionCode) ?? []);
  }
  async function save() {
    setBusy(true); setMessage("");
    try {
      const response = await fetch("/api/v1/admin/permissions", { method: "PUT", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ roleCode, permissionCodes: selected }) });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "Enregistrement refusé.");
      setMessage("Matrice enregistrée et événement d’audit créé."); await load();
    } catch (error) { setMessage(error instanceof Error ? error.message : "Enregistrement refusé."); }
    finally { setBusy(false); }
  }
  if (!matrix) return <p className="admin-panel">Chargement des permissions… {message}</p>;
  return <>
    <div className="admin-page-heading"><div><p className="admin-eyebrow">SÉCURITÉ</p><h1>Rôles et permissions</h1><p>Les changements s’appliquent aux contrôles serveur. Les privilèges du Super Administrateur sont protégés.</p></div></div>
    {message && <p className="admin-success" role="status">{message}</p>}
    <section className="admin-panel admin-form-panel">
      <label>Rôle<select value={roleCode} onChange={(event) => chooseRole(event.target.value)}>{matrix.roles.filter((role) => role.code !== "super_administrator").map((role) => <option key={role.code} value={role.code}>{role.displayName} ({role.code})</option>)}</select></label>
      <div className="admin-table-wrap"><table className="admin-table"><thead><tr><th>Permission</th><th>Description</th><th>Autoriser</th></tr></thead><tbody>{matrix.permissions.filter((permission) => !["PERMISSION_MANAGE", "ROLE_MANAGE"].includes(permission.code)).map((permission) => <tr key={permission.code}><td><code>{permission.code}</code></td><td>{permission.description}</td><td><input aria-label={`Autoriser ${permission.code}`} checked={selected.includes(permission.code)} onChange={(event) => setSelected((current) => event.target.checked ? [...current, permission.code] : current.filter((code) => code !== permission.code))} type="checkbox" /></td></tr>)}</tbody></table></div>
      <button className="admin-primary-button" disabled={busy} onClick={() => void save()} type="button">{busy ? "Enregistrement…" : "Enregistrer les permissions"}</button>
    </section>
  </>;
}

"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

type Vaccine = { id: string; vaccineCode: string; displayName: string; description: string | null; isActive: boolean; doses: { id: string; doseCode: string; displayName: string; doseOrder: number }[] };
type Center = { id: string; name: string; city: string | null; province: string | null; phoneE164: string | null; addressText: string | null };

export function AdminContent({ vaccines, centers }: { vaccines: Vaccine[]; centers: Center[] }) {
  const router = useRouter();
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  async function submit(event: FormEvent<HTMLFormElement>, endpoint: string, transform: (data: Record<string, FormDataEntryValue>) => unknown) {
    event.preventDefault(); setBusy(true); setError(""); setMessage("");
    const form = event.currentTarget;
    const values = Object.fromEntries(new FormData(form).entries());
    try {
      const response = await fetch(endpoint, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(transform(values)) });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "Enregistrement impossible.");
      form.reset(); setMessage("Le contenu a été enregistré."); router.refresh();
    } catch (reason) { setError(reason instanceof Error ? reason.message : "Enregistrement impossible."); }
    finally { setBusy(false); }
  }

  async function toggleVaccine(vaccine: Vaccine) {
    setBusy(true); setError(""); setMessage("");
    try {
      const response = await fetch(`/api/v1/admin/vaccines/${vaccine.id}`, { method: "PATCH", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ isActive: !vaccine.isActive }) });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "Modification impossible.");
      setMessage(vaccine.isActive ? "La référence est désactivée." : "La référence est activée."); router.refresh();
    } catch (reason) { setError(reason instanceof Error ? reason.message : "Modification impossible."); }
    finally { setBusy(false); }
  }

  return <>
    <div className="admin-page-heading"><div><p className="admin-eyebrow">RÉFÉRENTIELS DE SANTÉ</p><h1>Contenus</h1><p>Gère les références vaccinales, les doses et les centres disponibles.</p></div><span className="admin-count-chip">{vaccines.length + centers.length} ressources</span></div>
    {message && <p className="admin-success" role="status">{message}</p>}{error && <p className="admin-alert" role="alert">{error}</p>}
    <section className="admin-panel admin-form-panel"><div className="admin-panel-heading"><div><p className="admin-eyebrow">RÉFÉRENCE VACCINALE</p><h2>Ajouter un vaccin et ses doses</h2></div></div>
      <form className="admin-form-grid" onSubmit={(event) => void submit(event, "/api/v1/admin/vaccines", (values) => ({ vaccineCode: values.vaccineCode, displayName: values.displayName, description: values.description, doses: [{ doseCode: values.doseCode, displayName: values.doseName, doseOrder: 1 }] }))}>
        <label>Code du vaccin<input name="vaccineCode" pattern="[A-Za-z0-9_-]{1,64}" title="Lettres sans accent, chiffres, tirets ou _ ; aucun espace. Exemple : PENTA_1." required /><small>Sans espaces ni accents : BCG, PENTA_1.</small></label><label>Nom affiché<input name="displayName" maxLength={160} required /></label>
        <label>Description<input name="description" maxLength={500} /></label><label>Code de la dose<input name="doseCode" pattern="[A-Za-z0-9_-]{1,64}" title="Lettres sans accent, chiffres, tirets ou _ ; aucun espace. Exemple : BCG_1." required /><small>Exemple : BCG_1 (pas « BCG 1 »).</small></label>
        <label>Nom de la dose<input name="doseName" maxLength={120} required /></label><div className="admin-form-actions"><small>Les codes doivent être uniques. D’autres doses peuvent être gérées dans le référentiel.</small><button className="admin-primary-button" disabled={busy} type="submit">Créer la référence</button></div>
      </form>
    </section>
    <section className="admin-panel admin-users-panel"><div className="admin-panel-heading"><div><p className="admin-eyebrow">VACCINATION</p><h2>Références et doses</h2></div></div>
      <div className="admin-table-wrap"><table className="admin-table"><thead><tr><th>Vaccin</th><th>Code</th><th>Doses</th><th>Statut</th><th>Action</th></tr></thead><tbody>
        {vaccines.map((vaccine) => <tr key={vaccine.id}><td><strong>{vaccine.displayName}</strong><small>{vaccine.description || "Aucune description"}</small></td><td><code>{vaccine.vaccineCode}</code></td><td>{vaccine.doses.map((dose) => `${dose.doseOrder}. ${dose.displayName}`).join(" · ") || "Aucune dose"}</td><td><span className={`admin-status ${vaccine.isActive ? "is-active" : "is-inactive"}`}>{vaccine.isActive ? "Active" : "Inactive"}</span></td><td><button className="admin-table-action" disabled={busy} onClick={() => void toggleVaccine(vaccine)} type="button">{vaccine.isActive ? "Désactiver" : "Activer"}</button></td></tr>)}
        {!vaccines.length && <tr><td className="admin-empty-cell" colSpan={5}>Aucune référence vaccinale enregistrée.</td></tr>}
      </tbody></table></div>
    </section>
    <section className="admin-panel admin-users-panel"><div className="admin-panel-heading"><div><p className="admin-eyebrow">ÉTABLISSEMENTS</p><h2>Centres de santé</h2></div><span className="admin-count-chip">{centers.length} centres</span></div>
      <div className="admin-center-grid">{centers.map((center) => <article className="admin-center-card" key={center.id}><span className="admin-center-icon">⌂</span><div><strong>{center.name}</strong><small>{[center.city, center.province].filter(Boolean).join(" · ") || center.addressText || "Localisation non renseignée"}</small></div><span>{center.phoneE164 || "—"}</span></article>)}{!centers.length && <p className="admin-empty">Aucun centre enregistré.</p>}</div>
      <form className="admin-form-grid admin-center-form" onSubmit={(event) => void submit(event, "/api/v1/health-centers", (values) => values)}>
        <label>Nom du centre<input name="name" maxLength={180} required /></label><label>Ville<input name="city" maxLength={120} /></label><label>Province<input name="province" maxLength={120} /></label><label>Téléphone<input name="phoneE164" placeholder="+243…" /></label><label className="admin-wide-field">Adresse<input name="addressText" maxLength={500} /></label><div className="admin-form-actions"><span /><button className="admin-primary-button" disabled={busy} type="submit">Ajouter un centre</button></div>
      </form>
    </section>
  </>;
}

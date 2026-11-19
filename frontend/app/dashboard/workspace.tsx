"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import type { PublicUser } from "@/lib/auth";

type Snapshot = Record<string, unknown> & {
  totals?: Record<string, number>;
  children?: Array<Record<string, unknown>>;
  centers?: Array<Record<string, unknown>>;
};

const roleNames: Record<string, string> = {
  super_administrator: "Espace super administrateur",
  national_administrator: "Espace administrateur national",
  provincial_administrator: "Espace administrateur provincial",
  health_zone_administrator: "Espace administrateur de zone de santé",
  health_facility_administrator: "Espace administrateur d’établissement",
  health_data_analyst: "Espace analyste de données sanitaires",
  parent: "Espace parent",
  community_health_worker: "Espace agent communautaire",
  health_professional: "Espace professionnel de santé",
  health_center_manager: "Espace centre de santé",
  health_supervisor: "Espace superviseur",
  administrator: "Administration",
};

export function DashboardWorkspace({ user }: { user: PublicUser }) {
  const router = useRouter();
  const [snapshot, setSnapshot] = useState<Snapshot | null>(null);
  const [records, setRecords] = useState<Record<string, unknown>[]>([]);
  const [centers, setCenters] = useState<Record<string, unknown>[]>([]);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    const response = await fetch("/api/v1/dashboard", { cache: "no-store" });
    if (response.status === 401) { router.replace("/login?scope=public"); return; }
    const value = await response.json();
    if (!response.ok) throw new Error(value.error || "Chargement impossible.");
    setSnapshot(value);
    if (user.roleCode === "health_professional") {
      const alerts = await fetch("/api/v1/professional/alerts", { cache: "no-store" }).then((result) => result.json());
      setRecords(alerts.alerts ?? []);
    } else if (user.roleCode === "health_center_manager") {
      const appointments = await fetch("/api/v1/appointments", { cache: "no-store" }).then((result) => result.json());
      setRecords(appointments.appointments ?? []);
    } else if (user.roleCode === "administrator" || user.roleCode === "super_administrator") {
      const users = await fetch("/api/v1/admin/users", { cache: "no-store" }).then((result) => result.json());
      setRecords(users.users ?? []);
    } else if (user.roleCode === "parent") {
      const centersResult = await fetch("/api/v1/health-centers", { cache: "no-store" }).then((result) => result.json());
      setCenters(centersResult.centers ?? []);
    } else if (user.roleCode === "community_health_worker") {
      const centersResult = await fetch("/api/v1/health-centers", { cache: "no-store" }).then((result) => result.json());
      setCenters(centersResult.centers ?? []);
    }
  }, [router, user.roleCode]);

  useEffect(() => { void load().catch((error) => setMessage(error instanceof Error ? error.message : "Chargement impossible.")); }, [load]);

  async function send(path: string, method: string, payload: Record<string, unknown>, success: string) {
    setBusy(true);
    setMessage("");
    try {
      const response = await fetch(path, { method, headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) });
      const result = response.status === 204 ? {} : await response.json();
      if (!response.ok) throw new Error(result.error || "La demande a échoué.");
      setMessage(success);
      await load();
      return true;
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Une erreur est survenue.");
      return false;
    } finally { setBusy(false); }
  }

  async function submit(event: FormEvent<HTMLFormElement>, path: string, fields: string[], success: string, transform?: (values: Record<string, FormDataEntryValue>) => Record<string, unknown>) {
    event.preventDefault();
    const form = event.currentTarget;
    const values = Object.fromEntries(new FormData(form).entries());
    const sent = await send(path, "POST", transform ? transform(values) : Object.fromEntries(fields.map((field) => [field, values[field]])), success);
    if (sent) form.reset();
  }

  async function logout() {
    await fetch("/api/v1/auth/logout", { method: "POST" });
    router.replace("/login?scope=public");
    router.refresh();
  }

  const totals = snapshot?.totals ?? {};
  const summaryCards = Object.entries(totals).map(([key, value]) => (
    <article className="summary-card" key={key}><span>{key.replace(/[A-Z]/gu, (char) => ` ${char.toLowerCase()}`)}</span><strong>{value}</strong></article>
  ));

  if (user.roleCode === "parent") {
    return <ParentDashboard user={user} snapshot={snapshot} centers={centers} busy={busy} message={message} send={send} submit={submit} logout={logout} />;
  }

  return (
    <main className="dashboard-page">
      <header className="dashboard-header">
        <a className="auth-brand" href="/">AFYAMTOTO <span>AI</span></a>
        <div className="dashboard-user"><span>{user.displayName}</span><button className="button button-outline" onClick={logout} type="button">Déconnexion</button></div>
      </header>
      <div className="dashboard-content">
        <p className="section-kicker">Espace sécurisé</p>
        <h1>{roleNames[user.roleCode] ?? "Mon espace"}</h1>
        <p className="auth-intro">Bonjour {user.displayName}. Les informations affichées dépendent de votre rôle et des autorisations de partage.</p>
        {message && <p className="dashboard-message" role="status">{message}</p>}
        <section className="summary-grid" aria-label="Résumé">{summaryCards}</section>

        {user.roleCode === "community_health_worker" && <section className="dashboard-section">
          <h2>Enregistrer un rattachement</h2><form className="workflow-form" onSubmit={(event) => submit(event, "/api/v1/community/children", ["parentEmail", "firstName", "lastName", "dateOfBirth", "sex", "relationshipType"], "Profil rattaché. Le parent doit confirmer son consentement.")}>
            <label>E-mail du parent<input name="parentEmail" required type="email" /></label><label>Prénom<input name="firstName" required maxLength={100} /></label><label>Nom<input name="lastName" required maxLength={100} /></label><label>Date de naissance<input name="dateOfBirth" required type="date" /></label><label>Sexe<select name="sex"><option value="unknown">Non précisé</option><option value="female">Fille</option><option value="male">Garçon</option></select></label><label>Lien<select name="relationshipType"><option value="parent">Parent</option><option value="guardian">Tuteur</option></select></label><button className="button button-primary" disabled={busy}>Rattacher</button>
          </form>
          <div className="record-grid">{(snapshot?.children ?? []).map((child) => <article className="record-card" key={String(child.id)}><strong>{String(child.firstName)} {String(child.lastName)}</strong><span>Consentement : {String(child.consentStatus)}</span></article>)}</div>
          <WorkerActivityForm children={(snapshot?.children ?? []).filter((child) => child.consentStatus === "granted")} centers={centers} busy={busy} submit={submit} />
        </section>}

        {user.roleCode === "health_professional" && <section className="dashboard-section"><h2>Alertes à examiner</h2><div className="record-grid">{records.map((alert) => <article className="record-card" key={String(alert.id)}><strong>{String(alert.alertType)} · {String(alert.severity)}</strong><p>{String(alert.description)}</p><span>Statut : {String(alert.status)}</span>{["pending_review", "under_review"].includes(String(alert.status)) && <div className="record-actions">{["under_review", "referred", "resolved", "dismissed"].map((status) => <button className="button button-outline" key={status} onClick={() => void send(`/api/v1/professional/alerts/${alert.id}`, "PATCH", { status }, "Alerte mise à jour.")} type="button">{status.replaceAll("_", " ")}</button>)}</div>}</article>)}</div></section>}

        {user.roleCode === "health_center_manager" && <section className="dashboard-section"><h2>Demandes de rendez-vous</h2><div className="record-grid">{records.map((appointment) => <article className="record-card" key={String(appointment.id)}><strong>{String(appointment.requestType).replaceAll("_", " ")}</strong><span>Statut : {String(appointment.status)}</span><span>Enfant : {String(appointment.childId)}</span>{appointment.status === "pending" && <div className="record-actions"><button className="button button-primary" onClick={() => void send(`/api/v1/appointments/${appointment.id}/response`, "PATCH", { status: "confirmed", scheduledAt: new Date(Date.now() + 86400000).toISOString() }, "Rendez-vous confirmé.")} type="button">Confirmer demain</button><button className="button button-outline" onClick={() => void send(`/api/v1/appointments/${appointment.id}/response`, "PATCH", { status: "declined" }, "Demande refusée.")} type="button">Refuser</button></div>}</article>)}</div></section>}

        {user.roleCode === "administrator" && <section className="dashboard-section"><h2>Comptes utilisateurs</h2><div className="record-grid">{records.slice(0, 50).map((account) => <article className="record-card" key={String(account.id)}><strong>{String(account.displayName)}</strong><span>{String(account.email ?? account.phoneE164 ?? "Sans identifiant")}</span><span>{Array.isArray(account.roles) ? account.roles.join(", ") : ""} · {account.isActive ? "Actif" : "Désactivé"}</span></article>)}</div></section>}

        {user.roleCode === "health_supervisor" && <section className="dashboard-section">
          <h2>Activité agrégée par centre</h2>
          <p>Les chiffres sont masqués pour les centres comptant moins de cinq enfants concernés. Aucun dossier individuel n’est affiché.</p>
          <div className="record-grid">{(snapshot?.centers ?? []).map((center) => <article className="record-card" key={String(center.id)}>
            <strong>{String(center.name)}</strong><span>{[center.city, center.province].filter(Boolean).join(", ")}</span>
            {center.privacySuppressed ? <span>Données masquées : cohorte inférieure à cinq.</span> : <>
              <span>{String(center.referredChildren)} enfants concernés · {String(center.referrals)} orientations</span>
              <span>{String(center.openReferrals)} orientations à suivre · {String(center.vaccinationDoses)} doses enregistrées</span>
              <span>{String(center.growthMeasurements)} mesures de croissance · {String(center.nutritionScreenings)} dépistages nutritionnels</span>
            </>}
          </article>)}</div>
          {!(snapshot?.centers ?? []).length && <p>Aucun centre n’est affecté à ce compte.</p>}
        </section>}

        {!summaryCards.length && !message && <p>Chargement de votre espace…</p>}
      </div>
    </main>
  );
}

function HistorySection({ snapshot }: { snapshot: Snapshot | null }) {
  const measurements = (snapshot?.measurements ?? []) as Array<Record<string, unknown>>;
  const vaccinations = (snapshot?.vaccinations ?? []) as Array<Record<string, unknown>>;
  const appointments = (snapshot?.appointments ?? []) as Array<Record<string, unknown>>;
  const notifications = (snapshot?.notifications ?? []) as Array<Record<string, unknown>>;
  const sections: Array<[string, string[]]> = [
    ["Mesures de croissance", measurements.slice(0, 4).map((item) => `${formatDate(item.measuredAt)} · ${String(item.weightKg ?? "—")} kg`)],
    ["Vaccinations", vaccinations.slice(0, 4).map((item) => `${String(item.vaccine)} · ${String(item.dose)} · ${formatDate(item.administeredAt)}`)],
    ["Rendez-vous", appointments.slice(0, 4).map((item) => `${String(item.requestType).replaceAll("_", " ")} · ${translateAppointmentStatus(String(item.status))} · ${String((item.center as Record<string, unknown> | null | undefined)?.name ?? "Centre")}`)],
    ["Notifications", notifications.slice(0, 4).map((item) => `${String(item.title)} · ${formatDate(item.createdAt)}`)],
  ];
  return <div className="workflow-columns">{sections.map(([title, lines]) => <section className="record-card" key={title}><h3>{title}</h3>{lines.length ? lines.map((line, index) => <span key={`${title}-${index}`}>{line}</span>) : <span>Aucun élément enregistré.</span>}</section>)}</div>;
}

function translateAppointmentStatus(status: string) {
  const labels: Record<string, string> = { pending: "En attente", confirmed: "Confirmé", declined: "Refusé", cancelled: "Annulé", completed: "Terminé" };
  return labels[status] ?? status.replaceAll("_", " ");
}

function ParentDashboard({ user, snapshot, centers, busy, message, send, submit, logout }: {
  user: PublicUser;
  snapshot: Snapshot | null;
  centers: Array<Record<string, unknown>>;
  busy: boolean;
  message: string;
  send: (path: string, method: string, payload: Record<string, unknown>, success: string) => Promise<boolean>;
  submit: DashboardSubmit;
  logout: () => Promise<void>;
}) {
  const children = snapshot?.children ?? [];
  const measurements = (snapshot?.measurements ?? []) as Array<Record<string, unknown>>;
  const vaccinations = (snapshot?.vaccinations ?? []) as Array<Record<string, unknown>>;
  const appointments = (snapshot?.appointments ?? []) as Array<Record<string, unknown>>;
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const upcomingDoses = vaccinations.filter((item) => item.nextDoseDate && new Date(String(item.nextDoseDate)) >= today);
  const firstName = user.displayName.trim().split(/\s+/u)[0] || "Parent";

  return <main className="dashboard-page parent-dashboard">
    <aside className="parent-sidebar">
      <a className="auth-brand" href="/">AFYAMTOTO <span>AI</span></a>
      <nav className="parent-nav">
        {["Tableau de bord","#mes-enfants","#ajouter-enfant","#rendez-vous","#centres"].map((item,i)=>(
          <a key={i} href={item.startsWith("#")?item:"/dashboard/parent"} className="parent-nav-link">{["Tableau de bord","Mes enfants","Ajouter un enfant","Suivi & rendez-vous","Centres de santé"][i]}</a>
        ))}
      </nav>
    </aside>
    <div className="parent-dashboard-content">
    <header className="dashboard-header parent-dashboard-header">
      <div><h2 className="parent-welcome-title">Bonjour, {firstName}</h2><p>Espace famille · Suivi 0–5 ans</p></div>
      <div className="dashboard-user"><span className="parent-user-avatar" aria-hidden="true">{firstName.slice(0, 1).toUpperCase()}</span><span>{user.displayName}</span><button className="button button-outline" onClick={() => void logout()} type="button">Déconnexion</button></div>
    </header>
    <section className="parent-welcome" aria-labelledby="parent-welcome-title">
      <div className="parent-welcome-copy"><span className="parent-eyebrow">ESPACE FAMILLE · SUIVI DES 0–5 ANS</span><h1 id="parent-welcome-title">Bonjour, {firstName} 👋</h1><p>Retrouvez en un coup d’œil les informations de suivi de vos enfants et les prochaines étapes.</p>
        <div className="parent-welcome-actions"><a className="button button-primary" href="#ajouter-enfant">Ajouter un enfant <span aria-hidden="true">＋</span></a><a className="parent-secondary-link" href="#rendez-vous">Demander un rendez-vous <span aria-hidden="true">→</span></a></div>
      </div>
    </section>
    {message && <p className="dashboard-message" role="status">{message}</p>}

    <section className="parent-stat-grid" aria-label="Résumé du suivi">
      <article className="parent-stat-card"><span className="parent-stat-icon tone-green" aria-hidden="true">♧</span><span>Profils d’enfants</span><strong>{children.length}</strong><small>{children.length === 1 ? "enfant rattaché" : "enfants rattachés"}</small></article>
      <article className="parent-stat-card"><span className="parent-stat-icon tone-blue" aria-hidden="true">↗</span><span>Mesures récentes</span><strong>{measurements.length}</strong><small>mesures de croissance</small></article>
      <article className="parent-stat-card"><span className="parent-stat-icon tone-orange" aria-hidden="true">＋</span><span>Prochaines doses</span><strong>{upcomingDoses.length}</strong><small>rappels vaccinaux</small></article>
      <article className="parent-stat-card"><span className="parent-stat-icon tone-violet" aria-hidden="true">▣</span><span>Rendez-vous</span><strong>{appointments.filter((item) => item.status === "pending" || item.status === "confirmed").length}</strong><small>en attente ou confirmés</small></article>
    </section>

    <section className="parent-section" id="mes-enfants" aria-labelledby="parent-children-title">
      <div className="parent-section-heading"><div><span className="parent-eyebrow">VOTRE FAMILLE</span><h2 id="parent-children-title">Mes enfants</h2><p>Les profils associés à votre compte.</p></div><a className="parent-inline-link" href="#ajouter-enfant">Ajouter un profil <span aria-hidden="true">＋</span></a></div>
      {children.length ? <div className="parent-child-grid">{children.map((child) => {
        const consent = String(child.consentStatus);
        const consentLabel = consent === "granted" ? "Partage autorisé" : consent === "pending" ? "En attente" : "Partage retiré";
        return <article className="parent-child-card" key={String(child.id)}>
          <div className="parent-child-card-top"><span className="parent-child-avatar" aria-hidden="true">{String(child.firstName).slice(0, 1).toUpperCase()}</span><span className={`parent-consent-badge ${consent === "granted" ? "is-granted" : consent === "pending" ? "is-pending" : "is-withdrawn"}`}><i aria-hidden="true"/>{consentLabel}</span></div>
          <h3>{String(child.firstName)} {String(child.lastName)}</h3><p>Né(e) le {formatDate(child.dateOfBirth)}</p>
          <div className="parent-child-card-bottom"><span>Accès aux informations de santé</span><button className="parent-text-button" disabled={busy} onClick={() => void send(`/api/v1/children/${child.id}`, "PATCH", { firstName: child.firstName, lastName: child.lastName, dateOfBirth: String(child.dateOfBirth).slice(0, 10), sex: child.sex, consentGranted: consent !== "granted" }, consent === "granted" ? "Consentement retiré." : "Consentement confirmé.")} type="button">{consent === "granted" ? "Retirer" : "Confirmer"}</button></div>
        </article>;
      })}</div> : <div className="parent-empty-state"><span className="parent-empty-icon" aria-hidden="true">＋</span><div><strong>Aucun profil enfant</strong><p>Ajoutez un profil pour commencer.</p></div><a className="button button-primary" href="#ajouter-enfant">Ajouter un enfant</a></div>}
    </section>

    <section className="parent-section parent-add-section" id="ajouter-enfant" aria-labelledby="parent-add-title">
      <div className="parent-section-heading"><div><span className="parent-eyebrow">NOUVEAU PROFIL</span><h2 id="parent-add-title">Ajouter un enfant</h2><p>Renseignez les informations de base.</p></div></div>
      <form className="workflow-form parent-add-form" onSubmit={(event) => submit(event, "/api/v1/children", ["firstName", "lastName", "dateOfBirth", "sex", "relationshipType", "consentGranted"], "Profil créé.", (values) => ({ ...values, consentGranted: values.consentGranted === "on" }))}>
        <label>Prénom<input name="firstName" autoComplete="given-name" required maxLength={100} /></label><label>Nom<input name="lastName" autoComplete="family-name" required maxLength={100} /></label><label>Date de naissance<input type="date" name="dateOfBirth" required /></label><label>Sexe<select name="sex"><option value="unknown">Non précisé</option><option value="female">Fille</option><option value="male">Garçon</option></select></label><label>Lien<select name="relationshipType"><option value="parent">Parent</option><option value="guardian">Tuteur</option></select></label><label className="checkbox-label parent-consent-check"><input type="checkbox" name="consentGranted" required /><span>Je confirme être autorisé(e) et consens au partage.</span></label><button className="button button-primary" disabled={busy}>Créer le profil</button>
      </form>
    </section>

    <section className="parent-section" id="rendez-vous" aria-labelledby="parent-followup-title">
      <div className="parent-section-heading"><div><span className="parent-eyebrow">SUIVI PRÉVENTIF</span><h2 id="parent-followup-title">Suivi et rendez-vous</h2></div></div>
      <ParentActivityForm children={children} centers={centers} busy={busy} submit={submit} />
    </section>

    <section className="parent-section" id="centres" aria-labelledby="parent-centers-title">
      <div className="parent-section-heading"><div><span className="parent-eyebrow">À PROXIMITÉ</span><h2 id="parent-centers-title">Centres de santé</h2></div></div>
      {centers.length ? <div className="parent-center-grid">{centers.slice(0, 4).map((center) => <article className="parent-center-card" key={String(center.id)}><span className="parent-center-icon" aria-hidden="true">＋</span><div><strong>{String(center.name)}</strong><p>{[center.city, center.province].filter(Boolean).map(String).join(" · ") || "Localisation non renseignée"}</p></div>{center.phoneE164 ? <a href={`tel:${String(center.phoneE164)}`}>Appeler</a> : null}</article>)}</div> : <p className="parent-no-centers">Aucun centre référencé.</p>}
    </section>
    <footer className="parent-dashboard-footer"><span>AFYAMTOTO AI · Espace famille</span><span>Les informations ne remplacent pas l’avis d’un professionnel.</span></footer>
    </div>
  </main>;
}

function formatDate(value: unknown) {
  if (!value) return "Date non renseignée";
  const date = new Date(String(value));
  if (Number.isNaN(date.getTime())) return "Date non renseignée";
  return new Intl.DateTimeFormat("fr-FR", { day: "numeric", month: "long", year: "numeric", timeZone: "UTC" }).format(date);
}

function WorkerActivityForm({ children, centers, busy, submit }: { children: Array<Record<string, unknown>>; centers: Array<Record<string, unknown>>; busy: boolean; submit: DashboardSubmit }) {
  const childSelect = <label>Enfant<select name="childId" required>{children.map((child) => <option key={String(child.id)} value={String(child.id)}>{String(child.firstName)} {String(child.lastName)}</option>)}</select></label>;
  return <div className="workflow-columns">
    <form className="workflow-form" onSubmit={(event) => submit(event, "/api/v1/growth", ["childId", "measuredAt", "weightKg", "lengthHeightCm", "lengthHeightType"], "Mesure enregistrée.", (values) => ({ ...values, measuredAt: `${values.measuredAt}T12:00:00.000Z` }))}>
      <h3>Mesure de croissance</h3>{childSelect}<label>Date<input name="measuredAt" required type="date" /></label><label>Poids (kg)<input name="weightKg" inputMode="decimal" /></label><label>Taille ou longueur (cm)<input name="lengthHeightCm" inputMode="decimal" /></label><label>Type<select name="lengthHeightType"><option value="length">Longueur</option><option value="height">Taille</option></select></label><button className="button button-primary" disabled={busy || !children.length}>Enregistrer</button>
    </form>
    <form className="workflow-form" onSubmit={(event) => submit(event, "/api/v1/community/visits", ["childId", "visitType", "visitedOn", "purpose", "observations"], "Visite enregistrée.")}>
      <h3>Visite communautaire</h3>{childSelect}<label>Type<select name="visitType"><option value="home">Visite à domicile</option><option value="community_outreach">Sensibilisation</option><option value="follow_up">Suivi</option></select></label><label>Date<input name="visitedOn" required type="date" /></label><label>Motif<input name="purpose" required maxLength={160} /></label><label>Observations<textarea name="observations" maxLength={4000} /></label><button className="button button-primary" disabled={busy || !children.length}>Enregistrer la visite</button>
    </form>
    <form className="workflow-form" onSubmit={(event) => submit(event, "/api/v1/community/nutrition", ["childId", "recordDate", "feedingType", "observations"], "Dépistage enregistré.")}>
      <h3>Dépistage nutritionnel</h3>{childSelect}<label>Date<input name="recordDate" required type="date" /></label><label>Type d’alimentation<input name="feedingType" maxLength={64} /></label><label>Observations<textarea name="observations" required maxLength={4000} /></label><button className="button button-primary" disabled={busy || !children.length}>Enregistrer</button>
    </form>
    <form className="workflow-form" onSubmit={(event) => submit(event, "/api/v1/community/referrals", ["childId", "healthCenterId", "priority", "concernDescription"], "Orientation créée.")}>
      <h3>Orienter vers un centre</h3>{childSelect}<label>Centre<select name="healthCenterId" required>{centers.map((center) => <option key={String(center.id)} value={String(center.id)}>{String(center.name)}</option>)}</select></label><label>Priorité<select name="priority"><option value="routine">Routine</option><option value="prompt">Rapide</option><option value="emergency">Urgente</option></select></label><label>Situation à signaler<textarea name="concernDescription" required maxLength={1000} /></label><button className="button button-primary" disabled={busy || !children.length || !centers.length}>Créer l’orientation</button>
    </form>
  </div>;
}

function ParentActivityForm({ children, centers, busy, submit }: { children: Array<Record<string, unknown>>; centers: Array<Record<string, unknown>>; busy: boolean; submit: DashboardSubmit }) {
  const eligibleChildren = children.filter((child) => child.consentStatus === "granted");
  return <div className="workflow-columns">
    <form className="workflow-form" onSubmit={(event) => submit(event, "/api/v1/growth", ["childId", "measuredAt", "weightKg", "lengthHeightCm", "lengthHeightType"], "La mesure a été enregistrée.", (values) => ({ ...values, measuredAt: `${values.measuredAt}T12:00:00.000Z` }))}>
      <h3>Mesure de croissance</h3><label>Enfant concerné<select name="childId" required>{eligibleChildren.map((child) => <option key={String(child.id)} value={String(child.id)}>{String(child.firstName)} {String(child.lastName)}</option>)}</select></label><label>Date de la mesure<input name="measuredAt" required type="date" /></label><label>Poids (kg)<input name="weightKg" inputMode="decimal" /></label><label>Taille ou longueur (cm)<input name="lengthHeightCm" inputMode="decimal" /></label><label>Mesure effectuée<select name="lengthHeightType"><option value="length">Longueur</option><option value="height">Taille</option></select></label><button className="button button-primary" disabled={busy || !eligibleChildren.length}>Enregistrer la mesure</button>
    </form>
    <form className="workflow-form" onSubmit={(event) => submit(event, "/api/v1/appointments", ["childId", "healthCenterId", "requestType", "preferredDate"], "Votre demande de rendez-vous a été envoyée.")}>
      <h3>Demander un rendez-vous</h3><label>Enfant concerné<select name="childId" required>{eligibleChildren.map((child) => <option key={String(child.id)} value={String(child.id)}>{String(child.firstName)} {String(child.lastName)}</option>)}</select></label><label>Centre de santé<select name="healthCenterId" required>{centers.map((center) => <option key={String(center.id)} value={String(center.id)}>{String(center.name)}</option>)}</select></label><label>Motif du rendez-vous<select name="requestType"><option value="consultation">Consultation</option><option value="vaccination_follow_up">Suivi vaccinal</option><option value="growth_follow_up">Suivi de croissance</option><option value="nutrition_consultation">Consultation nutritionnelle</option></select></label><label>Date souhaitée<input name="preferredDate" type="date" /></label><button className="button button-primary" disabled={busy || !eligibleChildren.length || !centers.length}>Envoyer la demande</button>
    </form>
  </div>;
}

type DashboardSubmit = (event: FormEvent<HTMLFormElement>, path: string, fields: string[], success: string, transform?: (values: Record<string, FormDataEntryValue>) => Record<string, unknown>) => Promise<void>;

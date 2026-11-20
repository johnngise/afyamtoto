"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

type Overview = {
  stats: Record<string, number>;
  activity: { id: string; action: string; description: string; createdAt: string; actor: string }[];
};

const statLabels: Record<string, string> = {
  users: "Comptes utilisateurs", activeUsers: "Comptes actifs", children: "Profils enfants",
  centers: "Centres de santé", pendingAppointments: "Rendez-vous à traiter", openAlerts: "Alertes ouvertes",
  activeVaccines: "Références vaccinales actives",
};

function formatAction(action: string) {
  return action.replaceAll("_", " ").replace(/^\w/u, (letter) => letter.toUpperCase());
}

export function AdminOverview() {
  const [data, setData] = useState<Overview | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    fetch("/api/v1/admin/overview", { cache: "no-store" }).then(async (response) => {
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "Chargement impossible.");
      setData(result);
    }).catch((reason: unknown) => setError(reason instanceof Error ? reason.message : "Chargement impossible."));
  }, []);

  return <>
    <div className="admin-page-heading"><div><p className="admin-eyebrow">TABLEAU DE BORD</p><h1>Vue d’ensemble</h1><p>Les indicateurs sont issus des dossiers actuellement enregistrés dans PostgreSQL.</p></div><span className="admin-live-pill"><i /> Données en direct</span></div>
    {error && <p className="admin-alert" role="alert">{error}</p>}
    <section aria-label="Statistiques de la plateforme" className="admin-stat-grid">
      {Object.entries(statLabels).map(([key, label], index) => <article className="admin-stat-card" key={key}><div className={`admin-stat-icon tone-${index % 4}`} aria-hidden="true">{["♙", "✓", "♡", "⌂", "◷", "!", "＋"][index]}</div><span>{label}</span><strong>{data?.stats[key] ?? "—"}</strong><small>{data ? "Base actualisée" : "Chargement"}</small></article>)}
    </section>
    <div className="admin-dashboard-grid">
      <section className="admin-panel"><div className="admin-panel-heading"><div><p className="admin-eyebrow">JOURNAL</p><h2>Activité récente</h2></div><span className="admin-panel-count">{data?.activity.length ?? 0}</span></div>
        {!data?.activity.length && <p className="admin-empty">{data ? "Aucune activité enregistrée." : "Chargement des activités…"}</p>}
        <div className="admin-activity-list">{data?.activity.map((entry) => <article className="admin-activity-row" key={entry.id}><span className="admin-activity-dot" /><div><strong>{formatAction(entry.action)}</strong><p>{entry.description}</p><small>{entry.actor} · {new Date(entry.createdAt).toLocaleString("fr-FR")}</small></div></article>)}</div>
      </section>
      <section className="admin-panel admin-quick-panel"><div className="admin-panel-heading"><div><p className="admin-eyebrow">ACCÈS RAPIDE</p><h2>Gestion</h2></div></div>
        <Link className="admin-quick-link" href="/admin/users"><span>♙</span><div><strong>Utilisateurs</strong><small>Comptes, rôles et accès</small></div><b>→</b></Link>
        <Link className="admin-quick-link" href="/admin/content"><span>▤</span><div><strong>Contenus santé</strong><small>Vaccins, doses et centres</small></div><b>→</b></Link>
        <Link className="admin-quick-link" href="/admin/settings"><span>⚙</span><div><strong>Paramètres</strong><small>Compte et sécurité</small></div><b>→</b></Link>
      </section>
    </div>
  </>;
}

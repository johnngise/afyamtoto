import { getCurrentUser } from "@/lib/auth";
import { prisma } from "@/lib/db";

export const dynamic = "force-dynamic";

export default async function AdminSettingsPage() {
  const actor = await getCurrentUser();
  const account = actor ? await prisma.user.findUnique({ where: { id: BigInt(actor.id) }, select: { displayName: true, username: true, email: true, isActive: true, createdAt: true } }) : null;
  const userCount = await prisma.user.count();
  return <>
    <div className="admin-page-heading"><div><p className="admin-eyebrow">CONFIGURATION</p><h1>Paramètres</h1><p>État du compte administrateur et règles de sécurité de la plateforme.</p></div><span className="admin-live-pill"><i /> PostgreSQL connecté</span></div>
    <div className="admin-settings-grid">
      <section className="admin-panel"><p className="admin-eyebrow">COMPTE CONNECTÉ</p><h2>{account?.displayName ?? actor?.displayName}</h2><dl className="admin-definition-list"><div><dt>Nom d’utilisateur</dt><dd>{account?.username ? `@${account.username}` : "Non défini"}</dd></div><div><dt>E-mail</dt><dd>{account?.email ?? "Non renseigné"}</dd></div><div><dt>Rôle</dt><dd>Administrateur</dd></div><div><dt>Statut</dt><dd>{account?.isActive ? "Actif" : "Inactif"}</dd></div><div><dt>Compte créé le</dt><dd>{account?.createdAt.toLocaleDateString("fr-FR") ?? "—"}</dd></div></dl></section>
      <section className="admin-panel"><p className="admin-eyebrow">SÉCURITÉ ET SESSION</p><h2>Accès protégé</h2><ul className="admin-security-list"><li><span>✓</span> Pages administratives vérifiées côté serveur</li><li><span>✓</span> API sensibles réservées au rôle administrateur</li><li><span>✓</span> Cookie de session HttpOnly et empreinte stockée</li><li><span>✓</span> Limitation des tentatives de connexion</li></ul><p className="admin-settings-note">Expiration : 30 minutes d’inactivité, 8 heures maximum.</p></section>
      <section className="admin-panel admin-settings-summary"><p className="admin-eyebrow">PLATEFORME</p><h2>État des données</h2><div className="admin-setting-metric"><span>Comptes utilisateurs enregistrés</span><strong>{userCount}</strong></div><div className="admin-setting-metric"><span>Base de données</span><strong className="admin-db-state">Opérationnelle</strong></div><p className="admin-settings-note">Les paramètres SMTP et de réinitialisation de mot de passe ne sont pas configurés dans l’application.</p></section>
    </div>
  </>;
}

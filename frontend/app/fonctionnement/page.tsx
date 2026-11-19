import Link from "next/link";

export const metadata = { title: "Fonctionnement - AFYAMTOTO AI" };

export default function FonctionnementPage() {
  return (
    <>
      <header className="landing-header">
        <Link className="auth-brand" href="/">AFYAMTOTO <span>AI</span></Link>
        <nav className="landing-nav">
          <Link href="/">Accueil</Link>
          <Link href="/mission">Notre mission</Link>
          <Link href="/solutions">Nos solutions</Link>
          <Link href="/fonctionnement">Fonctionnement</Link>
          <Link href="/impact">Impact</Link>
          <Link href="/contact">Contact</Link>
        </nav>
        <div className="landing-actions">
          <Link className="button button-outline" href="/login?scope=public">Se connecter</Link>
        </div>
      </header>

      <main className="landing-section" style={{ background: "linear-gradient(135deg,#EAF6FC,#DDF5E9)" }}>
        <section className="container" style={{ textAlign: "center", padding: "64px 0" }}>
          <span className="landing-eyebrow">COMMENT ÇA FONCTIONNE ?</span>
          <h1 style={{ color: "#123F5A" }}>Une technologie intelligente au service de la santé de chaque enfant.</h1>
          <p>AFYAMTOTO AI transforme les données sanitaires en informations utiles.</p>
          <div className="landing-hero-actions">
            <a className="button button-primary" href="#etapes">Découvrir les étapes</a>
            <Link className="button button-outline" href="/solutions">Explorer nos solutions</Link>
          </div>
        </section>

        <section id="etapes" className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>Un processus simple, intelligent et connecté</h2>
          <ol className="landing-steps">
            <li><strong>Enregistrement</strong> : Le parent ou l'agent crée le profil.</li>
            <li><strong>Collecte des données</strong> :  Poids, taille, suivi.</li>
            <li><strong>Analyse intelligente</strong> : Références OMS.</li>
            <li><strong>Alertes et orientation</strong> : Signalement aux professionnels.</li>
            <li><strong>Suivi et accompagnement</strong> : Vérification et suivi.</li>
          </ol>
        </section>

        <section className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>Un écosystème connecté autour de l'enfant</h2>
          <div className="landing-beneficiaries">
            {["Parents et tuteurs","Agents communautaires","Infirmiers","Médecins","Centres de santé","Responsables sanitaires"].map((a)=>(
              <article key={a} className="landing-card">{a}</article>
            ))}
          </div>
        </section>

        <section className="container" style={{ padding: "48px 0", background: "#123F5A", color: "#fff", borderRadius: "16px" }}>
          <h2 style={{ color: "#fff" }}>De la donnée à la prévention intelligente</h2>
          <ol className="landing-steps" style={{ color: "#fff" }}>
            <li><strong>Collecte</strong> — Âge, poids, taille, historique.</li>
            <li><strong>Analyse</strong> — Références OMS, courbes.</li>
            <li><strong>Alertes</strong> — Signalement et notification.</li>
            <li><strong>Validation humaine</strong> — Vérification, orientation, suivi.</li>
          </ol>
        </section>

        <section className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>Une solution pensée pour les réalités du terrain africain</h2>
          <p>Collecte hors connexion, stockage local, synchronisation automatique.</p>
        </section>

        <section className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>Une vision globale de la santé communautaire</h2>
          <p>Nombre d'enfants suivis, répartition par âge, indicateurs, alertes, statistiques, rapports (données fictives).</p>
        </section>

        <section className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>La sécurité des données au cœur de notre technologie</h2>
          <p>Authentification, rôles, accès, protection, chiffrement, traçabilité, consentement.</p>
        </section>

        <section className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>AFYAMTOTO AI en situation réelle</h2>
          <p>Scénario illustratif : mère à Goma, création de profil, mesure, analyse, alerte, intervention, accompagnement.</p>
        </section>

        <section className="solutions-cta">
          <h2>Ensemble, construisons une nouvelle approche de la santé infantile.</h2>
          <div className="landing-hero-actions">
            <Link className="button button-primary" href="/solutions">Découvrir nos solutions</Link>
            <Link className="button button-outline" href="/contact">Nous contacter</Link>
          </div>
        </section>
      </main>

      <footer className="landing-footer">
        <div className="landing-footer-grid">
          <div><strong>AFYAMTOTO AI</strong><p>Plateforme de suivi de la santé des enfants de 0 à 5 ans.</p></div>
          <div><h4>Navigation</h4><Link href="/">Accueil</Link><Link href="/mission">Notre mission</Link><Link href="/solutions">Nos solutions</Link><Link href="/fonctionnement">Fonctionnement</Link><Link href="/impact">Impact</Link><Link href="/contact">Contact</Link></div>
          <div><h4>Accès</h4><Link href="/login?scope=public">Connexion</Link><Link href="/register">Inscription</Link></div>
          <div><h4>Légal</h4><a href="#">Confidentialité</a><a href="#">Conditions</a><a href="#">Mentions légales</a></div>
        </div>
        <p className="landing-footer-copy">© 2026 AFYAMTOTO AI. Tous droits réservés.</p>
      </footer>
    </>
  );
}

import Link from "next/link";

export const metadata = { title: "Nos solutions - AFYAMTOTO AI" };

export default function SolutionsPage() {
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

      <main className="solutions-page">
        <section className="solutions-hero">
          <div className="solutions-hero-copy">
            <span className="landing-eyebrow">HEALTH TECH INNOVATION</span>
            <h1>Des solutions intelligentes pour protéger la santé de <span className="gradient-text">chaque enfant</span>.</h1>
            <p>AFYAMTOTO AI associe technologie mobile, intelligence artificielle et surveillance sanitaire communautaire.</p>
            <div className="landing-hero-actions">
              <a className="button button-primary" href="#solutions">Découvrir nos solutions</a>
              <Link className="button button-outline" href="/fonctionnement">Comprendre notre fonctionnement</Link>
            </div>
          </div>
          <div className="solutions-hero-art" aria-hidden="true">
            <div className="floating-card">Croissance</div>
            <div className="floating-card">Vaccination</div>
            <div className="floating-card">Nutrition</div>
          </div>
        </section>

        <section id="solutions" className="solutions-section">
          <h2>Nos solutions</h2>
          <div className="solutions-grid">
            {[
              { title: "Application mobile pour les parents", desc: "Suivi de la croissance, vaccins, rappels, nutrition." },
              { title: "Application agents communautaires", desc: "Enregistrement, collecte, suivi nutritionnel." },
              { title: "Intelligence artificielle", desc: "Analyse de croissance et alertes." },
              { title: "Tableau de bord sanitaire", desc: "Statistiques et cartographie." },
              { title: "Coordination professionnels", desc: "Collaboration et autorisations." },
              { title: "Hors connexion", desc: "Collecte locale et synchronisation." },
            ].map((s) => (
              <article key={s.title} className="solution-card">
                <h3>{s.title}</h3>
                <p>{s.desc}</p>
                <button className="button button-outline">En savoir plus</button>
              </article>
            ))}
          </div>
        </section>

        <section className="solutions-section solutions-alt">
          <h2>Application mobile pour les parents</h2>
          <p>Profil de l'enfant, dossier sanitaire, suivi poids/taille, courbes, vaccins, rappels, conseils, historique.</p>
        </section>

        <section className="solutions-section">
          <h2>Intelligence artificielle</h2>
          <p>Analyse de croissance, nutrition, alertes, aide à la décision. Références OMS, validation médicale.</p>
        </section>

        <section className="solutions-section solutions-alt">
          <h2>Tableau de bord de surveillance sanitaire</h2>
          <p>Statistiques, répartition, indicateurs, alertes, cartographie, rapports (données fictives de démo).</p>
        </section>

        <section className="solutions-section">
          <h2>Fonctionnement hors connexion</h2>
          <p>Collecte locale, stockage sécurisé, synchronisation automatique.</p>
        </section>

        <section className="solutions-section solutions-alt">
          <h2>Sécurité et confidentialité</h2>
          <p>Authentification, rôles, autorisations, protection des données, chiffrement, traçabilité.</p>
        </section>

        <section className="solutions-cta">
          <h2>La technologie au service d'un avenir en meilleure santé pour chaque enfant.</h2>
          <div className="landing-hero-actions">
            <Link className="button button-primary" href="/fonctionnement">Découvrir notre fonctionnement</Link>
            <Link className="button button-outline" href="/contact">Nous rejoindre</Link>
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

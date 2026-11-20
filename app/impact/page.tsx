import Link from "next/link";

export const metadata = { title: "Impact - AFYAMTOTO AI" };

export default function ImpactPage() {
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
          <span className="landing-eyebrow">NOTRE ENGAGEMENT POUR LA SANTÉ INFANTILE</span>
          <h1 style={{ color: "#123F5A" }}>Un avenir où chaque enfant a la chance de grandir en bonne santé.</h1>
          <p>AFYAMTOTO AI ambitionne de transformer le suivi de la santé infantile grâce à l'IA, aux technologies numériques et à la surveillance sanitaire communautaire.</p>
          <div className="landing-hero-actions">
            <a className="button button-primary" href="#impact">Découvrir notre impact</a>
            <Link className="button button-outline" href="/contact">Rejoindre notre initiative</Link>
          </div>
        </section>

        <section id="impact" className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>Agir aujourd'hui pour améliorer la santé de demain</h2>
          <div className="landing-pillars">
            {["Santé infantile","Nutrition","Prévention","Accompagnement des familles","Professionnels de santé","Surveillance communautaire"].map((d)=>(
              <article key={d} className="landing-card">{d}</article>
            ))}
          </div>
        </section>

        <section className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>Mesurer le changement, suivre les progrès</h2>
          <div className="landing-beneficiaries">
            {["Enfants enregistrés","Enfants suivis","Parents accompagnés","Agents formés","Alertes générées","Orientations sanitaires"].map((k)=>(
              <article key={k} className="landing-card">{k} : données de démo</article>
            ))}
          </div>
        </section>

        <section className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>Chaque enfant mérite une attention particulière</h2>
          <p>Enfants : suivi de croissance, surveillance nutritionnelle, identification des situations préoccupantes, continuité du suivi.</p>
          <p>Parents : informations accessibles, rappels, historique, conseils, communication avec les professionnels.</p>
        </section>

        <section className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>Donner aux professionnels des outils pour mieux agir</h2>
          <p>Centralisation des dossiers, analyse des données, gestion des alertes, organisation du suivi, coordination, statistiques.</p>
        </section>

        <section className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>Des communautés mieux informées et accompagnées</h2>
          <p>Phase 1 : pilote à Goma (Nord-Kivu). Phase 2 : extension au Nord-Kivu. Phase 3 : autres provinces RDC. Phase 4 : autres pays africains.</p>
        </section>

        <section className="container" style={{ padding: "48px 0", background: "#123F5A", color: "#fff", borderRadius: "16px" }}>
          <h2 style={{ color: "#fff" }}>L'innovation au service d'une santé plus accessible</h2>
          <p>IA, santé numérique, surveillance communautaire, accessibilité hors connexion.</p>
        </section>

        <section className="container" style={{ padding: "48px 0" }}>
          <h2 style={{ color: "#123F5A" }}>Un impact mesurable, vérifiable et évolutif</h2>
          <p>Taux d'adoption, enfants enregistrés, continuité du suivi, situations signalées, orientations, satisfaction, qualité des données (objectifs prévisionnels).</p>
        </section>

        <section className="solutions-cta">
          <h2>Ensemble, donnons à chaque enfant une meilleure chance de grandir en bonne santé.</h2>
          <div className="landing-hero-actions">
            <Link className="button button-primary" href="/contact">Devenir partenaire</Link>
            <Link className="button button-outline" href="/solutions">Découvrir nos solutions</Link>
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

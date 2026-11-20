import Link from "next/link";

export const metadata = { title: "Notre mission - AFYAMTOTO AI" };

export default function MissionPage() {
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

      <main className="landing-section" style={{ background: "#174A70", color: "#fff" }}>
        <section className="mission-hero">
          <h1>Notre mission : protéger la santé de chaque enfant</h1>
          <p>AFYAMTOTO AI s'engage pour un suivi sanitaire régulier et accessible des enfants de 0 à 5 ans.</p>
          <div className="landing-hero-actions">
            <a className="button button-primary" href="#approche">Découvrir notre approche</a>
            <Link className="button button-outline" href="/contact">Nous contacter</Link>
          </div>
        </section>

        <section className="landing-section" style={{ background: "#42B883", color: "#174A70" }}>
          <h2>Notre raison d'être</h2>
          <div className="landing-pillars">
            <article className="landing-card">Suivi insuffisant de la croissance</article>
            <article className="landing-card">Risques de malnutrition</article>
            <article className="landing-card">Manque de suivi régulier</article>
            <article className="landing-card">Surveillance sanitaire communautaire</article>
            <article className="landing-card">Outils numériques adaptés</article>
          </div>
        </section>

        <section className="landing-section landing-alt">
          <h2>Notre mission</h2>
          <blockquote className="mission-quote" style={{ color: "#174A70" }}>
            Notre mission est de contribuer à réduire les risques sanitaires chez les enfants de 0 à 5 ans en facilitant le suivi de leur croissance, la prévention de la malnutrition et l'identification précoce des signes de vulnérabilité, grâce à des solutions numériques accessibles, intelligentes et adaptées aux réalités des communautés africaines.
          </blockquote>
        </section>

        <section className="landing-section" style={{ background: "#42B883", color: "#174A70" }}>
          <h2>Notre vision</h2>
          <p>Construire un avenir dans lequel chaque enfant, quel que soit son lieu de résidence ou sa situation socio-économique, bénéficie d'un suivi sanitaire régulier et de meilleures opportunités de grandir en bonne santé.</p>
        </section>

        <section className="landing-section landing-alt">
          <h2>Nos objectifs stratégiques</h2>
          <div className="landing-pillars">
            <article className="landing-card">Suivi de la croissance</article>
            <article className="landing-card">Prévention de la malnutrition</article>
            <article className="landing-card">IA au service de la santé</article>
            <article className="landing-card">Surveillance communautaire</article>
            <article className="landing-card">Accompagnement des parents</article>
            <article className="landing-card">Orientation vers les structures</article>
          </div>
        </section>

        <section className="landing-section" style={{ background: "#42B883", color: "#174A70" }}>
          <h2>Nos bénéficiaires</h2>
          <div className="landing-beneficiaries">
            {["Enfants 0-5 ans","Parents et tuteurs","Agents communautaires","Infirmiers et médecins","Centres de santé","Responsables sanitaires"].map((b)=>(
              <article key={b} className="landing-card">{b}</article>
            ))}
          </div>
        </section>

        <section className="landing-section landing-alt">
          <h2>Nos valeurs</h2>
          <div className="landing-challenges">
            {["Innovation responsable","Protection de l'enfant","Accessibilité","Confidentialité","Inclusion","Collaboration","Fiabilité scientifique"].map((v)=>(
              <article key={v} className="landing-chip">{v}</article>
            ))}
          </div>
        </section>

        <section id="approche" className="landing-section">
          <h2>Notre approche</h2>
          <ol className="landing-steps">
            <li><strong>Collecter</strong> — Recueillir les informations de suivi.</li>
            <li><strong>Analyser</strong> — Comparer aux références OMS.</li>
            <li><strong>Identifier et alerter</strong> — Signaler les indicateurs préoccupants.</li>
            <li><strong>Orienter</strong> — Faciliter l'intervention des professionnels.</li>
          </ol>
        </section>

        <section className="landing-section landing-alt">
          <h2>Notre engagement pour l'Afrique</h2>
          <p>Phase pilote à Goma (Nord-Kivu), puis extension progressive en RDC et dans d'autres communautés africaines, en collaboration avec les professionnels et partenaires.</p>
        </section>

        <section className="landing-cta">
          <h2>Ensemble, construisons un avenir où chaque enfant compte.</h2>
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

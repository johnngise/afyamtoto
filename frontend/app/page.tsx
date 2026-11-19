"use client";
import { useEffect, useState } from "react";
import Link from "next/link";

export default function HomePage() {
  const slides = ["/CAR1.PNG", "/CAR2.PNG", "/CAR3.PNG", "/CAR4.PNG"];
  const [i, setI] = useState(0);
  useEffect(() => {
    const t = setInterval(() => setI((x) => (x + 1) % slides.length), 4000);
    return () => clearInterval(t);
  }, [slides.length]);

  return (
    <main className="landing-page">
      <header className="landing-header">
        <Link className="auth-brand" href="/">AFYAMTOTO <span>AI</span></Link>
        <nav className="landing-nav"><Link href="/">Accueil</Link><Link href="/mission">Notre mission</Link><Link href="/solutions">Nos solutions</Link><Link href="/fonctionnement">Fonctionnement</Link><Link href="/impact">Impact</Link><Link href="/contact">Contact</Link></nav>
        <div className="landing-actions">
          <Link className="button button-outline" href="/login?scope=public">Se connecter</Link>
          <Link className="button button-primary" href="/register">Créer un compte</Link>
        </div>
      </header>

      <section className="landing-hero">
        <div className="landing-hero-copy">
          <span className="landing-eyebrow">SANTÉ INFANTILE · 0–5 ANS</span>
          <h1>Chaque enfant mérite de grandir en bonne santé.</h1>
          <p>AFYAMTOTO AI utilise la technologie, l'intelligence artificielle et la surveillance sanitaire communautaire pour améliorer le suivi de la croissance, de la nutrition et de la santé des enfants de 0 à 5 ans.</p>
          <div className="landing-hero-actions">
            <a className="button button-primary" href="#solutions">Découvrir la plateforme</a>
            <Link className="button button-outline" href="/register">Rejoindre AFYAMTOTO AI</Link>
          </div>
        </div>
        <div className="landing-carousel" aria-hidden="true">
          {slides.map((s, idx) => (
            <div key={s} className={`landing-slide ${idx === i ? "is-active" : ""}`} style={{ backgroundImage: `url(${s})` }} />
          ))}
          <div className="landing-dots">
            {slides.map((_, idx) => (
              <button key={idx} className={idx === i ? "is-active" : ""} onClick={() => setI(idx)} />
            ))}
          </div>
        </div>
      </section>

      <section id="mission" className="landing-section">
        <h2>Une nouvelle approche du suivi de la santé infantile</h2>
        <div className="landing-pillars">
          <article className="landing-card"><h3>Application de santé</h3><p>Suivi individuel, historique médical, vaccination, croissance, nutrition, rappels et rendez-vous.</p></article>
          <article className="landing-card"><h3>Intelligence artificielle</h3><p>Analyse des données anthropométriques, standards OMS, identification des facteurs de risque, alertes précoces.</p></article>
          <article className="landing-card"><h3>Surveillance communautaire</h3><p>Tendances sanitaires, statistiques, zones à risque, tableaux de bord pour professionnels et responsables.</p></article>
        </div>
      </section>

      <section id="solutions" className="landing-section landing-alt">
        <h2>Les défis que nous voulons relever</h2>
        <div className="landing-challenges">
          {["Malnutrition infantile", "Retard de croissance", "Accès aux soins", "Suivi vaccinal", "Détection tardive", "Données communautaires"].map((c) => (
            <article key={c} className="landing-chip">{c}</article>
          ))}
        </div>
      </section>

      <section id="how" className="landing-section">
        <h2>Comment fonctionne AFYAMTOTO AI ?</h2>
        <ol className="landing-steps">
          <li><strong>Enregistrement</strong> — Le parent ou l'agent crée le profil de l'enfant.</li>
          <li><strong>Collecte des données</strong> — Croissance, vaccination, suivi sanitaire.</li>
          <li><strong>Analyse intelligente</strong> — La plateforme identifie les situations à surveiller.</li>
          <li><strong>Alerte et orientation</strong> — Les personnes autorisées reçoivent des alertes.</li>
        </ol>
      </section>

      <section id="impact" className="landing-section landing-alt">
        <h2>Les bénéficiaires</h2>
        <div className="landing-beneficiaries">
          {["Parents et tuteurs", "Agents de santé communautaires", "Professionnels de santé", "Administrateurs", "Responsables sanitaires"].map((b) => (
            <article key={b} className="landing-card">{b}</article>
          ))}
        </div>
        <p className="landing-note">AFYAMTOTO AI est un outil d'aide au suivi et à la décision. Il ne remplace pas le diagnostic ni la prise en charge par un professionnel de santé.</p>
      </section>

      <section className="landing-cta">
        <h2>Ensemble, construisons un avenir où chaque enfant bénéficie d'un meilleur suivi de sa santé.</h2>
        <div className="landing-hero-actions">
          <Link className="button button-primary" href="/register">Rejoindre la plateforme</Link>
          <a className="button button-outline" href="#mission">Découvrir notre mission</a>
        </div>
      </section>

      <footer id="contact" className="landing-footer">
        <div className="landing-footer-grid">
          <div><strong>AFYAMTOTO AI</strong><p>Plateforme de suivi de la santé des enfants de 0 à 5 ans.</p></div>
          <div><h4>Navigation</h4><a href="#mission">Mission</a><a href="#solutions">Solutions</a><a href="#how">Fonctionnement</a><a href="#impact">Impact</a></div>
          <div><h4>Accès</h4><Link href="/login?scope=public">Connexion</Link><Link href="/register">Inscription</Link><Link href="/setup-administrator">Initialisation</Link></div>
          <div><h4>Légal</h4><a href="#">Confidentialité</a><a href="#">Conditions</a><a href="#">Mentions légales</a></div>
        </div>
        <p className="landing-footer-copy">© 2026 AFYAMTOTO AI. Tous droits réservés.</p>
      </footer>
    </main>
  );
}

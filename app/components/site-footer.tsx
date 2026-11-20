"use client";

import Link from "next/link";

export function SiteFooter() {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="site-footer">
      <div className="footer-inner">
        <div className="footer-grid">
          <div className="footer-brand-col">
            <a className="footer-brand" href="#accueil" aria-label="AFYAMTOTO AI, accueil">
              <span className="footer-brand-mark" aria-hidden="true">
                <svg fill="none" viewBox="0 0 42 42">
                  <rect fill="currentColor" height="42" rx="14" width="42" />
                  <path
                    d="M21 31.5s-10-5.8-10-13.1a5.8 5.8 0 0 1 10-4 5.8 5.8 0 0 1 10 4c0 7.3-10 13.1-10 13.1Z"
                    fill="white"
                  />
                  <path d="M21 17v8m-4-4h8" stroke="#168A78" strokeLinecap="round" strokeWidth="2" />
                </svg>
              </span>
              <span className="footer-brand-copy">
                <span className="footer-brand-name">AFYAMTOTO <span>AI</span></span>
                <span className="footer-brand-caption">Grandir en bonne santé</span>
              </span>
            </a>
            <p className="footer-desc">Plateforme de suivi de la santé des enfants de 0 à 5 ans en RDC.</p>
          </div>

          <nav className="footer-nav" aria-label="Navigation">
            <h4>Navigation</h4>
            <ul>
              <li><Link href="#mission">Notre mission</Link></li>
              <li><Link href="#solutions">Nos solutions</Link></li>
              <li><Link href="#approche">Notre approche</Link></li>
              <li><Link href="#impact">Impact</Link></li>
              <li><Link href="/contact">Contact</Link></li>
            </ul>
          </nav>

          <nav className="footer-nav" aria-label="Accès">
            <h4>Accès</h4>
            <ul>
              <li><Link href="/login?scope=public">Connexion</Link></li>
              <li><Link href="/register">Inscription</Link></li>
              <li><Link href="/setup-administrator">Initialisation</Link></li>
            </ul>
          </nav>

          <nav className="footer-nav" aria-label="Légal">
            <h4>Légal</h4>
            <ul>
              <li><a href="/privacy">Confidentialité</a></li>
              <li><a href="/terms">Conditions</a></li>
              <li><a href="/legal">Mentions légales</a></li>
            </ul>
          </nav>

          <div className="footer-contact">
            <h4>Contact</h4>
            <address>
              <p>Kinshasa, République Démocratique du Congo</p>
              <p>
                <a href="mailto:contact@afyamtoto.ai">contact@afyamtoto.ai</a>
              </p>
            </address>
          </div>
        </div>

        <div className="footer-bottom">
          <p className="footer-copy">© {currentYear} AFYAMTOTO AI. Tous droits réservés.</p>
          <div className="footer-social" aria-label="Réseaux sociaux">
            <a href="#" aria-label="Twitter" rel="noopener noreferrer">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
            </a>
            <a href="#" aria-label="LinkedIn" rel="noopener noreferrer">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2zM4 6a2 2 0 100-4 2 2 0 000 4z"/></svg>
            </a>
            <a href="#" aria-label="Facebook" rel="noopener noreferrer">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
          </div>
        </div>
      </div>
    </footer>
  );
}
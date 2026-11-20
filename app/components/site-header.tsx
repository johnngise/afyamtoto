"use client";

import { useState } from "react";

const links = [
  { href: "#accueil", label: "Accueil" },
  { href: "#plateforme", label: "La plateforme" },
  { href: "#approche", label: "Notre approche" },
  { href: "/login?scope=public", label: "Connexion" },
];

export function SiteHeader() {
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <header className="site-header">
      <div className="header-inner">
        <a
          aria-label="AFYAMTOTO AI, accueil"
          className="brand"
          href="#accueil"
          onClick={() => setMenuOpen(false)}
        >
          <span aria-hidden="true" className="brand-mark">
            <svg fill="none" viewBox="0 0 42 42">
              <rect fill="currentColor" height="42" rx="14" width="42" />
              <path
                d="M21 31.5s-10-5.8-10-13.1a5.8 5.8 0 0 1 10-4 5.8 5.8 0 0 1 10 4c0 7.3-10 13.1-10 13.1Z"
                fill="white"
              />
              <path d="M21 17v8m-4-4h8" stroke="#168A78" strokeLinecap="round" strokeWidth="2" />
            </svg>
          </span>
          <span className="brand-copy">
            <span className="brand-name">AFYAMTOTO <span>AI</span></span>
            <span className="brand-caption">Grandir en bonne santé</span>
          </span>
        </a>

        <button
          aria-controls="primary-navigation"
          aria-expanded={menuOpen}
          aria-label={menuOpen ? "Fermer le menu" : "Ouvrir le menu"}
          className={`menu-toggle${menuOpen ? " is-open" : ""}`}
          onClick={() => setMenuOpen(!menuOpen)}
          type="button"
        >
          <span />
          <span />
        </button>

        <nav
          aria-label="Navigation principale"
          className={`primary-navigation${menuOpen ? " is-open" : ""}`}
          id="primary-navigation"
        >
          {links.map((link) => (
            <a href={link.href} key={link.href} onClick={() => setMenuOpen(false)}>
              {link.label}
            </a>
          ))}
          <a
            className="nav-cta"
            href="/register"
            onClick={() => setMenuOpen(false)}
          >
            Créer un compte <span aria-hidden="true">↗</span>
          </a>
        </nav>
      </div>
    </header>
  );
}

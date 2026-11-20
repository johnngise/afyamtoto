"use client";

import { useState, useEffect } from "react";

const links = [
  { href: "/", label: "Accueil" },
  { href: "#mission", label: "Mission" },
  { href: "#solutions", label: "Solutions" },
  { href: "#approche", label: "Approche" },
  { href: "/contact", label: "Contact" },
];

export function SiteHeader() {
  const [menuOpen, setMenuOpen] = useState(false);
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    const checkMobile = () => setIsMobile(window.innerWidth < 768);
    checkMobile();
    window.addEventListener("resize", checkMobile);
    return () => window.removeEventListener("resize", checkMobile);
  }, []);

  const closeMenu = () => setMenuOpen(false);

  return (
    <header className="site-header">
      <div className="header-inner">
        <a
          aria-label="AFYAMTOTO AI, accueil"
          className="brand"
          href="/"
          onClick={closeMenu}
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

        {isMobile && (
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
        )}

        <nav
          aria-label="Navigation principale"
          className={`primary-navigation${isMobile && menuOpen ? " is-open" : ""}`}
          id="primary-navigation"
        >
          {links.map((link) => (
            <a href={link.href} key={link.href} onClick={closeMenu}>
              {link.label}
            </a>
          ))}
          <a
            className="nav-cta"
            href="/register"
            onClick={closeMenu}
          >
            Créer un compte <span aria-hidden="true">↗</span>
          </a>
        </nav>
      </div>
    </header>
  );
}

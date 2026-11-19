import { createRoot } from "react-dom/client";
import { useEffect, useState } from "react";

type NavigationItem = {
  id: string;
  label: string;
  href: string;
};

const navigationItems: NavigationItem[] = [
  { id: "accueil", label: "Accueil", href: "/index.php" },
  { id: "plateforme", label: "La plateforme", href: "/pages/plateforme.php" },
  { id: "approche", label: "Notre approche", href: "/pages/approche.php" },
  { id: "connexion", label: "Connexion", href: "/pages/connexion.php" },
  { id: "contact", label: "En savoir plus", href: "/pages/contact.php" },
];

function SiteNavigation({
  basePath,
  currentPage,
}: {
  basePath: string;
  currentPage: string;
}) {
  const [menuOpen, setMenuOpen] = useState(false);

  useEffect(() => {
    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setMenuOpen(false);
      }
    };
    const closeOnDesktopResize = () => {
      if (window.innerWidth > 640) {
        setMenuOpen(false);
      }
    };

    document.addEventListener("keydown", closeOnEscape);
    window.addEventListener("resize", closeOnDesktopResize);

    return () => {
      document.removeEventListener("keydown", closeOnEscape);
      window.removeEventListener("resize", closeOnDesktopResize);
    };
  }, []);

  return (
    <div className="site-navigation">
      <button
        aria-controls="primary-navigation"
        aria-expanded={menuOpen}
        aria-label={menuOpen ? "Fermer le menu" : "Ouvrir le menu"}
        className={`menu-toggle${menuOpen ? " is-open" : ""}`}
        onClick={() => setMenuOpen((isOpen) => !isOpen)}
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
        {navigationItems.map((item) => (
          <a
            aria-current={item.id === currentPage ? "page" : undefined}
            className={item.id === "contact" ? "nav-cta" : undefined}
            href={`${basePath}${item.href}`}
            key={item.id}
            onClick={() => setMenuOpen(false)}
          >
            {item.label}
            {item.id === "contact" && <span aria-hidden="true">↗</span>}
          </a>
        ))}
      </nav>
    </div>
  );
}

const mount = document.querySelector<HTMLElement>("#site-navigation-root");

if (mount) {
  const { basePath = ".", currentPage = "accueil" } = mount.dataset;

  createRoot(mount).render(
    <SiteNavigation basePath={basePath} currentPage={currentPage} />,
  );
}

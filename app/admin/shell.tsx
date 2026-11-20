"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useState } from "react";
import type { PublicUser } from "@/lib/auth";

const items = [
  { href: "/admin", label: "Vue d’ensemble", icon: "▦" },
  { href: "/admin/users", label: "Utilisateurs", icon: "♙" },
  { href: "/admin/permissions", label: "Permissions", icon: "⌘" },
  { href: "/admin/content", label: "Contenus", icon: "▤" },
  { href: "/admin/settings", label: "Paramètres", icon: "⚙" },
];

export function AdminShell({ user, children }: { user: PublicUser; children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);

  async function logout() {
    await fetch("/api/v1/auth/logout", { method: "POST" });
    router.replace("/login");
    router.refresh();
  }

  return (
    <div className={`admin-app${collapsed ? " sidebar-collapsed" : ""}${mobileOpen ? " mobile-sidebar-open" : ""}`}>
      <aside className="admin-sidebar" aria-label="Navigation administrateur">
        <Link className="admin-brand" href="/admin"><span className="admin-brand-mark">A</span><span className="admin-brand-name">AFYAMTOTO <b>ADMIN</b></span></Link>
        <p className="admin-nav-caption">ESPACE DE TRAVAIL</p>
        <nav className="admin-nav">
          {items.map((item) => <Link aria-current={pathname === item.href ? "page" : undefined} className={`admin-nav-link${pathname === item.href ? " active" : ""}`} href={item.href} key={item.href} onClick={() => setMobileOpen(false)}>
            <span className="admin-nav-icon" aria-hidden="true">{item.icon}</span><span className="admin-nav-label">{item.label}</span>
          </Link>)}
        </nav>
        <div className="admin-sidebar-bottom"><span className="admin-avatar">{user.displayName.slice(0, 1).toUpperCase()}</span><span className="admin-sidebar-user"><strong>{user.displayName}</strong><small>Administrateur</small></span></div>
      </aside>
      {mobileOpen && <button aria-label="Fermer le menu" className="admin-backdrop" onClick={() => setMobileOpen(false)} type="button" />}
      <div className="admin-main">
        <header className="admin-topbar">
          <button aria-label="Ouvrir ou réduire la navigation" className="admin-menu-toggle" onClick={() => { if (window.matchMedia("(max-width: 760px)").matches) setMobileOpen((value) => !value); else setCollapsed((value) => !value); }} type="button"><span>☰</span></button>
          <div className="admin-breadcrumb"><span>Administration</span><b>/</b><strong>{items.find((item) => item.href === pathname)?.label ?? "Espace"}</strong></div>
          <div className="admin-topbar-actions"><span className="admin-topbar-name">{user.displayName}</span><button className="admin-logout" onClick={() => void logout()} type="button">Déconnexion</button></div>
        </header>
        <main className="admin-page-content">{children}</main>
      </div>
    </div>
  );
}

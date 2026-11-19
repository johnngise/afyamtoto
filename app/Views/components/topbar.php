<?php

declare(strict_types=1);

$topbarTitle = $pageTitle ?? 'Tableau de bord';
$topbarRoleLabel = $roleLabel ?? 'Espace de démonstration';
$topbarSafeTitle = htmlspecialchars($topbarTitle, ENT_QUOTES, 'UTF-8');
$topbarSafeRole = htmlspecialchars($topbarRoleLabel, ENT_QUOTES, 'UTF-8');
$topbarUserName = htmlspecialchars((string) ($authUser['display_name'] ?? 'Compte connecté'), ENT_QUOTES, 'UTF-8');
$topbarCsrfToken = htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8');

?>
<header class="dashboard-topbar">
    <button
        aria-controls="dashboard-sidebar"
        aria-expanded="false"
        aria-label="Ouvrir le menu"
        class="btn btn-secondary dashboard-menu-toggle"
        data-sidebar-toggle
        type="button"
    >
        <span aria-hidden="true">☰</span>
    </button>
    <div class="dashboard-topbar-heading">
        <p class="dashboard-topbar-eyebrow"><?= $topbarSafeRole ?></p>
        <h1><?= $topbarSafeTitle ?></h1>
    </div>
    <div class="dashboard-topbar-actions">
        <span class="dashboard-user-name"><?= $topbarUserName ?></span>
        <a aria-label="Site public" class="btn btn-outline-primary dashboard-home-link" href="<?= htmlspecialchars($basePath ?? '..', ENT_QUOTES, 'UTF-8') ?>/index.php">
            <span aria-hidden="true">⌂</span>
            <span>Site public</span>
        </a>
        <form action="<?= htmlspecialchars($basePath ?? '..', ENT_QUOTES, 'UTF-8') ?>/pages/deconnexion.php" method="post">
            <input name="_csrf" type="hidden" value="<?= $topbarCsrfToken ?>">
            <button aria-label="Fermer la session" class="btn btn-outline-primary dashboard-logout-button" data-confirm="Voulez-vous fermer votre session ?" title="Fermer la session" type="submit">
                <span aria-hidden="true">↪</span><span class="dashboard-logout-label">Quitter</span>
            </button>
        </form>
    </div>
</header>

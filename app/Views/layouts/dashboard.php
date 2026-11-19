<?php

declare(strict_types=1);

$role = $role ?? '';
$roleLabels = [
    'parent' => 'Espace parent',
    'health-worker' => 'Espace agent de santé',
    'health-professional' => 'Espace professionnel de santé',
    'health-center' => 'Espace établissement de santé',
    'health-supervisor' => 'Supervision sanitaire',
    'intelligence' => 'AfyamToto Intelligence',
    'administrator' => 'Administration',
];

if (!isset($roleLabels[$role])) {
    throw new InvalidArgumentException('Le rôle fourni pour la mise en page est invalide.');
}

$basePath = $basePath ?? '..';
$pageTitle = $pageTitle ?? 'Tableau de bord';
$pageDescription = $pageDescription ?? 'Prototype de démonstration AFYAMTOTO AI.';
$roleLabel = $roleLabels[$role];
$currentItem = $currentItem ?? 'overview';

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F7FAFC">
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — AFYAMTOTO AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/public/assets/css/design-system.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/public/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/public/assets/css/dashboard-pages.css?v=2">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/public/assets/css/child-management.css?v=1">
    <?php if ($currentItem === 'vaccinations'): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/public/assets/css/vaccinations.css?v=1">
    <?php endif; ?>
</head>
<body class="dashboard-page">
<div class="dashboard-shell" data-dashboard-shell>
    <?php require __DIR__ . '/../components/sidebar.php'; ?>
    <button aria-label="Fermer le menu" class="ds-sidebar-overlay" data-sidebar-overlay tabindex="-1" type="button"></button>
    <div class="dashboard-main">
        <?php require __DIR__ . '/../components/topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
            <?php if (is_array($notice ?? null)): ?>
                <div class="alert alert-<?= $notice['type'] === 'success' ? 'success' : 'danger' ?> dashboard-flash" role="status"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <div class="dashboard-demo-banner" role="note">
                <span aria-hidden="true">i</span>
                <?php if ($role === 'intelligence'): ?>
                    <p><strong>Démonstration fictive.</strong> Les indicateurs et noms affichés sont inventés ; aucune action de cette interface ne modifie la base de données.</p>
                <?php else: ?>
                    <p><strong>Données personnelles protégées.</strong> Les informations sont issues de la base et limitées au rôle et aux autorisations du compte connecté. Elles ne constituent pas un diagnostic.</p>
                <?php endif; ?>
            </div>
            <?= $content ?? '<div class="alert alert-info" role="status">Cette interface est un gabarit de démonstration. Aucune donnée patient réelle n’est chargée.</div>' ?>
        </main>
    </div>
</div>
<div aria-atomic="false" aria-live="polite" class="toast-container position-fixed bottom-0 end-0 p-3" data-toast-region></div>
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script defer src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/public/assets/js/ui.js"></script>
<script defer src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/public/assets/js/dashboard-pages.js"></script>
</body>
</html>

<?php

declare(strict_types=1);

$basePath = $basePath ?? '.';
$currentPage = $currentPage ?? 'accueil';
$pageTitle = $pageTitle ?? 'AFYAMTOTO AI — Grandir en bonne santé';
$pageDescription = $pageDescription ?? 'Une initiative en préparation pour accompagner le suivi préventif des enfants de 0 à 5 ans en RDC.';
$safeBasePath = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
$navigation = [
    'accueil' => ['Accueil', $safeBasePath . '/index.php'],
    'plateforme' => ['La plateforme', $safeBasePath . '/pages/plateforme.php'],
    'approche' => ['Notre approche', $safeBasePath . '/pages/approche.php'],
    'contact' => ['En savoir plus', $safeBasePath . '/pages/contact.php'],
];

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F7FAFC">
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $safeBasePath ?>/public/assets/css/site.css">
    <link rel="stylesheet" href="<?= $safeBasePath ?>/public/assets/css/design-system.css">
    <?php if ($currentPage === 'accueil'): ?>
        <link rel="stylesheet" href="<?= $safeBasePath ?>/public/assets/css/landing.css">
    <?php endif; ?>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script defer src="<?= $safeBasePath ?>/public/assets/js/ui.js"></script>
    <script defer src="<?= $safeBasePath ?>/public/assets/js/react-site.js"></script>
    <?php if ($currentPage === 'accueil'): ?>
        <script defer src="<?= $safeBasePath ?>/public/assets/js/landing.js"></script>
    <?php endif; ?>
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a aria-label="AFYAMTOTO AI, accueil" class="brand" href="<?= $navigation['accueil'][1] ?>">
            <span aria-hidden="true" class="brand-mark">✚</span>
            <span class="brand-copy"><span class="brand-name">AFYAMTOTO <span>AI</span></span><span class="brand-caption">Grandir en bonne santé</span></span>
        </a>
        <div
            data-base-path="<?= $safeBasePath ?>"
            data-current-page="<?= htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8') ?>"
            id="site-navigation-root"
        ></div>
    </div>
</header>

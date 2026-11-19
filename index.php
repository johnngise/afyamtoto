<?php

declare(strict_types=1);

$basePath = '.';
$currentPage = 'accueil';
$pageTitle = 'AFYAMTOTO AI — Grandir en bonne santé';
$pageDescription = 'Une initiative en préparation pour accompagner le suivi préventif des enfants de 0 à 5 ans en RDC.';

require __DIR__ . '/app/Views/site-header.php';

?>
<main class="landing-page">
    <?php require __DIR__ . '/app/Views/components/landing/hero.php'; ?>
    <?php require __DIR__ . '/app/Views/components/landing/features.php'; ?>
    <?php require __DIR__ . '/app/Views/components/landing/ai.php'; ?>
    <?php require __DIR__ . '/app/Views/components/landing/impact.php'; ?>
    <?php require __DIR__ . '/app/Views/components/landing/partners.php'; ?>
</main>
<?php require __DIR__ . '/app/Views/site-footer.php'; ?>

<?php

declare(strict_types=1);

$basePath = '..';
$currentPage = 'contact';
$pageTitle = 'À propos du projet — AFYAMTOTO AI';
$pageDescription = 'AFYAMTOTO AI est un projet en préparation pour accompagner le suivi préventif des enfants de 0 à 5 ans en RDC.';

require dirname(__DIR__) . '/app/Views/site-header.php';

?>
<main>
    <section class="inner-hero">
        <div class="page-shell inner-hero-content">
            <p class="eyebrow"><span aria-hidden="true" class="eyebrow-dot"></span> À propos du projet</p>
            <h1>Chaque enfant mérite de <span>bien grandir.</span></h1>
            <p class="hero-description">AFYAMTOTO AI est une initiative en développement, imaginée pour soutenir le suivi préventif des enfants de 0 à 5 ans en RDC.</p>
            <a class="button button-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/index.php">Retour à l’accueil <span aria-hidden="true">→</span></a>
        </div>
    </section>
    <section class="section-space">
        <div class="page-shell contact-layout">
            <div class="contact-copy"><p class="section-kicker">Où en est le projet ?</p><h2>Une première étape, posée avec soin.</h2><p>Cette version présente la vision d’AFYAMTOTO AI. La plateforme, les services d’analyse et le suivi de santé ne sont pas encore disponibles.</p><p>Nous partagerons plus d’informations au fur et à mesure que le projet avance et que son cadre est validé.</p></div>
            <aside class="contact-notice"><span aria-hidden="true" class="notice-symbol">✳</span><h3>Le projet est en préparation</h3><p>Il n’y a pas encore de compte, de formulaire médical, de dossier enfant ou de service de diagnostic sur ce site.</p><a class="card-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/pages/approche.php">Lire notre approche <span aria-hidden="true">→</span></a></aside>
        </div>
    </section>
    <section class="closing-section"><div class="page-shell closing-inner"><p class="section-kicker">AFYAMTOTO AI</p><h2>Grandir ensemble, en bonne santé.</h2><p>Découvrez notre vision pour accompagner les premières années de l’enfance.</p><a class="button button-dark" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/pages/plateforme.php">Découvrir la plateforme <span aria-hidden="true">→</span></a></div></section>
</main>
<?php require dirname(__DIR__) . '/app/Views/site-footer.php'; ?>

<?php

declare(strict_types=1);

$basePath = '..';
$currentPage = 'plateforme';
$pageTitle = 'La plateforme — AFYAMTOTO AI';
$pageDescription = 'Découvrez les ambitions de la plateforme AFYAMTOTO AI, un projet en préparation pour le suivi préventif des enfants en RDC.';

require dirname(__DIR__) . '/app/Views/site-header.php';

?>
<main>
    <section class="inner-hero">
        <div class="page-shell inner-hero-content">
            <p class="eyebrow"><span aria-hidden="true" class="eyebrow-dot"></span> La plateforme</p>
            <h1>Un accompagnement pensé pour <span>bien grandir.</span></h1>
            <p class="hero-description">AFYAMTOTO AI est un projet en préparation pour aider à rendre le suivi préventif des jeunes enfants plus simple et mieux organisé.</p>
            <a class="button button-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/pages/approche.php">Découvrir notre approche <span aria-hidden="true">→</span></a>
        </div>
    </section>
    <section class="section-space">
        <div class="page-shell">
            <div class="section-heading"><p class="section-kicker">Ce que nous voulons faciliter</p><h2>Des repères utiles, à chaque étape.</h2><p class="section-intro">La plateforme est encore en construction. Ses objectifs guident le développement, mais aucun service de suivi ni compte utilisateur n’est disponible pour l’instant.</p></div>
            <div class="ambition-grid">
                <article class="ambition-card"><div class="card-topline"><span class="card-icon">↗</span><span class="card-number">01</span></div><h3>Suivre la croissance</h3><p>Mettre à disposition des repères qui aident à suivre les étapes du développement de l’enfant.</p><span class="card-status">Objectif en préparation</span></article>
                <article class="ambition-card"><div class="card-topline"><span class="card-icon">▣</span><span class="card-number">02</span></div><h3>Garder le fil</h3><p>Réfléchir à des outils qui facilitent l’organisation des informations de santé préventive.</p><span class="card-status">Objectif en préparation</span></article>
                <article class="ambition-card"><div class="card-topline"><span class="card-icon">♡</span><span class="card-number">03</span></div><h3>Rendre l’information claire</h3><p>Partager des informations accessibles, conçues pour les familles et proches des réalités locales.</p><span class="card-status">Objectif en préparation</span></article>
            </div>
        </div>
    </section>
    <section class="approach-section"><div class="page-shell approach-inner"><div aria-hidden="true" class="approach-mark"><span>✚</span></div><div class="approach-copy"><p class="section-kicker">Un projet en développement</p><h2>Les enfants et les familles au centre.</h2><p>La plateforme ne remplace ni les professionnels de santé ni leurs conseils. Son périmètre et ses fonctionnalités doivent être validés avant toute utilisation réelle.</p></div><a class="approach-aside" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/pages/contact.php"><span aria-hidden="true">✳</span><span>Suivre l’évolution<br>du projet</span></a></div></section>
</main>
<?php require dirname(__DIR__) . '/app/Views/site-footer.php'; ?>

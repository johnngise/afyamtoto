<?php

declare(strict_types=1);

$basePath = '..';
$currentPage = 'approche';
$pageTitle = 'Notre approche — AFYAMTOTO AI';
$pageDescription = 'La vision et les principes d’AFYAMTOTO AI pour un accompagnement préventif des enfants, pensé avec les familles et les communautés.';

require dirname(__DIR__) . '/app/Views/site-header.php';

?>
<main>
    <section class="inner-hero">
        <div class="page-shell inner-hero-content">
            <p class="eyebrow"><span aria-hidden="true" class="eyebrow-dot"></span> Notre approche</p>
            <h1>La technologie au service de <span>l’humain.</span></h1>
            <p class="hero-description">Nous préparons le projet avec une priorité claire : soutenir les familles et les professionnels, sans remplacer le lien humain ni l’expertise médicale.</p>
            <a class="button button-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/pages/contact.php">À propos du projet <span aria-hidden="true">→</span></a>
        </div>
    </section>
    <section class="section-space">
        <div class="page-shell">
            <div class="section-heading"><p class="section-kicker">Nos principes</p><h2>Une démarche responsable et attentive.</h2><p class="section-intro">Les fonctionnalités et les règles liées au suivi de santé restent à définir et à valider avec les parties prenantes compétentes.</p></div>
            <div class="principle-grid">
                <article class="principle-card"><span class="principle-icon">♡</span><div><h3>L’enfant avant l’outil</h3><p>Le bien-être de l’enfant et la place de sa famille doivent guider chaque décision de conception.</p></div></article>
                <article class="principle-card"><span class="principle-icon">✚</span><div><h3>Le professionnel reste essentiel</h3><p>La technologie ne pose pas de diagnostic et ne remplace jamais l’écoute, l’examen ou le jugement d’un professionnel de santé.</p></div></article>
                <article class="principle-card"><span class="principle-icon">⌖</span><div><h3>Le contexte local compte</h3><p>Les besoins, les informations partagées et les conditions d’utilisation devront être confirmés avec les communautés concernées.</p></div></article>
                <article class="principle-card"><span class="principle-icon">◇</span><div><h3>Avancer avec prudence</h3><p>Aucune donnée de santé ni dossier enfant n’est collecté dans cette version de présentation.</p></div></article>
            </div>
        </div>
    </section>
    <section class="approach-section"><div class="page-shell approach-inner"><div aria-hidden="true" class="approach-mark"><span>✳</span></div><div class="approach-copy"><p class="section-kicker">Maintenant</p><h2>Le projet se construit étape par étape.</h2><p>Les règles de santé, la protection des données, le consentement et les exigences réglementaires doivent être confirmés avant toute utilisation réelle.</p></div><a class="approach-aside" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/pages/plateforme.php"><span aria-hidden="true">→</span><span>En savoir plus<br>sur la plateforme</span></a></div></section>
</main>
<?php require dirname(__DIR__) . '/app/Views/site-footer.php'; ?>

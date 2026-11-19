<?php

declare(strict_types=1);

$landingBase = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');

?>
<section aria-labelledby="partners-title" class="landing-partners-section" id="partenaires">
    <div class="page-shell">
        <div class="landing-partners-heading landing-reveal">
            <p class="landing-section-kicker">Une démarche collective</p>
            <h2 id="partners-title">Le progrès en santé se construit ensemble.</h2>
            <p>La collaboration avec les familles, les professionnels et les acteurs locaux sera essentielle. Les partenariats du projet restent à confirmer.</p>
        </div>
        <div class="landing-partner-grid" aria-label="Communautés concernées par le projet">
            <div class="landing-partner-card landing-reveal"><span aria-hidden="true">♡</span><strong>Familles et aidants</strong><small>Au cœur de la démarche</small></div>
            <div class="landing-partner-card landing-reveal"><span aria-hidden="true">✚</span><strong>Professionnels de santé</strong><small>Une expertise essentielle</small></div>
            <div class="landing-partner-card landing-reveal"><span aria-hidden="true">◎</span><strong>Communautés locales</strong><small>Des besoins à écouter</small></div>
            <div class="landing-partner-card landing-partner-card-open landing-reveal"><span aria-hidden="true">↗</span><strong>Acteurs à associer</strong><small>Partenariats à confirmer</small></div>
        </div>
        <div class="landing-partners-cta landing-reveal">
            <span>Envie d’en savoir plus sur le projet ?</span>
            <a class="btn btn-primary" href="<?= $landingBase ?>/pages/contact.php">Découvrir AFYAMTOTO AI <span aria-hidden="true">→</span></a>
        </div>
    </div>
</section>

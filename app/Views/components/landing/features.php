<?php

declare(strict_types=1);

$landingBase = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');

?>
<section aria-labelledby="vision-title" class="landing-vision section-space" id="vision">
    <div class="page-shell">
        <div class="landing-section-heading landing-reveal">
            <p class="landing-section-kicker">AFYAMTOTO AI</p>
            <h2 id="vision-title">Mieux accompagner les premières années de la vie.</h2>
            <p>AFYAMTOTO AI est une initiative numérique en préparation pour soutenir le suivi préventif des enfants de 0 à 5 ans. La plateforme veut aider les familles et les professionnels à mieux s’organiser et à partager des repères utiles, dans le respect de leur rôle.</p>
        </div>

        <div aria-labelledby="features-title" class="landing-features" id="fonctionnalites">
            <div class="landing-features-heading landing-reveal">
                <div>
                    <p class="landing-section-kicker">Une vision, plusieurs repères</p>
                    <h2 id="features-title">Pensée autour des besoins essentiels.</h2>
                </div>
                <span class="landing-demo-badge"><span aria-hidden="true"></span> Fonctionnalités en préparation</span>
            </div>
            <div class="landing-feature-grid">
                <article class="landing-feature-card landing-reveal">
                    <span aria-hidden="true" class="landing-feature-icon feature-icon-blue">
                        <svg fill="none" viewBox="0 0 32 32"><path d="M6 25V17m10 8V7m10 18V12M4 28h24" stroke="currentColor" stroke-linecap="round" stroke-width="2.2"/><circle cx="16" cy="7" r="2.5" fill="currentColor"/></svg>
                    </span>
                    <span class="landing-feature-number">01</span>
                    <h3>Suivi de croissance</h3>
                    <p>Des repères pour accompagner le développement de l’enfant, avec l’appui des professionnels.</p>
                    <a href="<?= $landingBase ?>/pages/plateforme.php">Découvrir la vision <span aria-hidden="true">→</span></a>
                </article>
                <article class="landing-feature-card landing-reveal">
                    <span aria-hidden="true" class="landing-feature-icon feature-icon-green">
                        <svg fill="none" viewBox="0 0 32 32"><rect height="21" rx="4" stroke="currentColor" stroke-width="2" width="22" x="5" y="7"/><path d="M10 5v5m12-5v5M10 16h12m-12 5h7" stroke="currentColor" stroke-linecap="round" stroke-width="2"/></svg>
                    </span>
                    <span class="landing-feature-number">02</span>
                    <h3>Prévention mieux organisée</h3>
                    <p>Une approche pour faciliter, à terme, les rappels et l’organisation du suivi préventif.</p>
                    <a href="<?= $landingBase ?>/pages/plateforme.php">Découvrir la vision <span aria-hidden="true">→</span></a>
                </article>
                <article class="landing-feature-card landing-reveal">
                    <span aria-hidden="true" class="landing-feature-icon feature-icon-orange">
                        <svg fill="none" viewBox="0 0 32 32"><path d="M16 4 27 8v8c0 7-4.6 11.6-11 14-6.4-2.4-11-7-11-14V8l11-4Z" stroke="currentColor" stroke-linejoin="round" stroke-width="2"/><path d="m11 16 3.2 3.2L21 12" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                    </span>
                    <span class="landing-feature-number">03</span>
                    <h3>Information accessible</h3>
                    <p>Des contenus clairs, conçus pour les familles et proches des réalités du contexte local.</p>
                    <a href="<?= $landingBase ?>/pages/approche.php">Lire notre approche <span aria-hidden="true">→</span></a>
                </article>
            </div>
            <p class="landing-feature-disclaimer">Ces fonctionnalités décrivent la vision du projet ; elles ne sont pas encore disponibles et ne constituent pas un service médical.</p>
        </div>
    </div>
</section>

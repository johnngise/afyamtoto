<?php

declare(strict_types=1);

$landingBase = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');

?>
<section aria-labelledby="hero-title" class="landing-hero" id="accueil">
    <div class="page-shell landing-hero-grid">
        <div class="landing-hero-copy landing-reveal">
            <p class="landing-eyebrow"><span aria-hidden="true" class="landing-eyebrow-dot"></span> Une initiative pour les enfants de 0 à 5 ans</p>
            <h1 id="hero-title">Chaque enfant mérite de <span>bien grandir.</span></h1>
            <p class="landing-hero-description">Un accompagnement de santé préventive en préparation, conçu pour rapprocher les familles et les professionnels de santé en RDC.</p>
            <div class="landing-hero-actions">
                <a class="btn btn-primary landing-primary-action" href="#vision">Découvrir AFYAMTOTO AI <span aria-hidden="true">→</span></a>
                <a class="landing-secondary-action" href="<?= $landingBase ?>/pages/approche.php">Notre approche <span aria-hidden="true">↗</span></a>
            </div>
            <div class="landing-hero-assurance">
                <span aria-hidden="true" class="landing-assurance-icon">✓</span>
                <span>Une initiative en construction, pensée avec responsabilité.</span>
            </div>
        </div>

        <div aria-label="Illustration d’un parent prenant soin de son enfant" class="landing-hero-visual landing-reveal" role="img">
            <div aria-hidden="true" class="landing-visual-orbit landing-visual-orbit-one"></div>
            <div aria-hidden="true" class="landing-visual-orbit landing-visual-orbit-two"></div>
            <div aria-hidden="true" class="landing-visual-sun"></div>
            <svg aria-hidden="true" class="landing-family-art" fill="none" viewBox="0 0 540 500">
                <path d="M75 433c19-92 83-145 193-145s174 53 197 145H75Z" fill="#EAF5FB"/>
                <path d="M112 433c19-62 76-99 156-99s137 37 156 99H112Z" fill="#42B883"/>
                <path d="M188 258c0-64 34-111 82-111s82 47 82 111v91H188v-91Z" fill="#D99568"/>
                <path d="M181 274c-12-47-2-99 27-128 36-36 104-31 137 6 25 29 32 78 14 122-8-35-23-61-48-81-32 26-75 41-130 38v43Z" fill="#263746"/>
                <path d="M224 273h1m91 0h1" stroke="#263746" stroke-linecap="round" stroke-width="8"/>
                <path d="M240 307c9 12 23 18 42 18s33-6 42-18" stroke="#9E573F" stroke-linecap="round" stroke-width="4"/>
                <path d="M152 433c-3-69 24-117 83-145l35 45 37-45c58 28 86 76 82 145H152Z" fill="#F7FAFC"/>
                <path d="m235 289 35 44-31 44-36-80 32-8Zm70 0-35 44 31 44 36-80-32-8Z" fill="#DCECF5"/>
                <path d="M270 333v94" stroke="#49A9D6" stroke-linecap="round" stroke-width="4"/>
                <path d="M368 251c0 38-25 67-56 67s-56-29-56-67c0-39 25-68 56-68s56 29 56 68Z" fill="#F0BE91"/>
                <path d="M258 247c-3-33 10-56 31-67 29-16 66-4 78 25 7 17 7 37-1 54-9-18-22-31-40-39-17 16-40 25-68 27Z" fill="#263746"/>
                <path d="M278 256h1m41 0h1" stroke="#263746" stroke-linecap="round" stroke-width="5"/>
                <path d="M287 277c5 7 12 10 21 10s16-3 21-10" stroke="#9E573F" stroke-linecap="round" stroke-width="3"/>
                <path d="M218 433c7-51 26-83 58-99l36 30 35-30c33 16 52 48 59 99H218Z" fill="#F4A340"/>
                <path d="M306 338c-16 20-30 38-38 62 25 11 47 14 69 14 7-25 1-51-31-76Z" fill="#F7FAFC"/>
                <circle cx="414" cy="142" r="31" fill="#E9F7F0"/>
                <path d="M414 127v30m-15-15h30" stroke="#21865B" stroke-linecap="round" stroke-width="4"/>
                <path d="m121 208 5 13 13 5-13 5-5 13-5-13-13-5 13-5 5-13Zm325 98 4 10 10 4-10 4-4 10-4-10-10-4 10-4 4-10Z" fill="#F4A340"/>
                <circle cx="139" cy="315" r="5" fill="#49A9D6"/><circle cx="415" cy="365" r="5" fill="#49A9D6"/>
            </svg>
            <div class="landing-float-card landing-float-card-top">
                <span aria-hidden="true" class="landing-float-icon landing-float-icon-blue">✚</span>
                <span><strong>Le bien-être d’abord</strong><small>La santé, avec humanité</small></span>
            </div>
            <div class="landing-float-card landing-float-card-bottom">
                <span aria-hidden="true" class="landing-float-icon landing-float-icon-green">♡</span>
                <span><strong>De 0 à 5 ans</strong><small>Les premières années</small></span>
            </div>
            <span class="landing-visual-caption">Une vision pour les familles et les communautés</span>
        </div>
    </div>
    <div class="page-shell landing-hero-bottom" aria-hidden="true">
        <span>Grandir ensemble</span><span class="landing-bottom-line"></span><span>RDC</span>
    </div>
</section>

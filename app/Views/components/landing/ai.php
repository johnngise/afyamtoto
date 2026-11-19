<?php

declare(strict_types=1);

$landingBase = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');

?>
<section aria-labelledby="ai-title" class="landing-ai-section" id="intelligence">
    <div class="page-shell landing-ai-grid">
        <div class="landing-ai-visual landing-reveal" aria-hidden="true">
            <div class="landing-ai-orbit landing-ai-orbit-one"></div>
            <div class="landing-ai-orbit landing-ai-orbit-two"></div>
            <div class="landing-ai-core"><span>AI</span><span class="landing-ai-core-pulse"></span></div>
            <span class="landing-ai-node ai-node-one">Données encadrées</span>
            <span class="landing-ai-node ai-node-two">Repères utiles</span>
            <span class="landing-ai-node ai-node-three">Décision humaine</span>
            <svg class="landing-ai-lines" fill="none" viewBox="0 0 460 390"><path d="M230 195 104 92M230 195l126-103M230 195l-124 112M230 195l126 112" stroke="#49A9D6" stroke-dasharray="5 7" stroke-opacity=".48" stroke-width="2"/></svg>
        </div>
        <div class="landing-ai-copy landing-reveal">
            <p class="landing-section-kicker">L’intelligence, avec discernement</p>
            <h2 id="ai-title">L’IA peut éclairer. L’humain décide.</h2>
            <p>Dans AFYAMTOTO AI, l’intelligence artificielle est envisagée comme un outil d’aide à l’organisation de l’information. Elle ne pose pas de diagnostic, ne remplace pas un professionnel de santé et ne décide jamais à sa place.</p>
            <ul class="landing-ai-principles">
                <li><span aria-hidden="true">✓</span> Un rôle d’assistance clairement délimité</li>
                <li><span aria-hidden="true">✓</span> Une évaluation par des professionnels compétents</li>
                <li><span aria-hidden="true">✓</span> Des règles de protection à valider avant tout usage réel</li>
            </ul>
            <button class="btn btn-outline-primary landing-ai-button" data-bs-target="#aiDetailsModal" data-bs-toggle="modal" type="button">Comprendre notre approche <span aria-hidden="true">↗</span></button>
        </div>
    </div>
</section>

<div aria-hidden="true" aria-labelledby="aiDetailsTitle" class="modal fade" id="aiDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content landing-modal">
            <div class="modal-header">
                <div><p class="landing-section-kicker mb-2">Notre responsabilité</p><h2 class="modal-title" id="aiDetailsTitle">Une aide, jamais un diagnostic.</h2></div>
                <button aria-label="Fermer" class="btn-close" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body">
                <p>L’usage de l’IA dans un contexte de santé demande des règles claires, une validation compétente et des garanties adaptées. Ces règles restent à définir avant toute mise à disposition du service.</p>
                <p class="landing-modal-note"><strong>À retenir :</strong> cette présentation n’analyse aucun dossier médical et ne fournit aucun conseil personnalisé.</p>
            </div>
            <div class="modal-footer"><a class="btn btn-primary" href="<?= $landingBase ?>/pages/approche.php">Lire nos principes</a><button class="btn btn-light" data-bs-dismiss="modal" type="button">Fermer</button></div>
        </div>
    </div>
</div>

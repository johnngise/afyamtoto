<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

?>
<div class="dashboard-page-content" data-dashboard-page>
    <section aria-labelledby="intelligence-welcome" class="dashboard-welcome" id="overview">
        <div><p class="dashboard-eyebrow">Console analytique · accès administrateur</p><h2 id="intelligence-welcome">AfyamToto Intelligence</h2><p>Visualisation illustrative sur un jeu de données entièrement inventé.</p></div>
        <button class="btn btn-secondary" data-toast-message="Cette vue utilise uniquement des indicateurs fictifs, sans carte ni localisation réelle." type="button"><span aria-hidden="true">i</span> À propos des données</button>
    </section>

    <section aria-label="Indicateurs fictifs" class="dashboard-stat-grid">
        <?php foreach ($dashboardData['stats'] as $index => $stat): ?>
            <article class="dashboard-stat-card"><span aria-hidden="true" class="dashboard-stat-icon dashboard-stat-icon-<?= $index + 1 ?>"><?= ['▥', '◎', '!'][$index] ?></span><p><?= $e($stat['label']) ?></p><strong><?= $e($stat['value']) ?></strong><small><?= $e($stat['note']) ?></small></article>
        <?php endforeach; ?>
    </section>

    <section aria-labelledby="intelligence-indicators-title" class="dashboard-section" id="indicators">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Tendance fictive</p><h2 id="intelligence-indicators-title">Indicateurs de démonstration</h2><p>Ces valeurs ne décrivent aucune zone ou population réelle.</p></div><button class="btn btn-outline-primary" data-toast-message="Export désactivé : les indicateurs sont fictifs." type="button">Exporter la démo</button></div>
        <div class="dashboard-card dashboard-chart-card">
            <div class="dashboard-chart-heading"><div><strong>Indice illustratif</strong><small>Aucune projection ou interprétation clinique</small></div><div aria-label="Période du graphique" class="dashboard-chart-switch"><button aria-pressed="true" data-chart-range="week" type="button">8 périodes</button><button aria-pressed="false" data-chart-range="month" type="button">12 périodes</button></div></div>
            <div class="dashboard-chart" role="img" aria-label="Graphique indicatif généré à partir de valeurs fictives"><svg preserveAspectRatio="none" viewBox="0 0 550 120"><path class="dashboard-chart-gridline" d="M20 25H540M20 60H540M20 95H540"></path><polyline class="dashboard-chart-line dashboard-chart-line-green" data-chart-line data-points-week="<?= $e($dashboardData['indicatorPoints']['week']) ?>" data-points-month="<?= $e($dashboardData['indicatorPoints']['month']) ?>" points="<?= $e($dashboardData['indicatorPoints']['week']) ?>"></polyline></svg></div>
            <div class="dashboard-chart-axis"><span>Début</span><span data-chart-period-label>8 périodes fictives</span><span>Maintenant</span></div>
        </div>
    </section>

    <section aria-labelledby="intelligence-alerts-title" class="dashboard-section" id="alerts">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Exemples non cliniques</p><h2 id="intelligence-alerts-title">Éléments à examiner</h2><p>Ce ne sont ni des alertes médicales ni des diagnostics.</p></div><button class="dashboard-icon-button" data-toast-message="Toutes les alertes listées sont des exemples inventés." type="button" aria-label="Information sur les alertes">i</button></div>
        <div class="dashboard-alert-grid">
            <?php foreach ($dashboardData['alerts'] as $alert): ?>
                <article class="dashboard-alert-card"><span aria-hidden="true">!</span><div><strong><?= $e($alert['title']) ?></strong><p><?= $e($alert['zone']) ?> · <?= $e($alert['priority']) ?></p></div><button aria-label="Examiner l’exemple" class="dashboard-icon-button" data-toast-message="Exemple consulté : aucune action clinique n’est déclenchée." type="button">→</button></article>
            <?php endforeach; ?>
        </div>
    </section>

    <section aria-labelledby="intelligence-zones-title" class="dashboard-section" id="health-zones">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Schéma abstrait</p><h2 id="intelligence-zones-title">Zones fictives</h2><p>Aucune coordonnée ni carte géographique réelle.</p></div></div>
        <div class="dashboard-zone-layout">
            <div aria-label="Illustration abstraite de quatre zones fictives" class="dashboard-demo-map">
                <div class="dashboard-map-block map-block-one">Zone A</div><div class="dashboard-map-block map-block-two">Zone B</div><div class="dashboard-map-block map-block-three">Zone C</div><div class="dashboard-map-block map-block-four">Zone D</div>
                <span>Carte abstraite · sans géolocalisation</span>
            </div>
            <div class="dashboard-card dashboard-zone-list">
                <?php foreach ($dashboardData['zones'] as $zone): ?>
                    <article><span aria-hidden="true" class="dashboard-zone-dot"></span><div><strong><?= $e($zone['name']) ?></strong><small><?= $e($zone['records']) ?> dossiers démo.</small></div><span class="dashboard-zone-coverage"><?= $e($zone['coverage']) ?></span></article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section aria-labelledby="intelligence-analytics-title" class="dashboard-section" id="analytics">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Analyse</p><h2 id="intelligence-analytics-title">Cadre de démonstration</h2></div></div>
        <div class="dashboard-card dashboard-empty-inline"><span aria-hidden="true">✳</span><div><strong>Aucune analyse automatisée n’est active</strong><p>Les indicateurs, règles et seuils nécessitent une validation sanitaire avant toute utilisation.</p></div></div>
    </section>
</div>

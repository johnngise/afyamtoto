<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="dashboard-page-content">
    <section class="dashboard-welcome" id="overview">
        <div><p class="dashboard-eyebrow">Pilotage sanitaire</p><h2>Bonjour, <?= $e($dashboardData['displayName']) ?></h2><p>Vue agrégée des centres affectés. Aucun nom d’enfant, dossier individuel ou note médicale n’est accessible dans cet espace.</p></div>
    </section>
    <section aria-label="Indicateurs agrégés de supervision" class="dashboard-stat-grid">
        <?php foreach ($dashboardData['stats'] as $index => $stat): ?><article class="dashboard-stat-card"><span aria-hidden="true" class="dashboard-stat-icon dashboard-stat-icon-<?= ($index % 3) + 1 ?>"><?= ['⌖', '✓', '⚑', '✚'][$index] ?></span><p><?= $e($stat['label']) ?></p><strong><?= $e($stat['value']) ?></strong><small>Établissements affectés</small></article><?php endforeach; ?>
    </section>
    <section aria-labelledby="supervisor-centers-title" class="dashboard-section" id="centers">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Comparaison géographique</p><h2 id="supervisor-centers-title">Activité agrégée par centre</h2><p>Les lignes avec moins de cinq enfants sont masquées pour limiter les risques de réidentification.</p></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Centre / zone</th><th>Enfants référés</th><th>Orientations totales</th><th>Orientations ouvertes</th><th>Mesures de croissance</th><th>Dépistages nutritionnels</th><th>Doses enregistrées</th></tr></thead><tbody>
            <?php foreach ($dashboardData['centers'] as $center): ?>
                <tr><td><strong><?= $e($center['name']) ?></strong><span class="dashboard-table-subtitle"><?= $e(implode(', ', array_filter([$center['city'], $center['province']]))) ?></span></td>
                    <?php if ($center['privacy_suppressed']): ?><td colspan="6"><span class="dashboard-pill">Données masquées (cohorte inférieure à 5)</span></td>
                    <?php else: ?><td><?= (int) $center['referred_children'] ?></td><td><?= (int) $center['referrals_total'] ?></td><td><?= (int) $center['referrals_open'] ?></td><td><?= (int) $center['growth_measurements'] ?></td><td><?= (int) $center['nutrition_screenings'] ?></td><td><?= (int) $center['vaccination_doses'] ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if ($dashboardData['centers'] === []): ?><tr><td colspan="7">Aucun centre n’est affecté à ce compte. Un administrateur doit configurer l’affectation.</td></tr><?php endif; ?>
        </tbody></table></div>
        <p class="small text-secondary mt-2 mb-0">Les doses indiquent des enregistrements administrés; aucun calendrier de référence actif ne permet de calculer une couverture vaccinale fiable.</p>
    </section>
    <section class="dashboard-section dashboard-two-column" id="indicators">
        <div class="dashboard-section-panel"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Évolution temporelle</p><h2>Orientations par mois</h2><p>Les périodes comptant moins de cinq enfants sont omises.</p></div></div>
            <?php foreach ($dashboardData['monthlyTrends'] as $trend): ?><article class="dashboard-report-row"><span><?= $e($trend['period']) ?></span><span><?= (int) $trend['referred_children'] ?> enfants référés</span><strong><?= (int) $trend['referrals_total'] ?> orientations</strong></article><?php endforeach; ?>
            <?php if ($dashboardData['monthlyTrends'] === []): ?><p class="text-secondary mb-0">Aucune période récente n’atteint le seuil de confidentialité.</p><?php endif; ?>
        </div>
        <div class="dashboard-section-panel"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Supervision terrain</p><h2>Activité de référencement des agents</h2><p>Les lignes avec moins de cinq enfants sont omises; aucun détail patient n’est présenté.</p></div></div>
            <?php foreach ($dashboardData['workerActivity'] as $worker): ?><article class="dashboard-report-row"><span><?= $e($worker['display_name']) ?></span><span><?= (int) $worker['referred_children'] ?> enfants concernés</span><strong><?= (int) $worker['referrals_total'] ?> orientations</strong></article><?php endforeach; ?>
            <?php if ($dashboardData['workerActivity'] === []): ?><p class="text-secondary mb-0">Aucune activité d’agent ne dépasse le seuil d’agrégation.</p><?php endif; ?>
            <p class="small text-secondary mt-3 mb-0">Les visites communautaires ne sont pas encore reliées à une zone sanitaire; elles ne sont donc pas comparées ici.</p>
        </div>
    </section>
    <section class="dashboard-section dashboard-empty-inline" role="note"><span aria-hidden="true">i</span><div><strong>Indicateurs de suivi, pas de diagnostic</strong><p>Les statistiques servent à organiser la supervision. Elles ne déterminent pas une situation clinique et ne remplacent pas l’évaluation des professionnels.</p></div></section>
</div>

<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$severityLabels = ['low' => 'Faible', 'medium' => 'Modérée', 'high' => 'Élevée', 'critical' => 'Critique'];
$statusLabels = [
    'pending_review' => 'À examiner',
    'under_review' => 'En cours',
    'referred' => 'Orientée',
    'resolved' => 'Résolue',
    'dismissed' => 'Écartée',
];
$referralStatusLabels = ['awaiting_follow_up' => 'À évaluer', 'not_attended' => 'Non confirmé'];
$referralPriorityLabels = ['routine' => 'Courante', 'prompt' => 'Rapide', 'emergency' => 'Urgence signalée'];
$observationTypeLabels = [
    'growth_review' => 'Revue de croissance',
    'nutrition_review' => 'Évaluation nutritionnelle',
    'clinical_observation' => 'Observation professionnelle',
    'care_plan' => 'Plan de prise en charge',
];
$growthByChild = [];
foreach ($dashboardData['growthRecords'] as $record) {
    if ($record['weight_kg'] !== null) {
        $growthByChild[(int) $record['child_id']]['name'] = $record['first_name'] . ' ' . $record['last_name'];
        $growthByChild[(int) $record['child_id']]['records'][] = $record;
    }
}
$growthSeries = [];
foreach ($growthByChild as $childId => $childSeries) {
    $records = array_reverse(array_slice($childSeries['records'], 0, 10));
    if (count($records) < 2) {
        continue;
    }
    $weights = array_map(static fn (array $record): float => (float) $record['weight_kg'], $records);
    $minimum = min($weights);
    $range = max($weights) - $minimum;
    $range = $range > 0 ? $range : 1;
    $points = [];
    foreach ($weights as $index => $weight) {
        $x = 25 + ($index * 500 / (count($weights) - 1));
        $y = 100 - ((($weight - $minimum) / $range) * 70 + 15);
        $points[] = number_format($x, 1, '.', '') . ',' . number_format($y, 1, '.', '');
    }
    $growthSeries[] = [
        'name' => $childSeries['name'],
        'records' => $records,
        'points' => implode(' ', $points),
    ];
}

?>
<div class="dashboard-page-content">
    <section class="dashboard-welcome" id="overview">
        <div><p class="dashboard-eyebrow">Espace infirmier</p><h2>Bonjour, <?= $e($dashboardData['displayName']) ?></h2><p>Seuls les dossiers associés à vos centres et à un consentement actif sont accessibles.</p></div>
    </section>
    <section aria-label="Résumé des accès et alertes" class="dashboard-stat-grid">
        <?php foreach ($dashboardData['stats'] as $index => $stat): ?>
            <article class="dashboard-stat-card"><span aria-hidden="true" class="dashboard-stat-icon dashboard-stat-icon-<?= $index + 1 ?>"><?= ['♙', '!'][$index] ?></span><p><?= $e($stat['label']) ?></p><strong><?= $e($stat['value']) ?></strong><small>Données autorisées de la base</small></article>
        <?php endforeach; ?>
    </section>
    <section aria-labelledby="professional-alerts-title" class="dashboard-section" id="alerts">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Suivi professionnel</p><h2 id="professional-alerts-title">Alertes sanitaires accessibles</h2><p>Une alerte signale une demande de revue ; elle ne constitue pas un diagnostic.</p></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Enfant / centre</th><th>Signalement</th><th>Niveau</th><th>Statut</th><th>Revue</th></tr></thead><tbody>
            <?php foreach ($dashboardData['alerts'] as $alert): ?>
                <?php $severityClass = is_string($alert['severity']) && array_key_exists($alert['severity'], $severityLabels) ? $alert['severity'] : 'unknown'; ?>
                <tr><td><strong><?= $e($alert['first_name'] . ' ' . $alert['last_name']) ?></strong><span class="dashboard-table-subtitle"><?= $e($alert['health_center_name']) ?></span></td><td><?= $e($alert['description']) ?></td><td><span class="dashboard-severity is-<?= $e($severityClass) ?>"><?= $e($severityLabels[$alert['severity']] ?? $alert['severity']) ?></span></td><td><span class="dashboard-severity dashboard-alert-status"><?= $e($statusLabels[$alert['status']] ?? $alert['status']) ?></span></td><td>
                    <?php if (in_array($alert['status'], ['pending_review', 'under_review'], true)): ?><form action="../pages/espace-professionnel.php#alerts" class="d-grid gap-2" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="review_alert"><input name="alert_id" type="hidden" value="<?= (int) $alert['id'] ?>"><select aria-label="Décision de revue" class="form-select form-select-sm" name="status" required><option value="under_review">En cours de revue</option><option value="referred">Orientation recommandée</option><option value="resolved">Résolue après évaluation</option><option value="dismissed">Écartée après revue</option></select><textarea aria-label="Notes de revue" class="form-control form-control-sm" maxlength="1000" name="review_notes" placeholder="Observations professionnelles" rows="2"></textarea><button class="btn btn-outline-primary btn-sm" type="submit">Enregistrer la revue</button></form><?php else: ?><?= $alert['review_notes'] ? $e($alert['review_notes']) : $e($alert['created_at_utc']) ?><?php endif; ?>
                </td></tr>
            <?php endforeach; ?>
            <?php if ($dashboardData['alerts'] === []): ?><tr><td colspan="5">Aucune alerte autorisée à afficher.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section aria-labelledby="professional-referrals-title" class="dashboard-section" id="referrals">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Évaluation professionnelle</p><h2 id="professional-referrals-title">Orientations reçues</h2><p>Confirmez la prise en charge et documentez l’évaluation dans le dossier autorisé.</p></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Enfant</th><th>Situation transmise</th><th>Priorité</th><th>Centre</th><th>Évaluation</th></tr></thead><tbody>
            <?php foreach ($dashboardData['referrals'] as $referral): ?><tr><td><strong><?= $e($referral['first_name'] . ' ' . $referral['last_name']) ?></strong><span class="dashboard-table-subtitle">Référé le <?= $e($referral['referred_at_utc']) ?></span></td><td><?= $e($referral['concern_description']) ?></td><td><?= $e($referralPriorityLabels[$referral['priority']] ?? 'À examiner') ?></td><td><?= $e($referral['health_center_name']) ?></td><td><form action="../pages/espace-professionnel.php#referrals" class="d-grid gap-2" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="review_referral"><input name="referral_id" type="hidden" value="<?= (int) $referral['id'] ?>"><select aria-label="Résultat de prise en charge" class="form-select form-select-sm" name="outcome" required><option value="attended">Évaluation effectuée</option><option value="not_attended">Suivi à poursuivre</option></select><textarea aria-label="Observation professionnelle" class="form-control form-control-sm" maxlength="1000" name="professional_notes" placeholder="Observation ou plan de prise en charge" rows="2" required></textarea><button class="btn btn-outline-primary btn-sm" type="submit">Enregistrer</button></form></td></tr><?php endforeach; ?>
            <?php if ($dashboardData['referrals'] === []): ?><tr><td colspan="5">Aucune orientation en attente dans vos centres.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section class="dashboard-section dashboard-two-column" id="indicators">
        <div class="dashboard-section-panel"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Suivi des mesures</p><h2>Tendances de poids enregistrées</h2></div></div>
            <?php foreach ($growthSeries as $series): ?><article class="dashboard-list-item"><span class="dashboard-list-icon" aria-hidden="true">↗</span><div><strong><?= $e($series['name']) ?></strong><div class="dashboard-chart" role="img" aria-label="Évolution relative des poids enregistrés pour <?= $e($series['name']) ?>"><svg preserveAspectRatio="none" viewBox="0 0 550 120"><path class="dashboard-chart-gridline" d="M20 25H540M20 60H540M20 95H540"></path><polyline class="dashboard-chart-line" points="<?= $e($series['points']) ?>"></polyline></svg></div><p><?= count($series['records']) ?> mesures récentes, de <?= $e($series['records'][0]['measured_at_utc']) ?> à <?= $e($series['records'][count($series['records']) - 1]['measured_at_utc']) ?></p></div></article><?php endforeach; ?>
            <?php if ($growthSeries === []): ?><p class="text-secondary mb-0">Au moins deux mesures de poids sont nécessaires pour afficher une tendance.</p><?php endif; ?>
            <div class="table-responsive mt-3"><table class="table dashboard-data-table mb-0"><thead><tr><th>Enfant</th><th>Date</th><th>Poids</th><th>Taille / longueur</th><th>Périmètre brachial</th></tr></thead><tbody><?php foreach ($dashboardData['growthRecords'] as $record): ?><tr><td><?= $e($record['first_name'] . ' ' . $record['last_name']) ?></td><td><?= $e($record['measured_at_utc']) ?></td><td><?= $record['weight_kg'] !== null ? $e($record['weight_kg']) . ' kg' : '—' ?></td><td><?= $record['length_height_cm'] !== null ? $e($record['length_height_cm']) . ' cm' : '—' ?></td><td><?= $record['muac_cm'] !== null ? $e($record['muac_cm']) . ' cm' : '—' ?></td></tr><?php endforeach; ?><?php if ($dashboardData['growthRecords'] === []): ?><tr><td colspan="5">Aucune mesure accessible.</td></tr><?php endif; ?></tbody></table></div>
            <p class="small text-secondary mt-2 mb-0">Visualisation des valeurs brutes uniquement, sans courbes de référence ni seuil d’interprétation.</p>
        </div>
        <div class="dashboard-section-panel"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Dépistage descriptif</p><h2>Observations nutritionnelles</h2></div></div>
            <?php foreach ($dashboardData['nutritionRecords'] as $record): ?><article class="dashboard-list-item"><span class="dashboard-list-icon" aria-hidden="true">♡</span><div><strong><?= $e($record['first_name'] . ' ' . $record['last_name']) ?> · <?= $e($record['record_date']) ?></strong><p><?= $record['feeding_type'] ? 'Alimentation : ' . $e($record['feeding_type']) . ' · ' : '' ?><?= $e($record['observations']) ?></p></div></article><?php endforeach; ?>
            <?php if ($dashboardData['nutritionRecords'] === []): ?><p class="text-secondary mb-0">Aucune observation nutritionnelle autorisée.</p><?php endif; ?>
        </div>
    </section>
    <section aria-labelledby="professional-observations-title" class="dashboard-section" id="observations">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Dossier médical</p><h2 id="professional-observations-title">Observations professionnelles</h2><p>Les notes sont associées à une alerte ou une orientation et attribuées à leur auteur.</p></div></div>
        <?php if ($dashboardData['observationContexts'] !== []): ?><form action="../pages/espace-professionnel.php#observations" class="dashboard-section-panel row g-3" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="add_observation">
            <div class="col-12"><label class="form-label" for="observation-context">Dossier autorisé</label><select class="form-select" id="observation-context" name="context" required><?php foreach ($dashboardData['observationContexts'] as $context): ?><option value="<?= $e($context['context_type'] . ':' . $context['context_id']) ?>"><?= $e($context['first_name'] . ' ' . $context['last_name'] . ' — ' . $context['health_center_name'] . ' — ' . ($context['context_type'] === 'alert' ? 'Alerte' : 'Orientation')) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="observation-type">Type</label><select class="form-select" id="observation-type" name="observation_type" required><?php foreach ($observationTypeLabels as $code => $label): ?><option value="<?= $e($code) ?>"><?= $e($label) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-8"><label class="form-label" for="observation-content">Observation ou plan</label><textarea class="form-control" id="observation-content" maxlength="4000" name="content" required rows="3"></textarea></div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Ajouter au dossier</button></div>
        </form><?php else: ?><div class="dashboard-empty-inline"><span aria-hidden="true">i</span><div><strong>Aucun dossier accessible</strong><p>Les observations sont disponibles uniquement pour une alerte ou orientation d’un centre affecté et avec consentement actif.</p></div></div><?php endif; ?>
        <div class="dashboard-section-panel mt-3"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Historique</p><h3 class="h6 mb-0">Notes enregistrées</h3></div></div>
            <?php foreach ($dashboardData['professionalObservations'] as $observation): ?><article class="dashboard-list-item"><span class="dashboard-list-icon" aria-hidden="true">✎</span><div><strong><?= $e($observation['first_name'] . ' ' . $observation['last_name']) ?> · <?= $e($observationTypeLabels[$observation['observation_type']] ?? 'Observation') ?></strong><p><?= nl2br($e($observation['content'])) ?></p><small><?= $e($observation['author_name']) ?> · <?= $e($observation['health_center_name']) ?> · <?= $e($observation['created_at_utc']) ?></small></div></article><?php endforeach; ?>
            <?php if ($dashboardData['professionalObservations'] === []): ?><p class="text-secondary mb-0">Aucune observation professionnelle enregistrée.</p><?php endif; ?>
        </div>
    </section>
    <section class="dashboard-section dashboard-section-panel"><h2 class="h4">Vaccinations</h2><p>Le formulaire existant permet d’enregistrer une dose vérifiée, avec contrôle du consentement et de l’accès au dossier.</p><a class="btn btn-outline-primary" href="../pages/vaccinations.php">Ouvrir les vaccinations</a></section>
</div>

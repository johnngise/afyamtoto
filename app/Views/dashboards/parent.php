<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$consentLabels = ['granted' => 'Partage autorisé', 'pending' => 'Consentement en attente', 'withdrawn' => 'Partage retiré'];
$consentedChildren = array_values(array_filter($dashboardData['children'], static fn (array $child): bool => $child['consent_status'] === 'granted'));
$requestTypeLabels = [
    'consultation' => 'Consultation générale',
    'vaccination_follow_up' => 'Suivi vaccinal',
    'growth_follow_up' => 'Suivi de croissance',
    'nutrition_consultation' => 'Consultation nutritionnelle',
];
$requestStatusLabels = ['pending' => 'À confirmer par téléphone', 'confirmed' => 'Confirmée', 'declined' => 'Refusée', 'cancelled' => 'Annulée'];
$localToday = (new DateTimeImmutable('today', new DateTimeZone('Africa/Lubumbashi')))->format('Y-m-d');

?>
<div class="dashboard-page-content">
    <section class="dashboard-welcome" id="overview">
        <div><p class="dashboard-eyebrow">Espace famille</p><h2>Bonjour, <?= $e($dashboardData['displayName']) ?></h2><p>Consultez les profils associés à votre compte et les suivis auxquels vous avez consenti.</p></div>
        <a class="btn btn-primary" href="<?= $e($basePath) ?>/pages/gestion-enfants.php">＋ Gérer les profils</a>
    </section>
    <section aria-label="Résumé du compte" class="dashboard-stat-grid">
        <?php foreach ($dashboardData['stats'] as $index => $stat): ?>
            <article class="dashboard-stat-card"><span aria-hidden="true" class="dashboard-stat-icon dashboard-stat-icon-<?= $index + 1 ?>"><?= ['♡', '↗', '✚'][$index] ?></span><p><?= $e($stat['label']) ?></p><strong><?= $e($stat['value']) ?></strong><small>Données actuellement enregistrées</small></article>
        <?php endforeach; ?>
    </section>
    <section aria-labelledby="children-title" class="dashboard-section" id="children">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Profils liés à votre compte</p><h2 id="children-title">Mes enfants</h2><p>Les données d’un autre parent ne sont jamais affichées.</p></div><a class="btn btn-outline-primary" href="<?= $e($basePath) ?>/pages/gestion-enfants.php">Ajouter ou modifier</a></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Profil</th><th>Date de naissance</th><th>Consentement de partage</th><th>Dernière mesure</th></tr></thead><tbody>
            <?php foreach ($dashboardData['children'] as $child): ?>
                <tr><td><strong><?= $e($child['first_name'] . ' ' . $child['last_name']) ?></strong></td><td><?= $e($child['date_of_birth']) ?></td><td><?= $e($consentLabels[$child['consent_status']] ?? 'Non défini') ?></td><td><?= $child['consent_status'] === 'granted' ? $e($child['last_measurement'] ?? 'Aucune') : 'Non consultable sans consentement' ?></td></tr>
            <?php endforeach; ?>
            <?php if ($dashboardData['children'] === []): ?><tr><td colspan="4">Aucun enfant n’est encore associé à votre compte.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section aria-labelledby="growth-title" class="dashboard-section" id="growth">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Historique sanitaire</p><h2 id="growth-title">Suivi de croissance</h2><p>Mesures enregistrées par le personnel de santé pour les profils dont le partage est autorisé.</p></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Enfant</th><th>Date</th><th>Poids</th><th>Taille / longueur</th><th>Périmètre brachial</th><th>Saisie par</th></tr></thead><tbody>
            <?php foreach ($dashboardData['measurements'] as $measurement): ?>
                <tr><td><strong><?= $e($measurement['first_name'] . ' ' . $measurement['last_name']) ?></strong></td><td><?= $e($measurement['measured_at_utc']) ?></td><td><?= $measurement['weight_kg'] !== null ? $e($measurement['weight_kg']) . ' kg' : '—' ?></td><td><?= $measurement['length_height_cm'] !== null ? $e($measurement['length_height_cm']) . ' cm (' . ($measurement['length_height_type'] === 'length' ? 'longueur' : 'taille') . ')' : '—' ?></td><td><?= $measurement['muac_cm'] !== null ? $e($measurement['muac_cm']) . ' cm' : '—' ?></td><td><?= $measurement['recorded_by_type'] === 'parent' ? 'Vous' : 'Personnel de santé' ?></td></tr>
            <?php endforeach; ?>
            <?php if ($dashboardData['measurements'] === []): ?><tr><td colspan="6">Aucune mesure de croissance n’est encore enregistrée.</td></tr><?php endif; ?>
        </tbody></table></div>
        <p class="small text-secondary mt-2 mb-0">Les mesures sont présentées telles qu’enregistrées; elles ne constituent pas une évaluation médicale ni une courbe de croissance.</p>
        <?php if ($consentedChildren !== []): ?>
            <form action="<?= $e($basePath) ?>/pages/espace-parent.php#growth" class="dashboard-card row g-3 mt-3" method="post">
                <input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>">
                <input name="_action" type="hidden" value="record_growth">
                <div class="col-12"><h3 class="h6 mb-1">Ajouter une mesure</h3><p class="small text-secondary mb-0">Mesure déclarée par le parent; elle sera identifiée comme telle dans le dossier.</p></div>
                <div class="col-md-4"><label class="form-label" for="growth-child">Enfant</label><select class="form-select" id="growth-child" name="child_id" required><?php foreach ($consentedChildren as $child): ?><option value="<?= (int) $child['id'] ?>"><?= $e($child['first_name'] . ' ' . $child['last_name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label" for="growth-date">Date de mesure</label><input class="form-control" id="growth-date" max="<?= gmdate('Y-m-d') ?>" name="measured_on" required type="date" value="<?= gmdate('Y-m-d') ?>"></div>
                <div class="col-md-4"><label class="form-label" for="growth-weight">Poids (kg)</label><input class="form-control" id="growth-weight" inputmode="decimal" max="999.999" min="0.001" name="weight_kg" placeholder="Ex. 8.250" step="0.001" type="number"></div>
                <div class="col-md-4"><label class="form-label" for="growth-height">Taille ou longueur (cm)</label><input class="form-control" id="growth-height" inputmode="decimal" max="9999.99" min="0.01" name="length_height_cm" placeholder="Ex. 68.5" step="0.01" type="number"></div>
                <div class="col-md-4"><label class="form-label" for="growth-height-type">Type de mesure</label><select class="form-select" id="growth-height-type" name="length_height_type"><option value="length">Longueur</option><option value="height">Taille</option></select></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Enregistrer la mesure</button></div>
            </form>
        <?php else: ?>
            <p class="small text-secondary mt-3 mb-0">Autorisez le partage dans la gestion des profils pour ajouter ou consulter les mesures de croissance.</p>
        <?php endif; ?>
    </section>
    <section class="dashboard-section dashboard-two-column">
        <div class="dashboard-section-panel" id="health-records"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Dossier familial</p><h2>Dossier sanitaire</h2></div></div>
            <?php foreach ($dashboardData['medicalRecords'] as $record): ?>
                <article class="dashboard-list-item"><span aria-hidden="true" class="dashboard-list-icon">+</span><div><strong><?= $e($record['first_name'] . ' ' . $record['last_name']) ?></strong><p>Groupe sanguin : <?= $e($record['blood_group'] ?: 'Non renseigné') ?></p><?php if ($record['allergies']): ?><p>Allergies : <?= nl2br($e($record['allergies'])) ?></p><?php endif; ?><?php if ($record['medical_history']): ?><p>Antécédents : <?= nl2br($e($record['medical_history'])) ?></p><?php endif; ?><?php if ($record['notes']): ?><p><?= nl2br($e($record['notes'])) ?></p><?php endif; ?></div></article>
            <?php endforeach; ?>
            <?php if ($dashboardData['medicalRecords'] === []): ?><p class="text-secondary mb-0">Aucun dossier sanitaire n’est disponible pour le moment.</p><?php endif; ?>
        </div>
        <div class="dashboard-section-panel" id="nutrition"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Conseils de santé</p><h2>Nutrition</h2></div></div>
            <?php foreach ($dashboardData['nutrition'] as $record): ?>
                <article class="dashboard-list-item"><span aria-hidden="true" class="dashboard-list-icon">♡</span><div><strong><?= $e($record['first_name'] . ' ' . $record['last_name']) ?> · <?= $e($record['record_date']) ?></strong><?php if ($record['nutritional_status']): ?><p>État nutritionnel : <?= $e($record['nutritional_status']) ?></p><?php endif; ?><?php if ($record['feeding_type']): ?><p>Alimentation : <?= $e($record['feeding_type']) ?></p><?php endif; ?><?php if ($record['recommendations']): ?><p><?= nl2br($e($record['recommendations'])) ?></p><?php else: ?><p>Aucun conseil n’est associé à cet enregistrement.</p><?php endif; ?></div></article>
            <?php endforeach; ?>
            <?php if ($dashboardData['nutrition'] === []): ?><p class="text-secondary mb-0">Aucun conseil nutritionnel n’est encore enregistré.</p><?php endif; ?>
            <p class="small text-secondary mb-0 mt-2">Les conseils affichés ont été saisis par le personnel de santé; demandez un avis adapté à un professionnel.</p>
        </div>
    </section>
    <section aria-labelledby="alerts-title" class="dashboard-section" id="alerts">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Suivi à effectuer</p><h2 id="alerts-title">Alertes de santé</h2></div></div>
        <?php if ($dashboardData['alerts'] !== []): ?>
            <div class="dashboard-parent-alerts">
                <?php foreach ($dashboardData['alerts'] as $alert): ?>
                    <article class="dashboard-parent-alert"><span aria-hidden="true">!</span><div><strong><?= $e($alert['first_name'] . ' ' . $alert['last_name']) ?> · <?= $e($alert['alert_type']) ?></strong><p><?= $e($alert['description']) ?></p><small><?= $e($alert['created_at_utc']) ?></small></div><b><?= $e(['critical' => 'Critique', 'high' => 'Élevée', 'medium' => 'Modérée', 'low' => 'Faible'][$alert['severity']] ?? 'À vérifier') ?></b></article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dashboard-empty-inline"><span aria-hidden="true">✓</span><div><strong>Aucune alerte active</strong><p>Les alertes créées par le personnel de santé apparaîtront ici.</p></div></div>
        <?php endif; ?>
        <p class="small text-secondary mt-2 mb-0">Une alerte n’est pas un diagnostic. En cas d’inquiétude ou d’urgence, contactez un professionnel de santé.</p>
    </section>
    <section class="dashboard-section dashboard-two-column">
        <div class="dashboard-section-panel" id="vaccines"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Données de votre compte</p><h2>Prochaines vaccinations enregistrées</h2></div><a class="btn btn-outline-primary btn-sm" href="<?= $e($basePath) ?>/pages/vaccinations.php">Historique</a></div>
            <?php foreach ($dashboardData['appointments'] as $appointment): ?>
                <article class="dashboard-list-item"><span aria-hidden="true" class="dashboard-list-icon">✚</span><div><strong><?= $e($appointment['first_name'] . ' ' . $appointment['last_name']) ?> — <?= $e($appointment['vaccine_name']) ?></strong><p>Prochaine dose enregistrée</p></div><time><?= $e($appointment['next_dose_date']) ?></time></article>
            <?php endforeach; ?>
            <?php if ($dashboardData['appointments'] === []): ?><p class="text-secondary">Aucune prochaine date de vaccination n’est enregistrée.</p><?php endif; ?>
            <p class="vaccination-disclaimer small text-secondary mb-0 mt-3">Ces dates sont saisies par le personnel de santé et ne constituent pas une recommandation générée par la plateforme.</p>
        </div>
        <div class="dashboard-section-panel" id="notifications"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Compte personnel</p><h2>Notifications récentes</h2></div></div>
            <?php foreach ($dashboardData['notifications'] as $notification): ?><article class="dashboard-list-item"><span aria-hidden="true" class="dashboard-list-icon">•</span><div><strong><?= $e($notification['title']) ?></strong><p><?= $e($notification['message']) ?></p></div><time><?= $e($notification['created_at_utc']) ?></time></article><?php endforeach; ?>
            <?php if ($dashboardData['notifications'] === []): ?><p class="text-secondary">Aucune notification.</p><?php endif; ?>
        </div>
    </section>
    <section aria-labelledby="health-centers-title" class="dashboard-section" id="centers">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Structures de santé</p><h2 id="health-centers-title">Centres et consultations</h2><p>Envoyez une demande dans votre espace, puis contactez le centre pour convenir de l’horaire.</p></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Centre</th><th>Localisation</th><th>Adresse</th><th>Téléphone</th></tr></thead><tbody>
            <?php foreach ($dashboardData['centers'] as $center): ?>
                <tr><td><?= $e($center['name']) ?></td><td><?= $e(implode(', ', array_filter([$center['city'], $center['province']]))) ?></td><td><?= $e($center['address_text'] ?? '—') ?></td><td><?php if ($center['phone_e164']): ?><a href="tel:<?= $e($center['phone_e164']) ?>"><?= $e($center['phone_e164']) ?></a><?php else: ?>—<?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if ($dashboardData['centers'] === []): ?><tr><td colspan="4">Aucun centre réel n’est encore renseigné.</td></tr><?php endif; ?>
        </tbody></table></div>
        <?php if ($consentedChildren !== [] && $dashboardData['centers'] !== []): ?>
            <form action="<?= $e($basePath) ?>/pages/espace-parent.php#centers" class="dashboard-card row g-3 mt-3" method="post">
                <input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>">
                <input name="_action" type="hidden" value="request_appointment">
                <div class="col-12"><h3 class="h6 mb-1">Demander une consultation</h3><p class="small text-secondary mb-0">La date indiquée est une préférence, pas une confirmation. Appelez le centre pour convenir de l’horaire.</p></div>
                <div class="col-md-6"><label class="form-label" for="appointment-child">Enfant</label><select class="form-select" id="appointment-child" name="child_id" required><?php foreach ($consentedChildren as $child): ?><option value="<?= (int) $child['id'] ?>"><?= $e($child['first_name'] . ' ' . $child['last_name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="appointment-center">Centre de santé</label><select class="form-select" id="appointment-center" name="health_center_id" required><?php foreach ($dashboardData['centers'] as $center): ?><option value="<?= (int) $center['id'] ?>"><?= $e($center['name'] . (empty($center['city']) ? '' : ' — ' . $center['city'])) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="appointment-type">Motif</label><select class="form-select" id="appointment-type" name="request_type" required><?php foreach ($requestTypeLabels as $code => $label): ?><option value="<?= $e($code) ?>"><?= $e($label) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="appointment-date">Date souhaitée (facultatif)</label><input class="form-control" id="appointment-date" min="<?= $e($localToday) ?>" name="preferred_date" type="date"></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Envoyer la demande</button></div>
            </form>
        <?php elseif ($consentedChildren === []): ?>
            <p class="small text-secondary mt-3 mb-0">Un profil enfant avec partage autorisé est nécessaire pour demander une consultation.</p>
        <?php endif; ?>
        <div class="dashboard-section-panel mt-3" id="appointment-requests"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Suivi des demandes</p><h3 class="h6 mb-0">Mes demandes de consultation</h3></div></div>
            <?php foreach ($dashboardData['appointmentRequests'] as $request): ?>
                <article class="dashboard-list-item"><span aria-hidden="true" class="dashboard-list-icon">◷</span><div><strong><?= $e($request['first_name'] . ' ' . $request['last_name']) ?> · <?= $e($requestTypeLabels[$request['request_type']] ?? 'Consultation') ?></strong><p><?= $e($request['center_name']) ?> · Date souhaitée : <?= $request['preferred_date'] ? $e($request['preferred_date']) : 'à convenir' ?><?php if ($request['scheduled_at_utc']): ?> · Rendez-vous confirmé : <?= $e($request['scheduled_at_utc']) ?><?php endif; ?></p></div><span class="dashboard-pill"><?= $e($requestStatusLabels[$request['status']] ?? 'En traitement') ?></span></article>
            <?php endforeach; ?>
            <?php if ($dashboardData['appointmentRequests'] === []): ?><p class="text-secondary mb-0">Aucune demande envoyée pour le moment.</p><?php endif; ?>
        </div>
    </section>
</div>

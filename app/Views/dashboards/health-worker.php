<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$today = (new DateTimeImmutable('today', new DateTimeZone('Africa/Lubumbashi')))->format('Y-m-d');
$sexLabels = ['female' => 'Féminin', 'male' => 'Masculin', 'unknown' => 'Non renseigné'];
$visitTypeLabels = ['home' => 'Visite à domicile', 'community_outreach' => 'Activité de proximité', 'follow_up' => 'Visite de suivi'];
$priorityLabels = ['routine' => 'Courante', 'prompt' => 'Évaluation rapide', 'emergency' => 'Urgence signalée'];
$referralStatusLabels = ['awaiting_follow_up' => 'Suivi à effectuer', 'attended' => 'Prise en charge confirmée', 'not_attended' => 'Pas encore pris en charge', 'cancelled' => 'Annulé'];

?>
<div class="dashboard-page-content">
    <section class="dashboard-welcome" id="overview">
        <div><p class="dashboard-eyebrow">Espace communautaire</p><h2>Bonjour, <?= $e($dashboardData['displayName']) ?></h2><p>Les dossiers présentés sont limités aux enfants ayant un consentement actif et une activité associée à votre compte.</p><p class="small text-secondary mb-0">Connexion Internet requise. La saisie hors connexion et la synchronisation différée ne sont pas disponibles.</p></div>
    </section>
    <section aria-label="Résumé de l’activité autorisée" class="dashboard-stat-grid">
        <?php foreach ($dashboardData['stats'] as $index => $stat): ?>
            <article class="dashboard-stat-card"><span aria-hidden="true" class="dashboard-stat-icon dashboard-stat-icon-<?= ($index % 3) + 1 ?>"><?= ['♙', '↗', '!', '⌖'][$index] ?></span><p><?= $e($stat['label']) ?></p><strong><?= $e($stat['value']) ?></strong><small>Valeur calculée depuis les enregistrements associés</small></article>
        <?php endforeach; ?>
    </section>
    <section aria-labelledby="worker-patients-title" class="dashboard-section" id="patients">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Accès par autorisation</p><h2 id="worker-patients-title">Enfants suivis</h2><p>Aucun dossier sans consentement actif n’est exposé à cet espace.</p></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Identifiant</th><th>Profil</th><th>Date de naissance</th><th>Dernière mesure saisie par vous</th></tr></thead><tbody>
            <?php foreach ($dashboardData['patients'] as $patient): ?>
                <tr><td><?= (int) $patient['id'] ?></td><td><strong><?= $e($patient['first_name'] . ' ' . $patient['last_name']) ?></strong></td><td><?= $e($patient['date_of_birth']) ?></td><td><?= $e($patient['last_measurement'] ?? 'Aucune') ?></td></tr>
            <?php endforeach; ?>
            <?php if ($dashboardData['patients'] === []): ?><tr><td colspan="4">Aucun dossier autorisé n’est associé à votre activité.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section aria-labelledby="enrollment-title" class="dashboard-section" id="enrollment">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Nouveau dossier</p><h2 id="enrollment-title">Inscrire un enfant rencontré</h2><p>Le profil est rattaché à un compte parent existant. Les données de santé restent bloquées jusqu’à son consentement.</p></div></div>
        <form action="../pages/espace-agent.php#enrollment" class="dashboard-card row g-3" method="post">
            <input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="enroll_child">
            <div class="col-md-6"><label class="form-label" for="enrollment-parent-email">Adresse e-mail du compte parent</label><input autocomplete="email" class="form-control" id="enrollment-parent-email" maxlength="254" name="parent_email" required type="email"></div>
            <div class="col-md-3"><label class="form-label" for="enrollment-first-name">Prénom de l’enfant</label><input class="form-control" id="enrollment-first-name" maxlength="100" name="first_name" required></div>
            <div class="col-md-3"><label class="form-label" for="enrollment-last-name">Nom de l’enfant</label><input class="form-control" id="enrollment-last-name" maxlength="100" name="last_name" required></div>
            <div class="col-md-3"><label class="form-label" for="enrollment-dob">Date de naissance</label><input class="form-control" id="enrollment-dob" max="<?= $e($today) ?>" min="<?= (new DateTimeImmutable($today))->modify('-5 years')->format('Y-m-d') ?>" name="date_of_birth" required type="date"></div>
            <div class="col-md-3"><label class="form-label" for="enrollment-sex">Sexe</label><select class="form-select" id="enrollment-sex" name="sex" required><?php foreach ($sexLabels as $code => $label): ?><option value="<?= $e($code) ?>"><?= $e($label) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label class="form-label" for="enrollment-relationship">Lien du titulaire du compte</label><select class="form-select" id="enrollment-relationship" name="relationship_type" required><option value="parent">Parent</option><option value="guardian">Tuteur légal</option></select></div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Créer le profil en attente de consentement</button></div>
        </form>
        <p class="small text-secondary mt-2">Le parent doit déjà posséder un compte AFYAMTOTO AI et activer le consentement avant tout acte de santé.</p>
        <div class="dashboard-card table-responsive mt-3"><table class="table dashboard-data-table mb-0"><thead><tr><th>Enfant inscrit</th><th>Date de naissance</th><th>Compte parent</th><th>État</th></tr></thead><tbody>
            <?php foreach ($dashboardData['pendingEnrollments'] as $enrollment): ?><tr><td><strong><?= $e($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></strong></td><td><?= $e($enrollment['date_of_birth']) ?></td><td><?= $e($enrollment['parent_name']) ?></td><td><span class="dashboard-status is-inactive">Consentement en attente</span></td></tr><?php endforeach; ?>
            <?php if ($dashboardData['pendingEnrollments'] === []): ?><tr><td colspan="4">Aucune inscription en attente de consentement.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section class="dashboard-section dashboard-two-column" id="actions">
        <div class="dashboard-section-panel" id="growth"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Mesures terrain</p><h2>Suivi de croissance</h2></div></div>
            <?php if ($dashboardData['communityChildren'] !== []): ?>
                <form action="../pages/espace-agent.php#growth" class="row g-3" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="record_growth">
                    <div class="col-12"><label class="form-label" for="growth-child">Enfant</label><select class="form-select" id="growth-child" name="child_id" required><?php foreach ($dashboardData['communityChildren'] as $child): ?><option value="<?= (int) $child['id'] ?>"><?= $e($child['first_name'] . ' ' . $child['last_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label" for="growth-date">Date de mesure</label><input class="form-control" id="growth-date" max="<?= $e($today) ?>" name="measured_on" required type="date" value="<?= $e($today) ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="growth-weight">Poids (kg)</label><input class="form-control" id="growth-weight" max="999.999" min="0.001" name="weight_kg" step="0.001" type="number"></div>
                    <div class="col-md-6"><label class="form-label" for="growth-height">Taille / longueur (cm)</label><input class="form-control" id="growth-height" max="9999.99" min="0.01" name="length_height_cm" step="0.01" type="number"></div>
                    <div class="col-md-6"><label class="form-label" for="growth-height-type">Type de mesure</label><select class="form-select" id="growth-height-type" name="length_height_type"><option value="length">Longueur</option><option value="height">Taille</option></select></div>
                    <div class="col-md-6"><label class="form-label" for="growth-muac">Périmètre brachial (cm)</label><input class="form-control" id="growth-muac" max="9999.99" min="0.01" name="muac_cm" step="0.01" type="number"></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Enregistrer les mesures</button></div>
                </form>
            <?php else: ?><p class="text-secondary mb-0">Aucun enfant autorisé n’est associé à votre activité. Le parent doit consentir au partage des données.</p><?php endif; ?>
            <p class="small text-secondary mt-3 mb-0">Saisissez les mesures observées selon les protocoles locaux. Aucun score ni diagnostic automatique n’est calculé.</p>
            <div class="table-responsive mt-3"><table class="table dashboard-data-table mb-0"><thead><tr><th>Enfant</th><th>Date</th><th>Poids</th><th>Taille / longueur</th><th>Périmètre brachial</th></tr></thead><tbody>
                <?php foreach ($dashboardData['growthRecords'] as $record): ?><tr><td><strong><?= $e($record['first_name'] . ' ' . $record['last_name']) ?></strong></td><td><?= $e($record['measured_at_utc']) ?></td><td><?= $record['weight_kg'] !== null ? $e($record['weight_kg']) . ' kg' : '—' ?></td><td><?= $record['length_height_cm'] !== null ? $e($record['length_height_cm']) . ' cm (' . ($record['length_height_type'] === 'length' ? 'longueur' : 'taille') . ')' : '—' ?></td><td><?= $record['muac_cm'] !== null ? $e($record['muac_cm']) . ' cm' : '—' ?></td></tr><?php endforeach; ?>
                <?php if ($dashboardData['growthRecords'] === []): ?><tr><td colspan="5">Aucune mesure saisie par votre compte.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div>
        <div class="dashboard-section-panel" id="nutrition"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Dépistage descriptif</p><h2>Observations nutritionnelles</h2></div></div>
            <?php if ($dashboardData['communityChildren'] !== []): ?>
                <form action="../pages/espace-agent.php#nutrition" class="row g-3" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="record_nutrition">
                    <div class="col-12"><label class="form-label" for="nutrition-child">Enfant</label><select class="form-select" id="nutrition-child" name="child_id" required><?php foreach ($dashboardData['communityChildren'] as $child): ?><option value="<?= (int) $child['id'] ?>"><?= $e($child['first_name'] . ' ' . $child['last_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label" for="nutrition-date">Date</label><input class="form-control" id="nutrition-date" max="<?= $e($today) ?>" name="record_date" required type="date" value="<?= $e($today) ?>"></div>
                    <div class="col-12"><label class="form-label" for="nutrition-feeding">Type d’alimentation (facultatif)</label><input class="form-control" id="nutrition-feeding" maxlength="64" name="feeding_type"></div>
                    <div class="col-12"><label class="form-label" for="nutrition-observations">Observations selon le protocole local</label><textarea class="form-control" id="nutrition-observations" maxlength="4000" name="observations" required rows="3"></textarea></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Enregistrer les observations</button></div>
                </form>
            <?php else: ?><p class="text-secondary mb-0">Un enfant avec consentement actif est requis.</p><?php endif; ?>
            <p class="small text-secondary mt-3 mb-0">Les observations sont transmises pour évaluation; l’application ne classe pas l’état nutritionnel et ne génère pas de conseil clinique.</p>
            <?php foreach ($dashboardData['nutritionRecords'] as $record): ?><article class="dashboard-list-item"><span class="dashboard-list-icon" aria-hidden="true">♡</span><div><strong><?= $e($record['first_name'] . ' ' . $record['last_name']) ?> · <?= $e($record['record_date']) ?></strong><p><?= $record['feeding_type'] ? 'Alimentation : ' . $e($record['feeding_type']) . ' · ' : '' ?><?= $e($record['observations']) ?></p></div></article><?php endforeach; ?>
            <?php if ($dashboardData['nutritionRecords'] === []): ?><p class="small text-secondary mt-3 mb-0">Aucun dépistage enregistré par votre compte.</p><?php endif; ?>
        </div>
    </section>
    <section class="dashboard-section dashboard-two-column" id="visits">
        <div class="dashboard-section-panel"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Activité de proximité</p><h2>Enregistrer une visite</h2></div></div>
            <?php if ($dashboardData['communityChildren'] !== []): ?>
                <form action="../pages/espace-agent.php#visits" class="row g-3" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="record_visit">
                    <div class="col-12"><label class="form-label" for="visit-child">Enfant</label><select class="form-select" id="visit-child" name="child_id" required><?php foreach ($dashboardData['communityChildren'] as $child): ?><option value="<?= (int) $child['id'] ?>"><?= $e($child['first_name'] . ' ' . $child['last_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="visit-type">Type de visite</label><select class="form-select" id="visit-type" name="visit_type" required><?php foreach ($visitTypeLabels as $code => $label): ?><option value="<?= $e($code) ?>"><?= $e($label) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="visit-date">Date</label><input class="form-control" id="visit-date" max="<?= $e($today) ?>" name="visited_on" required type="date" value="<?= $e($today) ?>"></div>
                    <div class="col-12"><label class="form-label" for="visit-purpose">Motif</label><input class="form-control" id="visit-purpose" maxlength="160" name="purpose" required></div>
                    <div class="col-12"><label class="form-label" for="visit-observations">Observations (facultatif)</label><textarea class="form-control" id="visit-observations" maxlength="4000" name="observations" rows="3"></textarea></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Enregistrer la visite</button></div>
                </form>
            <?php else: ?><p class="text-secondary mb-0">Un enfant avec consentement actif est requis.</p><?php endif; ?>
        </div>
        <div class="dashboard-section-panel"><div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Historique terrain</p><h2>Visites récentes</h2></div></div>
            <?php foreach ($dashboardData['visits'] as $visit): ?><article class="dashboard-list-item"><span class="dashboard-list-icon" aria-hidden="true">⌂</span><div><strong><?= $e($visit['first_name'] . ' ' . $visit['last_name']) ?> · <?= $e($visitTypeLabels[$visit['visit_type']] ?? 'Visite') ?></strong><p><?= $e($visit['purpose']) ?><?= $visit['observations'] ? ' · ' . $e($visit['observations']) : '' ?></p></div><time><?= $e($visit['visited_at_utc']) ?></time></article><?php endforeach; ?>
            <?php if ($dashboardData['visits'] === []): ?><p class="text-secondary mb-0">Aucune visite enregistrée.</p><?php endif; ?>
        </div>
    </section>
    <section aria-labelledby="referrals-title" class="dashboard-section" id="referrals">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Orientation vers un professionnel</p><h2 id="referrals-title">Signaler et référer</h2><p>La priorité est déclarée par l’agent et ne remplace pas une évaluation clinique.</p></div></div>
        <div class="dashboard-section-panel">
            <?php if ($dashboardData['communityChildren'] !== [] && $dashboardData['centers'] !== []): ?>
                <form action="../pages/espace-agent.php#referrals" class="row g-3" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="create_referral">
                    <div class="col-md-6"><label class="form-label" for="referral-child">Enfant</label><select class="form-select" id="referral-child" name="child_id" required><?php foreach ($dashboardData['communityChildren'] as $child): ?><option value="<?= (int) $child['id'] ?>"><?= $e($child['first_name'] . ' ' . $child['last_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="referral-center">Centre de santé</label><select class="form-select" id="referral-center" name="health_center_id" required><?php foreach ($dashboardData['centers'] as $center): ?><option value="<?= (int) $center['id'] ?>"><?= $e($center['name'] . (empty($center['city']) ? '' : ' — ' . $center['city'])) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="referral-priority">Priorité observée</label><select class="form-select" id="referral-priority" name="priority" required><?php foreach ($priorityLabels as $code => $label): ?><option value="<?= $e($code) ?>"><?= $e($label) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label" for="referral-concern">Situation préoccupante observée</label><textarea class="form-control" id="referral-concern" maxlength="1000" name="concern" required rows="3"></textarea></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Transmettre l’orientation</button></div>
                </form>
            <?php elseif ($dashboardData['communityChildren'] === []): ?><p class="text-secondary mb-0">Un enfant avec consentement actif est requis.</p>
            <?php else: ?><p class="text-secondary mb-0">Aucun centre réel n’est configuré pour recevoir une orientation.</p><?php endif; ?>
            <div class="dashboard-empty-inline mt-3" role="note"><span aria-hidden="true">!</span><div><strong>En cas d’urgence</strong><p>Contactez directement les services d’urgence ou le centre de santé. Une alerte dans AFYAMTOTO AI ne remplace pas une prise en charge immédiate.</p></div></div>
            <?php if ($dashboardData['centers'] !== []): ?><div class="table-responsive mt-3"><table class="table dashboard-data-table mb-0"><thead><tr><th>Centre partenaire</th><th>Localisation</th><th>Adresse</th><th>Téléphone</th></tr></thead><tbody><?php foreach ($dashboardData['centers'] as $center): ?><tr><td><?= $e($center['name']) ?></td><td><?= $e(implode(', ', array_filter([$center['city'], $center['province']]))) ?></td><td><?= $e($center['address_text'] ?? '—') ?></td><td><?php if ($center['phone_e164']): ?><a href="tel:<?= $e($center['phone_e164']) ?>"><?= $e($center['phone_e164']) ?></a><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        </div>
        <div class="dashboard-card table-responsive mt-3"><table class="table dashboard-data-table mb-0"><thead><tr><th>Enfant / situation</th><th>Centre</th><th>Priorité</th><th>Suivi</th><th>Action</th></tr></thead><tbody>
            <?php foreach ($dashboardData['referrals'] as $referral): ?><tr><td><strong><?= $e($referral['first_name'] . ' ' . $referral['last_name']) ?></strong><span class="dashboard-table-subtitle"><?= $e($referral['concern_description']) ?></span></td><td><?= $e($referral['health_center_name']) ?></td><td><?= $e($priorityLabels[$referral['priority']] ?? 'À vérifier') ?></td><td><?= $e($referralStatusLabels[$referral['status']] ?? 'À suivre') ?><?php if ($referral['follow_up_notes']): ?><span class="dashboard-table-subtitle"><?= $e($referral['follow_up_notes']) ?></span><?php endif; ?></td><td>
                <?php if (in_array($referral['status'], ['awaiting_follow_up', 'not_attended'], true)): ?><form action="../pages/espace-agent.php#referrals" class="d-flex flex-wrap gap-2" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="follow_up_referral"><input name="referral_id" type="hidden" value="<?= (int) $referral['id'] ?>"><select aria-label="Résultat du suivi" class="form-select form-select-sm" name="outcome" required><option value="attended">Pris en charge</option><option value="not_attended">Pas encore pris en charge</option></select><input aria-label="Note de suivi" class="form-control form-control-sm" maxlength="500" name="follow_up_notes" placeholder="Note facultative"><button class="btn btn-outline-primary btn-sm" type="submit">Enregistrer</button></form><?php else: ?>—<?php endif; ?>
            </td></tr><?php endforeach; ?>
            <?php if ($dashboardData['referrals'] === []): ?><tr><td colspan="5">Aucun référencement enregistré.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section aria-label="Actions disponibles" class="dashboard-section dashboard-section-panel" id="permissions">
        <h2 class="h4">Vaccinations</h2>
        <p>Le formulaire existant permet d’enregistrer une dose vérifiée, avec contrôle du consentement et de l’accès au dossier.</p>
        <a class="btn btn-outline-primary" href="../pages/vaccinations.php">Ouvrir les vaccinations</a>
    </section>
    <section aria-labelledby="worker-activity-title" class="dashboard-section" id="activity">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Compte professionnel</p><h2 id="worker-activity-title">Activités récentes</h2></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Action</th><th>Détail</th><th>Date UTC</th></tr></thead><tbody>
            <?php foreach ($dashboardData['activities'] as $activity): ?><tr><td><?= $e($activity['action']) ?></td><td><?= $e($activity['description'] ?? '—') ?></td><td><?= $e($activity['created_at_utc']) ?></td></tr><?php endforeach; ?>
            <?php if ($dashboardData['activities'] === []): ?><tr><td colspan="3">Aucune activité enregistrée.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>

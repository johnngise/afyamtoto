<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$requestTypeLabels = [
    'consultation' => 'Consultation générale',
    'vaccination_follow_up' => 'Suivi vaccinal',
    'growth_follow_up' => 'Suivi de croissance',
    'nutrition_consultation' => 'Consultation nutritionnelle',
];
$priorityLabels = ['routine' => 'Courante', 'prompt' => 'Rapide', 'emergency' => 'Urgence signalée'];
?>
<div class="dashboard-page-content">
    <section class="dashboard-welcome" id="overview">
        <div><p class="dashboard-eyebrow">Opérations d’établissement</p><h2>Bonjour, <?= $e($dashboardData['displayName']) ?></h2><p>Les données affichées sont limitées aux centres qui vous sont affectés et aux enfants dont le consentement est actif.</p></div>
    </section>
    <section aria-label="Indicateurs des établissements affectés" class="dashboard-stat-grid">
        <?php foreach ($dashboardData['stats'] as $index => $stat): ?><article class="dashboard-stat-card"><span aria-hidden="true" class="dashboard-stat-icon dashboard-stat-icon-<?= ($index % 3) + 1 ?>"><?= ['⌖', '◷', '⚑', '✚'][$index] ?></span><p><?= $e($stat['label']) ?></p><strong><?= $e($stat['value']) ?></strong><small>Centres affectés uniquement</small></article><?php endforeach; ?>
    </section>
    <section aria-labelledby="center-summary-title" class="dashboard-section" id="centers">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Établissements affectés</p><h2 id="center-summary-title">Profil et activité des centres</h2><p>Les coordonnées et indicateurs sont regroupés par établissement.</p></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Centre</th><th>Localisation</th><th>Orientations à suivre</th><th>Rendez-vous à traiter</th><th>Vaccinations enregistrées</th></tr></thead><tbody>
            <?php foreach ($dashboardData['centerSummary'] as $center): ?><tr><td><strong><?= $e($center['name']) ?></strong></td><td><?= $e(implode(', ', array_filter([$center['city'], $center['province']]))) ?></td><td><?= (int) $center['referrals_to_follow'] ?></td><td><?= (int) $center['appointments_to_process'] ?></td><td><?= (int) $center['vaccinations_recorded'] ?></td></tr><?php endforeach; ?>
            <?php if ($dashboardData['centerSummary'] === []): ?><tr><td colspan="5">Aucun centre n’est affecté à ce compte. Un administrateur doit configurer l’affectation.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section aria-labelledby="facility-appointments-title" class="dashboard-section" id="appointments">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Demandes des familles</p><h2 id="facility-appointments-title">Rendez-vous à traiter</h2><p>Les parents sont notifiés de la réponse du centre.</p></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Enfant</th><th>Motif</th><th>Date souhaitée</th><th>Centre</th><th>Réponse du centre</th></tr></thead><tbody>
            <?php foreach ($dashboardData['appointments'] as $appointment): ?><tr><td><strong><?= $e($appointment['first_name'] . ' ' . $appointment['last_name']) ?></strong></td><td><?= $e($requestTypeLabels[$appointment['request_type']] ?? 'Consultation') ?></td><td><?= $e($appointment['preferred_date'] ?? 'À convenir') ?></td><td><?= $e($appointment['center_name']) ?></td><td><form action="../pages/espace-centre.php#appointments" class="d-grid gap-2" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="respond_appointment"><input name="request_id" type="hidden" value="<?= (int) $appointment['id'] ?>"><select aria-label="Réponse au rendez-vous" class="form-select form-select-sm" name="status" required><option value="confirmed">Confirmer</option><option value="declined">Ne pas accepter</option></select><label class="small" for="appointment-date-<?= (int) $appointment['id'] ?>">Horaire confirmé (heure locale)</label><input class="form-control form-control-sm" id="appointment-date-<?= (int) $appointment['id'] ?>" name="scheduled_local" type="datetime-local"><input class="form-control form-control-sm" maxlength="500" name="response_message" placeholder="Message au parent (facultatif)"><button class="btn btn-outline-primary btn-sm" type="submit">Envoyer la réponse</button></form></td></tr><?php endforeach; ?>
            <?php if ($dashboardData['appointments'] === []): ?><tr><td colspan="5">Aucun rendez-vous en attente.</td></tr><?php endif; ?>
        </tbody></table></div>
        <p class="small text-secondary mt-2 mb-0">La confirmation est enregistrée en UTC et présentée au parent comme une date de rendez-vous confirmée.</p>
    </section>
    <section aria-labelledby="facility-referrals-title" class="dashboard-section" id="referrals">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Coordination des soins</p><h2 id="facility-referrals-title">Orientations reçues</h2><p>Consignez si une prise en charge a eu lieu ou reste à poursuivre.</p></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Enfant / situation</th><th>Priorité déclarée</th><th>Centre</th><th>Suivi</th><th>Action</th></tr></thead><tbody>
            <?php foreach ($dashboardData['referrals'] as $referral): ?><tr><td><strong><?= $e($referral['first_name'] . ' ' . $referral['last_name']) ?></strong><span class="dashboard-table-subtitle"><?= $e($referral['concern_description']) ?></span></td><td><?= $e($priorityLabels[$referral['priority']] ?? 'À examiner') ?></td><td><?= $e($referral['center_name']) ?></td><td><?= $e($referral['referred_at_utc']) ?></td><td><form action="../pages/espace-centre.php#referrals" class="d-grid gap-2" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="update_referral"><input name="referral_id" type="hidden" value="<?= (int) $referral['id'] ?>"><select aria-label="Résultat de l’orientation" class="form-select form-select-sm" name="outcome" required><option value="attended">Prise en charge effectuée</option><option value="not_attended">Suivi à poursuivre</option></select><input aria-label="Note de suivi" class="form-control form-control-sm" maxlength="500" name="notes" placeholder="Note facultative"><button class="btn btn-outline-primary btn-sm" type="submit">Mettre à jour</button></form></td></tr><?php endforeach; ?>
            <?php if ($dashboardData['referrals'] === []): ?><tr><td colspan="5">Aucune orientation à suivre.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section aria-labelledby="facility-professionals-title" class="dashboard-section" id="professionals">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Équipe autorisée</p><h2 id="facility-professionals-title">Professionnels affectés</h2></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Nom</th><th>E-mail professionnel</th><th>Centre</th></tr></thead><tbody>
            <?php foreach ($dashboardData['professionals'] as $professional): ?><tr><td><strong><?= $e($professional['display_name']) ?></strong></td><td><?= $e($professional['email'] ?? '—') ?></td><td><?= $e($professional['center_name']) ?></td></tr><?php endforeach; ?>
            <?php if ($dashboardData['professionals'] === []): ?><tr><td colspan="3">Aucun professionnel actif n’est affecté à ces centres. Un administrateur gère les accès.</td></tr><?php endif; ?>
        </tbody></table></div>
        <p class="small text-secondary mt-2 mb-0">Les profils d’établissement et affectations sont administrés par une personne autorisée de la plateforme.</p>
    </section>
</div>

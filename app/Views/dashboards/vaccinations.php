<?php

declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('today', new DateTimeZone('Africa/Lubumbashi'));
$upcomingCount = count(array_filter(
    $history,
    static fn (array $record): bool => !empty($record['next_dose_date'])
        && new DateTimeImmutable((string) $record['next_dose_date'], new DateTimeZone('UTC')) >= $today,
));
$childCount = count(array_unique(array_map(static fn (array $record): int => (int) $record['child_id'], $history)));
$isStaff = $role !== 'parent';
$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))->format('d/m/Y');
};

?>
<section class="vaccination-page">
    <header class="vaccination-header">
        <div>
            <p class="vaccination-eyebrow">SUIVI DE SANTÉ</p>
            <h1>Vaccinations</h1>
            <p class="vaccination-intro">
                <?= $isStaff
                    ? 'Enregistrez les doses administrées et consultez les dossiers accessibles selon vos autorisations.'
                    : 'Consultez les vaccinations enregistrées pour vos enfants dont le consentement est actif.' ?>
            </p>
        </div>
        <div class="vaccination-header-mark" aria-hidden="true">✚</div>
    </header>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Vérifiez les informations :</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?><li><?= $escape($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="vaccination-summary" aria-label="Résumé du suivi vaccinal">
        <article class="vaccination-summary-card">
            <span class="vaccination-summary-icon" aria-hidden="true">▤</span>
            <div><span class="vaccination-summary-label">Doses enregistrées</span><strong><?= count($history) ?></strong></div>
        </article>
        <article class="vaccination-summary-card">
            <span class="vaccination-summary-icon is-green" aria-hidden="true">♡</span>
            <div><span class="vaccination-summary-label">Enfants concernés</span><strong><?= $childCount ?></strong></div>
        </article>
        <article class="vaccination-summary-card">
            <span class="vaccination-summary-icon is-orange" aria-hidden="true">◷</span>
            <div><span class="vaccination-summary-label">Prochaines dates saisies</span><strong><?= $upcomingCount ?></strong></div>
        </article>
    </div>

    <?php if ($isStaff): ?>
        <section class="vaccination-panel" aria-labelledby="vaccination-form-title">
            <div class="vaccination-panel-heading">
                <div>
                    <p class="vaccination-eyebrow">PERSONNEL AUTORISÉ</p>
                    <h2 id="vaccination-form-title">Enregistrer une dose administrée</h2>
                </div>
                <span class="vaccination-secure-label">Accès sécurisé</span>
            </div>

            <?php if ($doses === []): ?>
                <div class="alert alert-info mb-0" role="status">
                    Aucun vaccin ou aucune dose active n’est configuré dans la base. Demandez à l’administrateur de vérifier le catalogue avant tout enregistrement.
                </div>
            <?php elseif ($children === []): ?>
                <div class="alert alert-info mb-0" role="status">
                    Aucun dossier avec consentement actif n’est actuellement accessible à votre compte.
                </div>
            <?php else: ?>
                <form action="../pages/vaccinations.php" method="post" class="vaccination-form">
                    <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                    <div class="vaccination-form-grid">
                        <div>
                            <label class="form-label" for="child_id">Enfant concerné <span aria-hidden="true">*</span></label>
                            <select class="form-select" id="child_id" name="child_id" required>
                                <option value="">Sélectionner un dossier</option>
                                <?php foreach ($children as $child): ?>
                                    <?php $childName = trim($child['first_name'] . ' ' . $child['last_name']); ?>
                                    <option value="<?= (int) $child['id'] ?>" <?= (string) ($oldInput['child_id'] ?? '') === (string) $child['id'] ? 'selected' : '' ?>>
                                        <?= $escape($childName) ?> — né(e) le <?= $escape($formatDate($child['date_of_birth'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="vaccine_dose_id">Vaccin et dose <span aria-hidden="true">*</span></label>
                            <select class="form-select" id="vaccine_dose_id" name="vaccine_dose_id" required>
                                <option value="">Sélectionner une dose</option>
                                <?php foreach ($doses as $dose): ?>
                                    <option value="<?= (int) $dose['id'] ?>" <?= (string) ($oldInput['vaccine_dose_id'] ?? '') === (string) $dose['id'] ? 'selected' : '' ?>>
                                        <?= $escape($dose['vaccine_name'] . ' — ' . $dose['dose_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="administered_date">Date d’administration <span aria-hidden="true">*</span></label>
                            <input class="form-control" id="administered_date" name="administered_date" type="date" max="<?= $today->format('Y-m-d') ?>" value="<?= $escape($oldInput['administered_date'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label class="form-label" for="next_dose_date">Prochaine date, si prescrite/saisie</label>
                            <input class="form-control" id="next_dose_date" name="next_dose_date" type="date" value="<?= $escape($oldInput['next_dose_date'] ?? '') ?>">
                            <small class="form-text">Date saisie par le personnel ; aucune recommandation n’est calculée automatiquement.</small>
                        </div>
                        <?php if ($role === 'health-professional'): ?>
                            <div>
                                <label class="form-label" for="health_center_id">Centre de santé associé <span aria-hidden="true">*</span></label>
                                <select class="form-select" id="health_center_id" name="health_center_id" required>
                                    <option value="">Sélectionner un centre autorisé</option>
                                    <?php foreach ($centers as $center): ?>
                                        <option value="<?= (int) $center['id'] ?>" <?= (string) ($oldInput['health_center_id'] ?? '') === (string) $center['id'] ? 'selected' : '' ?>>
                                            <?= $escape($center['name']) ?><?= !empty($center['city']) ? ' — ' . $escape($center['city']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($centers === []): ?><small class="form-text">Aucun centre n’est associé à votre compte.</small><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="vaccination-notes-field">
                            <label class="form-label" for="notes">Notes de suivi (facultatif)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="500"><?= $escape($oldInput['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="vaccination-form-footer">
                        <p><span aria-hidden="true">*</span> Champs obligatoires. L’enregistrement est réservé au personnel de santé autorisé.</p>
                        <button class="btn btn-primary" type="submit" <?= $role === 'health-professional' && $centers === [] ? 'disabled' : '' ?>>Enregistrer la dose</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="vaccination-panel" aria-labelledby="vaccination-history-title">
        <div class="vaccination-panel-heading">
            <div>
                <p class="vaccination-eyebrow">DOSSIERS AUTORISÉS</p>
                <h2 id="vaccination-history-title">Historique des vaccinations</h2>
            </div>
            <span class="vaccination-history-count"><?= count($history) ?> enregistrement<?= count($history) > 1 ? 's' : '' ?></span>
        </div>
        <?php if ($history === []): ?>
            <div class="vaccination-empty-state">
                <span aria-hidden="true">✚</span>
                <h3>Aucune vaccination enregistrée</h3>
                <p>Les doses apparaîtront ici lorsqu’elles auront été saisies par un membre autorisé du personnel de santé.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table vaccination-table align-middle mb-0">
                    <thead>
                    <tr>
                        <th scope="col">Enfant</th>
                        <th scope="col">Vaccin / dose</th>
                        <th scope="col">Administration</th>
                        <th scope="col">Prochaine date saisie</th>
                        <th scope="col">Centre</th>
                        <?php if ($isStaff): ?><th scope="col">Enregistré par</th><?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $record): ?>
                        <tr>
                            <td><strong><?= $escape(trim($record['first_name'] . ' ' . $record['last_name'])) ?></strong></td>
                            <td><?= $escape($record['vaccine_name']) ?><small><?= $escape($record['dose_name']) ?></small></td>
                            <td><?= $escape($formatDate($record['administered_at_utc'])) ?></td>
                            <td><?= $escape($formatDate($record['next_dose_date'] ?? null)) ?></td>
                            <td><?= $escape($record['health_center_name'] ?? '—') ?></td>
                            <?php if ($isStaff): ?><td><?= $escape($record['recorded_by_name'] ?? '—') ?></td><?php endif; ?>
                        </tr>
                        <?php if ($isStaff && !empty($record['notes'])): ?>
                            <tr class="vaccination-notes-row"><td colspan="6"><strong>Note :</strong> <?= $escape($record['notes']) ?></td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <p class="vaccination-disclaimer">Les dates affichées sont des données d’enregistrement. Elles ne constituent pas une recommandation vaccinale générée par la plateforme.</p>
    </section>
</section>

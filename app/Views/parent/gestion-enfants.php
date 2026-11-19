<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$old = static fn (string $key): string => $e($oldInput[$key] ?? '');
$rolesRelation = ['parent' => 'Parent', 'guardian' => 'Responsable légal'];
$sexLabels = ['female' => 'Féminin', 'male' => 'Masculin', 'unknown' => 'Non renseigné'];
$consentLabels = ['granted' => 'Partage autorisé', 'pending' => 'En attente', 'withdrawn' => 'Partage retiré'];
$grantedCount = count(array_filter(
    $profiles,
    static fn (array $profile): bool => $profile['consent_status'] === 'granted',
));
$pageTitle = 'Gestion des enfants';
$pageDescription = 'Gérez les profils des enfants associés à votre compte parent.';
$role = 'parent';
$currentItem = 'manage-children';
$failedChildId = isset($oldInput['child_id']) && is_string($oldInput['child_id']) && ctype_digit($oldInput['child_id'])
    ? (int) $oldInput['child_id']
    : null;

ob_start();
?>
<div class="child-management" data-dashboard-url="<?= $e($basePath) ?>/pages/espace-parent.php">
    <section class="child-management-heading" aria-labelledby="children-page-title">
        <div>
            <p class="dashboard-eyebrow">ESPACE FAMILLE</p>
            <h2 id="children-page-title">Vos enfants</h2>
            <p>Consultez et mettez à jour les informations des enfants associés à votre compte.</p>
        </div>
        <a class="btn btn-primary child-management-add" href="#add-child" data-open-details="add-child">
            <span aria-hidden="true">＋</span> Ajouter un enfant
        </a>
    </section>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger child-management-alert" role="alert">
            <span aria-hidden="true">!</span>
            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <section aria-label="Résumé des profils" class="child-management-stats">
        <article class="child-management-stat">
            <span class="child-management-stat-mark is-blue" aria-hidden="true">♡</span>
            <div><span>Enfants associés</span><strong><?= count($profiles) ?></strong></div>
        </article>
        <article class="child-management-stat">
            <span class="child-management-stat-mark is-green" aria-hidden="true">✓</span>
            <div><span>Partage autorisé</span><strong><?= $grantedCount ?></strong></div>
        </article>
        <div class="child-management-privacy">
            <span aria-hidden="true">i</span>
            <p>Seuls les profils liés à votre compte sont visibles. Vous pouvez retirer le consentement au partage à tout moment.</p>
        </div>
    </section>

    <details class="child-management-create" id="add-child"<?= $errors !== [] && $failedChildId === null ? ' open' : '' ?>>
        <summary>
            <span class="child-management-create-icon" aria-hidden="true">＋</span>
            <span><strong>Ajouter le profil d’un enfant</strong><small>Les champs marqués d’un astérisque sont obligatoires.</small></span>
            <span aria-hidden="true" class="child-management-chevron">⌄</span>
        </summary>
        <div class="child-management-form-body">
            <form action="<?= $e($basePath) ?>/pages/gestion-enfants.php" class="row g-3" method="post">
                <input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>">
                <div class="col-md-6"><label class="form-label" for="first-name">Prénom <span aria-hidden="true">*</span></label><input autocomplete="given-name" class="form-control" id="first-name" maxlength="100" name="first_name" required value="<?= $old('first_name') ?>"></div>
                <div class="col-md-6"><label class="form-label" for="last-name">Nom <span aria-hidden="true">*</span></label><input autocomplete="family-name" class="form-control" id="last-name" maxlength="100" name="last_name" required value="<?= $old('last_name') ?>"></div>
                <div class="col-md-4"><label class="form-label" for="date-of-birth">Date de naissance <span aria-hidden="true">*</span></label><input class="form-control" id="date-of-birth" max="<?= gmdate('Y-m-d') ?>" min="<?= gmdate('Y-m-d', strtotime('-5 years')) ?>" name="date_of_birth" required type="date" value="<?= $old('date_of_birth') ?>"></div>
                <div class="col-md-4"><label class="form-label" for="sex">Sexe <span aria-hidden="true">*</span></label><select class="form-select" id="sex" name="sex" required><option value="">Choisir une option</option><?php foreach ($sexLabels as $code => $label): ?><option value="<?= $e($code) ?>"<?= ($oldInput['sex'] ?? '') === $code ? ' selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label" for="relationship">Votre lien avec l’enfant <span aria-hidden="true">*</span></label><select class="form-select" id="relationship" name="relationship_type" required><?php foreach ($rolesRelation as $code => $label): ?><option value="<?= $e($code) ?>"<?= ($oldInput['relationship_type'] ?? 'parent') === $code ? ' selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?></select></div>
                <div class="col-12">
                    <label class="child-management-consent">
                        <input class="form-check-input" name="consent_granted" type="checkbox" value="1" required<?= ($oldInput['consent_granted'] ?? null) === '1' ? ' checked' : '' ?>>
                        <span><strong>Autoriser le partage des données de santé</strong><small>Je confirme être autorisé(e) à enregistrer ce profil et consens au partage avec les professionnels habilités. Je comprends que je peux retirer ce consentement.</small></span>
                    </label>
                </div>
                <div class="col-12 child-management-form-actions"><span>Les informations sont protégées et limitées aux personnes autorisées.</span><button class="btn btn-primary" type="submit">Enregistrer le profil</button></div>
            </form>
        </div>
    </details>

    <section aria-labelledby="child-list-title" class="child-management-list">
        <header class="child-management-list-heading">
            <div><h3 id="child-list-title">Mes enfants</h3><p>Profils rattachés à votre compte parent</p></div>
            <span class="child-management-count"><?= count($profiles) ?> profil<?= count($profiles) === 1 ? '' : 's' ?></span>
        </header>

        <?php if ($profiles === []): ?>
            <div class="child-management-empty">
                <span aria-hidden="true">♡</span>
                <strong>Aucun profil pour le moment</strong>
                <p>Ajoutez le profil d’un enfant pour retrouver ici ses informations.</p>
                <a class="btn btn-outline-primary" href="#add-child" data-open-details="add-child">Ajouter un enfant</a>
            </div>
        <?php else: ?>
            <div class="child-management-profile-list">
                <?php foreach ($profiles as $profile):
                    $formId = 'child-form-' . (int) $profile['id'];
                    $isEditing = $failedChildId === (int) $profile['id'];
                    $formValues = $isEditing ? $oldInput : $profile;
                    $consentGranted = $isEditing
                        ? ($oldInput['consent_granted'] ?? null) === '1'
                        : $profile['consent_status'] === 'granted';
                    $birthDate = new DateTimeImmutable((string) $profile['date_of_birth'], new DateTimeZone('UTC'));
                    $age = $birthDate->diff(new DateTimeImmutable('today', new DateTimeZone('UTC')));
                    $ageLabel = $age->y > 0
                        ? $age->y . ' an' . ($age->y > 1 ? 's' : '') . ($age->m > 0 ? ' et ' . $age->m . ' mois' : '')
                        : $age->m . ' mois';
                ?>
                    <article class="child-management-profile">
                        <div class="child-management-profile-summary">
                            <span aria-hidden="true" class="child-management-avatar"><?= $e(strtoupper(substr((string) $profile['first_name'], 0, 1))) ?></span>
                            <div class="child-management-profile-name">
                                <h4><?= $e($profile['first_name'] . ' ' . $profile['last_name']) ?></h4>
                                <p><?= $e($ageLabel) ?> <span aria-hidden="true">·</span> Né(e) le <?= $e($birthDate->format('d/m/Y')) ?></p>
                            </div>
                            <div class="child-management-profile-meta">
                                <span class="child-management-sex"><?= $e($sexLabels[$profile['sex']] ?? 'Non renseigné') ?></span>
                                <span class="child-management-consent-badge<?= $profile['consent_status'] === 'granted' ? ' is-granted' : ' is-withdrawn' ?>">
                                    <span aria-hidden="true"><?= $profile['consent_status'] === 'granted' ? '✓' : '!' ?></span>
                                    <?= $e($consentLabels[$profile['consent_status']] ?? 'Non défini') ?>
                                </span>
                            </div>
                            <button aria-controls="<?= $e($formId) ?>-editor" aria-expanded="<?= $isEditing ? 'true' : 'false' ?>" class="btn btn-outline-primary child-management-edit" data-edit-toggle type="button"><?= $isEditing ? 'Fermer' : 'Modifier' ?></button>
                        </div>
                        <div class="child-management-editor" id="<?= $e($formId) ?>-editor"<?= $isEditing ? '' : ' hidden' ?>>
                            <form action="<?= $e($basePath) ?>/pages/gestion-enfants.php" id="<?= $e($formId) ?>" method="post" class="row g-3">
                                <input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="child_id" type="hidden" value="<?= (int) $profile['id'] ?>">
                                <div class="col-md-6"><label class="form-label" for="<?= $e($formId) ?>-first">Prénom</label><input class="form-control" id="<?= $e($formId) ?>-first" maxlength="100" name="first_name" required value="<?= $e($formValues['first_name'] ?? $profile['first_name']) ?>"></div>
                                <div class="col-md-6"><label class="form-label" for="<?= $e($formId) ?>-last">Nom</label><input class="form-control" id="<?= $e($formId) ?>-last" maxlength="100" name="last_name" required value="<?= $e($formValues['last_name'] ?? $profile['last_name']) ?>"></div>
                                <div class="col-md-4"><label class="form-label" for="<?= $e($formId) ?>-birth">Date de naissance</label><input class="form-control" id="<?= $e($formId) ?>-birth" max="<?= gmdate('Y-m-d') ?>" min="<?= gmdate('Y-m-d', strtotime('-5 years')) ?>" name="date_of_birth" required type="date" value="<?= $e($formValues['date_of_birth'] ?? $profile['date_of_birth']) ?>"></div>
                                <div class="col-md-4"><label class="form-label" for="<?= $e($formId) ?>-sex">Sexe</label><select class="form-select" id="<?= $e($formId) ?>-sex" name="sex" required><?php foreach ($sexLabels as $code => $label): ?><option value="<?= $e($code) ?>"<?= ($formValues['sex'] ?? $profile['sex']) === $code ? ' selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-4"><label class="form-label" for="<?= $e($formId) ?>-relation">Lien avec l’enfant</label><select class="form-select" id="<?= $e($formId) ?>-relation" name="relationship_type" required><?php foreach ($rolesRelation as $code => $label): ?><option value="<?= $e($code) ?>"<?= ($formValues['relationship_type'] ?? $profile['relationship_type']) === $code ? ' selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?></select></div>
                                <div class="col-12">
                                    <label class="child-management-consent child-management-consent-compact">
                                        <input class="form-check-input" name="consent_granted" type="checkbox" value="1"<?= $consentGranted ? ' checked' : '' ?>>
                                        <span><strong>Partage des données de santé</strong><small>Décochez pour retirer le consentement. Les professionnels ne pourront alors plus consulter les données de cet enfant.</small></span>
                                    </label>
                                </div>
                                <div class="col-12 child-management-form-actions"><button class="btn btn-primary" type="submit">Enregistrer les modifications</button><button class="btn btn-link child-management-cancel" data-edit-cancel type="button">Annuler</button></div>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p class="child-management-disclaimer"><span aria-hidden="true">i</span> Cette plateforme aide au suivi et ne remplace pas une consultation ni un avis médical. En cas d’urgence, contactez un centre de santé.</p>
    </section>
</div>
<script>
(() => {
    const page = document.querySelector('.child-management');
    const dashboardSections = new Set(['overview', 'children', 'vaccines', 'notifications', 'centers']);
    const fragment = window.location.hash.slice(1);
    if (
        page instanceof HTMLElement &&
        dashboardSections.has(fragment) &&
        !document.getElementById(fragment)
    ) {
        window.location.replace(`${page.dataset.dashboardUrl}#${fragment}`);
        return;
    }

    document.querySelectorAll('[data-open-details]').forEach((link) => {
        link.addEventListener('click', () => {
            const details = document.getElementById(link.dataset.openDetails);
            if (details instanceof HTMLDetailsElement) details.open = true;
        });
    });

    document.querySelectorAll('[data-edit-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const editor = document.getElementById(button.getAttribute('aria-controls'));
            if (!(editor instanceof HTMLElement)) return;
            const opening = editor.hidden;
            editor.hidden = !opening;
            button.setAttribute('aria-expanded', String(opening));
            button.textContent = opening ? 'Fermer' : 'Modifier';
            if (opening) editor.querySelector('input:not([type="hidden"])')?.focus();
        });
    });

    document.querySelectorAll('[data-edit-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            const editor = button.closest('.child-management-editor');
            if (!(editor instanceof HTMLElement)) return;
            editor.querySelector('form')?.reset();
            editor.hidden = true;
            const profile = editor.closest('.child-management-profile');
            const toggle = profile?.querySelector('[data-edit-toggle]');
            toggle?.setAttribute('aria-expanded', 'false');
            if (toggle instanceof HTMLButtonElement) toggle.textContent = 'Modifier';
        });
    });
})();
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/dashboard.php';

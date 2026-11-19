<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$roles = [
    'parent' => 'Parent',
    'community_health_worker' => 'Agent communautaire',
    'health_professional' => 'Professionnel de santé',
    'health_center_manager' => 'Responsable de centre',
    'health_supervisor' => 'Superviseur sanitaire',
    'administrator' => 'Administrateur',
];
$roleCounts = array_fill_keys(array_keys($roles), 0);
$activeCount = 0;
foreach ($users as $account) {
    if (isset($roleCounts[$account['role_code'] ?? ''])) {
        $roleCounts[$account['role_code']]++;
    }
    if ((int) ($account['is_active'] ?? 0) === 1) {
        $activeCount++;
    }
}
$accountCount = count($users);

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f8fc">
    <title>Gestion des utilisateurs — AFYAMTOTO AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $e($basePath) ?>/public/assets/css/design-system.css">
    <link rel="stylesheet" href="<?= $e($basePath) ?>/public/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= $e($basePath) ?>/public/assets/css/admin-users.css">
</head>
<body class="admin-users-page">
<div class="admin-users-shell">
    <aside aria-label="Navigation administrateur" class="admin-users-sidebar">
        <a class="admin-users-brand" href="<?= $e($basePath) ?>/pages/espace-administrateur.php">
            <span aria-hidden="true" class="admin-users-brand-mark">✚</span>
            <span><strong>AFYAMTOTO <em>AI</em></strong><small>Portail de santé</small></span>
        </a>
        <p class="admin-users-nav-label">ESPACE ADMINISTRATEUR</p>
        <nav class="admin-users-nav">
            <a href="<?= $e($basePath) ?>/pages/espace-administrateur.php"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg></span> Tableau de bord</a>
            <a aria-current="page" class="is-current" href="<?= $e($basePath) ?>/pages/gestion-utilisateurs.php"><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20v-1.5a6.5 6.5 0 0 1 13 0V20zM17 5.5a3 3 0 0 1 0 5.8M18 14a5 5 0 0 1 3.5 4.8V20h-3"/></svg></span> Utilisateurs</a>
                <a href="<?= $e($basePath) ?>/pages/gestion-centres.php"><span aria-hidden="true">⌖</span> Centres de santé</a>
                <a href="<?= $e($basePath) ?>/pages/gestion-referentiels.php"><span aria-hidden="true">✚</span> Référentiels</a>
            <a href="<?= $e($basePath) ?>/pages/espace-intelligence.php"><span aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="5" rx="1"/><rect x="13" y="10" width="8" height="11" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/></svg></span> Vue d’ensemble</a>
        </nav>
        <div class="admin-users-sidebar-footer">
            <span aria-hidden="true" class="admin-users-secure-icon">✓</span>
            <div><strong>Accès sécurisé</strong><small>Session administrateur</small></div>
        </div>
    </aside>

    <main class="admin-users-main">
        <header class="admin-users-topbar">
            <div class="admin-users-breadcrumb"><span>Administration</span><span aria-hidden="true">/</span><strong>Utilisateurs</strong></div>
            <a class="admin-users-dashboard-link" href="<?= $e($basePath) ?>/pages/espace-administrateur.php"><span aria-hidden="true">←</span> Tableau de bord</a>
        </header>

        <div class="admin-users-content">
            <section class="admin-users-heading">
                <div>
                    <p class="admin-users-eyebrow">GESTION DES ACCÈS</p>
                    <h1>Utilisateurs</h1>
                    <p>Créez les comptes, gérez les rôles et contrôlez les accès aux centres de santé.</p>
                </div>
                <a class="btn btn-primary admin-users-create-cta" href="#create-user"><span aria-hidden="true">＋</span> Nouveau compte</a>
            </section>

            <?php if (is_array($notice ?? null)): ?>
                <div class="alert alert-success admin-users-alert" role="status"><span aria-hidden="true">✓</span><?= $e($notice['message']) ?></div>
            <?php endif; ?>
            <?php if ($errors !== []): ?>
                <div class="alert alert-danger admin-users-alert" role="alert"><span aria-hidden="true">!</span><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <section aria-label="Résumé des comptes affichés" class="admin-users-stats">
                <article class="admin-users-stat-card"><span class="admin-users-stat-label">Comptes affichés</span><strong><?= $accountCount ?></strong></article>
                <article class="admin-users-stat-card"><span class="admin-users-stat-label">Comptes actifs</span><strong id="active-account-count"><?= $activeCount ?></strong></article>
                <article class="admin-users-stat-card"><span class="admin-users-stat-label">Équipe sanitaire</span><strong><?= $roleCounts['community_health_worker'] + $roleCounts['health_professional'] + $roleCounts['health_center_manager'] + $roleCounts['health_supervisor'] ?></strong></article>
            </section>

            <details class="admin-users-create-card" id="create-user"<?= $errors !== [] ? ' open' : '' ?>>
                <summary>
                    <span class="admin-users-create-symbol" aria-hidden="true">＋</span>
                    <span class="admin-users-create-title"><strong>Créer un compte utilisateur</strong><small>Ajoutez un parent ou un membre autorisé de l’équipe de santé.</small></span>
                    <span aria-hidden="true" class="admin-users-chevron">⌄</span>
                </summary>
                <div class="admin-users-create-body">
                    <form action="<?= $e($basePath) ?>/pages/gestion-utilisateurs.php" method="post" class="row g-3">
                        <input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>">
                        <div class="col-md-6"><label class="form-label" for="new-name">Nom complet</label><input autocomplete="name" class="form-control" id="new-name" maxlength="160" name="display_name" required></div>
                        <div class="col-md-6"><label class="form-label" for="new-role">Rôle du compte</label><select class="form-select" id="new-role" name="role_code" required><?php foreach ($roles as $code => $label): ?><option value="<?= $e($code) ?>"><?= $e($label) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label" for="new-email">Adresse e-mail</label><input autocomplete="email" class="form-control" id="new-email" maxlength="254" name="email" required type="email" placeholder="nom@exemple.com"></div>
                        <div class="col-md-6"><label class="form-label" for="new-phone">Téléphone <span class="fw-normal text-secondary">(facultatif)</span></label><input autocomplete="tel" class="form-control" id="new-phone" maxlength="16" name="phone_e164" pattern="\+[1-9][0-9]{1,14}" placeholder="+243…"></div>
                        <div class="col-md-6"><label class="form-label" for="new-center">Centres de santé <span class="fw-normal text-secondary">(personnel et supervision)</span></label><select class="form-select admin-users-multi-select" id="new-center" multiple name="health_center_ids[]"><?php foreach ($healthCenters as $center): ?><option value="<?= (int) $center['id'] ?>"><?= $e($center['name'] . ($center['city'] ? ' — ' . $center['city'] : '')) ?></option><?php endforeach; ?></select><div class="form-text">Maintenez Ctrl (ou ⌘ sur Mac) pour choisir plusieurs centres.</div></div>
                        <div class="col-md-6"><label class="form-label" for="new-password">Mot de passe temporaire</label><input autocomplete="new-password" class="form-control" id="new-password" maxlength="72" minlength="12" name="password" required type="password"><div class="form-text">12 caractères avec 3 types de caractères, ou 20 caractères minimum.</div></div>
                        <div class="col-12 admin-users-form-footer"><span>Le compte sera actif dès sa création.</span><input name="is_active" type="hidden" value="1"><button class="btn btn-primary" type="submit">Créer le compte <span aria-hidden="true">→</span></button></div>
                    </form>
                </div>
            </details>

            <section aria-labelledby="accounts-title" class="admin-users-list-card">
                <div class="admin-users-list-heading">
                    <div><h2 id="accounts-title">Comptes utilisateurs</h2><p>Recherchez un compte ou ouvrez sa fiche pour la modifier.</p></div>
                    <span class="admin-users-count"><span id="visible-user-count"><?= $accountCount ?></span> compte<?= $accountCount > 1 ? 's' : '' ?></span>
                </div>
                <div class="admin-users-toolbar">
                    <label class="admin-users-search"><span aria-hidden="true">⌕</span><span class="visually-hidden">Rechercher un utilisateur</span><input id="user-search" autocomplete="off" type="search" placeholder="Rechercher un nom, e-mail ou téléphone…"></label>
                    <label class="visually-hidden" for="role-filter">Filtrer par rôle</label>
                    <select class="form-select admin-users-filter" id="role-filter"><option value="">Tous les rôles</option><?php foreach ($roles as $code => $label): ?><option value="<?= $e($code) ?>"><?= $e($label) ?></option><?php endforeach; ?></select>
                    <label class="visually-hidden" for="status-filter">Filtrer par statut</label>
                    <select class="form-select admin-users-filter" id="status-filter"><option value="">Tous les statuts</option><option value="1">Actifs</option><option value="0">Désactivés</option></select>
                </div>

                <?php if ($users === []): ?>
                    <div class="admin-users-empty"><span aria-hidden="true">♙</span><strong>Aucun utilisateur à afficher</strong><p>Créez un compte pour commencer à gérer les accès.</p></div>
                <?php else: ?>
                    <div class="table-responsive admin-users-table-wrap">
                        <table class="table admin-users-table align-middle">
                            <thead><tr><th scope="col">Utilisateur</th><th scope="col">Coordonnées</th><th scope="col">Rôle</th><th scope="col">Statut</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                            <tbody>
                            <?php foreach ($users as $user):
                                $formId = 'user-form-' . (int) $user['id'];
                                $isActive = (int) $user['is_active'] === 1;
                                $initials = strtoupper(substr((string) $user['display_name'], 0, 1));
                                $searchText = strtolower(implode(' ', array_filter([
                                    (string) $user['display_name'],
                                    (string) ($user['email'] ?? ''),
                                    (string) ($user['phone_e164'] ?? ''),
                                ])));
                            ?>
                                <tr data-role="<?= $e($user['role_code']) ?>" data-status="<?= $isActive ? '1' : '0' ?>" data-search="<?= $e($searchText) ?>">
                                    <td><div class="admin-users-person"><span aria-hidden="true" class="admin-users-avatar"><?= $e($initials) ?></span><div><strong><?= $e($user['display_name']) ?></strong><small>ID #<?= (int) $user['id'] ?></small></div></div></td>
                                    <td><span class="admin-users-contact"><?= $e($user['email'] ?? '—') ?></span><small class="admin-users-phone"><?= $e($user['phone_e164'] ?? 'Téléphone non renseigné') ?></small></td>
                                    <td><span class="admin-users-role"><?= $e($roles[$user['role_code']] ?? $user['role_code']) ?></span></td>
                                    <td><span class="admin-users-status-pill<?= $isActive ? ' is-active' : ' is-inactive' ?>"><?= $isActive ? 'Actif' : 'Désactivé' ?></span></td>
                                    <td><button aria-expanded="false" aria-controls="<?= $e($formId) ?>-editor" class="btn btn-outline-primary admin-users-edit-toggle" data-edit-toggle type="button">Modifier</button></td>
                                </tr>
                                <tr class="admin-users-editor-row" hidden id="<?= $e($formId) ?>-editor" data-editor-for="<?= $e($formId) ?>">
                                    <td colspan="5">
                                        <form action="<?= $e($basePath) ?>/pages/gestion-utilisateurs.php" class="admin-users-editor" id="<?= $e($formId) ?>" method="post">
                                            <input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>">
                                            <input name="user_id" type="hidden" value="<?= (int) $user['id'] ?>">
                                            <div><label class="form-label" for="<?= $e($formId) ?>-name">Nom complet</label><input class="form-control" id="<?= $e($formId) ?>-name" maxlength="160" name="display_name" required value="<?= $e($user['display_name']) ?>"></div>
                                            <div><label class="form-label" for="<?= $e($formId) ?>-email">Adresse e-mail</label><input class="form-control" id="<?= $e($formId) ?>-email" maxlength="254" name="email" required type="email" value="<?= $e($user['email']) ?>"></div>
                                            <div><label class="form-label" for="<?= $e($formId) ?>-phone">Téléphone</label><input class="form-control" id="<?= $e($formId) ?>-phone" maxlength="16" name="phone_e164" pattern="\+[1-9][0-9]{1,14}" value="<?= $e($user['phone_e164']) ?>" placeholder="+243…"></div>
                                            <div><label class="form-label" for="<?= $e($formId) ?>-role">Rôle</label><select class="form-select" id="<?= $e($formId) ?>-role" name="role_code"><?php foreach ($roles as $code => $label): ?><option value="<?= $e($code) ?>"<?= $user['role_code'] === $code ? ' selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?></select></div>
                                            <div class="admin-users-editor-centers"><label class="form-label" for="<?= $e($formId) ?>-center">Centres de santé</label><select class="form-select admin-users-multi-select" id="<?= $e($formId) ?>-center" multiple name="health_center_ids[]"><?php foreach ($healthCenters as $center): ?><option value="<?= (int) $center['id'] ?>"<?= in_array((int) $center['id'], $healthCenterAssignments[(int) $user['id']] ?? [], true) ? ' selected' : '' ?>><?= $e($center['name']) ?></option><?php endforeach; ?></select></div>
                                            <div><label class="form-label" for="<?= $e($formId) ?>-password">Nouveau mot de passe <span class="fw-normal text-secondary">(facultatif)</span></label><input autocomplete="new-password" class="form-control" id="<?= $e($formId) ?>-password" maxlength="72" minlength="12" name="password" placeholder="Laisser vide pour conserver" type="password"></div>
                                            <label class="admin-users-editor-active"><input class="form-check-input" name="is_active" type="checkbox" value="1"<?= $isActive ? ' checked' : '' ?>><span>Compte actif</span></label>
                                            <div class="admin-users-editor-actions"><button class="btn btn-primary" type="submit">Enregistrer</button><button class="btn btn-link" data-edit-cancel type="button">Annuler</button></div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="admin-users-table-note">Liste limitée aux 200 comptes les plus récents. La dernière personne administratrice active ne peut pas être désactivée.</p>
                <?php endif; ?>
            </section>
            <footer class="admin-users-footer">AFYAMTOTO AI <span aria-hidden="true">·</span> Gestion sécurisée des accès de la plateforme</footer>
        </div>
    </main>
</div>
<script>
(() => {
    const search = document.getElementById('user-search');
    const role = document.getElementById('role-filter');
    const status = document.getElementById('status-filter');
    const rows = Array.from(document.querySelectorAll('.admin-users-table tbody tr[data-role]'));
    const count = document.getElementById('visible-user-count');

    const filterRows = () => {
        const query = search.value.trim().toLocaleLowerCase('fr');
        let visible = 0;
        rows.forEach((row) => {
            const matches = row.dataset.search.includes(query)
                && (role.value === '' || row.dataset.role === role.value)
                && (status.value === '' || row.dataset.status === status.value);
            row.hidden = !matches;
            const editor = row.nextElementSibling;
            if (!matches) {
                editor.hidden = true;
                const toggle = row.querySelector('[data-edit-toggle]');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.textContent = 'Modifier';
            }
            if (matches) visible++;
        });
        count.textContent = String(visible);
    };

    search?.addEventListener('input', filterRows);
    role?.addEventListener('change', filterRows);
    status?.addEventListener('change', filterRows);

    document.querySelectorAll('[data-edit-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('tr');
            const editor = row.nextElementSibling;
            const opening = editor.hidden;
            editor.hidden = !opening;
            button.setAttribute('aria-expanded', String(opening));
            button.textContent = opening ? 'Fermer' : 'Modifier';
        });
    });

    document.querySelectorAll('[data-edit-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            const editor = button.closest('.admin-users-editor-row');
            editor.hidden = true;
            const toggle = editor.previousElementSibling.querySelector('[data-edit-toggle]');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.textContent = 'Modifier';
        });
    });

    const newRole = document.getElementById('new-role');
    const newCenters = document.getElementById('new-center');
    const syncNewCenters = () => {
        const enabled = ['community_health_worker', 'health_professional', 'health_center_manager', 'health_supervisor'].includes(newRole.value);
        newCenters.disabled = !enabled;
        if (!enabled) Array.from(newCenters.options).forEach((option) => { option.selected = false; });
    };
    newRole?.addEventListener('change', syncNewCenters);
    if (newRole && newCenters) syncNewCenters();

    document.querySelectorAll('.admin-users-editor select[name="role_code"]').forEach((select) => {
        const syncCenters = () => {
            const centers = select.closest('form').querySelector('.admin-users-editor-centers select');
            const enabled = ['community_health_worker', 'health_professional', 'health_center_manager', 'health_supervisor'].includes(select.value);
            centers.disabled = !enabled;
            if (!enabled) Array.from(centers.options).forEach((option) => { option.selected = false; });
        };
        select.addEventListener('change', syncCenters);
        syncCenters();
    });
})();
</script>
</body>
</html>

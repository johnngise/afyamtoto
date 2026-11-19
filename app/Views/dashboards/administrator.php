<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$roleLabels = [
    'administrator' => 'Administrateur',
    'parent' => 'Parent',
    'community_health_worker' => 'Agent communautaire',
    'health_professional' => 'Professionnel de santé',
    'health_center_manager' => 'Responsable de centre',
    'health_supervisor' => 'Superviseur sanitaire',
];

?>
<div class="dashboard-page-content">
    <section class="dashboard-welcome" id="overview">
        <div><p class="dashboard-eyebrow">Console de gestion</p><h2>Administration</h2><p>Statistiques des comptes et actions récentes issues de la base de données.</p></div>
        <a class="btn btn-primary" href="<?= $e($basePath) ?>/pages/gestion-utilisateurs.php">Gérer les utilisateurs</a>
        <a class="btn btn-outline-primary" href="<?= $e($basePath) ?>/pages/gestion-centres.php">Gérer les centres</a>
        <a class="btn btn-outline-primary" href="<?= $e($basePath) ?>/pages/gestion-referentiels.php">Référentiels sanitaires</a>
    </section>
    <section aria-label="Statistiques utilisateurs" class="dashboard-stat-grid">
        <?php foreach ($dashboardData['stats'] as $index => $stat): ?>
            <article class="dashboard-stat-card"><span aria-hidden="true" class="dashboard-stat-icon dashboard-stat-icon-<?= ($index % 3) + 1 ?>"><?= ['♙', '♡', '↗', '✚', '⌖', '▥'][$index] ?></span><p><?= $e($stat['label']) ?></p><strong><?= $e($stat['value']) ?></strong><small>Valeur réelle de la base</small></article>
        <?php endforeach; ?>
    </section>
    <section aria-labelledby="admin-users-title" class="dashboard-section" id="users">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Comptes</p><h2 id="admin-users-title">Derniers utilisateurs inscrits</h2></div><a class="btn btn-outline-primary" href="<?= $e($basePath) ?>/pages/gestion-utilisateurs.php">Créer ou modifier un compte</a></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Statut</th><th>Inscription UTC</th></tr></thead><tbody>
            <?php foreach ($dashboardData['users'] as $account): ?>
                <tr><td><?= $e($account['name']) ?></td><td><?= $e($account['email'] ?? '—') ?></td><td><?= $e($roleLabels[$account['role_code']] ?? $account['role_code']) ?></td><td><span class="dashboard-status<?= (int) $account['is_active'] === 1 ? '' : ' is-inactive' ?>"><?= (int) $account['is_active'] === 1 ? 'Actif' : 'Désactivé' ?></span></td><td><?= $e($account['created_at_utc']) ?></td></tr>
            <?php endforeach; ?>
            <?php if ($dashboardData['users'] === []): ?><tr><td colspan="5">Aucun utilisateur enregistré.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section aria-labelledby="admin-activity-title" class="dashboard-section" id="activity">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Traçabilité</p><h2 id="admin-activity-title">Activités récentes</h2><p>Les journaux ne contiennent pas de mot de passe ni de note médicale.</p></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Action</th><th>Compte</th><th>Détail technique</th><th>Date UTC</th></tr></thead><tbody>
            <?php foreach ($dashboardData['activities'] as $activity): ?>
                <tr><td><?= $e($activity['action']) ?></td><td><?= $e($activity['display_name'] ?? 'Compte supprimé') ?></td><td><?= $e($activity['description'] ?? '—') ?></td><td><?= $e($activity['created_at_utc']) ?></td></tr>
            <?php endforeach; ?>
            <?php if ($dashboardData['activities'] === []): ?><tr><td colspan="4">Aucune activité enregistrée.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>

<?php

declare(strict_types=1);

$sidebarItemsByRole = [
    'parent' => [
        'label' => 'Espace parent',
        'items' => [
            ['label' => 'Vue d’ensemble', 'href' => '#overview', 'icon' => '⌂'],
            ['label' => 'Mes enfants', 'href' => '#children', 'icon' => '♡'],
            ['label' => 'Gérer les profils', 'href' => '/pages/gestion-enfants.php', 'icon' => '✎', 'key' => 'manage-children'],
            ['label' => 'Vaccinations', 'href' => '/pages/vaccinations.php', 'icon' => '✚', 'key' => 'vaccinations'],
            ['label' => 'Notifications', 'href' => '#notifications', 'icon' => '•'],
            ['label' => 'Centres de santé', 'href' => '#centers', 'icon' => '⌖'],
        ],
    ],
    'health-worker' => [
        'label' => 'Espace agent de santé',
        'items' => [
            ['label' => 'Vue d’ensemble', 'href' => '#overview', 'icon' => '⌂'],
            ['label' => 'Enfants suivis', 'href' => '#patients', 'icon' => '♡'],
            ['label' => 'Inscrire un enfant', 'href' => '#enrollment', 'icon' => '＋'],
            ['label' => 'Croissance et nutrition', 'href' => '#actions', 'icon' => '↗'],
            ['label' => 'Visites', 'href' => '#visits', 'icon' => '⌂'],
            ['label' => 'Référencements', 'href' => '#referrals', 'icon' => '⚑'],
            ['label' => 'Vaccinations', 'href' => '/pages/vaccinations.php', 'icon' => '✚', 'key' => 'vaccinations'],
            ['label' => 'Activité récente', 'href' => '#activity', 'icon' => '▤'],
        ],
    ],
    'health-professional' => [
        'label' => 'Espace professionnel de santé',
        'items' => [
            ['label' => 'Vue d’ensemble', 'href' => '#overview', 'icon' => '⌂'],
            ['label' => 'Alertes à examiner', 'href' => '#alerts', 'icon' => '!'],
            ['label' => 'Orientations reçues', 'href' => '#referrals', 'icon' => '⚑'],
            ['label' => 'Croissance et nutrition', 'href' => '#indicators', 'icon' => '↗'],
            ['label' => 'Observations', 'href' => '#observations', 'icon' => '▤'],
            ['label' => 'Vaccinations', 'href' => '/pages/vaccinations.php', 'icon' => '✚', 'key' => 'vaccinations'],
        ],
    ],
    'health-center' => [
        'label' => 'Espace établissement de santé',
        'items' => [
            ['label' => 'Vue d’ensemble', 'href' => '#overview', 'icon' => '⌂'],
            ['label' => 'Référencements reçus', 'href' => '#referrals', 'icon' => '⚑'],
            ['label' => 'Demandes de rendez-vous', 'href' => '#appointments', 'icon' => '◷'],
            ['label' => 'Professionnels affectés', 'href' => '#professionals', 'icon' => '♙'],
        ],
    ],
    'health-supervisor' => [
        'label' => 'Supervision sanitaire',
        'items' => [
            ['label' => 'Vue d’ensemble', 'href' => '#overview', 'icon' => '⌂'],
            ['label' => 'Indicateurs agrégés', 'href' => '#indicators', 'icon' => '▥'],
            ['label' => 'Couverture par centre', 'href' => '#centers', 'icon' => '⌖'],
        ],
    ],
    'intelligence' => [
        'label' => 'AfyamToto Intelligence',
        'items' => [
            ['label' => 'Vue d’ensemble', 'href' => '#overview', 'icon' => '⌂'],
            ['label' => 'Indicateurs', 'href' => '#indicators', 'icon' => '▥'],
            ['label' => 'Alertes à examiner', 'href' => '#alerts', 'icon' => '!'],
            ['label' => 'Zones de santé', 'href' => '#health-zones', 'icon' => '◎'],
            ['label' => 'Analyses', 'href' => '#analytics', 'icon' => '↗'],
            ['label' => 'Administration', 'href' => '/pages/espace-administrateur.php', 'icon' => '⚙'],
        ],
    ],
    'administrator' => [
        'label' => 'Administration',
        'items' => [
            ['label' => 'Vue d’ensemble', 'href' => '#overview', 'icon' => '⌂'],
            ['label' => 'Utilisateurs', 'href' => '/pages/gestion-utilisateurs.php', 'icon' => '♙'],
            ['label' => 'Centres de santé', 'href' => '/pages/gestion-centres.php', 'icon' => '⌖', 'key' => 'health-centers'],
            ['label' => 'Référentiels sanitaires', 'href' => '/pages/gestion-referentiels.php', 'icon' => '✚', 'key' => 'health-references'],
            ['label' => 'Activités récentes', 'href' => '#activity', 'icon' => '▤'],
            ['label' => 'AfyamToto Intelligence', 'href' => '/pages/espace-intelligence.php', 'icon' => '▥'],
        ],
    ],
];

$sidebarRole = $role ?? '';
if (!isset($sidebarItemsByRole[$sidebarRole])) {
    throw new InvalidArgumentException('Le rôle fourni pour la navigation est invalide.');
}

$sidebarConfig = $sidebarItemsByRole[$sidebarRole];
$sidebarRoleLabel = htmlspecialchars($sidebarConfig['label'], ENT_QUOTES, 'UTF-8');
$sidebarSafeBasePath = htmlspecialchars($basePath ?? '..', ENT_QUOTES, 'UTF-8');
$sidebarCurrentItem = $currentItem ?? 'overview';

?>
<aside aria-label="<?= $sidebarRoleLabel ?>" class="dashboard-sidebar" data-dashboard-sidebar id="dashboard-sidebar">
    <a class="dashboard-brand" href="<?= $sidebarSafeBasePath ?>/index.php">
        <span aria-hidden="true" class="dashboard-brand-mark">✚</span>
        <span class="dashboard-brand-copy">
            <strong>AFYAMTOTO <span>AI</span></strong>
            <small><?= $sidebarRoleLabel ?></small>
        </span>
    </a>
    <p class="dashboard-sidebar-caption">MENU PRINCIPAL</p>
    <nav aria-label="Navigation <?= $sidebarRoleLabel ?>" class="dashboard-sidebar-nav">
        <?php foreach ($sidebarConfig['items'] as $item): ?>
            <?php $isCurrent = $item['href'] === '#' . $sidebarCurrentItem || ($item['key'] ?? null) === $sidebarCurrentItem; ?>
            <?php
            $itemHref = str_starts_with($item['href'], '/')
                ? $sidebarSafeBasePath . $item['href']
                : $item['href'];
            if ($sidebarRole === 'parent' && $sidebarCurrentItem === 'manage-children' && str_starts_with($item['href'], '#')) {
                $itemHref = $sidebarSafeBasePath . '/pages/espace-parent.php' . $item['href'];
            }
            ?>
            <a<?= $isCurrent ? ' aria-current="page"' : '' ?> class="dashboard-sidebar-link<?= $isCurrent ? ' is-active' : '' ?>" href="<?= htmlspecialchars($itemHref, ENT_QUOTES, 'UTF-8') ?>">
                <span aria-hidden="true" class="dashboard-sidebar-icon"><?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="dashboard-sidebar-note">
        <span aria-hidden="true">i</span>
        <p>Données visibles selon vos autorisations.</p>
    </div>
</aside>

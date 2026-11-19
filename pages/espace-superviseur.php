<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Controllers/DashboardController.php';
require_once dirname(__DIR__) . '/app/Middleware/RequireRole.php';
require_once dirname(__DIR__) . '/app/Middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/config/database.php';

AuthService::startSession();
header('Cache-Control: no-store, private');
$basePath = '..';
$sessionUser = AuthService::currentUser();
if ($sessionUser === null) {
    header('Location: ../pages/connexion-agent.php', true, 303);
    exit;
}
if (($sessionUser['role_code'] ?? null) !== 'health_supervisor') {
    http_response_code(403);
    exit('Accès refusé.');
}

try {
    $database = getDatabaseConnection();
    $authUser = RequireRole::user($database, ['health_supervisor'], '../pages/connexion-agent.php');
    $dashboardData = (new DashboardController($database))->healthSupervisorDashboard($authUser);
} catch (RuntimeException $error) {
    DashboardController::renderServiceUnavailable($basePath, $error);
}

$role = 'health-supervisor';
$pageTitle = 'Supervision sanitaire';
$currentItem = 'overview';
$pageDescription = 'Indicateurs agrégés et protégés des centres sanitaires affectés.';
$csrfToken = CsrfMiddleware::token();
$notice = AuthService::consumeFlash();
ob_start();
require dirname(__DIR__) . '/app/Views/dashboards/health-supervisor.php';
$content = ob_get_clean();
require dirname(__DIR__) . '/app/Views/layouts/dashboard.php';

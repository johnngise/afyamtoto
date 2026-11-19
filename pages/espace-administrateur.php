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
    header('Location: ../pages/connexion-administrateur.php', true, 303);
    exit;
}
if (($sessionUser['role_code'] ?? null) !== 'administrator') {
    http_response_code(403);
    exit('Accès refusé.');
}

try {
    $database = getDatabaseConnection();
    $authUser = RequireRole::user($database, ['administrator'], '../pages/connexion-administrateur.php');
    $dashboardData = (new DashboardController($database))->administratorDashboard();
} catch (RuntimeException $error) {
    DashboardController::renderServiceUnavailable($basePath, $error);
}

$role = 'administrator';
$pageTitle = 'Administration';
$currentItem = 'overview';
$pageDescription = 'Console de gestion réservée aux administrateurs AFYAMTOTO AI.';
$csrfToken = CsrfMiddleware::token();
$notice = AuthService::consumeFlash();
ob_start();
require dirname(__DIR__) . '/app/Views/dashboards/administrator.php';
$content = ob_get_clean();
require dirname(__DIR__) . '/app/Views/layouts/dashboard.php';

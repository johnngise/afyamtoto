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
    $authUser = RequireRole::user(getDatabaseConnection(), ['administrator'], '../pages/connexion-administrateur.php');
} catch (RuntimeException $error) {
    DashboardController::renderServiceUnavailable($basePath, $error);
}

$role = 'intelligence';
$pageTitle = 'AfyamToto Intelligence';
$currentItem = 'overview';
$pageDescription = 'Tableau analytique de démonstration réservé aux administrateurs.';
$dashboardData = (new DashboardController())->intelligenceDashboard();
$csrfToken = CsrfMiddleware::token();
$notice = AuthService::consumeFlash();
ob_start();
require dirname(__DIR__) . '/app/Views/dashboards/intelligence.php';
$content = ob_get_clean();
require dirname(__DIR__) . '/app/Views/layouts/dashboard.php';

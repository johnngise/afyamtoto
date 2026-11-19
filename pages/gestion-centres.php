<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/HealthCenterManagementService.php';
require_once dirname(__DIR__) . '/app/Controllers/DashboardController.php';
require_once dirname(__DIR__) . '/app/Middleware/RequireRole.php';
require_once dirname(__DIR__) . '/app/Middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/config/database.php';

AuthService::startSession();
header('Cache-Control: no-store, private');
$basePath = '..';
$errors = [];
try {
    $database = getDatabaseConnection();
    $authUser = RequireRole::user($database, ['administrator'], '../pages/connexion-administrateur.php');
    $service = new HealthCenterManagementService($database);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        CsrfMiddleware::assertValid($_POST['_csrf'] ?? null);
        $rawId = $_POST['center_id'] ?? null;
        $centerId = is_string($rawId) && ctype_digit($rawId) && (int) $rawId > 0 ? (int) $rawId : null;
        if ($rawId !== null && $centerId === null) {
            throw new InvalidArgumentException('L’identifiant du centre est invalide.');
        }
        $service->save(
            (int) $authUser['id'],
            $centerId,
            is_string($_POST['name'] ?? null) ? $_POST['name'] : '',
            is_string($_POST['phone_e164'] ?? null) ? $_POST['phone_e164'] : '',
            is_string($_POST['address_text'] ?? null) ? $_POST['address_text'] : '',
            is_string($_POST['city'] ?? null) ? $_POST['city'] : '',
            is_string($_POST['province'] ?? null) ? $_POST['province'] : '',
        );
        AuthService::setFlash('success', $centerId === null ? 'Le centre de santé a été créé.' : 'Le profil du centre a été mis à jour.');
        header('Location: ../pages/gestion-centres.php', true, 303);
        exit;
    }
    $centers = $service->centers();
    $notice = AuthService::consumeFlash();
} catch (InvalidArgumentException $error) {
    $errors[] = $error->getMessage();
    $centers = isset($service) ? $service->centers() : [];
    $notice = null;
} catch (RuntimeException $error) {
    DashboardController::renderServiceUnavailable($basePath, $error);
}

$role = 'administrator';
$pageTitle = 'Gestion des centres';
$currentItem = 'health-centers';
$pageDescription = 'Gestion administrative des profils de centres de santé.';
$csrfToken = CsrfMiddleware::token();
ob_start();
require dirname(__DIR__) . '/app/Views/admin/gestion-centres.php';
$content = ob_get_clean();
require dirname(__DIR__) . '/app/Views/layouts/dashboard.php';

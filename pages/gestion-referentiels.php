<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/HealthReferenceManagementService.php';
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
    $service = new HealthReferenceManagementService($database);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        CsrfMiddleware::assertValid($_POST['_csrf'] ?? null);
        $action = $_POST['_action'] ?? null;
        if ($action === 'create_vaccine') {
            $doseCodes = $_POST['dose_codes'] ?? [];
            $doseNames = $_POST['dose_names'] ?? [];
            if (!is_array($doseCodes) || !is_array($doseNames)) {
                throw new InvalidArgumentException('La liste des doses est invalide.');
            }
            $service->createVaccine(
                (int) $authUser['id'],
                is_string($_POST['vaccine_code'] ?? null) ? $_POST['vaccine_code'] : '',
                is_string($_POST['display_name'] ?? null) ? $_POST['display_name'] : '',
                is_string($_POST['description'] ?? null) ? $_POST['description'] : '',
                $doseCodes,
                $doseNames,
            );
            AuthService::setFlash('success', 'Le référentiel vaccinal a été créé et reste désactivé jusqu’à validation clinique.');
            header('Location: ../pages/gestion-referentiels.php#vaccines', true, 303);
            exit;
        }
        if ($action === 'set_vaccine_active') {
            $vaccineId = filter_var($_POST['vaccine_id'] ?? null, FILTER_VALIDATE_INT);
            if ($vaccineId === false) {
                throw new InvalidArgumentException('Sélectionnez un référentiel valide.');
            }
            $service->setVaccineActive((int) $authUser['id'], $vaccineId, ($_POST['is_active'] ?? null) === '1');
            AuthService::setFlash('success', 'La disponibilité du référentiel a été mise à jour.');
            header('Location: ../pages/gestion-referentiels.php#vaccines', true, 303);
            exit;
        }
        throw new InvalidArgumentException('Cette action n’est pas reconnue.');
    }
    $vaccines = $service->vaccines();
    $notice = AuthService::consumeFlash();
} catch (InvalidArgumentException $error) {
    $errors[] = $error->getMessage();
    $vaccines = isset($service) ? $service->vaccines() : [];
    $notice = null;
} catch (PDOException $error) {
    error_log('[AFYAMTOTO reference management] ' . $error->getMessage());
    http_response_code(($error->errorInfo[1] ?? null) === 1062 ? 400 : 503);
    $errors[] = ($error->errorInfo[1] ?? null) === 1062
        ? 'Ce code de vaccin ou de dose existe déjà.'
        : 'La gestion des référentiels est momentanément indisponible.';
    $vaccines = isset($service) ? $service->vaccines() : [];
    $notice = null;
} catch (RuntimeException $error) {
    DashboardController::renderServiceUnavailable($basePath, $error);
}

$role = 'administrator';
$pageTitle = 'Référentiels sanitaires';
$currentItem = 'health-references';
$pageDescription = 'Gestion administrative des références vaccinales, sous réserve de validation clinique.';
$csrfToken = CsrfMiddleware::token();
ob_start();
require dirname(__DIR__) . '/app/Views/admin/gestion-referentiels.php';
$content = ob_get_clean();
require dirname(__DIR__) . '/app/Views/layouts/dashboard.php';

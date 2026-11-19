<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/AppointmentRequestService.php';
require_once dirname(__DIR__) . '/app/Services/ParentGrowthMeasurementService.php';
require_once dirname(__DIR__) . '/app/Controllers/DashboardController.php';
require_once dirname(__DIR__) . '/app/Middleware/RequireRole.php';
require_once dirname(__DIR__) . '/app/Middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/config/database.php';

AuthService::startSession();
header('Cache-Control: no-store, private');
$basePath = '..';
$formSection = 'growth';
$sessionUser = AuthService::currentUser();
if ($sessionUser === null) {
    header('Location: ../pages/connexion.php', true, 303);
    exit;
}
if (($sessionUser['role_code'] ?? null) !== 'parent') {
    http_response_code(403);
    exit('Accès refusé.');
}

try {
    $database = getDatabaseConnection();
    $authUser = RequireRole::user($database, ['parent'], '../pages/connexion.php');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        CsrfMiddleware::assertValid($_POST['_csrf'] ?? null);
        $action = $_POST['_action'] ?? null;
        $formSection = $action === 'request_appointment' ? 'centers' : 'growth';
        $childId = $_POST['child_id'] ?? null;
        if (!is_string($childId) || !ctype_digit($childId) || (int) $childId < 1) {
            throw new InvalidArgumentException('Sélectionnez un profil enfant valide.');
        }

        if ($action === 'record_growth') {
            (new ParentGrowthMeasurementService($database))->record(
                (int) $authUser['id'],
                (int) $childId,
                is_string($_POST['measured_on'] ?? null) ? $_POST['measured_on'] : '',
                is_string($_POST['weight_kg'] ?? null) ? $_POST['weight_kg'] : null,
                is_string($_POST['length_height_cm'] ?? null) ? $_POST['length_height_cm'] : null,
                is_string($_POST['length_height_type'] ?? null) ? $_POST['length_height_type'] : null,
            );
            AuthService::setFlash('success', 'La mesure de croissance a été enregistrée.');
            header('Location: ../pages/espace-parent.php#growth', true, 303);
            exit;
        }

        if ($action === 'request_appointment') {
            $centerId = $_POST['health_center_id'] ?? null;
            if (!is_string($centerId) || !ctype_digit($centerId) || (int) $centerId < 1) {
                throw new InvalidArgumentException('Sélectionnez un centre de santé valide.');
            }
            (new AppointmentRequestService($database))->create(
                (int) $authUser['id'],
                (int) $childId,
                (int) $centerId,
                is_string($_POST['request_type'] ?? null) ? $_POST['request_type'] : '',
                is_string($_POST['preferred_date'] ?? null) ? $_POST['preferred_date'] : null,
            );
            AuthService::setFlash('success', 'Votre demande a été enregistrée dans votre espace. Appelez le centre pour convenir de l’horaire.');
            header('Location: ../pages/espace-parent.php#centers', true, 303);
            exit;
        }

        throw new InvalidArgumentException('Cette action n’est pas reconnue.');
    }
    $dashboardData = (new DashboardController($database))->parentDashboard($authUser);
} catch (InvalidArgumentException $error) {
    AuthService::setFlash('error', $error->getMessage());
    header('Location: ../pages/espace-parent.php#' . $formSection, true, 303);
    exit;
} catch (RuntimeException $error) {
    DashboardController::renderServiceUnavailable($basePath, $error);
}

$role = 'parent';
$pageTitle = 'Espace parent';
$currentItem = 'overview';
$pageDescription = 'Espace parent sécurisé AFYAMTOTO AI.';
$csrfToken = CsrfMiddleware::token();
$notice = AuthService::consumeFlash();
ob_start();
require dirname(__DIR__) . '/app/Views/dashboards/parent.php';
$content = ob_get_clean();
require dirname(__DIR__) . '/app/Views/layouts/dashboard.php';

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/HealthCenterOperationsService.php';
require_once dirname(__DIR__) . '/app/Controllers/DashboardController.php';
require_once dirname(__DIR__) . '/app/Middleware/RequireRole.php';
require_once dirname(__DIR__) . '/app/Middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/config/database.php';

AuthService::startSession();
header('Cache-Control: no-store, private');
$basePath = '..';
$formSection = 'appointments';
$sessionUser = AuthService::currentUser();
if ($sessionUser === null) {
    header('Location: ../pages/connexion-agent.php', true, 303);
    exit;
}
if (($sessionUser['role_code'] ?? null) !== 'health_center_manager') {
    http_response_code(403);
    exit('Accès refusé.');
}

try {
    $database = getDatabaseConnection();
    $authUser = RequireRole::user($database, ['health_center_manager'], '../pages/connexion-agent.php');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        CsrfMiddleware::assertValid($_POST['_csrf'] ?? null);
        $action = $_POST['_action'] ?? null;
        $value = static fn (string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
        $service = new HealthCenterOperationsService($database);

        if ($action === 'respond_appointment') {
            $requestId = filter_var($value('request_id'), FILTER_VALIDATE_INT);
            if ($requestId === false) {
                throw new InvalidArgumentException('Sélectionnez une demande valide.');
            }
            $formSection = 'appointments';
            $service->respondToAppointment(
                $authUser,
                $requestId,
                $value('status'),
                $value('scheduled_local'),
                $value('response_message'),
            );
            AuthService::setFlash('success', 'La réponse au rendez-vous a été envoyée au parent.');
            header('Location: ../pages/espace-centre.php#appointments', true, 303);
            exit;
        }

        if ($action === 'update_referral') {
            $referralId = filter_var($value('referral_id'), FILTER_VALIDATE_INT);
            if ($referralId === false) {
                throw new InvalidArgumentException('Sélectionnez un référencement valide.');
            }
            $formSection = 'referrals';
            $service->updateReferral($authUser, $referralId, $value('outcome'), $value('notes'));
            AuthService::setFlash('success', 'Le suivi de l’orientation a été mis à jour.');
            header('Location: ../pages/espace-centre.php#referrals', true, 303);
            exit;
        }

        throw new InvalidArgumentException('Cette action n’est pas reconnue.');
    }
    $dashboardData = (new DashboardController($database))->healthCenterDashboard($authUser);
} catch (InvalidArgumentException $error) {
    AuthService::setFlash('error', $error->getMessage());
    header('Location: ../pages/espace-centre.php#' . $formSection, true, 303);
    exit;
} catch (RuntimeException $error) {
    DashboardController::renderServiceUnavailable($basePath, $error);
}

$role = 'health-center';
$pageTitle = 'Espace établissement de santé';
$currentItem = 'overview';
$pageDescription = 'Gestion opérationnelle des centres de santé qui vous sont affectés.';
$csrfToken = CsrfMiddleware::token();
$notice = AuthService::consumeFlash();
ob_start();
require dirname(__DIR__) . '/app/Views/dashboards/health-center.php';
$content = ob_get_clean();
require dirname(__DIR__) . '/app/Views/layouts/dashboard.php';

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/HealthProfessionalService.php';
require_once dirname(__DIR__) . '/app/Controllers/DashboardController.php';
require_once dirname(__DIR__) . '/app/Middleware/RequireRole.php';
require_once dirname(__DIR__) . '/app/Middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/config/database.php';

AuthService::startSession();
header('Cache-Control: no-store, private');
$basePath = '..';
$formSection = 'alerts';
$sessionUser = AuthService::currentUser();
if ($sessionUser === null) {
    header('Location: ../pages/connexion-agent.php', true, 303);
    exit;
}
if (($sessionUser['role_code'] ?? null) !== 'health_professional') {
    http_response_code(403);
    exit('Accès refusé.');
}

try {
    $database = getDatabaseConnection();
    $authUser = RequireRole::user($database, ['health_professional'], '../pages/connexion-agent.php');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        CsrfMiddleware::assertValid($_POST['_csrf'] ?? null);
        $action = $_POST['_action'] ?? null;
        $value = static fn (string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
        $service = new HealthProfessionalService($database);

        if ($action === 'review_alert') {
            $alertId = filter_var($value('alert_id'), FILTER_VALIDATE_INT);
            if ($alertId === false) {
                throw new InvalidArgumentException('Sélectionnez une alerte valide.');
            }
            $service->reviewAlert($authUser, $alertId, $value('status'), $value('review_notes'));
            AuthService::setFlash('success', 'La revue de l’alerte a été enregistrée.');
            header('Location: ../pages/espace-professionnel.php#alerts', true, 303);
            exit;
        }

        if ($action === 'review_referral') {
            $referralId = filter_var($value('referral_id'), FILTER_VALIDATE_INT);
            if ($referralId === false) {
                throw new InvalidArgumentException('Sélectionnez un référencement valide.');
            }
            $service->reviewReferral($authUser, $referralId, $value('outcome'), $value('professional_notes'));
            AuthService::setFlash('success', 'La prise en charge et les observations ont été enregistrées.');
            header('Location: ../pages/espace-professionnel.php#referrals', true, 303);
            exit;
        }

        if ($action === 'add_observation') {
            $formSection = 'observations';
            $contextParts = explode(':', $value('context'), 2);
            $contextId = filter_var($contextParts[1] ?? null, FILTER_VALIDATE_INT);
            if (!isset($contextParts[0]) || $contextId === false) {
                throw new InvalidArgumentException('Sélectionnez un dossier et un centre valides.');
            }
            $service->addObservation(
                $authUser,
                $contextParts[0],
                $contextId,
                $value('observation_type'),
                $value('content'),
            );
            AuthService::setFlash('success', 'L’observation professionnelle a été ajoutée au dossier autorisé.');
            header('Location: ../pages/espace-professionnel.php#observations', true, 303);
            exit;
        }

        throw new InvalidArgumentException('Cette action n’est pas reconnue.');
    }
    $dashboardData = (new DashboardController($database))->healthProfessionalDashboard($authUser);
} catch (InvalidArgumentException $error) {
    AuthService::setFlash('error', $error->getMessage());
    header('Location: ../pages/espace-professionnel.php#' . $formSection, true, 303);
    exit;
} catch (RuntimeException $error) {
    DashboardController::renderServiceUnavailable($basePath, $error);
}

$role = 'health-professional';
$pageTitle = 'Espace professionnel de santé';
$currentItem = 'overview';
$pageDescription = 'Vue des dossiers auxquels ce professionnel est autorisé à accéder.';
$csrfToken = CsrfMiddleware::token();
$notice = AuthService::consumeFlash();
ob_start();
require dirname(__DIR__) . '/app/Views/dashboards/health-professional.php';
$content = ob_get_clean();
require dirname(__DIR__) . '/app/Views/layouts/dashboard.php';

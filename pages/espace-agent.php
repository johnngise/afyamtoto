<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/CommunityHealthWorkerService.php';
require_once dirname(__DIR__) . '/app/Controllers/DashboardController.php';
require_once dirname(__DIR__) . '/app/Middleware/RequireRole.php';
require_once dirname(__DIR__) . '/app/Middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/config/database.php';

AuthService::startSession();
header('Cache-Control: no-store, private');
$basePath = '..';
$formSection = 'actions';
$sessionUser = AuthService::currentUser();
if ($sessionUser === null) {
    header('Location: ../pages/connexion-agent.php', true, 303);
    exit;
}
if (!in_array($sessionUser['role_code'] ?? null, ['community_health_worker', 'health_professional'], true)) {
    http_response_code(403);
    exit('Accès refusé.');
}

try {
    $database = getDatabaseConnection();
    $authUser = RequireRole::user(
        $database,
        ['community_health_worker'],
        '../pages/connexion-agent.php',
    );
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        CsrfMiddleware::assertValid($_POST['_csrf'] ?? null);
        $action = $_POST['_action'] ?? null;
        $service = new CommunityHealthWorkerService($database);
        $value = static fn (string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : '';

        if ($action === 'enroll_child') {
            $formSection = 'enrollment';
            $service->enrollChild(
                $authUser,
                $value('parent_email'),
                $value('first_name'),
                $value('last_name'),
                $value('date_of_birth'),
                $value('sex'),
                $value('relationship_type'),
            );
            AuthService::setFlash('success', 'Le profil a été créé et rattaché au compte parent, en attente de son consentement.');
            header('Location: ../pages/espace-agent.php#enrollment', true, 303);
            exit;
        }

        $formSection = match ($action) {
            'record_growth' => 'growth',
            'record_nutrition' => 'nutrition',
            'record_visit' => 'visits',
            'create_referral', 'follow_up_referral' => 'referrals',
            default => 'actions',
        };

        if ($action === 'follow_up_referral') {
            $referralId = filter_var($value('referral_id'), FILTER_VALIDATE_INT);
            if ($referralId === false) {
                throw new InvalidArgumentException('Sélectionnez un référencement valide.');
            }
            $service->followUpReferral($authUser, $referralId, $value('outcome'), $value('follow_up_notes'));
            AuthService::setFlash('success', 'Le suivi du référencement a été mis à jour.');
            header('Location: ../pages/espace-agent.php#referrals', true, 303);
            exit;
        }

        $childId = filter_var($value('child_id'), FILTER_VALIDATE_INT);
        if ($childId === false) {
            throw new InvalidArgumentException('Sélectionnez un enfant autorisé.');
        }

        if ($action === 'record_growth') {
            $service->recordGrowth(
                $authUser,
                $childId,
                $value('measured_on'),
                $value('weight_kg'),
                $value('length_height_cm'),
                $value('length_height_type'),
                $value('muac_cm'),
            );
            AuthService::setFlash('success', 'Les mesures ont été enregistrées. Elles ne constituent pas un diagnostic.');
            header('Location: ../pages/espace-agent.php#growth', true, 303);
            exit;
        }

        if ($action === 'record_nutrition') {
            $service->recordNutritionScreening(
                $authUser,
                $childId,
                $value('record_date'),
                $value('feeding_type'),
                $value('observations'),
            );
            AuthService::setFlash('success', 'Les observations de dépistage ont été enregistrées pour évaluation.');
            header('Location: ../pages/espace-agent.php#nutrition', true, 303);
            exit;
        }

        if ($action === 'record_visit') {
            $service->recordVisit(
                $authUser,
                $childId,
                $value('visit_type'),
                $value('visited_on'),
                $value('purpose'),
                $value('observations'),
            );
            AuthService::setFlash('success', 'La visite communautaire a été enregistrée.');
            header('Location: ../pages/espace-agent.php#visits', true, 303);
            exit;
        }

        if ($action === 'create_referral') {
            $centerId = filter_var($value('health_center_id'), FILTER_VALIDATE_INT);
            if ($centerId === false) {
                throw new InvalidArgumentException('Sélectionnez un centre de santé valide.');
            }
            $service->createReferral($authUser, $childId, $centerId, $value('concern'), $value('priority'));
            AuthService::setFlash('success', 'Le référencement a été transmis au centre et aux professionnels qui y sont affectés.');
            header('Location: ../pages/espace-agent.php#referrals', true, 303);
            exit;
        }

        throw new InvalidArgumentException('Cette action n’est pas reconnue.');
    }
    $dashboardData = (new DashboardController($database))->healthWorkerDashboard($authUser);
} catch (InvalidArgumentException $error) {
    AuthService::setFlash('error', $error->getMessage());
    header('Location: ../pages/espace-agent.php#' . $formSection, true, 303);
    exit;
} catch (RuntimeException $error) {
    DashboardController::renderServiceUnavailable($basePath, $error);
}

$role = 'health-worker';
$pageTitle = 'Espace agent de santé';
$currentItem = 'overview';
$pageDescription = 'Espace de suivi communautaire sécurisé AFYAMTOTO AI.';
$csrfToken = CsrfMiddleware::token();
$notice = AuthService::consumeFlash();
ob_start();
require dirname(__DIR__) . '/app/Views/dashboards/health-worker.php';
$content = ob_get_clean();
require dirname(__DIR__) . '/app/Views/layouts/dashboard.php';

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/VaccinationService.php';
require_once dirname(__DIR__) . '/app/Controllers/DashboardController.php';
require_once dirname(__DIR__) . '/app/Middleware/RequireRole.php';
require_once dirname(__DIR__) . '/app/Middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/config/database.php';

AuthService::startSession();
header('Cache-Control: no-store, private');
$basePath = '..';
$sessionUser = AuthService::currentUser();
if ($sessionUser === null) {
    header('Location: ../pages/connexion.php', true, 303);
    exit;
}

$roleCode = $sessionUser['role_code'] ?? null;
$roleMap = [
    'parent' => 'parent',
    'community_health_worker' => 'health-worker',
    'health_professional' => 'health-professional',
];
if (!isset($roleMap[$roleCode])) {
    http_response_code(403);
    exit('Accès refusé.');
}

$allowedRoles = array_keys($roleMap);
$errors = [];
$oldInput = [];
$csrfToken = CsrfMiddleware::token();

try {
    $database = getDatabaseConnection();
    $authUser = RequireRole::user($database, $allowedRoles, '../pages/connexion.php');
    $vaccinationService = new VaccinationService($database);
    $isStaff = $roleCode !== 'parent';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$isStaff) {
            http_response_code(403);
            exit('Seul le personnel de santé autorisé peut enregistrer une vaccination.');
        }

        $oldInput = [
            'child_id' => $_POST['child_id'] ?? '',
            'vaccine_dose_id' => $_POST['vaccine_dose_id'] ?? '',
            'administered_date' => $_POST['administered_date'] ?? '',
            'next_dose_date' => $_POST['next_dose_date'] ?? '',
            'health_center_id' => $_POST['health_center_id'] ?? '',
            'notes' => $_POST['notes'] ?? '',
        ];
        try {
            CsrfMiddleware::assertValid($_POST['_csrf'] ?? null);
            $childId = filter_var($oldInput['child_id'], FILTER_VALIDATE_INT);
            $doseId = filter_var($oldInput['vaccine_dose_id'], FILTER_VALIDATE_INT);
            $centerId = $oldInput['health_center_id'] === ''
                ? null
                : filter_var($oldInput['health_center_id'], FILTER_VALIDATE_INT);
            if ($childId === false || $doseId === false || ($centerId === false)) {
                throw new InvalidArgumentException('Sélectionnez un enfant, une dose et un centre valides.');
            }
            if (!is_string($oldInput['administered_date']) || !is_string($oldInput['next_dose_date']) || !is_string($oldInput['notes'])) {
                throw new InvalidArgumentException('Les champs du formulaire sont invalides.');
            }

            $vaccinationService->recordAdministeredDose(
                $authUser,
                $childId,
                $doseId,
                $oldInput['administered_date'],
                $oldInput['next_dose_date'] === '' ? null : $oldInput['next_dose_date'],
                $centerId,
                $oldInput['notes'],
            );
            AuthService::setFlash('success', 'La dose administrée a été enregistrée.');
            header('Location: ../pages/vaccinations.php', true, 303);
            exit;
        } catch (InvalidArgumentException $error) {
            $errors[] = $error->getMessage();
        } catch (RuntimeException $error) {
            $errors[] = 'L’enregistrement n’a pas pu être effectué. Réessayez ou contactez l’administrateur.';
            error_log('[AFYAMTOTO vaccination] ' . $error->getMessage());
        }
    }

    $doses = $isStaff ? $vaccinationService->activeDoses() : [];
    $children = $isStaff ? $vaccinationService->childrenForStaff($authUser) : [];
    $centers = $isStaff && $roleCode === 'health_professional'
        ? $vaccinationService->assignedCenters((int) $authUser['id'])
        : [];
    $history = $isStaff
        ? $vaccinationService->staffHistory($authUser)
        : $vaccinationService->parentHistory((int) $authUser['id']);
} catch (RuntimeException $error) {
    DashboardController::renderServiceUnavailable($basePath, $error);
}

$role = $roleMap[$roleCode];
$pageTitle = 'Vaccinations';
$currentItem = 'vaccinations';
$pageDescription = 'Historique sécurisé des vaccinations enregistrées pour les enfants autorisés.';
$notice = AuthService::consumeFlash();
ob_start();
require dirname(__DIR__) . '/app/Views/dashboards/vaccinations.php';
$content = ob_get_clean();
require dirname(__DIR__) . '/app/Views/layouts/dashboard.php';

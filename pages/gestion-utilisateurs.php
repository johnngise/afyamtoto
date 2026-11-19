<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/UserManagementService.php';
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
    $userService = new UserManagementService($database);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        CsrfMiddleware::assertValid($_POST['_csrf'] ?? null);
        $rawId = $_POST['user_id'] ?? null;
        $userId = is_string($rawId) && ctype_digit($rawId) && (int) $rawId > 0 ? (int) $rawId : null;
        if ($rawId !== null && $userId === null) {
            throw new InvalidArgumentException('L’identifiant du compte est invalide.');
        }

        $rawCenterIds = $_POST['health_center_ids'] ?? [];
        if (!is_array($rawCenterIds)) {
            throw new InvalidArgumentException('La sélection des centres de santé est invalide.');
        }
        $centerIds = [];
        foreach ($rawCenterIds as $rawCenterId) {
            if (!is_string($rawCenterId) || !ctype_digit($rawCenterId) || (int) $rawCenterId < 1) {
                throw new InvalidArgumentException('La sélection des centres de santé est invalide.');
            }
            $centerIds[] = (int) $rawCenterId;
        }

        $userService->save(
            (int) $authUser['id'],
            $userId,
            is_string($_POST['display_name'] ?? null) ? $_POST['display_name'] : '',
            is_string($_POST['email'] ?? null) ? $_POST['email'] : '',
            is_string($_POST['phone_e164'] ?? null) ? $_POST['phone_e164'] : '',
            is_string($_POST['role_code'] ?? null) ? $_POST['role_code'] : '',
            is_string($_POST['password'] ?? null) ? $_POST['password'] : '',
            ($_POST['is_active'] ?? null) === '1',
            $centerIds,
        );
        AuthService::setFlash('success', $userId === null ? 'Le compte a été créé.' : 'Le compte a été mis à jour.');
        header('Location: ../pages/gestion-utilisateurs.php', true, 303);
        exit;
    }

    $users = $userService->users();
    $healthCenters = $userService->healthCenters();
    $healthCenterAssignments = $userService->healthCenterAssignments();
    $notice = AuthService::consumeFlash();
} catch (InvalidArgumentException $error) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $errors[] = $error->getMessage();
        $users = isset($userService) ? $userService->users() : [];
        $healthCenters = isset($userService) ? $userService->healthCenters() : [];
        $healthCenterAssignments = isset($userService) ? $userService->healthCenterAssignments() : [];
        $notice = null;
    } else {
        http_response_code(400);
        $errors[] = $error->getMessage();
        $users = [];
        $healthCenters = [];
        $healthCenterAssignments = [];
        $notice = null;
    }
} catch (PDOException $error) {
    if (($error->errorInfo[1] ?? null) === 1062) {
        http_response_code(400);
        $errors[] = 'Cette adresse e-mail ou ce numéro de téléphone est déjà associé à un compte.';
        $users = isset($userService) ? $userService->users() : [];
        $healthCenters = isset($userService) ? $userService->healthCenters() : [];
        $healthCenterAssignments = isset($userService) ? $userService->healthCenterAssignments() : [];
        $notice = null;
    } else {
        error_log('[AFYAMTOTO user management] ' . $error->getMessage());
        http_response_code(503);
        $errors[] = 'La gestion des comptes est momentanément indisponible.';
        $users = [];
        $healthCenters = [];
        $healthCenterAssignments = [];
        $notice = null;
    }
} catch (RuntimeException $error) {
    error_log('[AFYAMTOTO user management] ' . $error->getMessage());
    http_response_code(503);
    $errors[] = 'La gestion des comptes est momentanément indisponible.';
    $users = [];
    $healthCenters = [];
    $healthCenterAssignments = [];
    $notice = null;
}

$csrfToken = CsrfMiddleware::token();
require dirname(__DIR__) . '/app/Views/admin/gestion-utilisateurs.php';

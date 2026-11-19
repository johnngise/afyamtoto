<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/ChildProfileService.php';
require_once dirname(__DIR__) . '/app/Middleware/RequireRole.php';
require_once dirname(__DIR__) . '/app/Middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/config/database.php';

AuthService::startSession();
header('Cache-Control: no-store, private');
$basePath = '..';
$errors = [];
$oldInput = [];
$notice = null;

try {
    $database = getDatabaseConnection();
    $authUser = RequireRole::user($database, ['parent'], '../pages/connexion.php');
    $profilesService = new ChildProfileService($database);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        CsrfMiddleware::assertValid($_POST['_csrf'] ?? null);
        $rawId = $_POST['child_id'] ?? null;
        $childId = is_string($rawId) && ctype_digit($rawId) && (int) $rawId > 0 ? (int) $rawId : null;
        if ($rawId !== null && $childId === null) {
            throw new InvalidArgumentException('L’identifiant du profil est invalide.');
        }

        $oldInput = $_POST;
        $profilesService->save(
            (int) $authUser['id'],
            $childId,
            is_string($_POST['first_name'] ?? null) ? $_POST['first_name'] : '',
            is_string($_POST['last_name'] ?? null) ? $_POST['last_name'] : '',
            is_string($_POST['date_of_birth'] ?? null) ? $_POST['date_of_birth'] : '',
            is_string($_POST['sex'] ?? null) ? $_POST['sex'] : '',
            is_string($_POST['relationship_type'] ?? null) ? $_POST['relationship_type'] : '',
            ($_POST['consent_granted'] ?? null) === '1',
        );
        AuthService::setFlash('success', $childId === null ? 'Le profil de l’enfant a été créé.' : 'Le profil a été mis à jour.');
        header('Location: ../pages/gestion-enfants.php', true, 303);
        exit;
    }

    $profiles = $profilesService->profiles((int) $authUser['id']);
    $notice = AuthService::consumeFlash();
} catch (InvalidArgumentException $error) {
    $errors[] = $error->getMessage();
    $profiles = isset($profilesService, $authUser) ? $profilesService->profiles((int) $authUser['id']) : [];
} catch (RuntimeException $error) {
    error_log('[AFYAMTOTO child profiles] ' . $error->getMessage());
    http_response_code(503);
    $errors[] = 'La gestion des profils est momentanément indisponible.';
    $profiles = [];
}

$csrfToken = CsrfMiddleware::token();
require dirname(__DIR__) . '/app/Views/parent/gestion-enfants.php';

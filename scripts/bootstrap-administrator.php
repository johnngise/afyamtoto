<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/app/Services/AuthService.php';

$name = trim((string) getenv('AFYAMTOTO_ADMIN_NAME'));
$email = strtolower(trim((string) getenv('AFYAMTOTO_ADMIN_EMAIL')));
$phone = trim((string) getenv('AFYAMTOTO_ADMIN_PHONE'));
$password = (string) getenv('AFYAMTOTO_ADMIN_PASSWORD');

if (
    $name === ''
    || preg_match('/^.{1,160}$/usD', $name) !== 1
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($email) > 254
    || !AuthService::isStrongPassword($password)
) {
    fwrite(STDERR, "Configurez AFYAMTOTO_ADMIN_NAME, AFYAMTOTO_ADMIN_EMAIL et AFYAMTOTO_ADMIN_PASSWORD (mot de passe fort) dans l’environnement du processus.\n");
    exit(2);
}
if ($phone !== '' && !preg_match('/^\+[1-9][0-9]{1,14}$/D', $phone)) {
    fwrite(STDERR, "AFYAMTOTO_ADMIN_PHONE doit utiliser le format international, par exemple +243XXXXXXXXX.\n");
    exit(2);
}

$database = getDatabaseConnection();
$database->beginTransaction();
try {
    $role = $database->prepare(
        'SELECT role_code FROM roles WHERE role_code = :role_code FOR UPDATE',
    );
    $role->execute(['role_code' => 'administrator']);
    if ($role->fetchColumn() === false) {
        $database->rollBack();
        fwrite(STDERR, "Le rôle administrateur est absent. Appliquez d’abord le schéma et les rôles.\n");
        exit(2);
    }

    $activeAdministrators = $database->query(
        'SELECT COUNT(DISTINCT u.id)
         FROM users AS u
         INNER JOIN user_roles AS ur ON ur.user_id = u.id
         WHERE u.is_active = 1 AND ur.role_code = \'administrator\'',
    )->fetchColumn();
    if ((int) $activeAdministrators > 0) {
        $database->rollBack();
        fwrite(STDERR, "Un administrateur actif existe déjà ; utilisez la gestion des utilisateurs.\n");
        exit(3);
    }

    $insertUser = $database->prepare(
        'INSERT INTO users (display_name, email, phone_e164, password_hash, is_active)
         VALUES (:display_name, :email, :phone, :password_hash, 1)',
    );
    $insertUser->execute([
        'display_name' => $name,
        'email' => $email,
        'phone' => $phone === '' ? null : $phone,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    $userId = (int) $database->lastInsertId();
    $assignRole = $database->prepare(
        'INSERT INTO user_roles (user_id, role_code) VALUES (:user_id, \'administrator\')',
    );
    $assignRole->execute(['user_id' => $userId]);
    $log = $database->prepare(
        'INSERT INTO activity_logs (user_id, action, description)
         VALUES (:user_id, \'administrator_bootstrapped\', \'Compte administrateur initial créé via CLI.\')',
    );
    $log->execute(['user_id' => $userId]);
    $database->commit();
    fwrite(STDOUT, "Compte administrateur initial créé. Supprimez les variables d’environnement de bootstrap.\n");
} catch (Throwable $error) {
    if ($database->inTransaction()) {
        $database->rollBack();
    }

    throw $error;
} finally {
    unset($password);
}

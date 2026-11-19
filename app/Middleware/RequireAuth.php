<?php

declare(strict_types=1);

require_once __DIR__ . '/../Services/AuthService.php';

final class RequireAuth
{
    public static function user(PDO $database, string $loginUrl): array
    {
        $user = AuthService::refreshCurrentUser($database);
        if ($user !== null) {
            return $user;
        }

        header('Location: ' . $loginUrl, true, 303);
        exit;
    }
}

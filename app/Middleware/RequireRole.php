<?php

declare(strict_types=1);

require_once __DIR__ . '/RequireAuth.php';

final class RequireRole
{
    private const ROLE_CODES = [
        'administrator',
        'parent',
        'community_health_worker',
        'health_professional',
        'health_center_manager',
        'health_supervisor',
    ];

    public static function user(PDO $database, array $allowedRoleCodes, string $loginUrl): array
    {
        if ($allowedRoleCodes === []) {
            throw new InvalidArgumentException('Au moins un rôle autorisé doit être fourni.');
        }

        foreach ($allowedRoleCodes as $roleCode) {
            if (!is_string($roleCode) || !in_array($roleCode, self::ROLE_CODES, true)) {
                throw new InvalidArgumentException('La liste des rôles autorisés est invalide.');
            }
        }

        $user = RequireAuth::user($database, $loginUrl);
        if (in_array($user['role_code'] ?? null, $allowedRoleCodes, true)) {
            return $user;
        }

        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Accès refusé.');
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../Services/AuthService.php';

final class CsrfMiddleware
{
    public static function token(): string
    {
        AuthService::startSession();
        if (!is_string($_SESSION['_csrf_token'] ?? null)) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function assertValid(mixed $submittedToken): void
    {
        $expectedToken = self::token();
        if (!is_string($submittedToken) || !hash_equals($expectedToken, $submittedToken)) {
            throw new InvalidArgumentException('Le formulaire a expiré. Rechargez la page et réessayez.');
        }
    }
}

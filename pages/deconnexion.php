<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Méthode non autorisée.');
}

AuthService::startSession();
$controller = new AuthController('..');
try {
    $controller->logout($_POST);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit($error->getMessage());
}

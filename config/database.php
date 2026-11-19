<?php

declare(strict_types=1);

function loadApplicationEnvironment(): void
{
    $environmentFile = dirname(__DIR__) . '/.env';
    if (!is_file($environmentFile)) {
        return;
    }

    $lines = file($environmentFile, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('Le fichier de configuration local ne peut pas être lu.');
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            throw new RuntimeException('Le fichier de configuration local contient une entrée invalide.');
        }

        $name = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/D', $name)) {
            throw new RuntimeException('Le fichier de configuration local contient une variable invalide.');
        }

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && $value[strlen($value) - 1] === '"')
                || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

function getDatabaseConnection(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    loadApplicationEnvironment();

    $databaseName = getenv('DB_NAME');
    $databaseUser = getenv('DB_USER');
    if ($databaseName === false || $databaseName === '' || $databaseUser === false) {
        throw new RuntimeException('Configurez DB_NAME et DB_USER dans le fichier .env avant d’utiliser l’authentification.');
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/D', $databaseName)) {
        throw new RuntimeException('Le nom de base de données configuré est invalide.');
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    if (!preg_match('/^[A-Za-z0-9.-]+$/D', $host) || !ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
        throw new RuntimeException('La configuration de l’hôte ou du port MySQL est invalide.');
    }

    $password = getenv('DB_PASSWORD');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, (int) $port, $databaseName);

    $connection = new PDO($dsn, $databaseUser, $password === false ? '' : $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}

<?php

declare(strict_types=1);

final class AuthService
{
    private const SESSION_IDLE_TIMEOUT = 1800;
    private const SESSION_MAX_LIFETIME = 28800;
    private const LOGIN_FAILURE_LIMIT = 5;
    private const LOGIN_LOCK_MINUTES = 15;
    private const VALID_ROLE_CODES = [
        'administrator',
        'parent',
        'community_health_worker',
        'health_professional',
        'health_center_manager',
        'health_supervisor',
    ];

    public function __construct(private readonly PDO $database)
    {
    }

    public static function isStrongPassword(string $password): bool
    {
        if (preg_match('//u', $password) !== 1) {
            return false;
        }

        $length = self::characterLength($password);
        if ($length < 12 || strlen($password) > 72) {
            return false;
        }
        if ($length >= 20) {
            $characters = preg_split('//u', $password, -1, PREG_SPLIT_NO_EMPTY);
            if (!is_array($characters)) {
                return false;
            }
            $characters = array_filter(
                $characters,
                static fn (string $character): bool => preg_match('/^\s$/u', $character) !== 1,
            );

            return count(array_unique($characters)) >= 8;
        }

        $classes = [
            preg_match('/\p{Ll}/u', $password) === 1,
            preg_match('/\p{Lu}/u', $password) === 1,
            preg_match('/\p{N}/u', $password) === 1,
            preg_match('/[^\p{L}\p{N}\s]/u', $password) === 1,
        ];

        return count(array_filter($classes)) >= 3;
    }

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (session_status() !== PHP_SESSION_NONE) {
            throw new RuntimeException('La session PHP ne peut pas être démarrée dans son état actuel.');
        }

        session_name('AFYAMTOTOSESSID');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_trans_sid', '0');

        $secureCookie = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => self::sessionCookiePath(),
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start()) {
            throw new RuntimeException('La session PHP n’a pas pu être démarrée.');
        }
    }

    public function createParentAccount(
        string $displayName,
        string $email,
        ?string $phone,
        string $password,
    ): void {
        $displayName = trim($displayName);
        $email = strtolower(trim($email));
        $phone = $phone === null ? null : trim($phone);

        if ($displayName === '' || self::characterLength($displayName) > 160) {
            throw new InvalidArgumentException('Le nom doit contenir entre 1 et 160 caractères.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            throw new InvalidArgumentException('L’adresse e-mail est invalide.');
        }

        if ($phone !== null && $phone !== '' && !preg_match('/^\+[1-9][0-9]{1,14}$/D', $phone)) {
            throw new InvalidArgumentException('Le numéro doit être au format international, par exemple +243XXXXXXXXX.');
        }

        if (!self::isStrongPassword($password)) {
            throw new InvalidArgumentException('Utilisez au moins 12 caractères, avec trois types de caractères, ou une phrase secrète de 20 caractères minimum (72 octets maximum).');
        }

        $roleQuery = $this->database->prepare('SELECT role_code FROM roles WHERE role_code = :role_code');
        $roleQuery->execute(['role_code' => 'parent']);
        if ($roleQuery->fetchColumn() === false) {
            throw new RuntimeException('Le rôle parent est absent. Appliquez d’abord les migrations et les rôles.');
        }

        $this->database->beginTransaction();
        try {
            $insertUser = $this->database->prepare(
                'INSERT INTO users (display_name, email, phone_e164, password_hash)
                 VALUES (:display_name, :email, :phone, :password_hash)',
            );
            $insertUser->execute([
                'display_name' => $displayName,
                'email' => $email,
                'phone' => $phone === '' ? null : $phone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $userId = (int) $this->database->lastInsertId();
            $assignRole = $this->database->prepare(
                'INSERT INTO user_roles (user_id, role_code) VALUES (:user_id, :role_code)',
            );
            $assignRole->execute([
                'user_id' => $userId,
                'role_code' => 'parent',
            ]);
            $this->database->commit();
        } catch (PDOException $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function isInitialAdministratorSetupAvailable(): bool
    {
        $setupSecret = getenv('AFYAMTOTO_ADMIN_SETUP_SECRET');
        if (!is_string($setupSecret) || strlen($setupSecret) < 32 || strlen($setupSecret) > 256) {
            return false;
        }

        $query = $this->database->query(
            'SELECT
                (SELECT COUNT(*)
                 FROM users AS u
                 INNER JOIN user_roles AS ur ON ur.user_id = u.id
                 WHERE u.is_active = 1 AND ur.role_code = \'administrator\') AS active_administrators,
                (SELECT COUNT(*)
                 FROM activity_logs
                 WHERE action = \'administrator_bootstrapped\') AS completed_setups',
        );
        $state = $query->fetch();

        return is_array($state)
            && (int) $state['active_administrators'] === 0
            && (int) $state['completed_setups'] === 0;
    }

    public function createInitialAdministrator(
        string $providedSetupSecret,
        string $displayName,
        string $email,
        ?string $phone,
        string $password,
    ): void {
        $displayName = trim($displayName);
        $email = strtolower(trim($email));
        $phone = $phone === null ? null : trim($phone);

        if ($displayName === '' || self::characterLength($displayName) > 160) {
            throw new InvalidArgumentException('Le nom doit contenir entre 1 et 160 caractères.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            throw new InvalidArgumentException('L’adresse e-mail est invalide.');
        }
        if ($phone !== null && $phone !== '' && !preg_match('/^\+[1-9][0-9]{1,14}$/D', $phone)) {
            throw new InvalidArgumentException('Le numéro doit être au format international, par exemple +243XXXXXXXXX.');
        }
        if (!self::isStrongPassword($password)) {
            throw new InvalidArgumentException('Utilisez au moins 12 caractères avec trois types de caractères, ou une phrase secrète de 20 caractères minimum.');
        }

        $attemptKey = $this->loginAttemptKey('initial-administrator-setup');
        if ($this->isLoginThrottled($attemptKey)) {
            throw new InvalidArgumentException('La configuration initiale est temporairement bloquée. Réessayez dans 15 minutes.');
        }

        $configuredSetupSecret = getenv('AFYAMTOTO_ADMIN_SETUP_SECRET');
        if (
            !is_string($configuredSetupSecret)
            || strlen($configuredSetupSecret) < 32
            || strlen($configuredSetupSecret) > 256
            || !hash_equals($configuredSetupSecret, $providedSetupSecret)
        ) {
            $this->recordLoginFailure($attemptKey);
            throw new InvalidArgumentException('Le code est incorrect ou la configuration initiale n’est plus disponible.');
        }
        $this->clearLoginFailures($attemptKey);

        $this->database->beginTransaction();
        try {
            $role = $this->database->prepare(
                'SELECT role_code FROM roles WHERE role_code = :role_code FOR UPDATE',
            );
            $role->execute(['role_code' => 'administrator']);
            if ($role->fetchColumn() === false) {
                throw new RuntimeException('Le rôle administrateur est absent. Appliquez d’abord le schéma et les rôles.');
            }

            $setupState = $this->database->query(
                'SELECT
                    (SELECT COUNT(*)
                     FROM users AS u
                     INNER JOIN user_roles AS ur ON ur.user_id = u.id
                     WHERE u.is_active = 1 AND ur.role_code = \'administrator\') AS active_administrators,
                    (SELECT COUNT(*)
                     FROM activity_logs
                     WHERE action = \'administrator_bootstrapped\') AS completed_setups',
            )->fetch();
            if (
                !is_array($setupState)
                || (int) $setupState['active_administrators'] > 0
                || (int) $setupState['completed_setups'] > 0
            ) {
                throw new InvalidArgumentException('La configuration initiale n’est plus disponible.');
            }

            $insertUser = $this->database->prepare(
                'INSERT INTO users (display_name, email, phone_e164, password_hash, is_active)
                 VALUES (:display_name, :email, :phone, :password_hash, 1)',
            );
            $insertUser->execute([
                'display_name' => $displayName,
                'email' => $email,
                'phone' => $phone === '' ? null : $phone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $userId = (int) $this->database->lastInsertId();
            $assignRole = $this->database->prepare(
                'INSERT INTO user_roles (user_id, role_code) VALUES (:user_id, \'administrator\')',
            );
            $assignRole->execute(['user_id' => $userId]);

            $log = $this->database->prepare(
                'INSERT INTO activity_logs (user_id, action, description)
                 VALUES (:user_id, \'administrator_bootstrapped\', \'Compte administrateur initial créé via la page de connexion.\')',
            );
            $log->execute(['user_id' => $userId]);
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function authenticate(string $identifier, string $password, array $allowedRoles): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '' || strlen($identifier) > 254 || $password === '' || strlen($password) > 1024) {
            return null;
        }

        $attemptKey = $this->loginAttemptKey($identifier);
        if ($this->isLoginThrottled($attemptKey)) {
            return null;
        }

        foreach ($allowedRoles as $roleCode) {
            if (!is_string($roleCode) || !in_array($roleCode, self::VALID_ROLE_CODES, true)) {
                throw new InvalidArgumentException('La liste des rôles autorisés est invalide.');
            }
        }

        $query = $this->database->prepare(
            'SELECT id, display_name, email, password_hash, is_active
             FROM users
             WHERE email = :email_identifier OR phone_e164 = :phone_identifier
             LIMIT 1',
        );
        $query->execute([
            'email_identifier' => strtolower($identifier),
            'phone_identifier' => $identifier,
        ]);
        $user = $query->fetch();

        if (!is_array($user) || !password_verify($password, (string) $user['password_hash']) || (int) $user['is_active'] !== 1) {
            $this->recordLoginFailure($attemptKey);
            return null;
        }

        $roleQuery = $this->database->prepare(
            'SELECT role_code FROM user_roles WHERE user_id = :user_id',
        );
        $roleQuery->execute(['user_id' => $user['id']]);
        $assignedRoles = $roleQuery->fetchAll(PDO::FETCH_COLUMN);

        $selectedRole = null;
        foreach ($allowedRoles as $allowedRole) {
            if (in_array($allowedRole, $assignedRoles, true)) {
                $selectedRole = $allowedRole;
                break;
            }
        }

        if (!is_string($selectedRole)) {
            $this->recordLoginFailure($attemptKey);
            return null;
        }

        $this->clearLoginFailures($attemptKey);
        $loginLog = $this->database->prepare(
            'INSERT INTO activity_logs (user_id, action, description)
             VALUES (:user_id, \'user_login\', \'Connexion réussie.\')',
        );
        $loginLog->execute(['user_id' => $user['id']]);
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $rehash = $this->database->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
            $rehash->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $user['id'],
            ]);
        }

        return [
            'id' => (int) $user['id'],
            'display_name' => (string) $user['display_name'],
            'email' => $user['email'] === null ? null : (string) $user['email'],
            'role_code' => $selectedRole,
        ];
    }

    public function createPasswordResetToken(string $email): ?string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            return null;
        }

        $query = $this->database->prepare(
            'SELECT id FROM users WHERE email = :email AND is_active = 1 LIMIT 1',
        );
        $query->execute(['email' => $email]);
        $userId = $query->fetchColumn();
        if ($userId === false) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $this->database->beginTransaction();
        try {
            $invalidate = $this->database->prepare(
                'UPDATE password_reset_tokens
                 SET used_at_utc = UTC_TIMESTAMP()
                 WHERE user_id = :user_id AND used_at_utc IS NULL',
            );
            $invalidate->execute(['user_id' => $userId]);

            $insert = $this->database->prepare(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at_utc)
                 VALUES (:user_id, :token_hash, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 60 MINUTE))',
            );
            $insert->execute([
                'user_id' => $userId,
                'token_hash' => hash('sha256', $token),
            ]);
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }

        return $token;
    }

    public function resetPassword(string $token, string $password): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)
            || !self::isStrongPassword($password)
        ) {
            return false;
        }

        $this->database->beginTransaction();
        try {
            $query = $this->database->prepare(
                'SELECT id, user_id FROM password_reset_tokens
                 WHERE token_hash = :token_hash
                   AND used_at_utc IS NULL
                   AND expires_at_utc > UTC_TIMESTAMP()
                 FOR UPDATE',
            );
            $query->execute(['token_hash' => hash('sha256', $token)]);
            $reset = $query->fetch();
            if (!is_array($reset)) {
                $this->database->commit();
                return false;
            }

            $updateUser = $this->database->prepare(
                'UPDATE users SET password_hash = :password_hash WHERE id = :user_id AND is_active = 1',
            );
            $updateUser->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'user_id' => $reset['user_id'],
            ]);
            if ($updateUser->rowCount() !== 1) {
                $this->database->rollBack();
                return false;
            }

            $consume = $this->database->prepare(
                'UPDATE password_reset_tokens SET used_at_utc = UTC_TIMESTAMP() WHERE id = :id',
            );
            $consume->execute(['id' => $reset['id']]);
            $log = $this->database->prepare(
                'INSERT INTO activity_logs (user_id, action, description)
                 VALUES (:user_id, \'password_reset\', \'Mot de passe réinitialisé.\')',
            );
            $log->execute(['user_id' => $reset['user_id']]);
            $emailQuery = $this->database->prepare('SELECT email FROM users WHERE id = :id');
            $emailQuery->execute(['id' => $reset['user_id']]);
            $email = $emailQuery->fetchColumn();
            if (is_string($email)) {
                $attemptKey = $this->loginAttemptKey($email);
                $clearAttempts = $this->database->prepare(
                    'DELETE FROM auth_login_attempts WHERE attempt_key = :attempt_key',
                );
                $clearAttempts->execute(['attempt_key' => $attemptKey]);
            }
            $this->database->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function discardPasswordResetToken(string $token): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return;
        }

        $query = $this->database->prepare(
            'UPDATE password_reset_tokens
             SET used_at_utc = UTC_TIMESTAMP()
             WHERE token_hash = :token_hash AND used_at_utc IS NULL',
        );
        $query->execute(['token_hash' => hash('sha256', $token)]);
    }

    private function loginAttemptKey(string $identifier): string
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipAddress = is_string($ipAddress) ? $ipAddress : '';

        return hash('sha256', strtolower(trim($identifier)) . "\0" . $ipAddress);
    }

    private function isLoginThrottled(string $attemptKey): bool
    {
        $query = $this->database->prepare(
            'SELECT 1 FROM auth_login_attempts
             WHERE attempt_key = :attempt_key AND locked_until_utc > UTC_TIMESTAMP()',
        );
        $query->execute(['attempt_key' => $attemptKey]);
        return $query->fetchColumn() !== false;
    }

    private function recordLoginFailure(string $attemptKey): void
    {
        $this->database->beginTransaction();
        try {
            $insert = $this->database->prepare(
                'INSERT IGNORE INTO auth_login_attempts (attempt_key, attempt_count)
                 VALUES (:attempt_key, 0)',
            );
            $insert->execute(['attempt_key' => $attemptKey]);

            $lock = $this->database->prepare(
                'SELECT CASE
                            WHEN locked_until_utc IS NOT NULL
                                 AND locked_until_utc <= UTC_TIMESTAMP()
                            THEN 0 ELSE attempt_count
                        END AS attempt_count
                 FROM auth_login_attempts
                 WHERE attempt_key = :attempt_key FOR UPDATE',
            );
            $lock->execute(['attempt_key' => $attemptKey]);
            $attemptCount = (int) $lock->fetchColumn() + 1;
            $update = $this->database->prepare(
                'UPDATE auth_login_attempts
                 SET attempt_count = :attempt_count,
                     locked_until_utc = CASE
                         WHEN :failure_count >= :failure_limit
                         THEN DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE)
                         ELSE NULL
                     END
                 WHERE attempt_key = :attempt_key',
            );
            $update->execute([
                'attempt_count' => $attemptCount,
                'failure_count' => $attemptCount,
                'failure_limit' => self::LOGIN_FAILURE_LIMIT,
                'attempt_key' => $attemptKey,
            ]);
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    private function clearLoginFailures(string $attemptKey): void
    {
        $query = $this->database->prepare(
            'DELETE FROM auth_login_attempts WHERE attempt_key = :attempt_key',
        );
        $query->execute(['attempt_key' => $attemptKey]);
    }

    public static function establishSession(array $user): void
    {
        self::startSession();
        if (!isset($user['id'], $user['display_name'], $user['role_code'])
            || !is_int($user['id'])
            || !is_string($user['display_name'])
            || !in_array($user['role_code'], self::VALID_ROLE_CODES, true)
        ) {
            throw new InvalidArgumentException('Les informations du compte ne permettent pas d’ouvrir une session.');
        }

        if (!session_regenerate_id(true)) {
            throw new RuntimeException('La session n’a pas pu être renouvelée après authentification.');
        }

        $_SESSION = [
            'auth_user' => [
                'id' => $user['id'],
                'display_name' => $user['display_name'],
                'email' => isset($user['email']) ? $user['email'] : null,
                'role_code' => $user['role_code'],
            ],
            'authenticated_at' => time(),
            'last_activity_at' => time(),
        ];
    }

    public static function currentUser(): ?array
    {
        self::startSession();

        $user = $_SESSION['auth_user'] ?? null;
        $authenticatedAt = $_SESSION['authenticated_at'] ?? null;
        $lastActivityAt = $_SESSION['last_activity_at'] ?? null;
        if (!is_array($user) || !is_int($user['id'] ?? null) || !is_int($authenticatedAt) || !is_int($lastActivityAt)) {
            return null;
        }

        $now = time();
        if (
            $now - $lastActivityAt > self::SESSION_IDLE_TIMEOUT
            || $now - $authenticatedAt > self::SESSION_MAX_LIFETIME
            || !in_array($user['role_code'] ?? null, self::VALID_ROLE_CODES, true)
        ) {
            self::logout();
            return null;
        }

        $_SESSION['last_activity_at'] = $now;
        return $user;
    }

    public static function refreshCurrentUser(PDO $database): ?array
    {
        $sessionUser = self::currentUser();
        if ($sessionUser === null) {
            return null;
        }

        $query = $database->prepare(
            'SELECT u.id, u.display_name, u.email, ur.role_code
             FROM users AS u
             INNER JOIN user_roles AS ur ON ur.user_id = u.id
             WHERE u.id = :user_id AND u.is_active = 1',
        );
        $query->execute(['user_id' => $sessionUser['id']]);

        foreach ($query->fetchAll() as $row) {
            if ($row['role_code'] !== $sessionUser['role_code']) {
                continue;
            }

            $_SESSION['auth_user'] = [
                'id' => (int) $row['id'],
                'display_name' => (string) $row['display_name'],
                'email' => $row['email'] === null ? null : (string) $row['email'],
                'role_code' => (string) $row['role_code'],
            ];

            return $_SESSION['auth_user'];
        }

        self::logout();
        return null;
    }

    public static function consumeFlash(): ?array
    {
        self::startSession();
        $flash = $_SESSION['_auth_flash'] ?? null;
        unset($_SESSION['_auth_flash']);

        if (!is_array($flash) || !is_string($flash['message'] ?? null) || !in_array($flash['type'] ?? null, ['success', 'error'], true)) {
            return null;
        }

        return $flash;
    }

    public static function setFlash(string $type, string $message): void
    {
        self::startSession();
        if (!in_array($type, ['success', 'error'], true)) {
            throw new InvalidArgumentException('Le type de notification est invalide.');
        }

        $_SESSION['_auth_flash'] = ['type' => $type, 'message' => $message];
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];

        $cookie = session_get_cookie_params();
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $cookie['path'],
                'domain' => $cookie['domain'],
                'secure' => $cookie['secure'],
                'httponly' => $cookie['httponly'],
                'samesite' => $cookie['samesite'] ?? 'Lax',
            ]);
        }

        if (!session_destroy()) {
            throw new RuntimeException('La session n’a pas pu être fermée.');
        }

        session_id('');
    }

    private static function sessionCookiePath(): string
    {
        $projectRoot = realpath(dirname(__DIR__, 2));
        $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        if ($projectRoot === false || $documentRoot === false) {
            return '/';
        }

        $documentRoot = rtrim($documentRoot, DIRECTORY_SEPARATOR);
        $prefix = $documentRoot . DIRECTORY_SEPARATOR;
        if (strncasecmp($projectRoot, $prefix, strlen($prefix)) !== 0) {
            return '/';
        }

        $relativePath = substr($projectRoot, strlen($prefix));
        $segments = array_filter(explode(DIRECTORY_SEPARATOR, $relativePath), static fn (string $segment): bool => $segment !== '');

        return '/' . implode('/', array_map('rawurlencode', $segments)) . '/';
    }

    private static function characterLength(string $value): int
    {
        $count = preg_match_all('/./us', $value);
        return $count === false ? PHP_INT_MAX : $count;
    }
}

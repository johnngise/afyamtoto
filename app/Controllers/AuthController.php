<?php

declare(strict_types=1);

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

final class AuthController
{
    private array $errors = [];
    private array $oldInput = [];
    private ?AuthService $service = null;

    public function __construct(private readonly string $basePath)
    {
    }

    public function parentLogin(array $input): void
    {
        if (!$this->hasValidCsrf($input)) {
            return;
        }

        $identifier = $this->stringValue($input['identifier'] ?? null);
        $password = $this->stringValue($input['password'] ?? null);
        $this->oldInput['identifier'] = $identifier;

        if ($identifier === '' || strlen($identifier) > 254 || $password === '' || strlen($password) > 1024) {
            $this->errors[] = 'Saisissez un identifiant et un mot de passe valides.';
            return;
        }

        $user = $this->service()->authenticate(
            $identifier,
            $password,
            ['administrator', 'health_professional', 'community_health_worker', 'health_center_manager', 'health_supervisor', 'parent'],
        );
        if ($user === null) {
            $this->errors[] = 'Identifiant ou mot de passe incorrect.';
            return;
        }

        AuthService::establishSession($user);
        AuthService::setFlash('success', 'Bienvenue dans votre espace.');
        $this->redirect($this->dashboardForRole((string) $user['role_code']));
    }

    public function agentLogin(array $input): void
    {
        if (!$this->hasValidCsrf($input)) {
            return;
        }

        $identifier = $this->stringValue($input['identifier'] ?? null);
        $password = $this->stringValue($input['password'] ?? null);
        $this->oldInput['identifier'] = $identifier;

        if ($identifier === '' || strlen($identifier) > 254 || $password === '' || strlen($password) > 1024) {
            $this->errors[] = 'Saisissez un identifiant et un mot de passe valides.';
            return;
        }

        $user = $this->service()->authenticate(
            $identifier,
            $password,
            ['health_professional', 'community_health_worker', 'health_center_manager', 'health_supervisor'],
        );
        if ($user === null) {
            $this->errors[] = 'Identifiant ou mot de passe incorrect.';
            return;
        }

        AuthService::establishSession($user);
        AuthService::setFlash('success', 'Bienvenue dans votre espace professionnel.');
        $this->redirect($this->dashboardForRole((string) $user['role_code']));
    }

    public function administratorLogin(array $input): void
    {
        if (!$this->hasValidCsrf($input)) {
            return;
        }

        $identifier = $this->stringValue($input['identifier'] ?? null);
        $password = $this->stringValue($input['password'] ?? null);
        $this->oldInput['identifier'] = $identifier;

        if ($identifier === '' || strlen($identifier) > 254 || $password === '' || strlen($password) > 1024) {
            $this->errors[] = 'Saisissez un identifiant et un mot de passe valides.';
            return;
        }

        $user = $this->service()->authenticate($identifier, $password, ['administrator']);
        if ($user === null) {
            $this->errors[] = 'Identifiant ou mot de passe incorrect.';
            return;
        }

        AuthService::establishSession($user);
        AuthService::setFlash('success', 'Bienvenue dans l’espace administrateur.');
        $this->redirect($this->dashboardForRole('administrator'));
    }

    public function initialAdministratorSetup(array $input): void
    {
        if (!$this->hasValidCsrf($input)) {
            return;
        }

        $name = trim($this->stringValue($input['display_name'] ?? null));
        $email = strtolower(trim($this->stringValue($input['email'] ?? null)));
        $phone = trim($this->stringValue($input['phone_e164'] ?? null));
        $password = $this->stringValue($input['password'] ?? null);
        $passwordConfirmation = $this->stringValue($input['password_confirmation'] ?? null);
        $setupSecret = $this->stringValue($input['setup_secret'] ?? null);
        $this->oldInput = ['display_name' => $name, 'email' => $email, 'phone_e164' => $phone];

        if ($name === '' || self::characterLength($name) > 160) {
            $this->errors[] = 'Saisissez un nom complet (160 caractères maximum).';
        }
        if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Saisissez une adresse e-mail valide.';
        }
        if ($phone !== '' && !preg_match('/^\+[1-9][0-9]{1,14}$/D', $phone)) {
            $this->errors[] = 'Le numéro doit être au format international, par exemple +243XXXXXXXXX.';
        }
        if (!AuthService::isStrongPassword($password)) {
            $this->errors[] = 'Utilisez au moins 12 caractères avec trois types de caractères, ou une phrase secrète de 20 caractères minimum.';
        }
        if ($password !== $passwordConfirmation) {
            $this->errors[] = 'Les deux mots de passe ne correspondent pas.';
        }
        if ($setupSecret === '' || strlen($setupSecret) > 256) {
            $this->errors[] = 'Saisissez le code secret de configuration fourni par la personne responsable du serveur.';
        }
        if ($this->errors !== []) {
            return;
        }

        try {
            $this->service()->createInitialAdministrator(
                $setupSecret,
                $name,
                $email,
                $phone === '' ? null : $phone,
                $password,
            );
        } catch (InvalidArgumentException $error) {
            $this->errors[] = $error->getMessage();
            return;
        } catch (PDOException $error) {
            if (($error->errorInfo[1] ?? null) === 1062) {
                $this->errors[] = 'Cette adresse e-mail ou ce numéro est déjà associé à un compte.';
                return;
            }

            throw $error;
        } finally {
            unset($password, $passwordConfirmation, $setupSecret);
        }

        AuthService::setFlash('success', 'Compte administrateur créé. Vous pouvez maintenant vous connecter.');
        $this->redirect('/pages/connexion-administrateur.php');
    }

    public function isInitialAdministratorSetupAvailable(): bool
    {
        return $this->service()->isInitialAdministratorSetupAvailable();
    }

    public function parentRegistration(array $input): void
    {
        if (!$this->hasValidCsrf($input)) {
            return;
        }

        $name = trim($this->stringValue($input['display_name'] ?? null));
        $email = strtolower(trim($this->stringValue($input['email'] ?? null)));
        $phone = trim($this->stringValue($input['phone_e164'] ?? null));
        $password = $this->stringValue($input['password'] ?? null);
        $passwordConfirmation = $this->stringValue($input['password_confirmation'] ?? null);
        $this->oldInput = ['display_name' => $name, 'email' => $email, 'phone_e164' => $phone];

        if ($name === '' || self::characterLength($name) > 160) {
            $this->errors[] = 'Saisissez votre nom (160 caractères maximum).';
        }
        if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Saisissez une adresse e-mail valide.';
        }
        if ($phone === '' || !preg_match('/^\+[1-9][0-9]{1,14}$/D', $phone)) {
            $this->errors[] = 'Le numéro doit être au format international, par exemple +243XXXXXXXXX.';
        }
        if (!AuthService::isStrongPassword($password)) {
            $this->errors[] = 'Utilisez au moins 12 caractères avec trois types de caractères, ou une phrase secrète de 20 caractères minimum.';
        }
        if ($password !== $passwordConfirmation) {
            $this->errors[] = 'Les deux mots de passe ne correspondent pas.';
        }
        if ($this->errors !== []) {
            return;
        }

        try {
            $this->service()->createParentAccount($name, $email, $phone, $password);
        } catch (PDOException $error) {
            if (($error->errorInfo[1] ?? null) === 1062) {
                $this->errors[] = 'Un compte utilise déjà cette adresse e-mail ou ce numéro.';
                return;
            }

            throw $error;
        }

        AuthService::setFlash('success', 'Votre compte parent a été créé. Vous pouvez maintenant vous connecter.');
        $this->redirect('/pages/connexion.php');
    }

    public function requestPasswordReset(array $input): void
    {
        if (!$this->hasValidCsrf($input)) {
            return;
        }

        $email = strtolower(trim($this->stringValue($input['email'] ?? null)));
        $this->oldInput['email'] = $email;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $this->errors[] = 'Saisissez une adresse e-mail valide.';
            return;
        }

        $token = $this->service()->createPasswordResetToken($email);
        if ($token !== null) {
            $applicationUrl = rtrim((string) getenv('APP_URL'), '/');
            $sender = (string) (getenv('MAIL_FROM') ?: 'noreply@localhost');
            $urlParts = parse_url($applicationUrl);
            if (
                filter_var($applicationUrl, FILTER_VALIDATE_URL) !== false
                && is_array($urlParts)
                && in_array($urlParts['scheme'] ?? null, ['http', 'https'], true)
                && isset($urlParts['host'])
                && !isset($urlParts['query'])
                && !isset($urlParts['fragment'])
                && preg_match('/^[^@\r\n]+@[^@\r\n]+$/D', $sender)
            ) {
                $resetUrl = $applicationUrl . '/pages/reinitialiser-mot-de-passe.php?token=' . rawurlencode($token);
                $sent = mail(
                    $email,
                    'Réinitialisation de votre mot de passe AFYAMTOTO AI',
                    "Pour choisir un nouveau mot de passe, ouvrez ce lien (valable 60 minutes et à usage unique) :\n\n"
                        . $resetUrl . "\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.",
                    ['From' => $sender, 'Content-Type' => 'text/plain; charset=UTF-8'],
                );
                if (!$sent) {
                    $this->service()->discardPasswordResetToken($token);
                    error_log('[AFYAMTOTO auth] L’envoi du courriel de réinitialisation a échoué.');
                }
            } else {
                $this->service()->discardPasswordResetToken($token);
                error_log('[AFYAMTOTO auth] APP_URL ou MAIL_FROM est absent ou invalide.');
            }
        }

        AuthService::setFlash(
            'success',
            'Si un compte actif correspond à cette adresse, un lien de réinitialisation lui a été envoyé.',
        );
        $this->redirect('/pages/mot-de-passe-oublie.php');
    }

    public function resetPassword(array $input): void
    {
        if (!$this->hasValidCsrf($input)) {
            return;
        }

        $token = $this->stringValue($input['token'] ?? null);
        $password = $this->stringValue($input['password'] ?? null);
        $confirmation = $this->stringValue($input['password_confirmation'] ?? null);
        if (!AuthService::isStrongPassword($password)) {
            $this->errors[] = 'Utilisez au moins 12 caractères avec trois types de caractères, ou une phrase secrète de 20 caractères minimum.';
        }
        if ($password !== $confirmation) {
            $this->errors[] = 'Les deux mots de passe ne correspondent pas.';
        }
        if ($this->errors !== []) {
            return;
        }

        if (!$this->service()->resetPassword($token, $password)) {
            $this->errors[] = 'Ce lien est invalide, expiré ou déjà utilisé. Demandez-en un nouveau.';
            return;
        }

        AuthService::setFlash('success', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');
        $this->redirect('/pages/connexion.php');
    }

    public function logout(array $input): void
    {
        CsrfMiddleware::assertValid($input['_csrf'] ?? null);
        AuthService::logout();
        AuthService::startSession();
        AuthService::setFlash('success', 'Votre session a été fermée.');
        $this->redirect('/index.php');
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function oldInput(): array
    {
        return $this->oldInput;
    }

    private function hasValidCsrf(array $input): bool
    {
        try {
            CsrfMiddleware::assertValid($input['_csrf'] ?? null);
            return true;
        } catch (InvalidArgumentException $error) {
            http_response_code(400);
            $this->errors[] = $error->getMessage();
            return false;
        }
    }

    private function service(): AuthService
    {
        if (!$this->service instanceof AuthService) {
            $this->service = new AuthService(getDatabaseConnection());
        }

        return $this->service;
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $this->basePath . $path, true, 303);
        exit;
    }

    private function dashboardForRole(string $role): string
    {
        return match ($role) {
            'administrator' => '/pages/espace-administrateur.php',
            'parent' => '/pages/espace-parent.php',
            'community_health_worker' => '/pages/espace-agent.php',
            'health_professional' => '/pages/espace-professionnel.php',
            'health_center_manager' => '/pages/espace-centre.php',
            'health_supervisor' => '/pages/espace-superviseur.php',
            default => throw new InvalidArgumentException('Le rôle du compte est invalide.'),
        };
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private static function characterLength(string $value): int
    {
        $count = preg_match_all('/./us', $value);
        return $count === false ? PHP_INT_MAX : $count;
    }
}

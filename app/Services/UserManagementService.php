<?php

declare(strict_types=1);

require_once __DIR__ . '/AuthService.php';

final class UserManagementService
{
    private const ROLES = [
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

    public function users(): array
    {
        $query = $this->database->query(
            'SELECT u.id, u.display_name, u.email, u.phone_e164, u.is_active,
                    u.created_at_utc, MIN(ur.role_code) AS role_code,
                    MIN(r.display_name) AS role_name
             FROM users AS u
             INNER JOIN user_roles AS ur ON ur.user_id = u.id
             INNER JOIN roles AS r ON r.role_code = ur.role_code
             GROUP BY u.id, u.display_name, u.email, u.phone_e164, u.is_active, u.created_at_utc
             ORDER BY u.created_at_utc DESC, u.id DESC
             LIMIT 200',
        );
        return $query->fetchAll();
    }

    public function healthCenters(): array
    {
        return $this->database->query(
            'SELECT id, name, city, province FROM health_centers ORDER BY name',
        )->fetchAll();
    }

    public function healthCenterAssignments(): array
    {
        $rows = $this->database->query(
            'SELECT user_id, health_center_id FROM user_health_centers ORDER BY user_id, health_center_id',
        )->fetchAll();
        $assignments = [];
        foreach ($rows as $row) {
            $assignments[(int) $row['user_id']][] = (int) $row['health_center_id'];
        }

        return $assignments;
    }

    public function save(
        int $actorId,
        ?int $userId,
        string $displayName,
        string $email,
        string $phone,
        string $role,
        string $password,
        bool $active,
        array $healthCenterIds,
    ): void {
        $displayName = trim($displayName);
        $email = strtolower(trim($email));
        $phone = trim($phone);

        if ($displayName === '' || self::characterLength($displayName) > 160) {
            throw new InvalidArgumentException('Le nom doit contenir entre 1 et 160 caractères.');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            throw new InvalidArgumentException('L’adresse e-mail est invalide.');
        }
        if ($phone !== '' && !preg_match('/^\+[1-9][0-9]{1,14}$/D', $phone)) {
            throw new InvalidArgumentException('Le numéro doit être au format international, par exemple +243XXXXXXXXX.');
        }
        if (!in_array($role, self::ROLES, true)) {
            throw new InvalidArgumentException('Le rôle sélectionné est invalide.');
        }
        if (array_filter($healthCenterIds, static fn (mixed $id): bool => !is_int($id) || $id < 1) !== []) {
            throw new InvalidArgumentException('La sélection des centres de santé est invalide.');
        }
        $healthCenterIds = array_values(array_unique($healthCenterIds));
        if (count($healthCenterIds) > 20) {
            throw new InvalidArgumentException('Sélectionnez au maximum 20 centres de santé.');
        }
        if ($healthCenterIds !== [] && !in_array($role, [
            'community_health_worker',
            'health_professional',
            'health_center_manager',
            'health_supervisor',
        ], true)) {
            throw new InvalidArgumentException('Seuls les comptes professionnels peuvent être associés à un centre.');
        }
        if ($healthCenterIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($healthCenterIds), '?'));
            $validateCenters = $this->database->prepare(
                'SELECT COUNT(*) FROM health_centers WHERE id IN (' . $placeholders . ')',
            );
            $validateCenters->execute($healthCenterIds);
            if ((int) $validateCenters->fetchColumn() !== count($healthCenterIds)) {
                throw new InvalidArgumentException('Un ou plusieurs centres de santé n’existent pas.');
            }
        }
        if ($userId === null && !AuthService::isStrongPassword($password)) {
            throw new InvalidArgumentException('Utilisez au moins 12 caractères avec trois types de caractères, ou une phrase secrète de 20 caractères minimum.');
        }
        if ($password !== '' && !AuthService::isStrongPassword($password)) {
            throw new InvalidArgumentException('Utilisez au moins 12 caractères avec trois types de caractères, ou une phrase secrète de 20 caractères minimum.');
        }
        if ($userId !== null && $userId === $actorId && (!$active || $role !== 'administrator')) {
            throw new InvalidArgumentException('Vous ne pouvez pas désactiver votre compte ni retirer votre propre rôle administrateur.');
        }

        $this->database->beginTransaction();
        try {
            $existing = null;
            if ($userId !== null) {
                $select = $this->database->prepare(
                    'SELECT u.id, u.is_active, ur.role_code
                     FROM users AS u
                     INNER JOIN user_roles AS ur ON ur.user_id = u.id
                     WHERE u.id = :id FOR UPDATE',
                );
                $select->execute(['id' => $userId]);
                $existingRows = $select->fetchAll();
                $existing = $existingRows[0] ?? null;
                if (!is_array($existing)) {
                    throw new InvalidArgumentException('Ce compte utilisateur n’existe plus.');
                }
                $hasAdministratorRole = in_array(
                    'administrator',
                    array_column($existingRows, 'role_code'),
                    true,
                );

                if (
                    (int) $existing['is_active'] === 1
                    && $hasAdministratorRole
                    && (!$active || $role !== 'administrator')
                ) {
                    $admins = $this->database->query(
                        'SELECT u.id FROM users AS u
                         INNER JOIN user_roles AS ur ON ur.user_id = u.id
                         WHERE u.is_active = 1 AND ur.role_code = \'administrator\'
                         FOR UPDATE',
                    )->fetchAll(PDO::FETCH_COLUMN);
                    if (count($admins) <= 1) {
                        throw new InvalidArgumentException('Il faut conserver au moins un compte administrateur actif.');
                    }
                }

                $updateSql = 'UPDATE users
                              SET display_name = :display_name, email = :email,
                                  phone_e164 = :phone, is_active = :is_active';
                if ($password !== '') {
                    $updateSql .= ', password_hash = :password_hash';
                }
                $updateSql .= ' WHERE id = :id';
                $update = $this->database->prepare($updateSql);
                $parameters = [
                    'display_name' => $displayName,
                    'email' => $email === '' ? null : $email,
                    'phone' => $phone === '' ? null : $phone,
                    'is_active' => $active ? 1 : 0,
                    'id' => $userId,
                ];
                if ($password !== '') {
                    $parameters['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $update->execute($parameters);

                $removeRoles = $this->database->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
                $removeRoles->execute(['user_id' => $userId]);
                $assignRole = $this->database->prepare(
                    'INSERT INTO user_roles (user_id, role_code) VALUES (:user_id, :role_code)',
                );
                $assignRole->execute(['user_id' => $userId, 'role_code' => $role]);
                $action = 'user_updated';
            } else {
                $insert = $this->database->prepare(
                    'INSERT INTO users (display_name, email, phone_e164, password_hash, is_active)
                     VALUES (:display_name, :email, :phone, :password_hash, :is_active)',
                );
                $insert->execute([
                    'display_name' => $displayName,
                    'email' => $email === '' ? null : $email,
                    'phone' => $phone === '' ? null : $phone,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'is_active' => $active ? 1 : 0,
                ]);
                $userId = (int) $this->database->lastInsertId();
                $assignRole = $this->database->prepare(
                    'INSERT INTO user_roles (user_id, role_code) VALUES (:user_id, :role_code)',
                );
                $assignRole->execute(['user_id' => $userId, 'role_code' => $role]);
                $action = 'user_created';
            }

            $clearCenters = $this->database->prepare(
                'DELETE FROM user_health_centers WHERE user_id = :user_id',
            );
            $clearCenters->execute(['user_id' => $userId]);
            if ($healthCenterIds !== []) {
                $assignCenter = $this->database->prepare(
                    'INSERT INTO user_health_centers (user_id, health_center_id)
                     VALUES (:user_id, :health_center_id)',
                );
                foreach ($healthCenterIds as $healthCenterId) {
                    $assignCenter->execute([
                        'user_id' => $userId,
                        'health_center_id' => $healthCenterId,
                    ]);
                }
            }

            $log = $this->database->prepare(
                'INSERT INTO activity_logs (user_id, action, description)
                 VALUES (:user_id, :action, :description)',
            );
            $log->execute([
                'user_id' => $actorId,
                'action' => $action,
                'description' => 'Compte utilisateur #' . $userId . ' — rôle ' . $role,
            ]);
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    private static function characterLength(string $value): int
    {
        $length = preg_match_all('/./us', $value);
        return $length === false ? PHP_INT_MAX : $length;
    }
}

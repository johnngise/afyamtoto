<?php

declare(strict_types=1);

final class ChildProfileService
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function profiles(int $parentId): array
    {
        $query = $this->database->prepare(
            'SELECT c.id, c.first_name, c.last_name, c.date_of_birth, c.sex,
                    cg.consent_status, cg.is_primary
             FROM children AS c
             INNER JOIN child_guardians AS cg ON cg.child_id = c.id
             WHERE cg.user_id = :user_id
             ORDER BY c.date_of_birth DESC, c.id DESC',
        );
        $query->execute(['user_id' => $parentId]);
        return $query->fetchAll();
    }

    public function save(
        int $parentId,
        ?int $childId,
        string $firstName,
        string $lastName,
        string $dateOfBirth,
        string $sex,
        string $relationship,
        bool $consentGranted,
    ): void {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        if ($firstName === '' || self::characterLength($firstName) > 100) {
            throw new InvalidArgumentException('Le prénom doit contenir entre 1 et 100 caractères.');
        }
        if ($lastName === '' || self::characterLength($lastName) > 100) {
            throw new InvalidArgumentException('Le nom doit contenir entre 1 et 100 caractères.');
        }
        if (!in_array($sex, ['female', 'male', 'unknown'], true)) {
            throw new InvalidArgumentException('Sélectionnez une valeur valide pour le sexe.');
        }
        if (!in_array($relationship, ['parent', 'guardian'], true)) {
            throw new InvalidArgumentException('Sélectionnez votre lien avec l’enfant.');
        }

        $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateOfBirth, new DateTimeZone('UTC'));
        $dateErrors = DateTimeImmutable::getLastErrors();
        $today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
        if (
            !$birthDate instanceof DateTimeImmutable
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $birthDate > $today
            || $birthDate < $today->modify('-5 years')
        ) {
            throw new InvalidArgumentException('La date de naissance doit correspondre à un enfant âgé de 0 à 5 ans.');
        }
        if ($childId === null && !$consentGranted) {
            throw new InvalidArgumentException('Confirmez votre autorité et le consentement avant de créer le profil.');
        }

        $this->database->beginTransaction();
        try {
            if ($childId === null) {
                $insertChild = $this->database->prepare(
                    'INSERT INTO children (first_name, last_name, date_of_birth, sex, created_by_user_id)
                     VALUES (:first_name, :last_name, :date_of_birth, :sex, :created_by_user_id)',
                );
                $insertChild->execute([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $dateOfBirth,
                    'sex' => $sex,
                    'created_by_user_id' => $parentId,
                ]);
                $childId = (int) $this->database->lastInsertId();
                $guardian = $this->database->prepare(
                    'INSERT INTO child_guardians (
                        child_id, user_id, relationship_type, is_primary,
                        consent_status, consent_at_utc
                     ) VALUES (
                        :child_id, :user_id, :relationship_type, 1, \'granted\', UTC_TIMESTAMP()
                     )',
                );
                $guardian->execute([
                    'child_id' => $childId,
                    'user_id' => $parentId,
                    'relationship_type' => $relationship,
                ]);
                $action = 'child_profile_created';
            } else {
                $update = $this->database->prepare(
                    'UPDATE children AS c
                     INNER JOIN child_guardians AS cg ON cg.child_id = c.id
                     SET c.first_name = :first_name, c.last_name = :last_name,
                         c.date_of_birth = :date_of_birth, c.sex = :sex
                     WHERE c.id = :child_id AND cg.user_id = :user_id',
                );
                $update->execute([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $dateOfBirth,
                    'sex' => $sex,
                    'child_id' => $childId,
                    'user_id' => $parentId,
                ]);
                if ($update->rowCount() === 0) {
                    $check = $this->database->prepare(
                        'SELECT 1 FROM child_guardians WHERE child_id = :child_id AND user_id = :user_id',
                    );
                    $check->execute(['child_id' => $childId, 'user_id' => $parentId]);
                    if ($check->fetchColumn() === false) {
                        throw new InvalidArgumentException('Ce profil n’existe pas ou vous n’y avez pas accès.');
                    }
                }

                $guardianUpdate = $this->database->prepare(
                    'UPDATE child_guardians
                     SET relationship_type = :relationship_type,
                         consent_status = :consent_status,
                         consent_at_utc = CASE
                             WHEN :is_granted = 1 AND consent_status <> \'granted\'
                             THEN UTC_TIMESTAMP() ELSE consent_at_utc END,
                         consent_withdrawn_at_utc = CASE
                             WHEN :is_withdrawn = 1 THEN UTC_TIMESTAMP()
                             WHEN :is_granted_again = 1 THEN NULL
                             ELSE consent_withdrawn_at_utc END
                     WHERE child_id = :child_id AND user_id = :user_id',
                );
                $guardianUpdate->execute([
                    'relationship_type' => $relationship,
                    'consent_status' => $consentGranted ? 'granted' : 'withdrawn',
                    'is_granted' => $consentGranted ? 1 : 0,
                    'is_withdrawn' => $consentGranted ? 0 : 1,
                    'is_granted_again' => $consentGranted ? 1 : 0,
                    'child_id' => $childId,
                    'user_id' => $parentId,
                ]);
                $action = 'child_profile_updated';
            }

            $log = $this->database->prepare(
                'INSERT INTO activity_logs (user_id, action, description)
                 VALUES (:user_id, :action, :description)',
            );
            $log->execute([
                'user_id' => $parentId,
                'action' => $action,
                'description' => 'Mise à jour d’un profil enfant (identifiant interne ' . $childId . ').',
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

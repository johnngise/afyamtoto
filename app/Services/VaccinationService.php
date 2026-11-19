<?php

declare(strict_types=1);

final class VaccinationService
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function childrenForStaff(array $user): array
    {
        $role = $user['role_code'] ?? null;
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1 || !in_array($role, ['community_health_worker', 'health_professional'], true)) {
            throw new InvalidArgumentException('Votre compte ne peut pas consulter les dossiers de vaccination.');
        }

        $query = $this->database->prepare(
            'SELECT c.id, c.first_name, c.last_name, c.date_of_birth
             FROM children AS c
             WHERE ' . $this->staffChildAccessSql($role) . '
               AND EXISTS (
                   SELECT 1 FROM child_guardians AS cg
                   WHERE cg.child_id = c.id AND cg.consent_status = \'granted\'
               )
             ORDER BY c.last_name, c.first_name, c.id',
        );
        $query->execute($this->childAccessParameters($role, $userId));
        return $query->fetchAll();
    }

    public function parentHistory(int $parentId): array
    {
        $query = $this->database->prepare(
            'SELECT c.id AS child_id, c.first_name, c.last_name, v.display_name AS vaccine_name,
                    vd.display_name AS dose_name, vr.administered_at_utc, vr.next_dose_date,
                    hc.name AS health_center_name, recorder.display_name AS recorded_by_name
             FROM vaccination_records AS vr
             INNER JOIN children AS c ON c.id = vr.child_id
             INNER JOIN child_guardians AS cg ON cg.child_id = c.id
             INNER JOIN vaccine_doses AS vd ON vd.id = vr.vaccine_dose_id
             INNER JOIN vaccines AS v ON v.id = vd.vaccine_id
             LEFT JOIN health_centers AS hc ON hc.id = vr.health_center_id
             LEFT JOIN users AS recorder ON recorder.id = vr.recorded_by_user_id
             WHERE cg.user_id = :parent_id AND cg.consent_status = \'granted\'
               AND vr.status = \'administered\'
             ORDER BY vr.administered_at_utc DESC, vr.id DESC LIMIT 200',
        );
        $query->execute(['parent_id' => $parentId]);
        return $query->fetchAll();
    }

    public function staffHistory(array $user): array
    {
        $role = $user['role_code'] ?? null;
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1 || !in_array($role, ['community_health_worker', 'health_professional'], true)) {
            throw new InvalidArgumentException('Votre compte ne peut pas consulter les dossiers de vaccination.');
        }

        $query = $this->database->prepare(
            'SELECT c.id AS child_id, c.first_name, c.last_name, v.display_name AS vaccine_name,
                    vd.display_name AS dose_name, vr.administered_at_utc, vr.next_dose_date,
                    vr.notes, hc.name AS health_center_name, recorder.display_name AS recorded_by_name
             FROM vaccination_records AS vr
             INNER JOIN children AS c ON c.id = vr.child_id
             INNER JOIN vaccine_doses AS vd ON vd.id = vr.vaccine_dose_id
             INNER JOIN vaccines AS v ON v.id = vd.vaccine_id
             LEFT JOIN health_centers AS hc ON hc.id = vr.health_center_id
             LEFT JOIN users AS recorder ON recorder.id = vr.recorded_by_user_id
             WHERE ' . $this->staffChildAccessSql($role) . '
               AND EXISTS (
                   SELECT 1 FROM child_guardians AS cg
                   WHERE cg.child_id = c.id AND cg.consent_status = \'granted\'
               )
               AND vr.status = \'administered\'
             ORDER BY vr.administered_at_utc DESC, vr.id DESC LIMIT 200',
        );
        $query->execute($this->childAccessParameters($role, $userId));
        return $query->fetchAll();
    }

    public function activeDoses(): array
    {
        return $this->database->query(
            'SELECT vd.id, vd.display_name AS dose_name, v.display_name AS vaccine_name
             FROM vaccine_doses AS vd
             INNER JOIN vaccines AS v ON v.id = vd.vaccine_id
             WHERE v.is_active = 1
             ORDER BY v.display_name, vd.dose_order',
        )->fetchAll();
    }

    public function assignedCenters(int $userId): array
    {
        $query = $this->database->prepare(
            'SELECT hc.id, hc.name, hc.city
             FROM user_health_centers AS uhc
             INNER JOIN health_centers AS hc ON hc.id = uhc.health_center_id
             WHERE uhc.user_id = :user_id
             ORDER BY hc.name',
        );
        $query->execute(['user_id' => $userId]);
        return $query->fetchAll();
    }

    public function recordAdministeredDose(
        array $user,
        int $childId,
        int $doseId,
        string $administeredDate,
        ?string $nextDoseDate,
        ?int $healthCenterId,
        string $notes,
    ): void {
        $role = $user['role_code'] ?? null;
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1 || !in_array($role, ['community_health_worker', 'health_professional'], true)) {
            throw new InvalidArgumentException('Seul le personnel de santé autorisé peut enregistrer une vaccination.');
        }
        if ($childId < 1 || $doseId < 1 || ($healthCenterId !== null && $healthCenterId < 1)) {
            throw new InvalidArgumentException('Les informations du formulaire de vaccination sont invalides.');
        }

        $administered = self::parseDate($administeredDate, 'La date d’administration est invalide.');
        $today = new DateTimeImmutable('today', new DateTimeZone('Africa/Lubumbashi'));
        if ($administered > $today) {
            throw new InvalidArgumentException('La date d’administration ne peut pas être dans le futur.');
        }
        $nextDose = null;
        if ($nextDoseDate !== null && $nextDoseDate !== '') {
            $nextDose = self::parseDate($nextDoseDate, 'La date de la prochaine dose est invalide.');
            if ($nextDose <= $administered) {
                throw new InvalidArgumentException('La prochaine date doit être postérieure à la date d’administration.');
            }
        }

        $notes = trim($notes);
        $notesLength = preg_match_all('/./us', $notes);
        if ($notesLength === false || $notesLength > 500) {
            throw new InvalidArgumentException('Les notes ne peuvent pas dépasser 500 caractères.');
        }
        if ($role === 'health_professional' && $healthCenterId === null) {
            throw new InvalidArgumentException('Sélectionnez le centre de santé associé au dossier autorisé.');
        }

        $this->database->beginTransaction();
        try {
            $consentCheck = $this->database->prepare(
                'SELECT child_id FROM child_guardians
                 WHERE child_id = :child_id AND consent_status = \'granted\'
                 FOR UPDATE',
            );
            $consentCheck->execute(['child_id' => $childId]);
            if ($consentCheck->fetchColumn() === false) {
                throw new InvalidArgumentException('Le consentement requis pour ce dossier n’est pas actif.');
            }

            $childAccess = $this->database->prepare(
                'SELECT c.id FROM children AS c
                 WHERE c.id = :child_id
                   AND EXISTS (
                       SELECT 1 FROM child_guardians AS cg
                       WHERE cg.child_id = c.id AND cg.consent_status = \'granted\'
                   )
                   AND ' . $this->staffChildAccessSql($role),
            );
            $childAccess->execute([
                'child_id' => $childId,
                ...$this->childAccessParameters($role, $userId),
            ]);
            if ($childAccess->fetchColumn() === false) {
                throw new InvalidArgumentException('Cet enfant n’est pas associé à votre activité ou son consentement n’est pas actif.');
            }

            $doseCheck = $this->database->prepare(
                'SELECT 1 FROM vaccine_doses AS vd
                 INNER JOIN vaccines AS v ON v.id = vd.vaccine_id
                 WHERE vd.id = :dose_id AND v.is_active = 1',
            );
            $doseCheck->execute(['dose_id' => $doseId]);
            if ($doseCheck->fetchColumn() === false) {
                throw new InvalidArgumentException('Le vaccin sélectionné n’est pas disponible.');
            }

            if ($healthCenterId !== null) {
                $centerCheck = $this->database->prepare(
                    'SELECT 1 FROM user_health_centers AS uhc
                     WHERE uhc.user_id = :user_id AND uhc.health_center_id = :center_id',
                );
                $centerCheck->execute(['user_id' => $userId, 'center_id' => $healthCenterId]);
                if ($centerCheck->fetchColumn() === false) {
                    throw new InvalidArgumentException('Vous n’êtes pas autorisé à enregistrer une vaccination pour ce centre.');
                }

                if ($role === 'health_professional') {
                    $centerChildCheck = $this->database->prepare(
                        'SELECT 1 FROM health_alerts AS ha
                         WHERE ha.child_id = :child_id AND ha.health_center_id = :center_id
                         LIMIT 1',
                    );
                    $centerChildCheck->execute(['child_id' => $childId, 'center_id' => $healthCenterId]);
                    if ($centerChildCheck->fetchColumn() === false) {
                        throw new InvalidArgumentException('Ce dossier n’est pas associé à ce centre de santé.');
                    }
                }
            }

            $insert = $this->database->prepare(
                'INSERT INTO vaccination_records (
                    child_id, vaccine_dose_id, administered_at_utc, next_dose_date, status,
                    notes, recorded_by_user_id, health_center_id
                 ) VALUES (
                    :child_id, :dose_id, :administered_at, :next_dose_date, \'administered\',
                    :notes, :user_id, :center_id
                 )',
            );
            $insert->execute([
                'child_id' => $childId,
                'dose_id' => $doseId,
                'administered_at' => $administered->format('Y-m-d') . ' 12:00:00',
                'next_dose_date' => $nextDose?->format('Y-m-d'),
                'notes' => $notes === '' ? null : $notes,
                'user_id' => $userId,
                'center_id' => $healthCenterId,
            ]);
            $recordId = (int) $this->database->lastInsertId();

            $activity = $this->database->prepare(
                'INSERT INTO activity_logs (user_id, action, description)
                 VALUES (:user_id, \'vaccination_recorded\', :description)',
            );
            $activity->execute([
                'user_id' => $userId,
                'description' => 'Enregistrement d’une dose de vaccination (identifiant interne ' . $recordId . ').',
            ]);
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $error;
        }
    }

    private function staffChildAccessSql(string $role): string
    {
        if ($role === 'community_health_worker') {
            return '(
                c.created_by_user_id = :created_by_user
                OR EXISTS (
                    SELECT 1 FROM growth_measurements AS gm
                    WHERE gm.child_id = c.id AND gm.recorded_by_user_id = :growth_user
                )
                OR EXISTS (
                    SELECT 1 FROM nutrition_records AS nr
                    WHERE nr.child_id = c.id AND nr.health_worker_id = :nutrition_user
                )
                OR EXISTS (
                    SELECT 1 FROM community_visits AS cv
                    WHERE cv.child_id = c.id AND cv.agent_user_id = :visit_user
                )
                OR EXISTS (
                    SELECT 1 FROM health_referrals AS hr
                    WHERE hr.child_id = c.id AND hr.agent_user_id = :referral_user
                )
            )';
        }

        if ($role === 'health_professional') {
            return 'EXISTS (
                SELECT 1 FROM health_alerts AS ha
                INNER JOIN user_health_centers AS uhc
                    ON uhc.health_center_id = ha.health_center_id
                WHERE ha.child_id = c.id AND uhc.user_id = :center_user
            )';
        }

        throw new InvalidArgumentException('Le rôle ne permet pas l’accès au parcours de vaccination.');
    }

    private function childAccessParameters(string $role, int $userId): array
    {
        return $role === 'community_health_worker'
            ? [
                'created_by_user' => $userId,
                'growth_user' => $userId,
                'nutrition_user' => $userId,
                'visit_user' => $userId,
                'referral_user' => $userId,
            ]
            : ['center_user' => $userId];
    }

    private static function parseDate(string $value, string $errorMessage): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();
        if (
            !($date instanceof DateTimeImmutable)
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        ) {
            throw new InvalidArgumentException($errorMessage);
        }

        return $date;
    }
}

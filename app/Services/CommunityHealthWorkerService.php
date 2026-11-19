<?php

declare(strict_types=1);

final class CommunityHealthWorkerService
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function enrollChild(
        array $agent,
        string $parentEmail,
        string $firstName,
        string $lastName,
        string $dateOfBirth,
        string $sex,
        string $relationship,
    ): void {
        $agentId = self::agentId($agent);
        $parentEmail = strtolower(trim($parentEmail));
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        if (!filter_var($parentEmail, FILTER_VALIDATE_EMAIL) || strlen($parentEmail) > 254) {
            throw new InvalidArgumentException('Saisissez l’adresse d’un compte parent valide.');
        }
        self::requiredLength($firstName, 100, 'Le prénom');
        self::requiredLength($lastName, 100, 'Le nom');
        if (!in_array($sex, ['female', 'male', 'unknown'], true)) {
            throw new InvalidArgumentException('Sélectionnez une valeur valide pour le sexe.');
        }
        if (!in_array($relationship, ['parent', 'guardian'], true)) {
            throw new InvalidArgumentException('Sélectionnez un lien familial valide.');
        }

        $birthDate = self::parseDate($dateOfBirth, 'La date de naissance est invalide.');
        $today = new DateTimeImmutable('today', new DateTimeZone('Africa/Lubumbashi'));
        if ($birthDate > $today || $birthDate < $today->modify('-5 years')) {
            throw new InvalidArgumentException('La date de naissance doit correspondre à un enfant âgé de 0 à 5 ans.');
        }

        $this->database->beginTransaction();
        try {
            $parent = $this->database->prepare(
                'SELECT u.id
                 FROM users AS u
                 INNER JOIN user_roles AS ur ON ur.user_id = u.id
                 WHERE u.email = :email AND u.is_active = 1 AND ur.role_code = \'parent\'
                 FOR UPDATE',
            );
            $parent->execute(['email' => $parentEmail]);
            $parentId = $parent->fetchColumn();
            if ($parentId === false) {
                throw new InvalidArgumentException('Le parent doit d’abord créer son compte pour rattacher le profil.');
            }

            $insertChild = $this->database->prepare(
                'INSERT INTO children (first_name, last_name, date_of_birth, sex, created_by_user_id)
                 VALUES (:first_name, :last_name, :date_of_birth, :sex, :created_by_user_id)',
            );
            $insertChild->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => $dateOfBirth,
                'sex' => $sex,
                'created_by_user_id' => $agentId,
            ]);
            $childId = (int) $this->database->lastInsertId();

            $guardian = $this->database->prepare(
                'INSERT INTO child_guardians (
                    child_id, user_id, relationship_type, is_primary, consent_status
                 ) VALUES (:child_id, :parent_id, :relationship_type, 1, \'pending\')',
            );
            $guardian->execute([
                'child_id' => $childId,
                'parent_id' => (int) $parentId,
                'relationship_type' => $relationship,
            ]);
            $parentNotification = $this->database->prepare(
                'INSERT INTO notifications (user_id, title, message, type)
                 VALUES (
                    :parent_id, \'Profil enfant à vérifier\',
                    \'Un agent de santé a rattaché un profil à votre compte. Vérifiez-le et confirmez votre consentement avant tout suivi de santé.\',
                    \'child_consent_requested\'
                 )',
            );
            $parentNotification->execute(['parent_id' => (int) $parentId]);
            $this->logActivity($agentId, 'community_child_enrolled', 'Création d’un profil communautaire en attente de consentement.');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function recordGrowth(
        array $agent,
        int $childId,
        string $measuredOn,
        ?string $weightKg,
        ?string $lengthHeightCm,
        ?string $lengthHeightType,
        ?string $muacCm,
    ): void {
        $agentId = self::agentId($agent);
        $date = self::parseDate($measuredOn, 'La date de mesure est invalide.');
        self::assertNotFuture($date, 'La date de mesure ne peut pas être dans le futur.');
        $weight = self::measurementValue($weightKg, 3, 999.999, 'poids');
        $height = self::measurementValue($lengthHeightCm, 2, 9999.99, 'taille ou longueur');
        $muac = self::measurementValue($muacCm, 2, 9999.99, 'périmètre brachial');
        if ($weight === null && $height === null && $muac === null) {
            throw new InvalidArgumentException('Saisissez au moins une mesure.');
        }
        if ($height !== null && !in_array($lengthHeightType, ['length', 'height'], true)) {
            throw new InvalidArgumentException('Indiquez si la mesure correspond à la taille ou à la longueur.');
        }

        $this->database->beginTransaction();
        try {
            $this->assertChildAccess($childId, $agentId, $date);
            $insert = $this->database->prepare(
                'INSERT INTO growth_measurements (
                    child_id, recorded_by_user_id, measured_at_utc, weight_kg,
                    length_height_cm, length_height_type, muac_cm
                 ) VALUES (
                    :child_id, :agent_id, :measured_at, :weight, :height, :height_type, :muac
                 )',
            );
            $insert->execute([
                'child_id' => $childId,
                'agent_id' => $agentId,
                'measured_at' => $measuredOn . ' 12:00:00',
                'weight' => $weight,
                'height' => $height,
                'height_type' => $height === null ? null : $lengthHeightType,
                'muac' => $muac,
            ]);
            $this->logActivity($agentId, 'community_growth_recorded', 'Enregistrement de mesures de croissance.');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function recordNutritionScreening(
        array $agent,
        int $childId,
        string $recordDate,
        string $feedingType,
        string $observations,
    ): void {
        $agentId = self::agentId($agent);
        $date = self::parseDate($recordDate, 'La date du dépistage est invalide.');
        self::assertNotFuture($date, 'La date du dépistage ne peut pas être dans le futur.');
        $feedingType = trim($feedingType);
        $observations = trim($observations);
        self::optionalLength($feedingType, 64, 'Le type d’alimentation');
        self::requiredLength($observations, 4000, 'Les observations');

        $this->database->beginTransaction();
        try {
            $this->assertChildAccess($childId, $agentId, $date);
            $insert = $this->database->prepare(
                'INSERT INTO nutrition_records (
                    child_id, record_date, nutritional_status, feeding_type,
                    observations, recommendations, health_worker_id
                 ) VALUES (
                    :child_id, :record_date, NULL, :feeding_type, :observations, NULL, :agent_id
                 )',
            );
            $insert->execute([
                'child_id' => $childId,
                'record_date' => $recordDate,
                'feeding_type' => $feedingType === '' ? null : $feedingType,
                'observations' => $observations,
                'agent_id' => $agentId,
            ]);
            $this->logActivity($agentId, 'community_nutrition_screening_recorded', 'Enregistrement d’observations de dépistage nutritionnel.');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function recordVisit(
        array $agent,
        int $childId,
        string $visitType,
        string $visitedOn,
        string $purpose,
        string $observations,
    ): void {
        $agentId = self::agentId($agent);
        if (!in_array($visitType, ['home', 'community_outreach', 'follow_up'], true)) {
            throw new InvalidArgumentException('Sélectionnez un type de visite valide.');
        }
        $date = self::parseDate($visitedOn, 'La date de visite est invalide.');
        self::assertNotFuture($date, 'La date de visite ne peut pas être dans le futur.');
        $purpose = trim($purpose);
        $observations = trim($observations);
        self::requiredLength($purpose, 160, 'Le motif');
        self::optionalLength($observations, 4000, 'Les observations');

        $this->database->beginTransaction();
        try {
            $this->assertChildAccess($childId, $agentId, $date);
            $insert = $this->database->prepare(
                'INSERT INTO community_visits (
                    child_id, agent_user_id, visit_type, visited_at_utc, purpose, observations
                 ) VALUES (
                    :child_id, :agent_id, :visit_type, :visited_at, :purpose, :observations
                 )',
            );
            $insert->execute([
                'child_id' => $childId,
                'agent_id' => $agentId,
                'visit_type' => $visitType,
                'visited_at' => $visitedOn . ' 12:00:00',
                'purpose' => $purpose,
                'observations' => $observations === '' ? null : $observations,
            ]);
            $this->logActivity($agentId, 'community_visit_recorded', 'Enregistrement d’une activité communautaire.');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function createReferral(
        array $agent,
        int $childId,
        int $healthCenterId,
        string $concern,
        string $priority,
    ): void {
        $agentId = self::agentId($agent);
        if (!in_array($priority, ['routine', 'prompt', 'emergency'], true)) {
            throw new InvalidArgumentException('Sélectionnez une priorité de référencement valide.');
        }
        $concern = trim($concern);
        self::requiredLength($concern, 1000, 'La situation à signaler');

        $this->database->beginTransaction();
        try {
            $this->assertChildAccess($childId, $agentId);
            $centerCheck = $this->database->prepare(
                'SELECT 1 FROM health_centers
                 WHERE id = :center_id AND name NOT LIKE \'DEMO UNIQUEMENT%\'
                 FOR UPDATE',
            );
            $centerCheck->execute(['center_id' => $healthCenterId]);
            if ($healthCenterId < 1 || $centerCheck->fetchColumn() === false) {
                throw new InvalidArgumentException('Sélectionnez un centre de santé actif.');
            }

            $referral = $this->database->prepare(
                'INSERT INTO health_referrals (
                    child_id, agent_user_id, health_center_id, concern_description, priority
                 ) VALUES (:child_id, :agent_id, :center_id, :concern, :priority)',
            );
            $referral->execute([
                'child_id' => $childId,
                'agent_id' => $agentId,
                'center_id' => $healthCenterId,
                'concern' => $concern,
                'priority' => $priority,
            ]);

            $alert = $this->database->prepare(
                'INSERT INTO health_alerts (
                    child_id, alert_type, severity, description, status,
                    created_by_user_id, health_center_id
                 ) VALUES (
                    :child_id, \'community_referral\', :severity, :description,
                    \'pending_review\', :agent_id, :center_id
                 )',
            );
            $alert->execute([
                'child_id' => $childId,
                'severity' => ['routine' => 'low', 'prompt' => 'medium', 'emergency' => 'high'][$priority],
                'description' => $concern,
                'agent_id' => $agentId,
                'center_id' => $healthCenterId,
            ]);

            $notifications = $this->database->prepare(
                'INSERT INTO notifications (user_id, title, message, type)
                 SELECT DISTINCT u.id, \'Orientation communautaire à examiner\',
                        \'Une orientation nécessite une évaluation professionnelle.\',
                        \'community_referral\'
                 FROM user_health_centers AS uhc
                 INNER JOIN users AS u ON u.id = uhc.user_id AND u.is_active = 1
                 INNER JOIN user_roles AS ur ON ur.user_id = u.id
                 WHERE uhc.health_center_id = :center_id
                   AND ur.role_code = \'health_professional\'',
            );
            $notifications->execute(['center_id' => $healthCenterId]);
                 $parentNotifications = $this->database->prepare(
                  'INSERT INTO notifications (user_id, title, message, type)
                   SELECT cg.user_id, \'Orientation vers un centre de santé\',
                       \'Un agent de santé a enregistré une orientation pour un enfant associé à votre compte.\',
                       \'community_referral\'
                   FROM child_guardians AS cg
                   WHERE cg.child_id = :child_id AND cg.consent_status = \'granted\'',
                 );
                 $parentNotifications->execute(['child_id' => $childId]);
            $this->logActivity($agentId, 'community_referral_created', 'Création d’une orientation vers un centre de santé.');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function followUpReferral(array $agent, int $referralId, string $outcome, string $notes): void
    {
        $agentId = self::agentId($agent);
        if ($referralId < 1 || !in_array($outcome, ['attended', 'not_attended'], true)) {
            throw new InvalidArgumentException('Sélectionnez un résultat de suivi valide.');
        }
        $notes = trim($notes);
        self::optionalLength($notes, 500, 'Les notes de suivi');

        $this->database->beginTransaction();
        try {
            $update = $this->database->prepare(
                'UPDATE health_referrals
                 SET status = :status, followed_up_at_utc = UTC_TIMESTAMP(),
                     follow_up_notes = :notes
                 WHERE id = :referral_id AND agent_user_id = :agent_id
                                     AND status IN (\'awaiting_follow_up\', \'not_attended\')',
            );
            $update->execute([
                'status' => $outcome,
                'notes' => $notes === '' ? null : $notes,
                'referral_id' => $referralId,
                'agent_id' => $agentId,
            ]);
            if ($update->rowCount() !== 1) {
                throw new InvalidArgumentException('Ce référencement n’existe pas, a déjà été suivi ou n’est pas associé à votre compte.');
            }
            $this->logActivity($agentId, 'community_referral_followed_up', 'Mise à jour du suivi d’une orientation.');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    private function assertChildAccess(int $childId, int $agentId, ?DateTimeImmutable $eventDate = null): void
    {
        if ($childId < 1) {
            throw new InvalidArgumentException('Sélectionnez un enfant valide.');
        }
        $consent = $this->database->prepare(
            'SELECT 1 FROM child_guardians
             WHERE child_id = :child_id AND consent_status = \'granted\'
             FOR UPDATE',
        );
        $consent->execute(['child_id' => $childId]);
        if ($consent->fetchColumn() === false) {
            throw new InvalidArgumentException('Le consentement requis pour ce dossier n’est pas actif.');
        }

        $access = $this->database->prepare(
            'SELECT c.date_of_birth
             FROM children AS c
             WHERE c.id = :child_id
               AND (
                   c.created_by_user_id = :created_by
                   OR EXISTS (SELECT 1 FROM growth_measurements AS gm
                              WHERE gm.child_id = c.id AND gm.recorded_by_user_id = :growth_by)
                   OR EXISTS (SELECT 1 FROM nutrition_records AS nr
                              WHERE nr.child_id = c.id AND nr.health_worker_id = :nutrition_by)
                   OR EXISTS (SELECT 1 FROM community_visits AS cv
                              WHERE cv.child_id = c.id AND cv.agent_user_id = :visit_by)
                   OR EXISTS (SELECT 1 FROM health_referrals AS hr
                              WHERE hr.child_id = c.id AND hr.agent_user_id = :referral_by)
               )
             FOR UPDATE',
        );
        $access->execute([
            'child_id' => $childId,
            'created_by' => $agentId,
            'growth_by' => $agentId,
            'nutrition_by' => $agentId,
            'visit_by' => $agentId,
            'referral_by' => $agentId,
        ]);
        $dateOfBirth = $access->fetchColumn();
        if (!is_string($dateOfBirth)) {
            throw new InvalidArgumentException('Cet enfant n’est pas associé à votre activité.');
        }
        if ($eventDate !== null && $eventDate->format('Y-m-d') < $dateOfBirth) {
            throw new InvalidArgumentException('La date de l’activité ne peut pas précéder la naissance de l’enfant.');
        }
    }

    private function logActivity(int $agentId, string $action, string $description): void
    {
        $log = $this->database->prepare(
            'INSERT INTO activity_logs (user_id, action, description)
             VALUES (:user_id, :action, :description)',
        );
        $log->execute(['user_id' => $agentId, 'action' => $action, 'description' => $description]);
    }

    private static function agentId(array $agent): int
    {
        $agentId = (int) ($agent['id'] ?? 0);
        if ($agentId < 1 || ($agent['role_code'] ?? null) !== 'community_health_worker') {
            throw new InvalidArgumentException('Cette opération est réservée aux agents de santé communautaires.');
        }

        return $agentId;
    }

    private static function parseDate(string $value, string $errorMessage): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();
        if (
            !$date instanceof DateTimeImmutable
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value
        ) {
            throw new InvalidArgumentException($errorMessage);
        }

        return $date;
    }

    private static function assertNotFuture(DateTimeImmutable $date, string $message): void
    {
        if ($date->format('Y-m-d') > (new DateTimeImmutable('today', new DateTimeZone('Africa/Lubumbashi')))->format('Y-m-d')) {
            throw new InvalidArgumentException($message);
        }
    }

    private static function measurementValue(?string $value, int $scale, float $maximum, string $label): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        $fraction = $scale === 3 ? '{1,3}' : '{1,2}';
        if (
            strlen($value) > 10
            || !preg_match('/^\d+(?:\.\d' . $fraction . ')?$/D', $value)
            || (float) $value <= 0
            || (float) $value > $maximum
        ) {
            throw new InvalidArgumentException('Saisissez une valeur positive valide pour le ' . $label . '.');
        }

        return $value;
    }

    private static function requiredLength(string $value, int $maximum, string $label): void
    {
        self::optionalLength($value, $maximum, $label);
        if (trim($value) === '') {
            throw new InvalidArgumentException($label . ' est obligatoire.');
        }
    }

    private static function optionalLength(string $value, int $maximum, string $label): void
    {
        $length = preg_match_all('/./us', $value);
        if ($length === false || $length > $maximum) {
            throw new InvalidArgumentException($label . ' dépasse la longueur autorisée.');
        }
    }
}
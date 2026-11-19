<?php

declare(strict_types=1);

final class HealthProfessionalService
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function reviewAlert(array $professional, int $alertId, string $status, string $notes): void
    {
        $userId = self::professionalId($professional);
        if ($alertId < 1 || !in_array($status, ['under_review', 'referred', 'resolved', 'dismissed'], true)) {
            throw new InvalidArgumentException('La mise à jour de l’alerte est invalide.');
        }
        $notes = trim($notes);
        self::optionalLength($notes, 1000, 'Les notes de revue');

        $this->database->beginTransaction();
        try {
            $parents = $this->database->prepare(
                'SELECT DISTINCT cg.user_id
                 FROM health_alerts AS ha
                 INNER JOIN user_health_centers AS uhc
                    ON uhc.health_center_id = ha.health_center_id AND uhc.user_id = :user_id
                 INNER JOIN child_guardians AS cg
                    ON cg.child_id = ha.child_id AND cg.consent_status = \'granted\'
                 WHERE ha.id = :alert_id
                   AND ha.status IN (\'pending_review\', \'under_review\')
                 FOR UPDATE',
            );
            $parents->execute(['user_id' => $userId, 'alert_id' => $alertId]);
            $parentIds = $parents->fetchAll(PDO::FETCH_COLUMN);
            if ($parentIds === []) {
                throw new InvalidArgumentException('Cette alerte n’est pas accessible ou le consentement n’est pas actif.');
            }

            $update = $this->database->prepare(
                'UPDATE health_alerts AS ha
                 INNER JOIN user_health_centers AS uhc
                    ON uhc.health_center_id = ha.health_center_id
                 INNER JOIN child_guardians AS cg
                    ON cg.child_id = ha.child_id AND cg.consent_status = \'granted\'
                 SET ha.status = :status, ha.reviewed_by_user_id = :reviewer_id,
                     ha.reviewed_at_utc = UTC_TIMESTAMP(), ha.review_notes = :notes,
                     ha.resolved_at_utc = CASE WHEN :is_resolved = 1
                         THEN UTC_TIMESTAMP() ELSE NULL END
                 WHERE ha.id = :alert_id AND uhc.user_id = :user_id
                   AND ha.status IN (\'pending_review\', \'under_review\')',
            );
            $update->execute([
                'status' => $status,
                'reviewer_id' => $userId,
                'notes' => $notes === '' ? null : $notes,
                'is_resolved' => $status === 'resolved' ? 1 : 0,
                'alert_id' => $alertId,
                'user_id' => $userId,
            ]);
            if ($update->rowCount() !== 1) {
                throw new InvalidArgumentException('Cette alerte n’est pas accessible, a déjà été close ou le consentement n’est pas actif.');
            }
            $notification = $this->database->prepare(
                'INSERT INTO notifications (user_id, title, message, type)
                 VALUES (:user_id, \'Mise à jour du suivi de santé\',
                         \'Un professionnel a mis à jour le suivi. Consultez votre espace parent.\',
                         \'health_alert_review\')',
            );
            foreach ($parentIds as $parentId) {
                $notification->execute(['user_id' => (int) $parentId]);
            }
            $this->log($userId, 'professional_alert_reviewed', 'Revue professionnelle d’une alerte (identifiant interne ' . $alertId . ').');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function reviewReferral(array $professional, int $referralId, string $outcome, string $notes): void
    {
        $userId = self::professionalId($professional);
        if ($referralId < 1 || !in_array($outcome, ['attended', 'not_attended'], true)) {
            throw new InvalidArgumentException('Le résultat de prise en charge est invalide.');
        }
        $notes = trim($notes);
        self::optionalLength($notes, 1000, 'Les observations professionnelles');

        $this->database->beginTransaction();
        try {
            $referral = $this->database->prepare(
                'SELECT hr.child_id, hr.health_center_id, cg.user_id AS parent_id
                 FROM health_referrals AS hr
                 INNER JOIN user_health_centers AS uhc
                    ON uhc.health_center_id = hr.health_center_id AND uhc.user_id = :user_id
                 INNER JOIN child_guardians AS cg
                    ON cg.child_id = hr.child_id AND cg.consent_status = \'granted\'
                 WHERE hr.id = :referral_id
                   AND hr.status IN (\'awaiting_follow_up\', \'not_attended\')
                 FOR UPDATE',
            );
            $referral->execute(['user_id' => $userId, 'referral_id' => $referralId]);
            $referralRows = $referral->fetchAll();
            if ($referralRows === []) {
                throw new InvalidArgumentException('Ce référencement n’est pas accessible ou le consentement n’est pas actif.');
            }
            $referralRow = $referralRows[0];

            $update = $this->database->prepare(
                'UPDATE health_referrals
                 SET status = :status, followed_up_at_utc = UTC_TIMESTAMP(), follow_up_notes = :notes
                 WHERE id = :referral_id',
            );
            $update->execute([
                'status' => $outcome,
                'notes' => $notes === '' ? null : $notes,
                'referral_id' => $referralId,
            ]);

            if ($notes !== '') {
                $this->insertObservation(
                    (int) $referralRow['child_id'],
                    (int) $referralRow['health_center_id'],
                    $userId,
                    null,
                    $referralId,
                    'care_plan',
                    $notes,
                );
            }
            $notification = $this->database->prepare(
                'INSERT INTO notifications (user_id, title, message, type)
                 VALUES (:parent_id, \'Mise à jour d’une orientation\',
                         \'Le centre a mis à jour le suivi de l’orientation. Consultez votre espace parent.\',
                         \'referral_update\')',
            );
            foreach ($referralRows as $parent) {
                $notification->execute(['parent_id' => (int) $parent['parent_id']]);
            }
            $this->log($userId, 'professional_referral_reviewed', 'Revue professionnelle d’une orientation (identifiant interne ' . $referralId . ').');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function addObservation(
        array $professional,
        string $contextType,
        int $contextId,
        string $type,
        string $content,
    ): void {
        $userId = self::professionalId($professional);
        if ($contextId < 1 || !in_array($contextType, ['alert', 'referral'], true)) {
            throw new InvalidArgumentException('Associez l’observation à une alerte ou orientation autorisée.');
        }
        if (!in_array($type, ['growth_review', 'nutrition_review', 'clinical_observation', 'care_plan'], true)) {
            throw new InvalidArgumentException('Sélectionnez un type d’observation valide.');
        }
        $content = trim($content);
        self::requiredLength($content, 4000, 'L’observation');

        $this->database->beginTransaction();
        try {
            $table = $contextType === 'alert' ? 'health_alerts' : 'health_referrals';
            $context = $this->database->prepare(
                'SELECT record.child_id, record.health_center_id
                 FROM ' . $table . ' AS record
                 INNER JOIN user_health_centers AS uhc
                    ON uhc.health_center_id = record.health_center_id AND uhc.user_id = :user_id
                 INNER JOIN child_guardians AS cg
                    ON cg.child_id = record.child_id AND cg.consent_status = \'granted\'
                 WHERE record.id = :context_id FOR UPDATE',
            );
            $context->execute(['user_id' => $userId, 'context_id' => $contextId]);
            $contextRow = $context->fetch();
            if (!is_array($contextRow)) {
                throw new InvalidArgumentException('Ce dossier n’est pas accessible depuis vos centres affectés.');
            }

            $this->insertObservation(
                (int) $contextRow['child_id'],
                (int) $contextRow['health_center_id'],
                $userId,
                $contextType === 'alert' ? $contextId : null,
                $contextType === 'referral' ? $contextId : null,
                $type,
                $content,
            );
            $this->log($userId, 'professional_observation_added', 'Ajout d’une observation professionnelle au dossier autorisé.');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    private function insertObservation(
        int $childId,
        int $centerId,
        int $userId,
        ?int $alertId,
        ?int $referralId,
        string $type,
        string $content,
    ): void {
        $insert = $this->database->prepare(
            'INSERT INTO professional_observations (
                child_id, health_center_id, health_professional_id,
                health_alert_id, health_referral_id, observation_type, content
             ) VALUES (
                :child_id, :center_id, :user_id, :alert_id, :referral_id, :type, :content
             )',
        );
        $insert->execute([
            'child_id' => $childId,
            'center_id' => $centerId,
            'user_id' => $userId,
            'alert_id' => $alertId,
            'referral_id' => $referralId,
            'type' => $type,
            'content' => $content,
        ]);
    }

    private function log(int $userId, string $action, string $description): void
    {
        $query = $this->database->prepare(
            'INSERT INTO activity_logs (user_id, action, description)
             VALUES (:user_id, :action, :description)',
        );
        $query->execute(['user_id' => $userId, 'action' => $action, 'description' => $description]);
    }

    private static function professionalId(array $professional): int
    {
        $userId = (int) ($professional['id'] ?? 0);
        if ($userId < 1 || ($professional['role_code'] ?? null) !== 'health_professional') {
            throw new InvalidArgumentException('Cette opération est réservée aux professionnels de santé.');
        }

        return $userId;
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
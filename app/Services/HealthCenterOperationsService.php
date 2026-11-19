<?php

declare(strict_types=1);

final class HealthCenterOperationsService
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function respondToAppointment(
        array $manager,
        int $requestId,
        string $status,
        string $scheduledLocal,
        string $responseMessage,
    ): void {
        $managerId = self::managerId($manager);
        if ($requestId < 1 || !in_array($status, ['confirmed', 'declined'], true)) {
            throw new InvalidArgumentException('La réponse au rendez-vous est invalide.');
        }
        $responseMessage = trim($responseMessage);
        self::optionalLength($responseMessage, 500, 'Le message de réponse');

        $scheduledUtc = null;
        if ($status === 'confirmed') {
            $localDate = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $scheduledLocal, new DateTimeZone('Africa/Lubumbashi'));
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (
                !$localDate instanceof DateTimeImmutable
                || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                || $localDate->format('Y-m-d\TH:i') !== $scheduledLocal
                || $localDate <= new DateTimeImmutable('now', new DateTimeZone('Africa/Lubumbashi'))
            ) {
                throw new InvalidArgumentException('Saisissez une date future valide pour confirmer le rendez-vous.');
            }
            $scheduledUtc = $localDate->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } elseif ($scheduledLocal !== '') {
            throw new InvalidArgumentException('Une date ne doit être saisie que pour confirmer le rendez-vous.');
        }

        $this->database->beginTransaction();
        try {
            $request = $this->database->prepare(
                'SELECT ar.parent_user_id
                 FROM appointment_requests AS ar
                 INNER JOIN user_health_centers AS uhc
                    ON uhc.health_center_id = ar.health_center_id AND uhc.user_id = :manager_id
                 INNER JOIN child_guardians AS cg
                    ON cg.child_id = ar.child_id AND cg.user_id = ar.parent_user_id
                   AND cg.consent_status = \'granted\'
                 WHERE ar.id = :request_id AND ar.status = \'pending\'
                 FOR UPDATE',
            );
            $request->execute(['manager_id' => $managerId, 'request_id' => $requestId]);
            $parentId = $request->fetchColumn();
            if ($parentId === false) {
                throw new InvalidArgumentException('Cette demande n’est pas accessible, a déjà été traitée ou le consentement est inactif.');
            }

            $update = $this->database->prepare(
                'UPDATE appointment_requests
                 SET status = :status, scheduled_at_utc = :scheduled_at,
                     response_message = :response_message
                 WHERE id = :request_id AND status = \'pending\'',
            );
            $update->execute([
                'status' => $status,
                'scheduled_at' => $scheduledUtc,
                'response_message' => $responseMessage === '' ? null : $responseMessage,
                'request_id' => $requestId,
            ]);
            if ($update->rowCount() !== 1) {
                throw new InvalidArgumentException('La demande a été modifiée entre-temps. Rechargez la page.');
            }

            $notification = $this->database->prepare(
                'INSERT INTO notifications (user_id, title, message, type)
                 VALUES (:parent_id, :title, :message, \'appointment_response\')',
            );
            $notification->execute([
                'parent_id' => (int) $parentId,
                'title' => $status === 'confirmed' ? 'Rendez-vous confirmé' : 'Réponse du centre de santé',
                'message' => $status === 'confirmed'
                    ? 'Le centre a confirmé votre rendez-vous. Consultez votre espace parent pour la date.'
                    : ($responseMessage !== '' ? $responseMessage : 'Le centre ne peut pas accepter cette demande. Contactez-le pour convenir d’une autre date.'),
            ]);
            $this->log($managerId, 'appointment_request_responded', 'Réponse apportée à une demande de rendez-vous (identifiant interne ' . $requestId . ').');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function updateReferral(array $manager, int $referralId, string $outcome, string $notes): void
    {
        $managerId = self::managerId($manager);
        if ($referralId < 1 || !in_array($outcome, ['attended', 'not_attended'], true)) {
            throw new InvalidArgumentException('Le résultat du référencement est invalide.');
        }
        $notes = trim($notes);
        self::optionalLength($notes, 500, 'Les notes de suivi');

        $this->database->beginTransaction();
        try {
            $referral = $this->database->prepare(
                'SELECT DISTINCT cg.user_id
                 FROM health_referrals AS hr
                 INNER JOIN user_health_centers AS uhc
                    ON uhc.health_center_id = hr.health_center_id AND uhc.user_id = :manager_id
                 INNER JOIN child_guardians AS cg
                    ON cg.child_id = hr.child_id AND cg.consent_status = \'granted\'
                 WHERE hr.id = :referral_id
                   AND hr.status IN (\'awaiting_follow_up\', \'not_attended\')
                 FOR UPDATE',
            );
            $referral->execute(['manager_id' => $managerId, 'referral_id' => $referralId]);
            $parentIds = $referral->fetchAll(PDO::FETCH_COLUMN);
            if ($parentIds === []) {
                throw new InvalidArgumentException('Ce référencement n’est pas accessible ou le consentement n’est pas actif.');
            }

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
            $notification = $this->database->prepare(
                'INSERT INTO notifications (user_id, title, message, type)
                 VALUES (:parent_id, \'Mise à jour de la prise en charge\',
                         \'Le centre a enregistré une mise à jour de l’orientation de votre enfant. Consultez votre espace parent.\',
                         \'referral_update\')',
            );
            foreach ($parentIds as $parentId) {
                $notification->execute(['parent_id' => (int) $parentId]);
            }
            $this->log($managerId, 'facility_referral_updated', 'Mise à jour du suivi d’un référencement (identifiant interne ' . $referralId . ').');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    private function log(int $managerId, string $action, string $description): void
    {
        $log = $this->database->prepare(
            'INSERT INTO activity_logs (user_id, action, description)
             VALUES (:user_id, :action, :description)',
        );
        $log->execute(['user_id' => $managerId, 'action' => $action, 'description' => $description]);
    }

    private static function managerId(array $manager): int
    {
        $userId = (int) ($manager['id'] ?? 0);
        if ($userId < 1 || ($manager['role_code'] ?? null) !== 'health_center_manager') {
            throw new InvalidArgumentException('Cette opération est réservée aux responsables de centre.');
        }

        return $userId;
    }

    private static function optionalLength(string $value, int $maximum, string $label): void
    {
        $length = preg_match_all('/./us', $value);
        if ($length === false || $length > $maximum) {
            throw new InvalidArgumentException($label . ' dépasse la longueur autorisée.');
        }
    }
}
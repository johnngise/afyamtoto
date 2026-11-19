<?php

declare(strict_types=1);

final class AppointmentRequestService
{
    private const REQUEST_TYPES = [
        'consultation',
        'vaccination_follow_up',
        'growth_follow_up',
        'nutrition_consultation',
    ];

    public function __construct(private readonly PDO $database)
    {
    }

    public function create(
        int $parentId,
        int $childId,
        int $healthCenterId,
        string $requestType,
        ?string $preferredDate,
    ): void {
        if ($parentId < 1 || $childId < 1 || $healthCenterId < 1) {
            throw new InvalidArgumentException('Sélectionnez un enfant et un centre valides.');
        }
        if (!in_array($requestType, self::REQUEST_TYPES, true)) {
            throw new InvalidArgumentException('Sélectionnez un motif de consultation valide.');
        }

        $preferredDate = $preferredDate === null ? '' : trim($preferredDate);
        if ($preferredDate !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $preferredDate, new DateTimeZone('UTC'));
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (
                !$date instanceof DateTimeImmutable
                || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                || $date->format('Y-m-d') !== $preferredDate
                || $preferredDate < (new DateTimeImmutable('today', new DateTimeZone('Africa/Lubumbashi')))->format('Y-m-d')
            ) {
                throw new InvalidArgumentException('La date souhaitée doit être aujourd’hui ou une date future valide.');
            }
        }

        $this->database->beginTransaction();
        try {
            $childQuery = $this->database->prepare(
                'SELECT 1 FROM child_guardians
                 WHERE child_id = :child_id AND user_id = :parent_id
                   AND consent_status = \'granted\'
                 FOR UPDATE',
            );
            $childQuery->execute(['child_id' => $childId, 'parent_id' => $parentId]);
            if ($childQuery->fetchColumn() === false) {
                throw new InvalidArgumentException('Ce profil est indisponible ou le partage de ses données n’est pas autorisé.');
            }

            $centerQuery = $this->database->prepare(
                'SELECT id FROM health_centers
                 WHERE id = :center_id AND name NOT LIKE \'DEMO UNIQUEMENT%\'
                 FOR UPDATE',
            );
            $centerQuery->execute(['center_id' => $healthCenterId]);
            if ($centerQuery->fetchColumn() === false) {
                throw new InvalidArgumentException('Ce centre de santé n’est pas disponible pour les demandes.');
            }

            $insert = $this->database->prepare(
                'INSERT INTO appointment_requests (
                    child_id, parent_user_id, health_center_id, request_type, preferred_date
                 ) VALUES (
                    :child_id, :parent_id, :center_id, :request_type, :preferred_date
                 )',
            );
            $insert->execute([
                'child_id' => $childId,
                'parent_id' => $parentId,
                'center_id' => $healthCenterId,
                'request_type' => $requestType,
                'preferred_date' => $preferredDate === '' ? null : $preferredDate,
            ]);

            $log = $this->database->prepare(
                'INSERT INTO activity_logs (user_id, action, description)
                 VALUES (:user_id, \'appointment_requested\', \'Demande de consultation envoyée.\')',
            );
            $log->execute(['user_id' => $parentId]);
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }
}
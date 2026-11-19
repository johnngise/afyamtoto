<?php

declare(strict_types=1);

final class ParentGrowthMeasurementService
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function record(
        int $parentId,
        int $childId,
        string $measuredOn,
        ?string $weightKg,
        ?string $lengthHeightCm,
        ?string $lengthHeightType,
    ): void {
        if ($parentId < 1 || $childId < 1) {
            throw new InvalidArgumentException('Sélectionnez un profil enfant valide.');
        }

        $measuredDate = DateTimeImmutable::createFromFormat('!Y-m-d', $measuredOn, new DateTimeZone('UTC'));
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (
            !$measuredDate instanceof DateTimeImmutable
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $measuredDate->format('Y-m-d') !== $measuredOn
            || $measuredOn > (new DateTimeImmutable('today', new DateTimeZone('Africa/Lubumbashi')))->format('Y-m-d')
        ) {
            throw new InvalidArgumentException('Saisissez une date de mesure valide qui n’est pas dans le futur.');
        }

        $weight = self::measurementValue($weightKg, 3, 'poids', 999.999);
        $height = self::measurementValue($lengthHeightCm, 2, 'taille ou longueur', 9999.99);
        if ($weight === null && $height === null) {
            throw new InvalidArgumentException('Saisissez au moins le poids ou la taille de l’enfant.');
        }
        if ($height !== null && !in_array($lengthHeightType, ['length', 'height'], true)) {
            throw new InvalidArgumentException('Indiquez si la mesure correspond à la taille ou à la longueur.');
        }

        $this->database->beginTransaction();
        try {
            $childQuery = $this->database->prepare(
                'SELECT c.date_of_birth
                 FROM children AS c
                 INNER JOIN child_guardians AS cg ON cg.child_id = c.id
                 WHERE c.id = :child_id AND cg.user_id = :user_id
                   AND cg.consent_status = \'granted\'
                 FOR UPDATE',
            );
            $childQuery->execute(['child_id' => $childId, 'user_id' => $parentId]);
            $dateOfBirth = $childQuery->fetchColumn();
            if (!is_string($dateOfBirth)) {
                throw new InvalidArgumentException('Ce profil est indisponible ou le partage de ses données n’est pas autorisé.');
            }
            if ($measuredOn < $dateOfBirth) {
                throw new InvalidArgumentException('La date de mesure ne peut pas précéder la naissance de l’enfant.');
            }

            $insert = $this->database->prepare(
                'INSERT INTO growth_measurements (
                    child_id, recorded_by_user_id, measured_at_utc, weight_kg,
                    length_height_cm, length_height_type
                 ) VALUES (
                    :child_id, :recorded_by_user_id, :measured_at_utc, :weight_kg,
                    :length_height_cm, :length_height_type
                 )',
            );
            $insert->execute([
                'child_id' => $childId,
                'recorded_by_user_id' => $parentId,
                'measured_at_utc' => $measuredOn . ' 12:00:00',
                'weight_kg' => $weight,
                'length_height_cm' => $height,
                'length_height_type' => $height === null ? null : $lengthHeightType,
            ]);

            $log = $this->database->prepare(
                'INSERT INTO activity_logs (user_id, action, description)
                 VALUES (:user_id, \'parent_growth_measurement_recorded\', :description)',
            );
            $log->execute([
                'user_id' => $parentId,
                'description' => 'Mesure déclarée par le parent pour le profil enfant ' . $childId . '.',
            ]);
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    private static function measurementValue(?string $value, int $scale, string $label, float $maximum): ?string
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
}
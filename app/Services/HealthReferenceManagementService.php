<?php

declare(strict_types=1);

final class HealthReferenceManagementService
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function vaccines(): array
    {
        return $this->database->query(
            'SELECT v.id AS vaccine_id, v.vaccine_code, v.display_name AS vaccine_name,
                    v.description, v.is_active, vd.id AS dose_id, vd.dose_code,
                    vd.display_name AS dose_name, vd.dose_order
             FROM vaccines AS v
             LEFT JOIN vaccine_doses AS vd ON vd.vaccine_id = v.id
             ORDER BY v.display_name, vd.dose_order',
        )->fetchAll();
    }

    public function createVaccine(
        int $actorId,
        string $code,
        string $name,
        string $description,
        array $doseCodes,
        array $doseNames,
    ): void {
        $code = trim($code);
        $name = trim($name);
        $description = trim($description);
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{1,63}$/D', $code)) {
            throw new InvalidArgumentException('Le code du vaccin doit contenir 2 à 64 caractères alphanumériques, tirets, points ou traits de soulignement.');
        }
        self::requiredLength($name, 160, 'Le nom du vaccin');
        self::optionalLength($description, 500, 'La description');
        if ($actorId < 1 || count($doseCodes) < 1 || count($doseCodes) > 20 || count($doseCodes) !== count($doseNames)) {
            throw new InvalidArgumentException('Ajoutez entre 1 et 20 doses valides au vaccin.');
        }

        $doses = [];
        foreach (array_values($doseCodes) as $index => $doseCode) {
            $doseCode = is_string($doseCode) ? trim($doseCode) : '';
            $doseName = is_string($doseNames[$index] ?? null) ? trim($doseNames[$index]) : '';
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/D', $doseCode)) {
                throw new InvalidArgumentException('Chaque dose doit avoir un code alphanumérique valide.');
            }
            self::requiredLength($doseName, 120, 'Le nom de la dose');
            $doses[] = ['code' => $doseCode, 'name' => $doseName, 'order' => $index + 1];
        }
        if (count(array_unique(array_column($doses, 'code'))) !== count($doses)) {
            throw new InvalidArgumentException('Les codes de dose doivent être uniques pour un même vaccin.');
        }

        $this->database->beginTransaction();
        try {
            $vaccine = $this->database->prepare(
                'INSERT INTO vaccines (vaccine_code, display_name, description, is_active)
                 VALUES (:code, :name, :description, 0)',
            );
            $vaccine->execute([
                'code' => $code,
                'name' => $name,
                'description' => $description === '' ? null : $description,
            ]);
            $vaccineId = (int) $this->database->lastInsertId();
            $insertDose = $this->database->prepare(
                'INSERT INTO vaccine_doses (vaccine_id, dose_code, display_name, dose_order)
                 VALUES (:vaccine_id, :code, :name, :dose_order)',
            );
            foreach ($doses as $dose) {
                $insertDose->execute([
                    'vaccine_id' => $vaccineId,
                    'code' => $dose['code'],
                    'name' => $dose['name'],
                    'dose_order' => $dose['order'],
                ]);
            }
            $this->log($actorId, 'vaccine_reference_created', 'Création d’un référentiel vaccinal en attente de validation clinique.');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    public function setVaccineActive(int $actorId, int $vaccineId, bool $isActive): void
    {
        if ($actorId < 1 || $vaccineId < 1) {
            throw new InvalidArgumentException('L’identifiant du référentiel est invalide.');
        }
        $this->database->beginTransaction();
        try {
            $update = $this->database->prepare(
                'UPDATE vaccines SET is_active = :is_active WHERE id = :vaccine_id',
            );
            $update->execute(['is_active' => $isActive ? 1 : 0, 'vaccine_id' => $vaccineId]);
            if ($update->rowCount() === 0) {
                $exists = $this->database->prepare('SELECT 1 FROM vaccines WHERE id = :vaccine_id');
                $exists->execute(['vaccine_id' => $vaccineId]);
                if ($exists->fetchColumn() === false) {
                    throw new InvalidArgumentException('Ce référentiel vaccinal n’existe pas.');
                }
            }
            $this->log($actorId, $isActive ? 'vaccine_reference_activated' : 'vaccine_reference_deactivated', 'Mise à jour de la disponibilité d’un référentiel vaccinal.');
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
    }

    private function log(int $actorId, string $action, string $description): void
    {
        $log = $this->database->prepare(
            'INSERT INTO activity_logs (user_id, action, description)
             VALUES (:user_id, :action, :description)',
        );
        $log->execute(['user_id' => $actorId, 'action' => $action, 'description' => $description]);
    }

    private static function requiredLength(string $value, int $maximum, string $label): void
    {
        self::optionalLength($value, $maximum, $label);
        if ($value === '') {
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
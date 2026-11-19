<?php

declare(strict_types=1);

final class HealthCenterManagementService
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function centers(): array
    {
        return $this->database->query(
            'SELECT hc.id, hc.name, hc.address_text, hc.city, hc.province, hc.phone_e164,
                    COUNT(DISTINCT uhc.user_id) AS assigned_users
             FROM health_centers AS hc
             LEFT JOIN user_health_centers AS uhc ON uhc.health_center_id = hc.id
             WHERE hc.name NOT LIKE \'DEMO UNIQUEMENT%\'
             GROUP BY hc.id, hc.name, hc.address_text, hc.city, hc.province, hc.phone_e164
             ORDER BY hc.name',
        )->fetchAll();
    }

    public function save(
        int $actorId,
        ?int $centerId,
        string $name,
        string $phone,
        string $address,
        string $city,
        string $province,
    ): void {
        $name = trim($name);
        $phone = trim($phone);
        $address = trim($address);
        $city = trim($city);
        $province = trim($province);

        self::requiredLength($name, 180, 'Le nom du centre');
        self::optionalLength($address, 500, 'L’adresse');
        self::optionalLength($city, 120, 'La ville');
        self::optionalLength($province, 120, 'La province');
        if ($phone !== '' && !preg_match('/^\+[1-9][0-9]{1,14}$/D', $phone)) {
            throw new InvalidArgumentException('Le téléphone doit être au format international, par exemple +243XXXXXXXXX.');
        }
        if ($actorId < 1 || ($centerId !== null && $centerId < 1)) {
            throw new InvalidArgumentException('L’identifiant du centre est invalide.');
        }

        $this->database->beginTransaction();
        try {
            if ($centerId === null) {
                $save = $this->database->prepare(
                    'INSERT INTO health_centers (name, phone_e164, address_text, city, province)
                     VALUES (:name, :phone, :address, :city, :province)',
                );
                $save->execute([
                    'name' => $name,
                    'phone' => $phone === '' ? null : $phone,
                    'address' => $address === '' ? null : $address,
                    'city' => $city === '' ? null : $city,
                    'province' => $province === '' ? null : $province,
                ]);
                $centerId = (int) $this->database->lastInsertId();
                $action = 'health_center_created';
            } else {
                $save = $this->database->prepare(
                    'UPDATE health_centers
                     SET name = :name, phone_e164 = :phone, address_text = :address,
                         city = :city, province = :province
                     WHERE id = :center_id AND name NOT LIKE \'DEMO UNIQUEMENT%\'',
                );
                $save->execute([
                    'name' => $name,
                    'phone' => $phone === '' ? null : $phone,
                    'address' => $address === '' ? null : $address,
                    'city' => $city === '' ? null : $city,
                    'province' => $province === '' ? null : $province,
                    'center_id' => $centerId,
                ]);
                if ($save->rowCount() === 0) {
                    $exists = $this->database->prepare(
                        'SELECT 1 FROM health_centers
                         WHERE id = :center_id AND name NOT LIKE \'DEMO UNIQUEMENT%\'',
                    );
                    $exists->execute(['center_id' => $centerId]);
                    if ($exists->fetchColumn() === false) {
                        throw new InvalidArgumentException('Ce centre est introuvable ou ne peut pas être modifié.');
                    }
                }
                $action = 'health_center_updated';
            }

            $log = $this->database->prepare(
                'INSERT INTO activity_logs (user_id, action, description)
                 VALUES (:user_id, :action, :description)',
            );
            $log->execute([
                'user_id' => $actorId,
                'action' => $action,
                'description' => 'Mise à jour des coordonnées d’un centre (identifiant interne ' . $centerId . ').',
            ]);
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $error;
        }
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
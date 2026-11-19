CREATE TABLE growth_measurements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id BIGINT UNSIGNED NOT NULL,
    recorded_by_user_id BIGINT UNSIGNED NOT NULL,
    measured_at_utc DATETIME NOT NULL,
    weight_kg DECIMAL(6, 3) NULL,
    length_height_cm DECIMAL(6, 2) NULL,
    length_height_type ENUM('length', 'height') NULL,
    muac_cm DECIMAL(6, 2) NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_growth_child_time (child_id, measured_at_utc),
    KEY idx_growth_recorded_by (recorded_by_user_id),
    CONSTRAINT chk_growth_has_measurement
        CHECK (
            weight_kg IS NOT NULL
            OR length_height_cm IS NOT NULL
            OR muac_cm IS NOT NULL
        ),
    CONSTRAINT chk_growth_positive_values
        CHECK (
            (weight_kg IS NULL OR weight_kg > 0)
            AND (length_height_cm IS NULL OR length_height_cm > 0)
            AND (muac_cm IS NULL OR muac_cm > 0)
        ),
    CONSTRAINT chk_growth_length_height_type
        CHECK (
            (length_height_cm IS NULL AND length_height_type IS NULL)
            OR (length_height_cm IS NOT NULL AND length_height_type IS NOT NULL)
        ),
    CONSTRAINT fk_growth_child
        FOREIGN KEY (child_id) REFERENCES children (id),
    CONSTRAINT fk_growth_recorded_by
        FOREIGN KEY (recorded_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vaccines (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vaccine_code VARCHAR(64) NOT NULL,
    display_name VARCHAR(160) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vaccines_code (vaccine_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vaccine_doses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vaccine_id BIGINT UNSIGNED NOT NULL,
    dose_code VARCHAR(64) NOT NULL,
    display_name VARCHAR(120) NOT NULL,
    dose_order SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vaccine_dose_code (vaccine_id, dose_code),
    UNIQUE KEY uq_vaccine_dose_order (vaccine_id, dose_order),
    CONSTRAINT fk_vaccine_doses_vaccine
        FOREIGN KEY (vaccine_id) REFERENCES vaccines (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vaccination_schedules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    schedule_code VARCHAR(64) NOT NULL,
    display_name VARCHAR(160) NOT NULL,
    jurisdiction_code CHAR(2) NOT NULL,
    program_name VARCHAR(160) NOT NULL,
    version_label VARCHAR(64) NOT NULL,
    effective_from DATE NULL,
    effective_until DATE NULL,
    status ENUM('draft', 'approved', 'retired') NOT NULL DEFAULT 'draft',
    approved_by_user_id BIGINT UNSIGNED NULL,
    approved_at_utc DATETIME NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_schedule_version (schedule_code, version_label),
    KEY idx_schedule_jurisdiction_status (jurisdiction_code, status),
    CONSTRAINT chk_schedule_approval
        CHECK (
            status <> 'approved'
            OR (approved_by_user_id IS NOT NULL AND approved_at_utc IS NOT NULL)
        ),
    CONSTRAINT chk_schedule_effective_range
        CHECK (
            effective_until IS NULL
            OR effective_from IS NULL
            OR effective_until >= effective_from
        ),
    CONSTRAINT fk_schedule_approved_by
        FOREIGN KEY (approved_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vaccination_schedule_doses (
    schedule_id BIGINT UNSIGNED NOT NULL,
    vaccine_dose_id BIGINT UNSIGNED NOT NULL,
    earliest_age_days INT UNSIGNED NULL,
    recommended_age_days INT UNSIGNED NOT NULL,
    latest_age_days INT UNSIGNED NULL,
    interval_reference_dose_id BIGINT UNSIGNED NULL,
    minimum_interval_days SMALLINT UNSIGNED NULL,
    PRIMARY KEY (schedule_id, vaccine_dose_id),
    CONSTRAINT chk_schedule_dose_age_range
        CHECK (
            (earliest_age_days IS NULL
                OR earliest_age_days <= recommended_age_days)
            AND (latest_age_days IS NULL
                OR latest_age_days >= recommended_age_days)
        ),
    CONSTRAINT chk_schedule_dose_interval
        CHECK (
            (interval_reference_dose_id IS NULL
                AND minimum_interval_days IS NULL)
            OR (interval_reference_dose_id IS NOT NULL
                AND minimum_interval_days IS NOT NULL
                AND interval_reference_dose_id <> vaccine_dose_id)
        ),
    CONSTRAINT fk_schedule_doses_schedule
        FOREIGN KEY (schedule_id) REFERENCES vaccination_schedules (id),
    CONSTRAINT fk_schedule_doses_dose
        FOREIGN KEY (vaccine_dose_id) REFERENCES vaccine_doses (id),
    CONSTRAINT fk_schedule_dose_interval_reference
        FOREIGN KEY (schedule_id, interval_reference_dose_id)
        REFERENCES vaccination_schedule_doses (schedule_id, vaccine_dose_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vaccination_records (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id BIGINT UNSIGNED NOT NULL,
    vaccine_dose_id BIGINT UNSIGNED NOT NULL,
    administered_at_utc DATETIME NOT NULL,
    recorded_by_user_id BIGINT UNSIGNED NOT NULL,
    health_center_id BIGINT UNSIGNED NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_vaccination_child_time (child_id, administered_at_utc),
    KEY idx_vaccination_dose (vaccine_dose_id),
    KEY idx_vaccination_recorded_by (recorded_by_user_id),
    CONSTRAINT fk_vaccination_child
        FOREIGN KEY (child_id) REFERENCES children (id),
    CONSTRAINT fk_vaccination_dose
        FOREIGN KEY (vaccine_dose_id) REFERENCES vaccine_doses (id),
    CONSTRAINT fk_vaccination_recorded_by
        FOREIGN KEY (recorded_by_user_id) REFERENCES users (id),
    CONSTRAINT fk_vaccination_health_center
        FOREIGN KEY (health_center_id) REFERENCES health_centers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

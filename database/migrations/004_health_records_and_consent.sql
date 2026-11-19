ALTER TABLE children
    ADD COLUMN place_of_birth VARCHAR(160) NULL AFTER sex,
    ADD COLUMN address_text VARCHAR(500) NULL AFTER place_of_birth,
    ADD COLUMN photo_path VARCHAR(255) NULL AFTER address_text;

ALTER TABLE child_guardians
    ADD COLUMN consent_status ENUM('pending', 'granted', 'withdrawn')
        NOT NULL DEFAULT 'pending' AFTER is_primary,
    ADD COLUMN consent_at_utc DATETIME NULL AFTER consent_status,
    ADD COLUMN consent_withdrawn_at_utc DATETIME NULL AFTER consent_at_utc;

ALTER TABLE health_centers
    ADD COLUMN province VARCHAR(120) NULL AFTER address_text,
    ADD COLUMN city VARCHAR(120) NULL AFTER province;

ALTER TABLE growth_measurements
    ADD COLUMN notes VARCHAR(500) NULL AFTER muac_cm;

ALTER TABLE vaccines
    ADD COLUMN description VARCHAR(500) NULL AFTER display_name;

ALTER TABLE vaccination_records
    ADD COLUMN next_dose_date DATE NULL AFTER administered_at_utc,
    ADD COLUMN status ENUM('administered', 'missed', 'voided')
        NOT NULL DEFAULT 'administered' AFTER next_dose_date,
    ADD COLUMN notes VARCHAR(500) NULL AFTER status;

CREATE TABLE medical_records (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id BIGINT UNSIGNED NOT NULL,
    blood_group VARCHAR(8) NULL,
    allergies TEXT NULL,
    medical_history TEXT NULL,
    notes TEXT NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_medical_records_child (child_id),
    CONSTRAINT fk_medical_records_child
        FOREIGN KEY (child_id) REFERENCES children (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nutrition_records (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id BIGINT UNSIGNED NOT NULL,
    record_date DATE NOT NULL,
    nutritional_status VARCHAR(64) NULL,
    feeding_type VARCHAR(64) NULL,
    observations TEXT NULL,
    recommendations TEXT NULL,
    health_worker_id BIGINT UNSIGNED NOT NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_nutrition_child_date (child_id, record_date),
    KEY idx_nutrition_health_worker (health_worker_id),
    CONSTRAINT fk_nutrition_child
        FOREIGN KEY (child_id) REFERENCES children (id),
    CONSTRAINT fk_nutrition_health_worker
        FOREIGN KEY (health_worker_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE health_alerts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id BIGINT UNSIGNED NOT NULL,
    alert_type VARCHAR(64) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    description VARCHAR(1000) NOT NULL,
    status ENUM('pending_review', 'under_review', 'referred', 'resolved', 'dismissed')
        NOT NULL DEFAULT 'pending_review',
    created_by_user_id BIGINT UNSIGNED NULL,
    reviewed_by_user_id BIGINT UNSIGNED NULL,
    health_center_id BIGINT UNSIGNED NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at_utc DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_health_alerts_child_status (child_id, status, created_at_utc),
    KEY idx_health_alerts_severity_status (severity, status, created_at_utc),
    KEY idx_health_alerts_reviewer (reviewed_by_user_id),
    CONSTRAINT chk_health_alert_resolution
        CHECK (
            (status = 'resolved' AND resolved_at_utc IS NOT NULL)
            OR (status <> 'resolved' AND resolved_at_utc IS NULL)
        ),
    CONSTRAINT fk_health_alerts_child
        FOREIGN KEY (child_id) REFERENCES children (id),
    CONSTRAINT fk_health_alerts_creator
        FOREIGN KEY (created_by_user_id) REFERENCES users (id),
    CONSTRAINT fk_health_alerts_reviewer
        FOREIGN KEY (reviewed_by_user_id) REFERENCES users (id),
    CONSTRAINT fk_health_alerts_center
        FOREIGN KEY (health_center_id) REFERENCES health_centers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    type VARCHAR(64) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user_unread (user_id, is_read, created_at_utc),
    CONSTRAINT chk_notifications_is_read CHECK (is_read IN (0, 1)),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    description VARCHAR(500) NULL,
    ip_address VARBINARY(16) NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activity_logs_user_time (user_id, created_at_utc),
    KEY idx_activity_logs_action_time (action, created_at_utc),
    CONSTRAINT fk_activity_logs_user
        FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

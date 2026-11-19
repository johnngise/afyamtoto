INSERT INTO roles (role_code, display_name) VALUES
    ('health_center_manager', 'Responsable d’établissement de santé'),
    ('health_supervisor', 'Superviseur sanitaire')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

ALTER TABLE health_alerts
    ADD COLUMN reviewed_at_utc DATETIME NULL AFTER reviewed_by_user_id,
    ADD COLUMN review_notes VARCHAR(1000) NULL AFTER reviewed_at_utc;

CREATE TABLE professional_observations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id BIGINT UNSIGNED NOT NULL,
    health_center_id BIGINT UNSIGNED NOT NULL,
    health_professional_id BIGINT UNSIGNED NOT NULL,
    health_alert_id BIGINT UNSIGNED NULL,
    health_referral_id BIGINT UNSIGNED NULL,
    observation_type ENUM(
        'growth_review',
        'nutrition_review',
        'clinical_observation',
        'care_plan'
    ) NOT NULL,
    content TEXT NOT NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_professional_observations_child_date (child_id, created_at_utc),
    KEY idx_professional_observations_center_date (health_center_id, created_at_utc),
    KEY idx_professional_observations_author (health_professional_id, created_at_utc),
    CONSTRAINT fk_professional_observations_child
        FOREIGN KEY (child_id) REFERENCES children (id),
    CONSTRAINT fk_professional_observations_center
        FOREIGN KEY (health_center_id) REFERENCES health_centers (id),
    CONSTRAINT fk_professional_observations_author
        FOREIGN KEY (health_professional_id) REFERENCES users (id),
    CONSTRAINT fk_professional_observations_alert
        FOREIGN KEY (health_alert_id) REFERENCES health_alerts (id),
    CONSTRAINT fk_professional_observations_referral
        FOREIGN KEY (health_referral_id) REFERENCES health_referrals (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS child_health_platform
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE child_health_platform;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE roles (
    role_code VARCHAR(64) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    permission_code VARCHAR(128) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (permission_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_code VARCHAR(64) NOT NULL,
    permission_code VARCHAR(128) NOT NULL,
    PRIMARY KEY (role_code, permission_code),
    CONSTRAINT fk_role_permissions_role
        FOREIGN KEY (role_code) REFERENCES roles (role_code),
    CONSTRAINT fk_role_permissions_permission
        FOREIGN KEY (permission_code) REFERENCES permissions (permission_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    display_name VARCHAR(160) NOT NULL,
    email VARCHAR(254) NULL,
    phone_e164 VARCHAR(16) NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_phone_e164 (phone_e164),
    CONSTRAINT chk_users_login_identifier
        CHECK (email IS NOT NULL OR phone_e164 IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_code VARCHAR(64) NOT NULL,
    assigned_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_code),
    KEY idx_user_roles_role (role_code),
    CONSTRAINT fk_user_roles_user
        FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_user_roles_role
        FOREIGN KEY (role_code) REFERENCES roles (role_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE children (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    sex ENUM('female', 'male', 'unknown') NOT NULL,
    place_of_birth VARCHAR(160) NULL,
    address_text VARCHAR(500) NULL,
    photo_path VARCHAR(255) NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_children_date_of_birth (date_of_birth),
    KEY idx_children_created_by (created_by_user_id),
    CONSTRAINT fk_children_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE child_guardians (
    child_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    relationship_type VARCHAR(32) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    consent_status ENUM('pending', 'granted', 'withdrawn')
        NOT NULL DEFAULT 'pending',
    consent_at_utc DATETIME NULL,
    consent_withdrawn_at_utc DATETIME NULL,
    linked_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (child_id, user_id),
    KEY idx_child_guardians_user (user_id),
    CONSTRAINT fk_child_guardians_child
        FOREIGN KEY (child_id) REFERENCES children (id),
    CONSTRAINT fk_child_guardians_user
        FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE health_centers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(180) NOT NULL,
    phone_e164 VARCHAR(16) NULL,
    address_text VARCHAR(500) NULL,
    province VARCHAR(120) NULL,
    city VARCHAR(120) NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_health_centers_name (name),
    CONSTRAINT chk_health_center_coordinates_pair
        CHECK ((latitude IS NULL AND longitude IS NULL)
            OR (latitude IS NOT NULL AND longitude IS NOT NULL)),
    CONSTRAINT chk_health_center_coordinate_ranges
        CHECK (
            (latitude IS NULL OR latitude BETWEEN -90 AND 90)
            AND (longitude IS NULL OR longitude BETWEEN -180 AND 180)
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE health_center_services (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    health_center_id BIGINT UNSIGNED NOT NULL,
    service_name VARCHAR(120) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_center_service (health_center_id, service_name),
    CONSTRAINT fk_center_services_center
        FOREIGN KEY (health_center_id) REFERENCES health_centers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_health_centers (
    user_id BIGINT UNSIGNED NOT NULL,
    health_center_id BIGINT UNSIGNED NOT NULL,
    assigned_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, health_center_id),
    KEY idx_user_health_centers_center (health_center_id),
    CONSTRAINT fk_user_health_centers_user
        FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_user_health_centers_center
        FOREIGN KEY (health_center_id) REFERENCES health_centers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointment_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id BIGINT UNSIGNED NOT NULL,
    parent_user_id BIGINT UNSIGNED NOT NULL,
    health_center_id BIGINT UNSIGNED NOT NULL,
    request_type ENUM(
        'consultation',
        'vaccination_follow_up',
        'growth_follow_up',
        'nutrition_consultation'
    ) NOT NULL DEFAULT 'consultation',
    preferred_date DATE NULL,
    status ENUM('pending', 'confirmed', 'declined', 'cancelled') NOT NULL DEFAULT 'pending',
    scheduled_at_utc DATETIME NULL,
    response_message VARCHAR(500) NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_appointment_requests_parent (parent_user_id, status, created_at_utc),
    KEY idx_appointment_requests_center (health_center_id, status, created_at_utc),
    KEY idx_appointment_requests_child (child_id, created_at_utc),
    CONSTRAINT fk_appointment_requests_child
        FOREIGN KEY (child_id) REFERENCES children (id),
    CONSTRAINT fk_appointment_requests_parent
        FOREIGN KEY (parent_user_id) REFERENCES users (id),
    CONSTRAINT fk_appointment_requests_center
        FOREIGN KEY (health_center_id) REFERENCES health_centers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE growth_measurements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id BIGINT UNSIGNED NOT NULL,
    recorded_by_user_id BIGINT UNSIGNED NOT NULL,
    measured_at_utc DATETIME NOT NULL,
    weight_kg DECIMAL(6, 3) NULL,
    length_height_cm DECIMAL(6, 2) NULL,
    length_height_type ENUM('length', 'height') NULL,
    muac_cm DECIMAL(6, 2) NULL,
    notes VARCHAR(500) NULL,
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
    description VARCHAR(500) NULL,
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
    next_dose_date DATE NULL,
    status ENUM('administered', 'missed', 'voided')
        NOT NULL DEFAULT 'administered',
    notes VARCHAR(500) NULL,
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

CREATE TABLE audit_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id BIGINT UNSIGNED NULL,
    event_code VARCHAR(100) NOT NULL,
    subject_type VARCHAR(64) NOT NULL,
    subject_id BIGINT UNSIGNED NULL,
    request_id CHAR(36) NULL,
    occurred_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_actor_time (actor_user_id, occurred_at_utc),
    KEY idx_audit_subject_time (subject_type, subject_id, occurred_at_utc),
    KEY idx_audit_request_id (request_id),
    CONSTRAINT fk_audit_actor
        FOREIGN KEY (actor_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    reviewed_at_utc DATETIME NULL,
    review_notes VARCHAR(1000) NULL,
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

CREATE TABLE community_visits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id BIGINT UNSIGNED NOT NULL,
    agent_user_id BIGINT UNSIGNED NOT NULL,
    visit_type ENUM('home', 'community_outreach', 'follow_up') NOT NULL,
    visited_at_utc DATETIME NOT NULL,
    purpose VARCHAR(160) NOT NULL,
    observations TEXT NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_community_visits_agent_date (agent_user_id, visited_at_utc),
    KEY idx_community_visits_child_date (child_id, visited_at_utc),
    CONSTRAINT fk_community_visits_child
        FOREIGN KEY (child_id) REFERENCES children (id),
    CONSTRAINT fk_community_visits_agent
        FOREIGN KEY (agent_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE health_referrals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id BIGINT UNSIGNED NOT NULL,
    agent_user_id BIGINT UNSIGNED NOT NULL,
    health_center_id BIGINT UNSIGNED NOT NULL,
    community_visit_id BIGINT UNSIGNED NULL,
    concern_description VARCHAR(1000) NOT NULL,
    priority ENUM('routine', 'prompt', 'emergency') NOT NULL DEFAULT 'routine',
    status ENUM('awaiting_follow_up', 'attended', 'not_attended', 'cancelled')
        NOT NULL DEFAULT 'awaiting_follow_up',
    referred_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    followed_up_at_utc DATETIME NULL,
    follow_up_notes VARCHAR(500) NULL,
    PRIMARY KEY (id),
    KEY idx_health_referrals_agent_status (agent_user_id, status, referred_at_utc),
    KEY idx_health_referrals_center_status (health_center_id, status, referred_at_utc),
    KEY idx_health_referrals_child (child_id, referred_at_utc),
    CONSTRAINT fk_health_referrals_child
        FOREIGN KEY (child_id) REFERENCES children (id),
    CONSTRAINT fk_health_referrals_agent
        FOREIGN KEY (agent_user_id) REFERENCES users (id),
    CONSTRAINT fk_health_referrals_center
        FOREIGN KEY (health_center_id) REFERENCES health_centers (id),
    CONSTRAINT fk_health_referrals_visit
        FOREIGN KEY (community_visit_id) REFERENCES community_visits (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE auth_login_attempts (
    attempt_key CHAR(64) NOT NULL,
    attempt_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until_utc DATETIME NULL,
    updated_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (attempt_key),
    KEY idx_auth_login_attempts_lock (locked_until_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at_utc DATETIME NOT NULL,
    used_at_utc DATETIME NULL,
    created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_reset_token_hash (token_hash),
    KEY idx_password_reset_user_expiry (user_id, expires_at_utc),
    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (role_code, display_name) VALUES
    ('administrator', 'Administrateur'),
    ('parent', 'Parent ou tuteur'),
    ('community_health_worker', 'Agent de santé communautaire'),
    ('health_professional', 'Professionnel de santé'),
    ('health_center_manager', 'Responsable d’établissement de santé'),
    ('health_supervisor', 'Superviseur sanitaire');

INSERT INTO health_centers (name, address_text, province, city)
VALUES (
    'DEMO UNIQUEMENT — structure fictive',
    'Adresse fictive — Goma',
    'Nord-Kivu (simulation)',
    'Goma (simulation)'
);

INSERT INTO health_center_services (health_center_id, service_name)
SELECT id, 'Service fictif — démonstration'
FROM health_centers
WHERE name = 'DEMO UNIQUEMENT — structure fictive';

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
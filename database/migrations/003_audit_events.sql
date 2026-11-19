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

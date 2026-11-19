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
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

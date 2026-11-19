INSERT INTO roles (role_code, display_name) VALUES
    ('administrator', 'Administrateur'),
    ('parent', 'Parent ou tuteur'),
    ('community_health_worker', 'Agent de santé communautaire'),
    ('health_professional', 'Professionnel de santé'),
    ('health_center_manager', 'Responsable d’établissement de santé'),
    ('health_supervisor', 'Superviseur sanitaire')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

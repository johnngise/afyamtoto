-- Additive sync for permissions introduced in the current role catalog.
INSERT INTO permissions (code, description)
VALUES
  ('FACILITY_CREATE', 'Créer une structure de santé.'),
  ('REFERRAL_FOLLOW_UP', 'Documenter le suivi d’une orientation autorisée.')
ON CONFLICT (code) DO UPDATE SET description = EXCLUDED.description;

INSERT INTO role_permissions (role_code, permission_code)
VALUES
  ('provincial_administrator', 'FACILITY_CREATE'),
  ('health_zone_administrator', 'FACILITY_CREATE'),
  ('community_health_worker', 'REFERRAL_FOLLOW_UP'),
  ('health_facility_administrator', 'REFERRAL_FOLLOW_UP'),
  ('health_center_manager', 'REFERRAL_FOLLOW_UP')
ON CONFLICT (role_code, permission_code) DO NOTHING;

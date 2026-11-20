-- AlterTable
ALTER TABLE "health_centers" ADD COLUMN     "health_zone_id" BIGINT,
ADD COLUMN     "province_id" BIGINT;

-- CreateTable
CREATE TABLE "provinces" (
    "id" BIGSERIAL NOT NULL,
    "code" VARCHAR(64) NOT NULL,
    "name" VARCHAR(120) NOT NULL,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "provinces_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "health_zones" (
    "id" BIGSERIAL NOT NULL,
    "code" VARCHAR(64) NOT NULL,
    "name" VARCHAR(120) NOT NULL,
    "province_id" BIGINT NOT NULL,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "health_zones_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "user_assignments" (
    "id" BIGSERIAL NOT NULL,
    "user_id" BIGINT NOT NULL,
    "role_code" VARCHAR(64) NOT NULL,
    "scope_type" VARCHAR(24) NOT NULL,
    "province_id" BIGINT,
    "health_zone_id" BIGINT,
    "health_center_id" BIGINT,
    "assigned_by_user_id" BIGINT,
    "assigned_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "expires_at" TIMESTAMPTZ(6),

    CONSTRAINT "user_assignments_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "professional_profiles" (
    "user_id" BIGINT NOT NULL,
    "specialty_code" VARCHAR(64) NOT NULL,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ(6) NOT NULL,

    CONSTRAINT "professional_profiles_pkey" PRIMARY KEY ("user_id")
);

-- CreateTable
CREATE TABLE "consent_records" (
    "id" BIGSERIAL NOT NULL,
    "child_id" BIGINT NOT NULL,
    "user_id" BIGINT NOT NULL,
    "action" VARCHAR(24) NOT NULL,
    "captured_at" TIMESTAMPTZ(6) NOT NULL,
    "recorded_by_user_id" BIGINT,
    "method" VARCHAR(40),
    "evidence_ref" VARCHAR(255),
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "consent_records_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "access_requests" (
    "id" BIGSERIAL NOT NULL,
    "requester_user_id" BIGINT NOT NULL,
    "child_id" BIGINT NOT NULL,
    "permission_code" VARCHAR(128) NOT NULL,
    "reason" VARCHAR(1000) NOT NULL,
    "status" VARCHAR(16) NOT NULL DEFAULT 'pending',
    "reviewer_user_id" BIGINT,
    "decision_reason" VARCHAR(1000),
    "requested_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "reviewed_at" TIMESTAMPTZ(6),

    CONSTRAINT "access_requests_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "provinces_code_key" ON "provinces"("code");

-- CreateIndex
CREATE UNIQUE INDEX "health_zones_code_key" ON "health_zones"("code");

-- CreateIndex
CREATE INDEX "health_zones_province_id_name_idx" ON "health_zones"("province_id", "name");

-- CreateIndex
CREATE INDEX "user_assignments_user_id_role_code_expires_at_idx" ON "user_assignments"("user_id", "role_code", "expires_at");

-- CreateIndex
CREATE INDEX "user_assignments_province_id_role_code_idx" ON "user_assignments"("province_id", "role_code");

-- CreateIndex
CREATE INDEX "user_assignments_health_zone_id_role_code_idx" ON "user_assignments"("health_zone_id", "role_code");

-- CreateIndex
CREATE INDEX "user_assignments_health_center_id_role_code_idx" ON "user_assignments"("health_center_id", "role_code");

-- CreateIndex
CREATE INDEX "consent_records_child_id_user_id_captured_at_idx" ON "consent_records"("child_id", "user_id", "captured_at");

-- CreateIndex
CREATE INDEX "consent_records_recorded_by_user_id_captured_at_idx" ON "consent_records"("recorded_by_user_id", "captured_at");

-- CreateIndex
CREATE INDEX "access_requests_requester_user_id_status_requested_at_idx" ON "access_requests"("requester_user_id", "status", "requested_at");

-- CreateIndex
CREATE INDEX "access_requests_child_id_status_requested_at_idx" ON "access_requests"("child_id", "status", "requested_at");

-- CreateIndex
CREATE INDEX "access_requests_reviewer_user_id_reviewed_at_idx" ON "access_requests"("reviewer_user_id", "reviewed_at");

-- AddForeignKey
ALTER TABLE "health_centers" ADD CONSTRAINT "health_centers_province_id_fkey" FOREIGN KEY ("province_id") REFERENCES "provinces"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_centers" ADD CONSTRAINT "health_centers_health_zone_id_fkey" FOREIGN KEY ("health_zone_id") REFERENCES "health_zones"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_zones" ADD CONSTRAINT "health_zones_province_id_fkey" FOREIGN KEY ("province_id") REFERENCES "provinces"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_assignments" ADD CONSTRAINT "user_assignments_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_assignments" ADD CONSTRAINT "user_assignments_role_code_fkey" FOREIGN KEY ("role_code") REFERENCES "roles"("code") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_assignments" ADD CONSTRAINT "user_assignments_province_id_fkey" FOREIGN KEY ("province_id") REFERENCES "provinces"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_assignments" ADD CONSTRAINT "user_assignments_health_zone_id_fkey" FOREIGN KEY ("health_zone_id") REFERENCES "health_zones"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_assignments" ADD CONSTRAINT "user_assignments_health_center_id_fkey" FOREIGN KEY ("health_center_id") REFERENCES "health_centers"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_assignments" ADD CONSTRAINT "user_assignments_assigned_by_user_id_fkey" FOREIGN KEY ("assigned_by_user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "professional_profiles" ADD CONSTRAINT "professional_profiles_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "consent_records" ADD CONSTRAINT "consent_records_child_id_user_id_fkey" FOREIGN KEY ("child_id", "user_id") REFERENCES "child_guardians"("child_id", "user_id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "consent_records" ADD CONSTRAINT "consent_records_recorded_by_user_id_fkey" FOREIGN KEY ("recorded_by_user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "access_requests" ADD CONSTRAINT "access_requests_requester_user_id_fkey" FOREIGN KEY ("requester_user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "access_requests" ADD CONSTRAINT "access_requests_reviewer_user_id_fkey" FOREIGN KEY ("reviewer_user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "access_requests" ADD CONSTRAINT "access_requests_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "access_requests" ADD CONSTRAINT "access_requests_permission_code_fkey" FOREIGN KEY ("permission_code") REFERENCES "permissions"("code") ON DELETE RESTRICT ON UPDATE CASCADE;



-- Seed role and permission catalog. Unlisted permissions are denied by default.
INSERT INTO "roles" ("code", "display_name") VALUES
  ('super_administrator', 'Super Administrator'),
  ('administrator', 'Administrator'),
  ('national_administrator', 'National Administrator'),
  ('provincial_administrator', 'Provincial Administrator'),
  ('health_zone_administrator', 'Health Zone Administrator'),
  ('health_facility_administrator', 'Health Facility Administrator'),
  ('health_professional', 'Health Professional'),
  ('community_health_worker', 'Community Health Worker'),
  ('parent', 'Parent'),
  ('health_data_analyst', 'Health Data Analyst'),
  ('health_center_manager', 'Health Center Manager'),
  ('health_supervisor', 'Health Supervisor')
ON CONFLICT ("code") DO NOTHING;

INSERT INTO "permissions" ("code", "description") VALUES
  ('ADMIN_DASHBOARD_VIEW', 'Consulter le tableau de bord administratif autorisé.'),
  ('USER_VIEW', 'Consulter les comptes dans le périmètre autorisé.'),
  ('USER_CREATE', 'Créer un compte dans le périmètre autorisé.'),
  ('USER_UPDATE', 'Modifier un compte dans le périmètre autorisé.'),
  ('USER_SUSPEND', 'Suspendre ou réactiver un compte dans le périmètre autorisé.'),
  ('ROLE_MANAGE', 'Attribuer les rôles autorisés.'),
  ('PERMISSION_MANAGE', 'Configurer les permissions de la plateforme.'),
  ('FACILITY_VIEW', 'Consulter les structures de santé autorisées.'),
  ('FACILITY_CREATE', 'Créer une structure de santé.'),
  ('FACILITY_UPDATE', 'Modifier une structure de santé autorisée.'),
  ('FACILITY_ASSIGN', 'Gérer les affectations de structures.'),
  ('CONTENT_MANAGE', 'Gérer les références et contenus de la plateforme.'),
  ('PLATFORM_SETTINGS_MANAGE', 'Modifier les paramètres de la plateforme.'),
  ('AUDIT_VIEW', 'Consulter les événements d''audit autorisés.'),
  ('REPORT_VIEW', 'Consulter les rapports autorisés.'),
  ('REPORT_EXPORT', 'Exporter les rapports explicitement autorisés.'),
  ('AGGREGATE_REPORT_VIEW', 'Consulter des statistiques agrégées dans le périmètre autorisé.'),
  ('ACCESS_REQUEST_REVIEW', 'Examiner les demandes exceptionnelles d''accès.'),
  ('CHILD_VIEW', 'Consulter les informations d''un enfant autorisé.'),
  ('CHILD_CREATE', 'Créer un dossier enfant selon le profil et le périmètre.'),
  ('CHILD_UPDATE', 'Modifier les informations autorisées d''un enfant.'),
  ('CHILD_ARCHIVE', 'Archiver un dossier enfant selon la politique de conservation.'),
  ('GROWTH_VIEW', 'Consulter les mesures de croissance autorisées.'),
  ('GROWTH_CREATE', 'Enregistrer une mesure de croissance autorisée.'),
  ('GROWTH_UPDATE', 'Corriger une mesure avec traçabilité.'),
  ('GROWTH_VALIDATE', 'Valider une mesure selon la spécialité et les règles cliniques.'),
  ('VACCINATION_VIEW', 'Consulter les vaccinations autorisées.'),
  ('VACCINATION_CREATE', 'Enregistrer une vaccination selon les compétences autorisées.'),
  ('VACCINATION_UPDATE', 'Corriger une vaccination avec traçabilité.'),
  ('VACCINATION_VALIDATE', 'Valider une vaccination selon les compétences autorisées.'),
  ('MEDICAL_RECORD_VIEW', 'Consulter un dossier médical autorisé.'),
  ('MEDICAL_RECORD_CREATE', 'Créer une entrée médicale autorisée.'),
  ('MEDICAL_RECORD_UPDATE', 'Corriger une entrée médicale avec traçabilité.'),
  ('HEALTH_ALERT_VIEW', 'Consulter les alertes sanitaires autorisées.'),
  ('HEALTH_ALERT_CREATE', 'Signaler une alerte sanitaire.'),
  ('HEALTH_ALERT_RESOLVE', 'Examiner et résoudre une alerte selon les compétences autorisées.'),
  ('APPOINTMENT_VIEW', 'Consulter les rendez-vous autorisés.'),
  ('APPOINTMENT_CREATE', 'Demander un rendez-vous pour un enfant autorisé.'),
  ('APPOINTMENT_MANAGE', 'Traiter les rendez-vous de structures autorisées.'),
  ('COMMUNITY_VISIT_CREATE', 'Enregistrer une visite communautaire autorisée.'),
  ('REFERRAL_VIEW', 'Consulter les orientations autorisées.'),
  ('REFERRAL_CREATE', 'Créer une orientation vers une structure autorisée.'),
  ('REFERRAL_FOLLOW_UP', 'Documenter le suivi d''une orientation autorisée.'),
  ('NOTIFICATION_VIEW', 'Consulter ses propres notifications.'),
  ('NOTIFICATION_UPDATE', 'Modifier l''état de ses propres notifications.')
ON CONFLICT ("code") DO NOTHING;

INSERT INTO "role_permissions" ("role_code", "permission_code") VALUES
  ('super_administrator', 'ADMIN_DASHBOARD_VIEW'),
  ('super_administrator', 'USER_VIEW'),
  ('super_administrator', 'USER_CREATE'),
  ('super_administrator', 'USER_UPDATE'),
  ('super_administrator', 'USER_SUSPEND'),
  ('super_administrator', 'ROLE_MANAGE'),
  ('super_administrator', 'PERMISSION_MANAGE'),
  ('super_administrator', 'FACILITY_VIEW'),
  ('super_administrator', 'FACILITY_CREATE'),
  ('super_administrator', 'FACILITY_UPDATE'),
  ('super_administrator', 'FACILITY_ASSIGN'),
  ('super_administrator', 'CONTENT_MANAGE'),
  ('super_administrator', 'PLATFORM_SETTINGS_MANAGE'),
  ('super_administrator', 'AUDIT_VIEW'),
  ('super_administrator', 'REPORT_VIEW'),
  ('super_administrator', 'REPORT_EXPORT'),
  ('super_administrator', 'AGGREGATE_REPORT_VIEW'),
  ('super_administrator', 'ACCESS_REQUEST_REVIEW'),
  ('administrator', 'ADMIN_DASHBOARD_VIEW'),
  ('administrator', 'USER_VIEW'),
  ('administrator', 'USER_CREATE'),
  ('administrator', 'USER_UPDATE'),
  ('administrator', 'USER_SUSPEND'),
  ('administrator', 'FACILITY_VIEW'),
  ('administrator', 'FACILITY_CREATE'),
  ('administrator', 'FACILITY_UPDATE'),
  ('administrator', 'FACILITY_ASSIGN'),
  ('administrator', 'CONTENT_MANAGE'),
  ('administrator', 'PLATFORM_SETTINGS_MANAGE'),
  ('administrator', 'AUDIT_VIEW'),
  ('administrator', 'REPORT_VIEW'),
  ('administrator', 'REPORT_EXPORT'),
  ('administrator', 'AGGREGATE_REPORT_VIEW'),
  ('national_administrator', 'ADMIN_DASHBOARD_VIEW'),
  ('national_administrator', 'USER_VIEW'),
  ('national_administrator', 'USER_CREATE'),
  ('national_administrator', 'USER_UPDATE'),
  ('national_administrator', 'USER_SUSPEND'),
  ('national_administrator', 'FACILITY_VIEW'),
  ('national_administrator', 'FACILITY_CREATE'),
  ('national_administrator', 'FACILITY_UPDATE'),
  ('national_administrator', 'FACILITY_ASSIGN'),
  ('national_administrator', 'REPORT_VIEW'),
  ('national_administrator', 'REPORT_EXPORT'),
  ('national_administrator', 'AGGREGATE_REPORT_VIEW'),
  ('national_administrator', 'ACCESS_REQUEST_REVIEW'),
  ('provincial_administrator', 'ADMIN_DASHBOARD_VIEW'),
  ('provincial_administrator', 'USER_VIEW'),
  ('provincial_administrator', 'USER_CREATE'),
  ('provincial_administrator', 'USER_UPDATE'),
  ('provincial_administrator', 'USER_SUSPEND'),
  ('provincial_administrator', 'FACILITY_VIEW'),
  ('provincial_administrator', 'FACILITY_UPDATE'),
  ('provincial_administrator', 'FACILITY_ASSIGN'),
  ('provincial_administrator', 'REPORT_VIEW'),
  ('provincial_administrator', 'AGGREGATE_REPORT_VIEW'),
  ('health_zone_administrator', 'ADMIN_DASHBOARD_VIEW'),
  ('health_zone_administrator', 'USER_VIEW'),
  ('health_zone_administrator', 'USER_CREATE'),
  ('health_zone_administrator', 'USER_UPDATE'),
  ('health_zone_administrator', 'USER_SUSPEND'),
  ('health_zone_administrator', 'FACILITY_VIEW'),
  ('health_zone_administrator', 'FACILITY_UPDATE'),
  ('health_zone_administrator', 'FACILITY_ASSIGN'),
  ('health_zone_administrator', 'REPORT_VIEW'),
  ('health_zone_administrator', 'AGGREGATE_REPORT_VIEW'),
  ('health_facility_administrator', 'ADMIN_DASHBOARD_VIEW'),
  ('health_facility_administrator', 'USER_VIEW'),
  ('health_facility_administrator', 'USER_CREATE'),
  ('health_facility_administrator', 'USER_UPDATE'),
  ('health_facility_administrator', 'FACILITY_VIEW'),
  ('health_facility_administrator', 'FACILITY_UPDATE'),
  ('health_facility_administrator', 'FACILITY_ASSIGN'),
  ('health_facility_administrator', 'APPOINTMENT_VIEW'),
  ('health_facility_administrator', 'APPOINTMENT_MANAGE'),
  ('health_facility_administrator', 'AGGREGATE_REPORT_VIEW'),
  ('health_professional', 'CHILD_VIEW'),
  ('health_professional', 'GROWTH_VIEW'),
  ('health_professional', 'GROWTH_CREATE'),
  ('health_professional', 'GROWTH_UPDATE'),
  ('health_professional', 'GROWTH_VALIDATE'),
  ('health_professional', 'VACCINATION_VIEW'),
  ('health_professional', 'VACCINATION_CREATE'),
  ('health_professional', 'VACCINATION_UPDATE'),
  ('health_professional', 'VACCINATION_VALIDATE'),
  ('health_professional', 'MEDICAL_RECORD_VIEW'),
  ('health_professional', 'MEDICAL_RECORD_CREATE'),
  ('health_professional', 'MEDICAL_RECORD_UPDATE'),
  ('health_professional', 'HEALTH_ALERT_VIEW'),
  ('health_professional', 'HEALTH_ALERT_CREATE'),
  ('health_professional', 'HEALTH_ALERT_RESOLVE'),
  ('health_professional', 'APPOINTMENT_VIEW'),
  ('health_professional', 'APPOINTMENT_MANAGE'),
  ('health_professional', 'REFERRAL_VIEW'),
  ('health_professional', 'REFERRAL_FOLLOW_UP'),
  ('health_professional', 'REPORT_VIEW'),
  ('health_professional', 'NOTIFICATION_VIEW'),
  ('health_professional', 'NOTIFICATION_UPDATE'),
  ('community_health_worker', 'CHILD_VIEW'),
  ('community_health_worker', 'CHILD_CREATE'),
  ('community_health_worker', 'GROWTH_VIEW'),
  ('community_health_worker', 'GROWTH_CREATE'),
  ('community_health_worker', 'VACCINATION_VIEW'),
  ('community_health_worker', 'VACCINATION_CREATE'),
  ('community_health_worker', 'HEALTH_ALERT_CREATE'),
  ('community_health_worker', 'COMMUNITY_VISIT_CREATE'),
  ('community_health_worker', 'REFERRAL_CREATE'),
  ('community_health_worker', 'REFERRAL_VIEW'),
  ('community_health_worker', 'APPOINTMENT_VIEW'),
  ('community_health_worker', 'AGGREGATE_REPORT_VIEW'),
  ('community_health_worker', 'NOTIFICATION_VIEW'),
  ('community_health_worker', 'NOTIFICATION_UPDATE'),
  ('parent', 'CHILD_VIEW'),
  ('parent', 'CHILD_CREATE'),
  ('parent', 'CHILD_UPDATE'),
  ('parent', 'CHILD_ARCHIVE'),
  ('parent', 'GROWTH_VIEW'),
  ('parent', 'GROWTH_CREATE'),
  ('parent', 'VACCINATION_VIEW'),
  ('parent', 'APPOINTMENT_VIEW'),
  ('parent', 'APPOINTMENT_CREATE'),
  ('parent', 'NOTIFICATION_VIEW'),
  ('parent', 'NOTIFICATION_UPDATE'),
  ('health_data_analyst', 'AGGREGATE_REPORT_VIEW'),
  ('health_data_analyst', 'REPORT_VIEW'),
  ('health_data_analyst', 'REPORT_EXPORT'),
  ('health_center_manager', 'ADMIN_DASHBOARD_VIEW'),
  ('health_center_manager', 'USER_VIEW'),
  ('health_center_manager', 'USER_CREATE'),
  ('health_center_manager', 'USER_UPDATE'),
  ('health_center_manager', 'FACILITY_VIEW'),
  ('health_center_manager', 'FACILITY_UPDATE'),
  ('health_center_manager', 'FACILITY_ASSIGN'),
  ('health_center_manager', 'APPOINTMENT_VIEW'),
  ('health_center_manager', 'APPOINTMENT_MANAGE'),
  ('health_center_manager', 'AGGREGATE_REPORT_VIEW'),
  ('health_supervisor', 'ADMIN_DASHBOARD_VIEW'),
  ('health_supervisor', 'FACILITY_VIEW'),
  ('health_supervisor', 'REFERRAL_VIEW'),
  ('health_supervisor', 'REPORT_VIEW'),
  ('health_supervisor', 'AGGREGATE_REPORT_VIEW')
ON CONFLICT ("role_code", "permission_code") DO NOTHING;

-- Scope assignments must contain exactly one matching resource id.
ALTER TABLE "user_assignments" ADD CONSTRAINT "user_assignments_scope_check" CHECK (
  ("scope_type" = 'province' AND "province_id" IS NOT NULL AND "health_zone_id" IS NULL AND "health_center_id" IS NULL) OR
  ("scope_type" = 'health_zone' AND "province_id" IS NULL AND "health_zone_id" IS NOT NULL AND "health_center_id" IS NULL) OR
  ("scope_type" = 'health_center' AND "province_id" IS NULL AND "health_zone_id" IS NULL AND "health_center_id" IS NOT NULL)
);
ALTER TABLE "consent_records" ADD CONSTRAINT "consent_records_action_check" CHECK ("action" IN ('granted','withdrawn','reaffirmed'));
ALTER TABLE "access_requests" ADD CONSTRAINT "access_requests_status_check" CHECK ("status" IN ('pending','approved','denied','expired'));

-- Normalize current province labels without deleting the original text.
INSERT INTO "provinces" ("code", "name")
SELECT 'legacy-' || substr(md5(lower(trim("province"))), 1, 24), max(trim("province"))
FROM "health_centers"
WHERE "province" IS NOT NULL AND trim("province") <> ''
GROUP BY lower(trim("province"))
ON CONFLICT ("code") DO NOTHING;
UPDATE "health_centers" AS h SET "province_id" = p."id"
FROM "provinces" AS p
WHERE h."province" IS NOT NULL AND lower(trim(h."province")) = lower(p."name") AND h."province_id" IS NULL;

-- Carry existing facility assignments into the scoped assignment model.
INSERT INTO "user_assignments" ("user_id", "role_code", "scope_type", "health_center_id", "assigned_at")
SELECT uh."user_id", ur."role_code", 'health_center', uh."health_center_id", uh."assigned_at"
FROM "user_health_centers" AS uh
JOIN "user_roles" AS ur ON ur."user_id" = uh."user_id"
ON CONFLICT DO NOTHING;

-- Copy only consent events for which the legacy fields contain an actual timestamp.
INSERT INTO "consent_records" ("child_id", "user_id", "action", "captured_at", "recorded_by_user_id", "method")
SELECT "child_id", "user_id", 'granted', "consent_at", "user_id", 'legacy_timestamp'
FROM "child_guardians" WHERE "consent_at" IS NOT NULL;
INSERT INTO "consent_records" ("child_id", "user_id", "action", "captured_at", "recorded_by_user_id", "method")
SELECT "child_id", "user_id", 'withdrawn', "consent_withdrawn_at", "user_id", 'legacy_timestamp'
FROM "child_guardians" WHERE "consent_withdrawn_at" IS NOT NULL;

-- Preserve existing administrator access while introducing the explicit Super Admin role.
INSERT INTO "user_roles" ("user_id", "role_code")
SELECT DISTINCT ur."user_id", 'super_administrator'
FROM "user_roles" AS ur WHERE ur."role_code" = 'administrator'
ON CONFLICT ("user_id", "role_code") DO NOTHING;

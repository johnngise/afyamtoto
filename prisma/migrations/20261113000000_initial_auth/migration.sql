-- CreateSchema
CREATE SCHEMA IF NOT EXISTS "public";

-- CreateEnum
CREATE TYPE "child_sex" AS ENUM ('female', 'male', 'unknown');

-- CreateTable
CREATE TABLE "roles" (
    "code" VARCHAR(64) NOT NULL,
    "display_name" VARCHAR(100) NOT NULL,

    CONSTRAINT "roles_pkey" PRIMARY KEY ("code")
);

-- CreateTable
CREATE TABLE "permissions" (
    "code" VARCHAR(128) NOT NULL,
    "description" VARCHAR(255) NOT NULL,

    CONSTRAINT "permissions_pkey" PRIMARY KEY ("code")
);

-- CreateTable
CREATE TABLE "role_permissions" (
    "role_code" VARCHAR(64) NOT NULL,
    "permission_code" VARCHAR(128) NOT NULL,

    CONSTRAINT "role_permissions_pkey" PRIMARY KEY ("role_code","permission_code")
);

-- CreateTable
CREATE TABLE "users" (
    "id" BIGSERIAL NOT NULL,
    "display_name" VARCHAR(160) NOT NULL,
    "email" VARCHAR(254),
    "phone_e164" VARCHAR(16),
    "password_hash" VARCHAR(255) NOT NULL,
    "is_active" BOOLEAN NOT NULL DEFAULT true,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ(6) NOT NULL,

    CONSTRAINT "users_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "user_roles" (
    "user_id" BIGINT NOT NULL,
    "role_code" VARCHAR(64) NOT NULL,
    "assigned_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "user_roles_pkey" PRIMARY KEY ("user_id","role_code")
);

-- CreateTable
CREATE TABLE "sessions" (
    "id" CHAR(64) NOT NULL,
    "user_id" BIGINT NOT NULL,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "expires_at" TIMESTAMPTZ(6) NOT NULL,
    "last_seen" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "role_code" VARCHAR(64) NOT NULL,

    CONSTRAINT "sessions_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "login_attempts" (
    "key" CHAR(64) NOT NULL,
    "count" INTEGER NOT NULL DEFAULT 0,
    "window_start" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "locked_until" TIMESTAMPTZ(6),

    CONSTRAINT "login_attempts_pkey" PRIMARY KEY ("key")
);

-- CreateTable
CREATE TABLE "activity_logs" (
    "id" BIGSERIAL NOT NULL,
    "user_id" BIGINT,
    "action" VARCHAR(100) NOT NULL,
    "description" VARCHAR(500),
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "activity_logs_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "password_reset_tokens" (
    "id" BIGSERIAL NOT NULL,
    "user_id" BIGINT NOT NULL,
    "token_hash" CHAR(64) NOT NULL,
    "expires_at" TIMESTAMPTZ(6) NOT NULL,
    "used_at" TIMESTAMPTZ(6),
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "password_reset_tokens_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "children" (
    "id" BIGSERIAL NOT NULL,
    "first_name" VARCHAR(100) NOT NULL,
    "last_name" VARCHAR(100) NOT NULL,
    "date_of_birth" DATE NOT NULL,
    "sex" "child_sex" NOT NULL,
    "place_of_birth" VARCHAR(160),
    "address_text" VARCHAR(500),
    "photo_path" VARCHAR(255),
    "created_by_user_id" BIGINT NOT NULL,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ(6) NOT NULL,

    CONSTRAINT "children_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "child_guardians" (
    "child_id" BIGINT NOT NULL,
    "user_id" BIGINT NOT NULL,
    "relationship_type" VARCHAR(32) NOT NULL,
    "is_primary" BOOLEAN NOT NULL DEFAULT false,
    "consent_status" VARCHAR(16) NOT NULL DEFAULT 'pending',
    "consent_at" TIMESTAMPTZ(6),
    "consent_withdrawn_at" TIMESTAMPTZ(6),
    "linked_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "child_guardians_pkey" PRIMARY KEY ("child_id","user_id")
);

-- CreateTable
CREATE TABLE "health_centers" (
    "id" BIGSERIAL NOT NULL,
    "name" VARCHAR(180) NOT NULL,
    "phone_e164" VARCHAR(16),
    "address_text" VARCHAR(500),
    "province" VARCHAR(120),
    "city" VARCHAR(120),
    "latitude" DECIMAL(10,7),
    "longitude" DECIMAL(10,7),
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ(6) NOT NULL,

    CONSTRAINT "health_centers_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "health_center_services" (
    "id" BIGSERIAL NOT NULL,
    "health_center_id" BIGINT NOT NULL,
    "service_name" VARCHAR(120) NOT NULL,

    CONSTRAINT "health_center_services_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "user_health_centers" (
    "user_id" BIGINT NOT NULL,
    "health_center_id" BIGINT NOT NULL,
    "assigned_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "user_health_centers_pkey" PRIMARY KEY ("user_id","health_center_id")
);

-- CreateTable
CREATE TABLE "appointment_requests" (
    "id" BIGSERIAL NOT NULL,
    "child_id" BIGINT NOT NULL,
    "parent_user_id" BIGINT NOT NULL,
    "health_center_id" BIGINT NOT NULL,
    "request_type" VARCHAR(40) NOT NULL DEFAULT 'consultation',
    "preferred_date" DATE,
    "status" VARCHAR(16) NOT NULL DEFAULT 'pending',
    "scheduled_at" TIMESTAMPTZ(6),
    "response_message" VARCHAR(500),
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ(6) NOT NULL,

    CONSTRAINT "appointment_requests_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "growth_measurements" (
    "id" BIGSERIAL NOT NULL,
    "child_id" BIGINT NOT NULL,
    "recorded_by_user_id" BIGINT NOT NULL,
    "measured_at" TIMESTAMPTZ(6) NOT NULL,
    "weight_kg" DECIMAL(6,3),
    "length_height_cm" DECIMAL(6,2),
    "length_height_type" VARCHAR(16),
    "muac_cm" DECIMAL(6,2),
    "notes" VARCHAR(500),
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "growth_measurements_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "vaccines" (
    "id" BIGSERIAL NOT NULL,
    "vaccine_code" VARCHAR(64) NOT NULL,
    "display_name" VARCHAR(160) NOT NULL,
    "description" VARCHAR(500),
    "is_active" BOOLEAN NOT NULL DEFAULT true,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "vaccines_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "vaccine_doses" (
    "id" BIGSERIAL NOT NULL,
    "vaccine_id" BIGINT NOT NULL,
    "dose_code" VARCHAR(64) NOT NULL,
    "display_name" VARCHAR(120) NOT NULL,
    "dose_order" SMALLINT NOT NULL,

    CONSTRAINT "vaccine_doses_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "vaccination_schedules" (
    "id" BIGSERIAL NOT NULL,
    "schedule_code" VARCHAR(64) NOT NULL,
    "display_name" VARCHAR(160) NOT NULL,
    "jurisdiction_code" CHAR(2) NOT NULL,
    "program_name" VARCHAR(160) NOT NULL,
    "version_label" VARCHAR(64) NOT NULL,
    "effective_from" DATE,
    "effective_until" DATE,
    "status" VARCHAR(16) NOT NULL DEFAULT 'draft',
    "approved_by_user_id" BIGINT,
    "approved_at" TIMESTAMPTZ(6),
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ(6) NOT NULL,

    CONSTRAINT "vaccination_schedules_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "vaccination_schedule_doses" (
    "schedule_id" BIGINT NOT NULL,
    "vaccine_dose_id" BIGINT NOT NULL,
    "earliest_age_days" INTEGER,
    "recommended_age_days" INTEGER NOT NULL,
    "latest_age_days" INTEGER,
    "interval_reference_dose_id" BIGINT,
    "minimum_interval_days" SMALLINT,

    CONSTRAINT "vaccination_schedule_doses_pkey" PRIMARY KEY ("schedule_id","vaccine_dose_id")
);

-- CreateTable
CREATE TABLE "vaccination_records" (
    "id" BIGSERIAL NOT NULL,
    "child_id" BIGINT NOT NULL,
    "vaccine_dose_id" BIGINT NOT NULL,
    "administered_at" TIMESTAMPTZ(6) NOT NULL,
    "next_dose_date" DATE,
    "status" VARCHAR(16) NOT NULL DEFAULT 'administered',
    "notes" VARCHAR(500),
    "recorded_by_user_id" BIGINT NOT NULL,
    "health_center_id" BIGINT,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "vaccination_records_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "medical_records" (
    "id" BIGSERIAL NOT NULL,
    "child_id" BIGINT NOT NULL,
    "blood_group" VARCHAR(8),
    "allergies" TEXT,
    "medical_history" TEXT,
    "notes" TEXT,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ(6) NOT NULL,

    CONSTRAINT "medical_records_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "nutrition_records" (
    "id" BIGSERIAL NOT NULL,
    "child_id" BIGINT NOT NULL,
    "record_date" DATE NOT NULL,
    "nutritional_status" VARCHAR(64),
    "feeding_type" VARCHAR(64),
    "observations" TEXT,
    "recommendations" TEXT,
    "health_worker_id" BIGINT NOT NULL,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "nutrition_records_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "health_alerts" (
    "id" BIGSERIAL NOT NULL,
    "child_id" BIGINT NOT NULL,
    "alert_type" VARCHAR(64) NOT NULL,
    "severity" VARCHAR(16) NOT NULL,
    "description" VARCHAR(1000) NOT NULL,
    "status" VARCHAR(20) NOT NULL DEFAULT 'pending_review',
    "created_by_user_id" BIGINT,
    "reviewed_by_user_id" BIGINT,
    "reviewed_at" TIMESTAMPTZ(6),
    "review_notes" VARCHAR(1000),
    "health_center_id" BIGINT,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "resolved_at" TIMESTAMPTZ(6),

    CONSTRAINT "health_alerts_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "community_visits" (
    "id" BIGSERIAL NOT NULL,
    "child_id" BIGINT NOT NULL,
    "agent_user_id" BIGINT NOT NULL,
    "visit_type" VARCHAR(32) NOT NULL,
    "visited_at" TIMESTAMPTZ(6) NOT NULL,
    "purpose" VARCHAR(160) NOT NULL,
    "observations" TEXT,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "community_visits_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "health_referrals" (
    "id" BIGSERIAL NOT NULL,
    "child_id" BIGINT NOT NULL,
    "agent_user_id" BIGINT NOT NULL,
    "health_center_id" BIGINT NOT NULL,
    "community_visit_id" BIGINT,
    "concern_description" VARCHAR(1000) NOT NULL,
    "priority" VARCHAR(16) NOT NULL DEFAULT 'routine',
    "status" VARCHAR(24) NOT NULL DEFAULT 'awaiting_follow_up',
    "referred_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "followed_up_at" TIMESTAMPTZ(6),
    "follow_up_notes" VARCHAR(500),

    CONSTRAINT "health_referrals_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "professional_observations" (
    "id" BIGSERIAL NOT NULL,
    "child_id" BIGINT NOT NULL,
    "health_center_id" BIGINT NOT NULL,
    "health_professional_id" BIGINT NOT NULL,
    "health_alert_id" BIGINT,
    "health_referral_id" BIGINT,
    "observation_type" VARCHAR(32) NOT NULL,
    "content" TEXT NOT NULL,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "professional_observations_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "notifications" (
    "id" BIGSERIAL NOT NULL,
    "user_id" BIGINT NOT NULL,
    "title" VARCHAR(180) NOT NULL,
    "message" VARCHAR(1000) NOT NULL,
    "type" VARCHAR(64) NOT NULL,
    "is_read" BOOLEAN NOT NULL DEFAULT false,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "notifications_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "audit_events" (
    "id" BIGSERIAL NOT NULL,
    "actor_user_id" BIGINT,
    "event_code" VARCHAR(100) NOT NULL,
    "subject_type" VARCHAR(64) NOT NULL,
    "subject_id" BIGINT,
    "request_id" CHAR(36),
    "occurred_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "audit_events_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "users_email_key" ON "users"("email");

-- CreateIndex
CREATE UNIQUE INDEX "users_phone_e164_key" ON "users"("phone_e164");

-- CreateIndex
CREATE INDEX "user_roles_role_code_idx" ON "user_roles"("role_code");

-- CreateIndex
CREATE INDEX "sessions_user_id_idx" ON "sessions"("user_id");

-- CreateIndex
CREATE INDEX "sessions_expires_at_idx" ON "sessions"("expires_at");

-- CreateIndex
CREATE INDEX "activity_logs_user_id_created_at_idx" ON "activity_logs"("user_id", "created_at");

-- CreateIndex
CREATE UNIQUE INDEX "password_reset_tokens_token_hash_key" ON "password_reset_tokens"("token_hash");

-- CreateIndex
CREATE INDEX "password_reset_tokens_user_id_expires_at_idx" ON "password_reset_tokens"("user_id", "expires_at");

-- CreateIndex
CREATE INDEX "children_date_of_birth_idx" ON "children"("date_of_birth");

-- CreateIndex
CREATE INDEX "children_created_by_user_id_idx" ON "children"("created_by_user_id");

-- CreateIndex
CREATE INDEX "child_guardians_user_id_idx" ON "child_guardians"("user_id");

-- CreateIndex
CREATE INDEX "health_centers_name_idx" ON "health_centers"("name");

-- CreateIndex
CREATE UNIQUE INDEX "health_center_services_health_center_id_service_name_key" ON "health_center_services"("health_center_id", "service_name");

-- CreateIndex
CREATE INDEX "appointment_requests_parent_user_id_status_created_at_idx" ON "appointment_requests"("parent_user_id", "status", "created_at");

-- CreateIndex
CREATE INDEX "appointment_requests_health_center_id_status_created_at_idx" ON "appointment_requests"("health_center_id", "status", "created_at");

-- CreateIndex
CREATE INDEX "appointment_requests_child_id_created_at_idx" ON "appointment_requests"("child_id", "created_at");

-- CreateIndex
CREATE INDEX "growth_measurements_child_id_measured_at_idx" ON "growth_measurements"("child_id", "measured_at");

-- CreateIndex
CREATE INDEX "growth_measurements_recorded_by_user_id_idx" ON "growth_measurements"("recorded_by_user_id");

-- CreateIndex
CREATE UNIQUE INDEX "vaccines_vaccine_code_key" ON "vaccines"("vaccine_code");

-- CreateIndex
CREATE UNIQUE INDEX "vaccine_doses_vaccine_id_dose_code_key" ON "vaccine_doses"("vaccine_id", "dose_code");

-- CreateIndex
CREATE UNIQUE INDEX "vaccine_doses_vaccine_id_dose_order_key" ON "vaccine_doses"("vaccine_id", "dose_order");

-- CreateIndex
CREATE INDEX "vaccination_schedules_jurisdiction_code_status_idx" ON "vaccination_schedules"("jurisdiction_code", "status");

-- CreateIndex
CREATE UNIQUE INDEX "vaccination_schedules_schedule_code_version_label_key" ON "vaccination_schedules"("schedule_code", "version_label");

-- CreateIndex
CREATE INDEX "vaccination_records_child_id_administered_at_idx" ON "vaccination_records"("child_id", "administered_at");

-- CreateIndex
CREATE INDEX "vaccination_records_vaccine_dose_id_idx" ON "vaccination_records"("vaccine_dose_id");

-- CreateIndex
CREATE INDEX "vaccination_records_recorded_by_user_id_idx" ON "vaccination_records"("recorded_by_user_id");

-- CreateIndex
CREATE UNIQUE INDEX "medical_records_child_id_key" ON "medical_records"("child_id");

-- CreateIndex
CREATE INDEX "nutrition_records_child_id_record_date_idx" ON "nutrition_records"("child_id", "record_date");

-- CreateIndex
CREATE INDEX "nutrition_records_health_worker_id_idx" ON "nutrition_records"("health_worker_id");

-- CreateIndex
CREATE INDEX "health_alerts_child_id_status_created_at_idx" ON "health_alerts"("child_id", "status", "created_at");

-- CreateIndex
CREATE INDEX "health_alerts_severity_status_created_at_idx" ON "health_alerts"("severity", "status", "created_at");

-- CreateIndex
CREATE INDEX "health_alerts_reviewed_by_user_id_idx" ON "health_alerts"("reviewed_by_user_id");

-- CreateIndex
CREATE INDEX "community_visits_agent_user_id_visited_at_idx" ON "community_visits"("agent_user_id", "visited_at");

-- CreateIndex
CREATE INDEX "community_visits_child_id_visited_at_idx" ON "community_visits"("child_id", "visited_at");

-- CreateIndex
CREATE INDEX "health_referrals_agent_user_id_status_referred_at_idx" ON "health_referrals"("agent_user_id", "status", "referred_at");

-- CreateIndex
CREATE INDEX "health_referrals_health_center_id_status_referred_at_idx" ON "health_referrals"("health_center_id", "status", "referred_at");

-- CreateIndex
CREATE INDEX "health_referrals_child_id_referred_at_idx" ON "health_referrals"("child_id", "referred_at");

-- CreateIndex
CREATE INDEX "professional_observations_child_id_created_at_idx" ON "professional_observations"("child_id", "created_at");

-- CreateIndex
CREATE INDEX "professional_observations_health_center_id_created_at_idx" ON "professional_observations"("health_center_id", "created_at");

-- CreateIndex
CREATE INDEX "professional_observations_health_professional_id_created_at_idx" ON "professional_observations"("health_professional_id", "created_at");

-- CreateIndex
CREATE INDEX "notifications_user_id_is_read_created_at_idx" ON "notifications"("user_id", "is_read", "created_at");

-- CreateIndex
CREATE INDEX "audit_events_actor_user_id_occurred_at_idx" ON "audit_events"("actor_user_id", "occurred_at");

-- CreateIndex
CREATE INDEX "audit_events_subject_type_subject_id_occurred_at_idx" ON "audit_events"("subject_type", "subject_id", "occurred_at");

-- CreateIndex
CREATE INDEX "audit_events_request_id_idx" ON "audit_events"("request_id");

-- AddForeignKey
ALTER TABLE "role_permissions" ADD CONSTRAINT "role_permissions_role_code_fkey" FOREIGN KEY ("role_code") REFERENCES "roles"("code") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "role_permissions" ADD CONSTRAINT "role_permissions_permission_code_fkey" FOREIGN KEY ("permission_code") REFERENCES "permissions"("code") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_roles" ADD CONSTRAINT "user_roles_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_roles" ADD CONSTRAINT "user_roles_role_code_fkey" FOREIGN KEY ("role_code") REFERENCES "roles"("code") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "sessions" ADD CONSTRAINT "sessions_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "sessions" ADD CONSTRAINT "sessions_role_code_fkey" FOREIGN KEY ("role_code") REFERENCES "roles"("code") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "activity_logs" ADD CONSTRAINT "activity_logs_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "password_reset_tokens" ADD CONSTRAINT "password_reset_tokens_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "children" ADD CONSTRAINT "children_created_by_user_id_fkey" FOREIGN KEY ("created_by_user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "child_guardians" ADD CONSTRAINT "child_guardians_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "child_guardians" ADD CONSTRAINT "child_guardians_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_center_services" ADD CONSTRAINT "health_center_services_health_center_id_fkey" FOREIGN KEY ("health_center_id") REFERENCES "health_centers"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_health_centers" ADD CONSTRAINT "user_health_centers_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_health_centers" ADD CONSTRAINT "user_health_centers_health_center_id_fkey" FOREIGN KEY ("health_center_id") REFERENCES "health_centers"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "appointment_requests" ADD CONSTRAINT "appointment_requests_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "appointment_requests" ADD CONSTRAINT "appointment_requests_parent_user_id_fkey" FOREIGN KEY ("parent_user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "appointment_requests" ADD CONSTRAINT "appointment_requests_health_center_id_fkey" FOREIGN KEY ("health_center_id") REFERENCES "health_centers"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "growth_measurements" ADD CONSTRAINT "growth_measurements_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "growth_measurements" ADD CONSTRAINT "growth_measurements_recorded_by_user_id_fkey" FOREIGN KEY ("recorded_by_user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "vaccine_doses" ADD CONSTRAINT "vaccine_doses_vaccine_id_fkey" FOREIGN KEY ("vaccine_id") REFERENCES "vaccines"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "vaccination_schedules" ADD CONSTRAINT "vaccination_schedules_approved_by_user_id_fkey" FOREIGN KEY ("approved_by_user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "vaccination_schedule_doses" ADD CONSTRAINT "vaccination_schedule_doses_schedule_id_fkey" FOREIGN KEY ("schedule_id") REFERENCES "vaccination_schedules"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "vaccination_schedule_doses" ADD CONSTRAINT "vaccination_schedule_doses_vaccine_dose_id_fkey" FOREIGN KEY ("vaccine_dose_id") REFERENCES "vaccine_doses"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "vaccination_schedule_doses" ADD CONSTRAINT "vaccination_schedule_doses_schedule_id_interval_reference__fkey" FOREIGN KEY ("schedule_id", "interval_reference_dose_id") REFERENCES "vaccination_schedule_doses"("schedule_id", "vaccine_dose_id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "vaccination_records" ADD CONSTRAINT "vaccination_records_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "vaccination_records" ADD CONSTRAINT "vaccination_records_vaccine_dose_id_fkey" FOREIGN KEY ("vaccine_dose_id") REFERENCES "vaccine_doses"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "vaccination_records" ADD CONSTRAINT "vaccination_records_recorded_by_user_id_fkey" FOREIGN KEY ("recorded_by_user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "vaccination_records" ADD CONSTRAINT "vaccination_records_health_center_id_fkey" FOREIGN KEY ("health_center_id") REFERENCES "health_centers"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "medical_records" ADD CONSTRAINT "medical_records_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "nutrition_records" ADD CONSTRAINT "nutrition_records_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "nutrition_records" ADD CONSTRAINT "nutrition_records_health_worker_id_fkey" FOREIGN KEY ("health_worker_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_alerts" ADD CONSTRAINT "health_alerts_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_alerts" ADD CONSTRAINT "health_alerts_created_by_user_id_fkey" FOREIGN KEY ("created_by_user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_alerts" ADD CONSTRAINT "health_alerts_reviewed_by_user_id_fkey" FOREIGN KEY ("reviewed_by_user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_alerts" ADD CONSTRAINT "health_alerts_health_center_id_fkey" FOREIGN KEY ("health_center_id") REFERENCES "health_centers"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "community_visits" ADD CONSTRAINT "community_visits_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "community_visits" ADD CONSTRAINT "community_visits_agent_user_id_fkey" FOREIGN KEY ("agent_user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_referrals" ADD CONSTRAINT "health_referrals_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_referrals" ADD CONSTRAINT "health_referrals_agent_user_id_fkey" FOREIGN KEY ("agent_user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_referrals" ADD CONSTRAINT "health_referrals_health_center_id_fkey" FOREIGN KEY ("health_center_id") REFERENCES "health_centers"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_referrals" ADD CONSTRAINT "health_referrals_community_visit_id_fkey" FOREIGN KEY ("community_visit_id") REFERENCES "community_visits"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "professional_observations" ADD CONSTRAINT "professional_observations_child_id_fkey" FOREIGN KEY ("child_id") REFERENCES "children"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "professional_observations" ADD CONSTRAINT "professional_observations_health_center_id_fkey" FOREIGN KEY ("health_center_id") REFERENCES "health_centers"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "professional_observations" ADD CONSTRAINT "professional_observations_health_professional_id_fkey" FOREIGN KEY ("health_professional_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "professional_observations" ADD CONSTRAINT "professional_observations_health_alert_id_fkey" FOREIGN KEY ("health_alert_id") REFERENCES "health_alerts"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "professional_observations" ADD CONSTRAINT "professional_observations_health_referral_id_fkey" FOREIGN KEY ("health_referral_id") REFERENCES "health_referrals"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "notifications" ADD CONSTRAINT "notifications_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "audit_events" ADD CONSTRAINT "audit_events_actor_user_id_fkey" FOREIGN KEY ("actor_user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

INSERT INTO "roles" ("code", "display_name") VALUES
  ('administrator', 'Administrateur'),
  ('parent', 'Parent'),
  ('community_health_worker', 'Agent de santé communautaire'),
  ('health_professional', 'Professionnel de santé'),
  ('health_center_manager', 'Gestionnaire de centre de santé'),
  ('health_supervisor', 'Superviseur de santé');

ALTER TABLE "users" ADD CONSTRAINT "users_login_identifier_check"
  CHECK ("email" IS NOT NULL OR "phone_e164" IS NOT NULL);
ALTER TABLE "health_centers" ADD CONSTRAINT "health_centers_coordinates_pair_check"
  CHECK (("latitude" IS NULL AND "longitude" IS NULL) OR ("latitude" IS NOT NULL AND "longitude" IS NOT NULL));
ALTER TABLE "health_centers" ADD CONSTRAINT "health_centers_coordinates_range_check"
  CHECK (("latitude" IS NULL OR "latitude" BETWEEN -90 AND 90) AND ("longitude" IS NULL OR "longitude" BETWEEN -180 AND 180));
ALTER TABLE "growth_measurements" ADD CONSTRAINT "growth_measurements_valid_values_check"
  CHECK (("weight_kg" IS NOT NULL OR "length_height_cm" IS NOT NULL OR "muac_cm" IS NOT NULL)
    AND ("weight_kg" IS NULL OR "weight_kg" > 0)
    AND ("length_height_cm" IS NULL OR "length_height_cm" > 0)
    AND ("muac_cm" IS NULL OR "muac_cm" > 0)
    AND (("length_height_cm" IS NULL AND "length_height_type" IS NULL)
      OR ("length_height_cm" IS NOT NULL AND "length_height_type" IS NOT NULL)));
ALTER TABLE "health_alerts" ADD CONSTRAINT "health_alerts_resolution_check"
  CHECK (("status" = 'resolved' AND "resolved_at" IS NOT NULL) OR ("status" <> 'resolved' AND "resolved_at" IS NULL));

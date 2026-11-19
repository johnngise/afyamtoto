-- CreateEnum
CREATE TYPE "HealthFacilityType" AS ENUM ('health_center', 'hospital', 'clinic', 'other');

-- AlterTable
ALTER TABLE "health_centers" ADD COLUMN     "facility_code" VARCHAR(64),
ADD COLUMN     "facility_type" "HealthFacilityType",
ADD COLUMN     "health_area_id" BIGINT,
ADD COLUMN     "source" VARCHAR(120),
ADD COLUMN     "source_version" VARCHAR(64),
ADD COLUMN     "verification_status" VARCHAR(32);

-- CreateTable
CREATE TABLE "health_areas" (
    "id" BIGSERIAL NOT NULL,
    "code" VARCHAR(64),
    "name" VARCHAR(120) NOT NULL,
    "health_zone_id" BIGINT NOT NULL,
    "created_at" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ(6) NOT NULL,

    CONSTRAINT "health_areas_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "health_areas_code_key" ON "health_areas"("code");

-- CreateIndex
CREATE INDEX "health_areas_health_zone_id_name_idx" ON "health_areas"("health_zone_id", "name");

-- AddForeignKey
ALTER TABLE "health_centers" ADD CONSTRAINT "health_centers_health_area_id_fkey" FOREIGN KEY ("health_area_id") REFERENCES "health_areas"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "health_areas" ADD CONSTRAINT "health_areas_health_zone_id_fkey" FOREIGN KEY ("health_zone_id") REFERENCES "health_zones"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

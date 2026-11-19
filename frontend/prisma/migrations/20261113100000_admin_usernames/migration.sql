ALTER TABLE "users" ADD COLUMN "username" VARCHAR(64);

CREATE UNIQUE INDEX "users_username_key" ON "users"("username");

# Next.js backend migration

Next.js now serves the public site, parent registration and login pages, and role-based dashboards. Its PostgreSQL/Prisma APIs cover authentication, child profiles and consent, growth records and appointments, vaccination records, community visits/nutrition/referrals, professional alert review and observations, center operations, notifications, and account/reference administration. Apache forwards the legacy PHP URLs to the Next app on port 3000.

## Local setup

1. Install Node.js 22.18 or newer and make a PostgreSQL server available.
2. From this directory, run `npm install`.
3. Copy `.env.example` to `.env.local` and set `DATABASE_URL` to the existing PostgreSQL database. Set `AFYA_ADMIN_SETUP_SECRET` to a random secret of at least 32 characters only when there is no administrator. If one administrator exists but its credentials are unavailable, set a random `AFYA_ADMIN_RECOVERY_SECRET` of at least 32 characters for one recovery of that existing account. Keep these server-only values out of browser code and remove them after use.
4. Run `npm run db:generate` and `npm run db:migrate`.
5. Start the app with `npm run dev` and open `http://localhost:3000`.

The initial Prisma migration creates the current application tables for accounts, permissions, sessions, children and guardians, health centers, appointments, growth, vaccination references and records, nutrition, alerts, referrals, observations, notifications, audit events, and activity logs. It seeds the six role codes. `npm run db:import:mysql` provides a dry-run report; use `npm run db:import:mysql -- --apply` only after backing up both databases, applying the PostgreSQL migration, validating the mapping, and putting the PHP site into maintenance mode. The importer preserves IDs and password hashes, omits ephemeral PHP sessions, and writes in per-table transactions. If an import fails partway, recreate an empty PostgreSQL target before retrying.

## API routes

- `/login` is the administrator sign-in. The existing family and staff sign-in remains at `/login?scope=public`.
- `/admin`, `/admin/users`, `/admin/content`, and `/admin/settings` are server-guarded by the administrator role. The overview API reads live statistics and the activity log from PostgreSQL.

- `POST /api/v1/auth/register` creates a parent account and starts a session.
- `POST /api/v1/auth/setup-administrator` creates the first administrator only when no administrator exists; a transaction lock and audit marker close it permanently after bootstrap.
- `POST /api/v1/auth/recover-administrator` updates the credentials of the single existing administrator only when a local one-time recovery secret is configured; it creates no account and closes after one successful recovery.
- `POST /api/v1/auth/login` accepts `{ "identifier", "password", "scope?" }`. Scope may be `administrator` or `agent`; otherwise the existing all-role login scope is used.
- `GET /api/v1/auth/me` returns the current account.
- `POST /api/v1/auth/logout` revokes the current session.
- `GET|POST /api/v1/children` lists the signed-in parent's children or creates a child profile.
- `GET|PATCH|DELETE /api/v1/children/:id` reads, updates profile/consent, or deletes a child profile subject to guardian checks.
- `GET|POST /api/v1/appointments` and `PATCH /api/v1/appointments/:id/response` handle parent requests and assigned center responses.
- `GET|POST /api/v1/growth` handles a parent's measurements for a child they are linked to with active consent.
- `GET /api/v1/vaccines` and `GET|POST /api/v1/vaccinations` provide reference and authorized record access.
- `POST /api/v1/community/children`, `/community/visits`, `/community/nutrition`, and `/community/referrals` handle community worker workflows.
- `/api/v1/professional/alerts` and `/api/v1/professional/observations` handle professional review and observations.
- `/api/v1/health-centers`, `/api/v1/admin/users`, and `/api/v1/admin/vaccines` provide center lookup and administrator management. Administrator accounts can sign in with e-mail or their optional unique username.
- `/api/v1/referrals`, `/api/v1/referrals/:id/follow-up`, and `/api/v1/professional/referrals/:id` handle referral follow-up.
- `GET /api/v1/dashboard` and `GET|PATCH /api/v1/notifications` provide role-scoped dashboard summaries and personal notifications.

Sessions use a random opaque token in an HttpOnly cookie; only its SHA-256 digest is stored. Sessions expire after 30 minutes of inactivity or 8 hours maximum. Passwords are bcrypt hashed, registration is parent-only, and login attempts are throttled.

Never expose `DATABASE_URL`, `MYSQL_URL`, `AFYA_ADMIN_SETUP_SECRET`, or `AFYA_ADMIN_RECOVERY_SECRET` to browser code. The local cutover imported 32 rows from MySQL after creating a source backup at `../storage/mysql-pre-cutover-2026-11-13.sql`. The source MySQL database remains intact for rollback. In this workspace, the project-owned PostgreSQL cluster is under `.postgres-data/` and listens on `127.0.0.1:5433`; it must be started again after a machine restart. Password reset email delivery and vaccine-schedule administration still need porting. Use HTTPS and a reverse proxy in production.

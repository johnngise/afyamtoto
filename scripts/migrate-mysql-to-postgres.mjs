import mysql from "mysql2/promise";
import dotenv from "dotenv";
import { Prisma, PrismaClient } from "@prisma/client";
import { PrismaPg } from "@prisma/adapter-pg";

dotenv.config({ path: ".env.local" });
dotenv.config();
dotenv.config({ path: "../.env" });

const apply = process.argv.includes("--apply");
const sourceUrl = process.env.MYSQL_URL;
const targetUrl = process.env.DATABASE_URL;
if (!targetUrl || (!sourceUrl && !(process.env.DB_HOST && process.env.DB_NAME && process.env.DB_USER))) {
  console.error("Configurez DATABASE_URL dans frontend/.env.local. La source utilise MYSQL_URL ou les paramètres DB_* du fichier .env racine.");
  process.exit(1);
}

const tableAliases = { login_attempts: "auth_login_attempts" };
const columnAliases = {
  roles: { code: "role_code" },
  permissions: { code: "permission_code" },
  login_attempts: { key: "attempt_key", count: "attempt_count", locked_until: "locked_until_utc" },
  users: { created_at: "created_at_utc", updated_at: "updated_at_utc" },
  user_roles: { assigned_at: "assigned_at_utc" },
  children: { created_at: "created_at_utc", updated_at: "updated_at_utc" },
  child_guardians: { consent_at: "consent_at_utc", consent_withdrawn_at: "consent_withdrawn_at_utc", linked_at: "linked_at_utc" },
  health_centers: { created_at: "created_at_utc", updated_at: "updated_at_utc" },
  user_health_centers: { assigned_at: "assigned_at_utc" },
  appointment_requests: { scheduled_at: "scheduled_at_utc", created_at: "created_at_utc", updated_at: "updated_at_utc" },
  growth_measurements: { measured_at: "measured_at_utc", created_at: "created_at_utc" },
  vaccines: { created_at: "created_at_utc" },
  vaccination_schedules: { approved_at: "approved_at_utc", created_at: "created_at_utc", updated_at: "updated_at_utc" },
  vaccination_records: { administered_at: "administered_at_utc", created_at: "created_at_utc" },
  medical_records: { created_at: "created_at_utc", updated_at: "updated_at_utc" },
  nutrition_records: { created_at: "created_at_utc" },
  health_alerts: { reviewed_at: "reviewed_at_utc", created_at: "created_at_utc", resolved_at: "resolved_at_utc" },
  community_visits: { visited_at: "visited_at_utc", created_at: "created_at_utc" },
  health_referrals: { referred_at: "referred_at_utc", followed_up_at: "followed_up_at_utc" },
  professional_observations: { created_at: "created_at_utc" },
  notifications: { created_at: "created_at_utc" },
  activity_logs: { created_at: "created_at_utc" },
  audit_events: { occurred_at: "occurred_at_utc" },
  password_reset_tokens: { expires_at: "expires_at_utc", used_at: "used_at_utc", created_at: "created_at_utc" },
};

const quotePg = (value) => `"${value.replaceAll('"', '""')}"`;
const quoteMy = (value) => `\`${value.replaceAll("`", "``")}\``;
const adapter = new PrismaPg({ connectionString: targetUrl });
const prisma = new PrismaClient({ adapter });
const mysqlOptions = sourceUrl ? (() => {
  const sourceUri = new URL(sourceUrl);
  return {
    host: sourceUri.hostname,
    port: Number(sourceUri.port || 3306),
    user: decodeURIComponent(sourceUri.username),
    password: decodeURIComponent(sourceUri.password),
    database: decodeURIComponent(sourceUri.pathname.replace(/^\//u, "")),
  };
})() : {
  host: process.env.DB_HOST,
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
};
const source = await mysql.createConnection({
  ...mysqlOptions,
  timezone: "Z",
  dateStrings: false,
  supportBigNumbers: true,
  bigNumberStrings: true,
});

try {
  const [tableRows] = await source.query("SHOW TABLES");
  const sourceTables = new Set(tableRows.map((row) => Object.values(row)[0]));
  const models = Prisma.dmmf.datamodel.models;
  const plan = [];

  for (const model of models) {
    const targetTable = model.dbName ?? model.name;
    const sourceTable = tableAliases[targetTable] ?? targetTable;
    if (!sourceTables.has(sourceTable) || targetTable === "sessions") continue;
    const [columnRows] = await source.query(`SHOW COLUMNS FROM ${quoteMy(sourceTable)}`);
    const sourceColumns = new Set(columnRows.map((column) => column.Field));
    // Prisma enums map to PostgreSQL enum columns too; include them in the
    // import so required values such as children.sex are not dropped.
    const fields = model.fields.filter((field) => field.kind === "scalar" || field.kind === "enum");
    const copyFields = fields.flatMap((field) => {
      const targetColumn = field.dbName ?? field.name;
      const sourceColumn = columnAliases[targetTable]?.[targetColumn] ?? targetColumn;
      return sourceColumns.has(sourceColumn) ? [{ field, sourceColumn, targetColumn }] : [];
    });
    const [countRows] = await source.query(`SELECT COUNT(*) AS total FROM ${quoteMy(sourceTable)}`);
    const missingRequiredFields = fields.filter((field) => (field.kind === "scalar" || field.kind === "enum") && field.isRequired && !field.hasDefaultValue && !field.isUpdatedAt && !copyFields.some((entry) => entry.field.name === field.name));
    plan.push({ model, sourceTable, targetTable, fields: copyFields, missingRequiredFields, count: Number(countRows[0].total) });
  }

  console.log(apply ? "Import MySQL → PostgreSQL (apply)" : "Simulation MySQL → PostgreSQL (aucune écriture)");
  for (const entry of plan) console.log(`${entry.sourceTable} → ${entry.targetTable}: ${entry.count} lignes, ${entry.fields.length} colonnes reconnues${entry.missingRequiredFields.length ? `, colonnes requises manquantes: ${entry.missingRequiredFields.map((field) => field.name).join(", ")}` : ""}`);
  console.log(`Total : ${plan.reduce((sum, entry) => sum + entry.count, 0)} lignes.`);
  if (!apply) {
    console.log("Relancez avec --apply uniquement après sauvegarde, validation du mapping et mise en maintenance de l’ancien site.");
    process.exitCode = 0;
  } else {
    if (plan.some((entry) => entry.missingRequiredFields.length > 0)) throw new Error("Mapping incomplet: des colonnes requises manquent. Import annulé.");
    for (const entry of plan) {
      const existing = await prisma.$queryRawUnsafe(`SELECT COUNT(*)::bigint AS total FROM ${quotePg(entry.targetTable)}`);
      if (entry.targetTable !== "roles" && BigInt(existing[0].total) !== 0n) throw new Error(`La table PostgreSQL ${entry.targetTable} n’est pas vide; import annulé pour éviter les doublons.`);
    }
    for (const entry of plan) {
      if (entry.count === 0 || entry.fields.length === 0) continue;
      const columns = entry.fields.map((field) => field.targetColumn);
      const insertHead = `INSERT INTO ${quotePg(entry.targetTable)} (${columns.map(quotePg).join(", ")}) VALUES `;
      const pageSize = 500;
      await prisma.$transaction(async (tx) => {
        for (let offset = 0; offset < entry.count; offset += pageSize) {
          const [rows] = await source.query(`SELECT ${entry.fields.map((field) => `${quoteMy(field.sourceColumn)} AS ${quoteMy(field.targetColumn)}`).join(", ")} FROM ${quoteMy(entry.sourceTable)} LIMIT ? OFFSET ?`, [pageSize, offset]);
          if (!rows.length) continue;
          const values = [];
          const placeholders = rows.map((row) => `(${entry.fields.map(({ field, targetColumn }) => {
            let value = row[targetColumn];
            if (value !== null && field.type === "BigInt") value = BigInt(value);
            else if (value !== null && field.type === "Boolean") value = Boolean(Number(value));
            else if (value !== null && field.type === "Int") value = Number(value);
            else if (value !== null && targetColumn === "email") value = String(value).trim().toLowerCase();
            values.push(value);
            return `$${values.length}`;
          }).join(", ")})`);
          const statement = `${insertHead}${placeholders.join(", ")} ON CONFLICT DO NOTHING`;
          await tx.$executeRawUnsafe(statement, ...values);
        }
        const idField = entry.model.fields.find((field) => field.isId && field.kind === "scalar" && field.type === "BigInt");
        if (idField) {
          const idColumn = idField.dbName ?? idField.name;
          await tx.$queryRaw(Prisma.sql`SELECT setval(pg_get_serial_sequence(${entry.targetTable}, ${idColumn}), COALESCE((SELECT MAX(${Prisma.raw(quotePg(idColumn))}) FROM ${Prisma.raw(quotePg(entry.targetTable))}), 1), EXISTS(SELECT 1 FROM ${Prisma.raw(quotePg(entry.targetTable))}))`);
        }
      }, { maxWait: 10_000, timeout: 600_000 });
      console.log(`Importé: ${entry.targetTable}`);
    }
    console.log("Import terminé. Vérifiez les volumes et les relations avant de rouvrir le site.");
    // Re-sync every imported serial after all table transactions have committed.
    // This prevents the next insert from reusing an ID present in the legacy data.
    for (const entry of plan) {
      const idField = entry.model.fields.find((field) => field.isId && field.kind === "scalar" && field.type === "BigInt");
      if (!idField) continue;
      const idColumn = idField.dbName ?? idField.name;
      const [sequence] = await prisma.$queryRaw(Prisma.sql`SELECT pg_get_serial_sequence(${entry.targetTable}, ${idColumn}) AS name`);
      if (typeof sequence?.name === "string") {
        await prisma.$queryRaw(Prisma.sql`SELECT setval(${sequence.name}::regclass, COALESCE((SELECT MAX(${Prisma.raw(quotePg(idColumn))}) FROM ${Prisma.raw(quotePg(entry.targetTable))}), 1), EXISTS(SELECT 1 FROM ${Prisma.raw(quotePg(entry.targetTable))}))`);
      }
    }
  }
} finally {
  await source.end();
  await prisma.$disconnect();
}

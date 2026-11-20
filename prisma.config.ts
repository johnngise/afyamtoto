import dotenv from "dotenv";
import type { PrismaConfig } from "prisma";
import { env } from "prisma/config";

dotenv.config({ path: ".env.local" });
dotenv.config();

export default {
  schema: "prisma/schema.prisma",
  migrations: {
    path: "prisma/migrations",
    seed: "npx tsx prisma/seed.ts",
  },
  datasource: {
    url: env("DATABASE_URL"),
  },
} satisfies PrismaConfig;
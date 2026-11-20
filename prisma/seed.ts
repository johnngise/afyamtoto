import dotenv from "dotenv";
dotenv.config({ path: ".env.local" });
dotenv.config();
import { PrismaClient } from "@prisma/client";
import { PrismaPg } from "@prisma/adapter-pg";
import { hash } from "bcryptjs";

const adapter = new PrismaPg({ connectionString: process.env.DATABASE_URL! });
const prisma = new PrismaClient({ adapter });

async function main() {
  const roles = [
    { code: "super_administrator", displayName: "Super Administrator" },
    { code: "administrator", displayName: "Administrator" },
    { code: "national_administrator", displayName: "National Administrator" },
    { code: "provincial_administrator", displayName: "Provincial Administrator" },
    { code: "health_zone_administrator", displayName: "Health Zone Administrator" },
    { code: "health_facility_administrator", displayName: "Health Facility Administrator" },
    { code: "health_professional", displayName: "Health Professional" },
    { code: "community_health_worker", displayName: "Community Health Worker" },
    { code: "health_center_manager", displayName: "Health Center Manager" },
    { code: "health_supervisor", displayName: "Health Supervisor" },
    { code: "health_data_analyst", displayName: "Health Data Analyst" },
    { code: "parent", displayName: "Parent" },
  ];
  for (const r of roles) {
    await prisma.role.upsert({ where: { code: r.code }, update: { displayName: r.displayName }, create: r });
  }

  const passwordHash = await hash("Admin@123456", 12);
  const admin = await prisma.user.upsert({
    where: { username: "admin" },
    update: {},
    create: {
      displayName: "Administrator",
      username: "admin",
      email: "admin@afyamtoto.ai",
      passwordHash,
      isActive: true,
    },
  });
  await prisma.userRole.upsert({    
    where: { userId_roleCode: { userId: admin.id, roleCode: "super_administrator" } },
    update: {},
    create: { userId: admin.id, roleCode: "super_administrator" },
  });
}

main()
  .then(() => prisma.$disconnect())
  .catch((e) => { console.error(e); prisma.$disconnect(); process.exit(1); });
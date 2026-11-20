import { PrismaClient } from '@prisma/client';
import { PrismaPg } from '@prisma/adapter-pg';

async function main() {
  const adapter = new PrismaPg({ connectionString: process.env.DATABASE_URL! });
  const prisma = new PrismaClient({ adapter, log: ['query', 'error', 'warn'] });

  try {
    const result = await prisma.$queryRaw`SELECT 1 as test`;
    console.log('Connection successful:', result);
  } catch (e) {
    console.error('Error:', e);
  }

  await prisma.$disconnect();
}

main();
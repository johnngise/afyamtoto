import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { contactSchema, CONTACT_SUBJECTS } from "@/lib/contact-validation";
import { Resend } from "resend";
import { z } from "zod";

const resend = process.env.RESEND_API_KEY ? new Resend(process.env.RESEND_API_KEY) : null;

const RATE_LIMIT_WINDOW_MS = parseInt(process.env.RATE_LIMIT_WINDOW_MS || "3600000", 10);
const RATE_LIMIT_MAX = parseInt(process.env.RATE_LIMIT_MAX_REQUESTS || "5", 10);
const HONEYPOT_FIELD = process.env.CONTACT_FORM_HONEYPOT || "website";

async function checkRateLimit(ip: string): Promise<boolean> {
  const since = new Date(Date.now() - RATE_LIMIT_WINDOW_MS);
  const count = await prisma.contactMessage.count({
    where: { ip, createdAt: { gte: since } },
  });
  return count < RATE_LIMIT_MAX;
}

function getClientIp(request: NextRequest): string {
  return request.headers.get("x-forwarded-for")?.split(",")[0]?.trim()
    || request.headers.get("x-real-ip")
    || "unknown";
}

async function sendNotificationEmail(data: z.infer<typeof contactSchema>, ip: string) {
  if (!resend || !process.env.CONTACT_NOTIFICATION_EMAIL) return;

  const subjectLabel = CONTACT_SUBJECTS.find(s => s.value === data.subject)?.label || data.subject;

  await resend.emails.send({
    from: process.env.AUTH_EMAIL_FROM || "AFYAMTOTO AI <noreply@afyamtoto.ai>",
    to: process.env.CONTACT_NOTIFICATION_EMAIL,
    subject: `[Contact] ${subjectLabel} — ${data.name}`,
    html: `
      <div style="font-family: system-ui, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #168a78;">Nouveau message de contact</h2>
        <table style="width: 100%; border-collapse: collapse;">
          <tr><td style="padding: 8px; font-weight: bold;">Nom</td><td style="padding: 8px;">${data.name}</td></tr>
          <tr><td style="padding: 8px; font-weight: bold;">Email</td><td style="padding: 8px;"><a href="mailto:${data.email}">${data.email}</a></td></tr>
          ${data.phone ? `<tr><td style="padding: 8px; font-weight: bold;">Téléphone</td><td style="padding: 8px;">${data.phone}</td></tr>` : ""}
          <tr><td style="padding: 8px; font-weight: bold;">Sujet</td><td style="padding: 8px;">${subjectLabel}</td></tr>
          <tr><td style="padding: 8px; font-weight: bold;">IP</td><td style="padding: 8px;">${ip}</td></tr>
        </table>
        <div style="margin-top: 16px; padding: 16px; background: #f5f5f5; border-radius: 8px; white-space: pre-wrap;">${data.message}</div>
        <p style="margin-top: 16px; font-size: 12px; color: #666;">Envoyé depuis le formulaire de contact AFYAMTOTO AI</p>
      </div>
    `,
  });
}

async function sendConfirmationEmail(data: z.infer<typeof contactSchema>) {
  if (!resend) return;

  await resend.emails.send({
    from: process.env.AUTH_EMAIL_FROM || "AFYAMTOTO AI <noreply@afyamtoto.ai>",
    to: data.email,
    subject: "Confirmation de votre message — AFYAMTOTO AI",
    html: `
      <div style="font-family: system-ui, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #168a78;">Merci de nous avoir contactés, ${data.name} !</h2>
        <p>Nous avons bien reçu votre message et nous vous répondrons dans les plus brefs délais.</p>
        <div style="margin: 16px 0; padding: 16px; background: #f5f5f5; border-radius: 8px;">
          <p style="margin: 0; font-weight: bold;">Récapitulatif :</p>
          <p style="margin: 8px 0 0;"><strong>Sujet :</strong> ${CONTACT_SUBJECTS.find(s => s.value === data.subject)?.label || data.subject}</p>
        </div>
        <p>Cordialement,<br>L'équipe AFYAMTOTO AI</p>
        <hr style="margin: 24px 0; border-color: #e8ece8;">
        <p style="font-size: 12px; color: #666;">AFYAMTOTO AI — Plateforme de suivi de la santé des enfants de 0 à 5 ans en RDC</p>
      </div>
    `,
  });
}

export async function POST(request: NextRequest) {
  const ip = getClientIp(request);

  // Rate limiting
  const allowed = await checkRateLimit(ip);
  if (!allowed) {
    return NextResponse.json(
      { error: "Trop de tentatives. Veuillez réessayer plus tard." },
      { status: 429 }
    );
  }

  let body: unknown;
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ error: "Corps JSON invalide." }, { status: 400 });
  }

  // Honeypot check
  if (body && typeof body === "object" && HONEYPOT_FIELD in body && (body as Record<string, unknown>)[HONEYPOT_FIELD]) {
    return NextResponse.json({ success: true }); // silently succeed
  }

  const result = contactSchema.safeParse(body);
  if (!result.success) {
    const errors = result.error.flatten().fieldErrors;
    return NextResponse.json({ error: "Validation échouée", details: errors }, { status: 400 });
  }

  const data = result.data;

  try {
    // Save to database
    await prisma.contactMessage.create({
      data: {
        name: data.name,
        email: data.email,
        phone: data.phone || null,
        subject: data.subject,
        message: data.message,
        ip,
      },
    });

    // Send emails (non-blocking)
    await Promise.allSettled([
      sendNotificationEmail(data, ip),
      sendConfirmationEmail(data),
    ]);

    return NextResponse.json({ success: true, message: "Votre message a été envoyé avec succès." });
  } catch (error) {
    console.error("Contact form error:", error);
    return NextResponse.json({ error: "Erreur serveur. Veuillez réessayer plus tard." }, { status: 500 });
  }
}
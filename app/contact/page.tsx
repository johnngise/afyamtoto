import type { Metadata } from "next";
import { ContactHero } from "@/app/components/contact/ContactHero";
import { ContactForm } from "@/app/components/contact/ContactForm";
import { ContactInfo } from "@/app/components/contact/ContactInfo";
import { ContactMap } from "@/app/components/contact/ContactMap";
import { ContactFAQ } from "@/app/components/contact/ContactFAQ";
import { ContactCTA } from "@/app/components/contact/ContactCTA";

export const metadata: Metadata = {
  title: "Contactez-nous — AFYAMTOTO AI",
  description:
    "Contactez l'équipe AFYAMTOTO AI pour vos questions, suggestions ou partenariats. Nous sommes basés à Kinshasa, RDC, et disponibles par email, téléphone et WhatsApp.",
  openGraph: {
    title: "Contactez-nous — AFYAMTOTO AI",
    description:
      "Contactez l'équipe AFYAMTOTO AI pour vos questions, suggestions ou partenariats.",
    type: "website",
    locale: "fr_CD",
    siteName: "AFYAMTOTO AI",
  },
};

export default function ContactPage() {
  return (
    <main className="contact-page">
      <ContactHero />

      <section className="contact-main" aria-labelledby="contact-main-heading">
        <div className="contact-main__container">
          <div className="contact-main__grid">
            <ContactInfo />
            <div className="contact-form-section">
              <h2 id="contact-main-heading" className="contact-form-section__heading">
                Envoyez-nous un message
              </h2>
              <ContactForm />
            </div>
          </div>
        </div>
      </section>

      <ContactMap />
      <ContactFAQ />
      <ContactCTA />
    </main>
  );
}
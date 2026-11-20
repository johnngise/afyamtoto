"use client";

import { MapPin, Mail, Phone, Clock, MessageSquare, Link, Users } from "lucide-react";

interface ContactInfoItem {
  icon: React.ReactNode;
  title: string;
  content: React.ReactNode;
}

const infoItems: ContactInfoItem[] = [
  {
    icon: <MapPin className="contact-info__icon-svg" aria-hidden="true" />,
    title: "Adresse",
    content: (
      <address className="contact-info__address">
        <p>Kinshasa, République Démocratique du Congo</p>
        <p>Quartier Gombe, Avenue de la Justice</p>
      </address>
    ),
  },
  {
    icon: <Mail className="contact-info__icon-svg" aria-hidden="true" />,
    title: "Email",
    content: (
      <div>
        <p><a href="mailto:contact@afyamtoto.ai" className="contact-info__link">contact@afyamtoto.ai</a></p>
        <p><a href="mailto:support@afyamtoto.ai" className="contact-info__link">support@afyamtoto.ai</a></p>
      </div>
    ),
  },
  {
    icon: <Phone className="contact-info__icon-svg" aria-hidden="true" />,
    title: "Téléphone / WhatsApp",
    content: (
      <div>
        <p><a href="tel:+243995772320" className="contact-info__link">+243 99 57 72 320</a></p>
        <p>
          <a href="https://wa.me/243995772320" target="_blank" rel="noopener noreferrer" className="contact-info__link contact-info__link--whatsapp">
            <MessageSquare className="contact-info__icon-svg contact-info__icon-svg--inline" aria-hidden="true" />
            WhatsApp disponible
          </a>
        </p>
      </div>
    ),
  },
  {
    icon: <Clock className="contact-info__icon-svg" aria-hidden="true" />,
    title: "Horaires d'ouverture",
    content: (
      <div>
        <p>Lundi – Vendredi : 8h00 – 17h00</p>
        <p>Samedi : 9h00 – 13h00</p>
        <p>Dimanche : Fermé</p>
      </div>
    ),
  },
];

const socialLinks = [
  { href: "https://twitter.com/afyamtoto", icon: <MessageSquare className="contact-social__icon" aria-hidden="true" />, label: "Twitter" },
  { href: "https://linkedin.com/company/afyamtoto", icon: <Link className="contact-social__icon" aria-hidden="true" />, label: "LinkedIn" },
  { href: "https://facebook.com/afyamtoto", icon: <Users className="contact-social__icon" aria-hidden="true" />, label: "Facebook" },
];

export function ContactInfo() {
  return (
    <aside className="contact-info" aria-labelledby="contact-info-heading">
      <h2 id="contact-info-heading" className="contact-info__heading">Nos coordonnées</h2>

      <div className="contact-info__cards">
        {infoItems.map((item, index) => (
          <article key={index} className="contact-info__card">
            <div className="contact-info__icon-wrapper" aria-hidden="true">
              {item.icon}
            </div>
            <div className="contact-info__content">
              <h3 className="contact-info__title">{item.title}</h3>
              <div className="contact-info__details">{item.content}</div>
            </div>
          </article>
        ))}
      </div>

      <div className="contact-social" aria-label="Réseaux sociaux">
        {socialLinks.map((social, index) => (
          <a
            key={index}
            href={social.href}
            target="_blank"
            rel="noopener noreferrer"
            className="contact-social__link"
            aria-label={social.label}
          >
            {social.icon}
          </a>
        ))}
      </div>
    </aside>
  );
}
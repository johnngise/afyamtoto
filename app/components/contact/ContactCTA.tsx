"use client";

import { MessageSquare, ArrowRight } from "lucide-react";
import Link from "next/link";

export function ContactCTA() {
  return (
    <section className="contact-cta" aria-labelledby="contact-cta-heading">
      <div className="contact-cta__container">
        <div className="contact-cta__content">
          <div className="contact-cta__icon" aria-hidden="true">
            <MessageSquare className="contact-cta__icon-svg" />
          </div>
          <div className="contact-cta__text">
            <h2 id="contact-cta-heading" className="contact-cta__heading">Une question urgente ?</h2>
            <p className="contact-cta__description">
              Discutez directement avec notre équipe via WhatsApp pour une réponse rapide.
            </p>
          </div>
        </div>
        <a
          href="https://wa.me/243995772320"
          target="_blank"
          rel="noopener noreferrer"
          className="contact-cta__button"
        >
          <span className="contact-cta__button-text">Écrire sur WhatsApp</span>
          <ArrowRight className="contact-cta__arrow" aria-hidden="true" />
        </a>
      </div>
    </section>
  );
}
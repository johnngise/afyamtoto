"use client";

import { useState } from "react";
import { ChevronDown } from "lucide-react";

interface FAQItem {
  question: string;
  answer: string;
}

const faqItems: FAQItem[] = [
  {
    question: "Quels sont les délais de réponse ?",
    answer: "Nous répondons généralement sous 24 à 48 heures ouvrées. Pour les urgences techniques, merci de le préciser dans l'objet du message.",
  },
  {
    question: "Comment devenir partenaire d'AFYAMTOTO AI ?",
    answer: "Nous sommes ouverts aux partenariats avec les ONG, structures de santé, institutions publiques et entreprises. Contactez-nous via le formulaire en sélectionnant « Partenariat / Collaboration ».",
  },
  {
    question: "La plateforme est-elle accessible hors ligne ?",
    answer: "Certaines fonctionnalités sont disponibles hors connexion (consultation des données déjà synchronisées). La synchronisation complète nécessite une connexion internet.",
  },
  {
    question: "Comment signaler un bug ou un problème de sécurité ?",
    answer: "Utilisez le formulaire avec le sujet « Support technique » ou envoyez un email direct à security@afyamtoto.ai pour les questions de sécurité.",
  },
  {
    question: "Puis-je demander une démonstration de la plateforme ?",
    answer: "Absolument ! Sélectionnez « Partenariat / Collaboration » ou « Question générale » et précisez votre demande. Nous organisons des démos personnalisées selon vos besoins.",
  },
];

export function ContactFAQ() {
  const [openIndex, setOpenIndex] = useState<number | null>(0);

  const toggleFAQ = (index: number) => {
    setOpenIndex(prev => (prev === index ? null : index));
  };

  return (
    <section className="contact-faq" aria-labelledby="contact-faq-heading">
      <div className="contact-faq__container">
        <h2 id="contact-faq-heading" className="contact-faq__heading">Questions fréquentes</h2>
        <div className="contact-faq__list">
          {faqItems.map((item, index) => (
            <details
              key={index}
              className={`contact-faq__item ${openIndex === index ? "contact-faq__item--open" : ""}`}
              onToggle={() => toggleFAQ(index)}
            >
              <summary className="contact-faq__summary">
                <span className="contact-faq__question">{item.question}</span>
                <ChevronDown className="contact-faq__chevron" aria-hidden="true" />
              </summary>
              <div className="contact-faq__answer">
                <p>{item.answer}</p>
              </div>
            </details>
          ))}
        </div>
      </div>
    </section>
  );
}
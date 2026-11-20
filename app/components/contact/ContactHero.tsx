"use client";

export function ContactHero() {
  return (
    <section className="contact-hero" aria-labelledby="contact-hero-heading">
      <div className="contact-hero__container">
        <div className="contact-hero__content">
          <h1 id="contact-hero-heading" className="contact-hero__title">Contactez-nous</h1>
          <p className="contact-hero__subtitle">
            Vous avez une question, une suggestion ou souhaitez collaborer ?
            Nous serions ravis d'échanger avec vous.
          </p>
        </div>
        <div className="contact-hero__decor" aria-hidden="true">
          <div className="contact-hero__shape contact-hero__shape--1"></div>
          <div className="contact-hero__shape contact-hero__shape--2"></div>
          <div className="contact-hero__shape contact-hero__shape--3"></div>
        </div>
      </div>
    </section>
  );
}
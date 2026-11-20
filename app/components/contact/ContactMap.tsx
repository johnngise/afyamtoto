"use client";

export function ContactMap() {
  return (
    <section className="contact-map" aria-labelledby="contact-map-heading">
      <div className="contact-map__container">
        <h2 id="contact-map-heading" className="contact-map__heading">Notre localisation</h2>
        <div className="contact-map__wrapper">
          <iframe
            title="Localisation AFYAMTOTO AI à Kinshasa"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3982.5!2d15.2663!3d-4.3224!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1a7d7b3e8b3e8b3e%3A0x3e8b3e8b3e8b3e8b!2sKinshasa!5e0!3m2!1sfr!2scd!4v1234567890"
            width="100%"
            height="400"
            style={{ border: 0 }}
            allowFullScreen
            loading="lazy"
            referrerPolicy="no-referrer-when-downgrade"
          ></iframe>
        </div>
      </div>
    </section>
  );
}
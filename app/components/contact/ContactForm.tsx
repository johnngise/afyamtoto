"use client";

import { useState, FormEvent, ChangeEvent } from "react";
import { Loader2, CheckCircle, AlertCircle } from "lucide-react";
import { CONTACT_SUBJECTS } from "@/lib/contact-validation";

interface FormData {
  name: string;
  email: string;
  phone: string;
  subject: string;
  message: string;
  website: string;
}

interface FormErrors {
  name?: string;
  email?: string;
  phone?: string;
  subject?: string;
  message?: string;
}

interface Toast {
  type: "success" | "error";
  message: string;
}

interface ApiResponse {
  success: boolean;
  message?: string;
  error?: string;
  details?: FormErrors;
}

export function ContactForm() {
  const [formData, setFormData] = useState<FormData>({
    name: "",
    email: "",
    phone: "",
    subject: "",
    message: "",
    website: "",
  });
  const [errors, setErrors] = useState<FormErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [toast, setToast] = useState<Toast | null>(null);
  const [touched, setTouched] = useState<Record<string, boolean>>({});

  const validateField = (name: string, value: string): string | undefined => {
    switch (name) {
      case "name":
        if (!value.trim()) return "Le nom est requis";
        if (value.trim().length < 2) return "Le nom doit contenir au moins 2 caractères";
        if (value.length > 160) return "Le nom est trop long";
        break;
      case "email":
        if (!value.trim()) return "L'email est requis";
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return "Adresse email invalide";
        if (value.length > 254) return "L'email est trop long";
        break;
      case "phone":
        if (value && value.length > 32) return "Le numéro de téléphone est trop long";
        break;
      case "subject":
        if (!value) return "Veuillez sélectionner un sujet";
        break;
      case "message":
        if (!value.trim()) return "Le message est requis";
        if (value.trim().length < 20) return "Le message doit contenir au moins 20 caractères";
        if (value.length > 5000) return "Le message est trop long";
        break;
    }
    return undefined;
  };

  const handleChange = (e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    if (touched[name]) {
      const error = validateField(name, value);
      setErrors(prev => ({ ...prev, [name]: error }));
    }
  };

  const handleBlur = (e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setTouched(prev => ({ ...prev, [name]: true }));
    const error = validateField(name, value);
    setErrors(prev => ({ ...prev, [name]: error }));
  };

  const validateAll = (): boolean => {
    const newErrors: FormErrors = {};
    let isValid = true;
    (Object.keys(formData) as Array<keyof FormData>).forEach(key => {
      if (key !== "website") {
        const error = validateField(key, formData[key]);
        if (error) {
          newErrors[key] = error;
          isValid = false;
        }
      }
    });
    setErrors(newErrors);
    setTouched({ name: true, email: true, phone: true, subject: true, message: true });
    return isValid;
  };

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!validateAll()) return;

    setIsSubmitting(true);
    setToast(null);

    try {
      const response = await fetch("/api/contact", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });

      const result: ApiResponse = await response.json();

      if (response.ok && result.success) {
        setToast({ type: "success", message: result.message || "Votre message a été envoyé avec succès." });
        setFormData({ name: "", email: "", phone: "", subject: "", message: "", website: "" });
        setTouched({});
      } else {
        if (result.details) {
          setErrors(result.details);
        }
        setToast({ type: "error", message: result.error || "Une erreur est survenue. Veuillez réessayer." });
      }
    } catch {
      setToast({ type: "error", message: "Erreur de connexion. Veuillez réessayer plus tard." });
    } finally {
      setIsSubmitting(false);
    }
  };

  const hasError = (field: keyof FormErrors) => errors[field] && touched[field];

  return (
    <form onSubmit={handleSubmit} noValidate className="contact-form">
      <input
        type="hidden"
        name="website"
        value={formData.website}
        onChange={handleChange}
        tabIndex={-1}
        autoComplete="off"
        style={{ display: "none" }}
        aria-hidden="true"
      />

      {toast && (
        <div
          className={`contact-toast contact-toast--${toast.type}`}
          role="alert"
          aria-live="polite"
        >
          <div className="contact-toast__icon">
            {toast.type === "success" ? (
              <CheckCircle className="contact-toast__icon--success" aria-hidden="true" />
            ) : (
              <AlertCircle className="contact-toast__icon--error" aria-hidden="true" />
            )}
          </div>
          <p className="contact-toast__message">{toast.message}</p>
          <button
            type="button"
            className="contact-toast__close"
            onClick={() => setToast(null)}
            aria-label="Fermer"
          >
            <span aria-hidden="true">×</span>
          </button>
        </div>
      )}

      <div className="contact-form__group">
        <label htmlFor="name" className="contact-form__label">
          Nom complet <span className="contact-form__required" aria-hidden="true">*</span>
        </label>
        <div className="contact-form__input-wrapper">
          <input
            type="text"
            id="name"
            name="name"
            value={formData.name}
            onChange={handleChange}
            onBlur={handleBlur}
            placeholder="Votre nom complet"
            className={`contact-form__input ${hasError("name") ? "contact-form__input--error" : ""}`}
            aria-invalid={hasError("name") ? "true" : "false"}
            aria-describedby={hasError("name") ? "name-error" : undefined}
            disabled={isSubmitting}
            required
            minLength={2}
            maxLength={160}
            autoComplete="name"
          />
        </div>
        {hasError("name") && (
          <p id="name-error" className="contact-form__error" role="alert">{errors.name}</p>
        )}
      </div>

      <div className="contact-form__group">
        <label htmlFor="email" className="contact-form__label">
          Email <span className="contact-form__required" aria-hidden="true">*</span>
        </label>
        <div className="contact-form__input-wrapper">
          <input
            type="email"
            id="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            onBlur={handleBlur}
            placeholder="votre@email.com"
            className={`contact-form__input ${hasError("email") ? "contact-form__input--error" : ""}`}
            aria-invalid={hasError("email") ? "true" : "false"}
            aria-describedby={hasError("email") ? "email-error" : undefined}
            disabled={isSubmitting}
            required
            maxLength={254}
            autoComplete="email"
          />
        </div>
        {hasError("email") && (
          <p id="email-error" className="contact-form__error" role="alert">{errors.email}</p>
        )}
      </div>

      <div className="contact-form__group">
        <label htmlFor="phone" className="contact-form__label">
          Téléphone / WhatsApp
        </label>
        <div className="contact-form__input-wrapper">
          <input
            type="tel"
            id="phone"
            name="phone"
            value={formData.phone}
            onChange={handleChange}
            onBlur={handleBlur}
            placeholder="+243 99 00 00 00"
            className={`contact-form__input ${hasError("phone") ? "contact-form__input--error" : ""}`}
            aria-invalid={hasError("phone") ? "true" : "false"}
            aria-describedby={hasError("phone") ? "phone-error" : undefined}
            disabled={isSubmitting}
            maxLength={32}
            autoComplete="tel"
          />
        </div>
        {hasError("phone") && (
          <p id="phone-error" className="contact-form__error" role="alert">{errors.phone}</p>
        )}
      </div>

      <div className="contact-form__group">
        <label htmlFor="subject" className="contact-form__label">
          Sujet <span className="contact-form__required" aria-hidden="true">*</span>
        </label>
        <div className="contact-form__select-wrapper">
          <select
            id="subject"
            name="subject"
            value={formData.subject}
            onChange={handleChange}
            onBlur={handleBlur}
            className={`contact-form__select ${hasError("subject") ? "contact-form__select--error" : ""}`}
            aria-invalid={hasError("subject") ? "true" : "false"}
            aria-describedby={hasError("subject") ? "subject-error" : undefined}
            disabled={isSubmitting}
            required
          >
            <option value="">— Choisir un sujet —</option>
            {CONTACT_SUBJECTS.map(opt => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
        </div>
        {hasError("subject") && (
          <p id="subject-error" className="contact-form__error" role="alert">{errors.subject}</p>
        )}
      </div>

      <div className="contact-form__group">
        <label htmlFor="message" className="contact-form__label">
          Message <span className="contact-form__required" aria-hidden="true">*</span>
        </label>
        <div className="contact-form__input-wrapper">
          <textarea
            id="message"
            name="message"
            value={formData.message}
            onChange={handleChange}
            onBlur={handleBlur}
            placeholder="Décrivez votre demande..."
            rows={6}
            className={`contact-form__textarea ${hasError("message") ? "contact-form__textarea--error" : ""}`}
            aria-invalid={hasError("message") ? "true" : "false"}
            aria-describedby={hasError("message") ? "message-error" : "message-hint"}
            disabled={isSubmitting}
            required
            minLength={20}
            maxLength={5000}
          />
        </div>
        <p id="message-hint" className="contact-form__hint">Minimum 20 caractères</p>
        {hasError("message") && (
          <p id="message-error" className="contact-form__error" role="alert">{errors.message}</p>
        )}
      </div>

      <button
        type="submit"
        className="contact-form__submit"
        disabled={isSubmitting}
      >
        {isSubmitting ? (
          <>
            <Loader2 className="contact-form__spinner" aria-hidden="true" />
            Envoi en cours...
          </>
        ) : (
          "Envoyer le message"
        )}
      </button>
    </form>
  );
}
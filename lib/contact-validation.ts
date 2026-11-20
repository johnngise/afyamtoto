import { z } from "zod";

export const contactSchema = z.object({
  name: z.string().min(2, "Le nom doit contenir au moins 2 caractères").max(160, "Le nom est trop long"),
  email: z.string().email("Adresse email invalide").max(254, "L'email est trop long"),
  phone: z.string().max(32, "Le numéro de téléphone est trop long").optional().or(z.literal("")),
  subject: z.string().min(1, "Veuillez sélectionner un sujet").max(120, "Le sujet est trop long"),
  message: z.string().min(20, "Le message doit contenir au moins 20 caractères").max(5000, "Le message est trop long"),
  website: z.string().optional(), // honeypot field
});

export type ContactFormData = z.infer<typeof contactSchema>;

export const CONTACT_SUBJECTS = [
  { value: "general", label: "Question générale" },
  { value: "technical", label: "Support technique" },
  { value: "partnership", label: "Partenariat / Collaboration" },
  { value: "press", label: "Presse / Médias" },
  { value: "other", label: "Autre" },
] as const;
export const ROLE_PERMISSION_GRANTS = {
  super_administrator: [
    "ADMIN_DASHBOARD_VIEW", "USER_VIEW", "USER_CREATE", "USER_UPDATE", "USER_SUSPEND",
    "ROLE_MANAGE", "PERMISSION_MANAGE", "FACILITY_VIEW", "FACILITY_CREATE", "FACILITY_UPDATE",
    "FACILITY_ASSIGN", "CAPABILITY_MANAGE", "CONTENT_MANAGE", "PLATFORM_SETTINGS_MANAGE", "AUDIT_VIEW",
    "REPORT_VIEW", "REPORT_EXPORT", "AGGREGATE_REPORT_VIEW", "ACCESS_REQUEST_REVIEW",
  ],
  administrator: [
    "ADMIN_DASHBOARD_VIEW", "USER_VIEW", "USER_CREATE", "USER_UPDATE", "USER_SUSPEND",
    "FACILITY_VIEW", "FACILITY_CREATE", "FACILITY_UPDATE", "FACILITY_ASSIGN", "CONTENT_MANAGE",
    "PLATFORM_SETTINGS_MANAGE", "AUDIT_VIEW", "REPORT_VIEW", "REPORT_EXPORT", "AGGREGATE_REPORT_VIEW",
  ],
  national_administrator: [
    "USER_VIEW", "USER_CREATE", "USER_UPDATE", "USER_SUSPEND",
    "FACILITY_VIEW", "FACILITY_CREATE", "FACILITY_UPDATE", "FACILITY_ASSIGN", "REPORT_VIEW",
    "REPORT_EXPORT", "AGGREGATE_REPORT_VIEW", "ACCESS_REQUEST_REVIEW",
  ],
  provincial_administrator: [
    "USER_VIEW", "USER_CREATE", "USER_UPDATE", "USER_SUSPEND",
    "FACILITY_VIEW", "FACILITY_CREATE", "FACILITY_UPDATE", "FACILITY_ASSIGN", "REPORT_VIEW", "AGGREGATE_REPORT_VIEW",
  ],
  health_zone_administrator: [
    "USER_VIEW", "USER_CREATE", "USER_UPDATE", "USER_SUSPEND",
    "FACILITY_VIEW", "FACILITY_CREATE", "FACILITY_UPDATE", "FACILITY_ASSIGN", "REPORT_VIEW", "AGGREGATE_REPORT_VIEW",
  ],
  health_facility_administrator: [
    "USER_VIEW", "USER_CREATE", "USER_UPDATE", "FACILITY_VIEW",
    "FACILITY_UPDATE", "FACILITY_ASSIGN", "APPOINTMENT_VIEW", "APPOINTMENT_MANAGE", "REFERRAL_FOLLOW_UP", "AGGREGATE_REPORT_VIEW",
  ],
  health_professional: [
    "CHILD_VIEW", "GROWTH_VIEW", "GROWTH_CREATE", "GROWTH_UPDATE", "GROWTH_VALIDATE",
    "VACCINATION_VIEW", "VACCINATION_CREATE", "VACCINATION_UPDATE", "VACCINATION_VALIDATE",
    "MEDICAL_RECORD_VIEW", "MEDICAL_RECORD_CREATE", "MEDICAL_RECORD_UPDATE", "HEALTH_ALERT_VIEW",
    "HEALTH_ALERT_CREATE", "HEALTH_ALERT_RESOLVE", "APPOINTMENT_VIEW", "APPOINTMENT_MANAGE",
    "REFERRAL_VIEW", "REFERRAL_FOLLOW_UP", "NUTRITION_RECORD_CREATE", "REPORT_VIEW", "NOTIFICATION_VIEW", "NOTIFICATION_UPDATE",
  ],
  community_health_worker: [
    "CHILD_VIEW", "CHILD_CREATE", "GROWTH_VIEW", "GROWTH_CREATE", "VACCINATION_VIEW",
    "VACCINATION_CREATE", "NUTRITION_RECORD_CREATE", "HEALTH_ALERT_CREATE", "COMMUNITY_VISIT_CREATE", "REFERRAL_CREATE",
    "REFERRAL_VIEW", "REFERRAL_FOLLOW_UP", "APPOINTMENT_VIEW", "AGGREGATE_REPORT_VIEW", "NOTIFICATION_VIEW", "NOTIFICATION_UPDATE",
  ],
  parent: [
    "CHILD_VIEW", "CHILD_CREATE", "CHILD_UPDATE", "CHILD_ARCHIVE", "GROWTH_VIEW", "GROWTH_CREATE",
    "VACCINATION_VIEW", "APPOINTMENT_VIEW", "APPOINTMENT_CREATE", "NOTIFICATION_VIEW", "NOTIFICATION_UPDATE",
  ],
  health_data_analyst: ["AGGREGATE_REPORT_VIEW", "REPORT_VIEW", "REPORT_EXPORT"],
  health_center_manager: [
    "USER_VIEW", "USER_CREATE", "USER_UPDATE", "FACILITY_VIEW", "FACILITY_UPDATE",
    "FACILITY_ASSIGN", "APPOINTMENT_VIEW", "APPOINTMENT_MANAGE", "REFERRAL_FOLLOW_UP", "AGGREGATE_REPORT_VIEW",
  ],
  health_supervisor: ["FACILITY_VIEW", "REFERRAL_VIEW", "REPORT_VIEW", "AGGREGATE_REPORT_VIEW"],
} as const satisfies Record<string, readonly string[]>;

export const PERMISSION_CATALOG = {
  ADMIN_DASHBOARD_VIEW: "Consulter le tableau de bord administratif autorisé.",
  USER_VIEW: "Consulter les comptes dans le périmètre autorisé.",
  USER_CREATE: "Créer un compte dans le périmètre autorisé.",
  USER_UPDATE: "Modifier un compte dans le périmètre autorisé.",
  USER_SUSPEND: "Suspendre ou réactiver un compte dans le périmètre autorisé.",
  ROLE_MANAGE: "Attribuer les rôles autorisés.",
  PERMISSION_MANAGE: "Configurer les permissions de la plateforme.",
  CAPABILITY_MANAGE: "Accorder ou retirer les qualifications opérationnelles vérifiées.",
  FACILITY_VIEW: "Consulter les structures de santé autorisées.",
  FACILITY_CREATE: "Créer une structure de santé.",
  FACILITY_UPDATE: "Modifier une structure de santé autorisée.",
  FACILITY_ASSIGN: "Gérer les affectations de structures.",
  CONTENT_MANAGE: "Gérer les références et contenus de la plateforme.",
  PLATFORM_SETTINGS_MANAGE: "Modifier les paramètres de la plateforme.",
  AUDIT_VIEW: "Consulter les événements d'audit autorisés.",
  REPORT_VIEW: "Consulter les rapports autorisés.",
  REPORT_EXPORT: "Exporter les rapports explicitement autorisés.",
  AGGREGATE_REPORT_VIEW: "Consulter des statistiques agrégées dans le périmètre autorisé.",
  ACCESS_REQUEST_REVIEW: "Examiner les demandes exceptionnelles d'accès.",
  CHILD_VIEW: "Consulter les informations d'un enfant autorisé.",
  CHILD_CREATE: "Créer un dossier enfant selon le profil et le périmètre.",
  CHILD_UPDATE: "Modifier les informations autorisées d'un enfant.",
  CHILD_ARCHIVE: "Archiver un dossier enfant selon la politique de conservation.",
  GROWTH_VIEW: "Consulter les mesures de croissance autorisées.",
  GROWTH_CREATE: "Enregistrer une mesure de croissance autorisée.",
  GROWTH_UPDATE: "Corriger une mesure avec traçabilité.",
  GROWTH_VALIDATE: "Valider une mesure selon la spécialité et les règles cliniques.",
  VACCINATION_VIEW: "Consulter les vaccinations autorisées.",
  VACCINATION_CREATE: "Enregistrer une vaccination selon les compétences autorisées.",
  VACCINATION_UPDATE: "Corriger une vaccination avec traçabilité.",
  VACCINATION_VALIDATE: "Valider une vaccination selon les compétences autorisées.",
  NUTRITION_RECORD_CREATE: "Enregistrer un dépistage nutritionnel autorisé.",
  MEDICAL_RECORD_VIEW: "Consulter un dossier médical autorisé.",
  MEDICAL_RECORD_CREATE: "Créer une entrée médicale autorisée.",
  MEDICAL_RECORD_UPDATE: "Corriger une entrée médicale avec traçabilité.",
  HEALTH_ALERT_VIEW: "Consulter les alertes sanitaires autorisées.",
  HEALTH_ALERT_CREATE: "Signaler une alerte sanitaire.",
  HEALTH_ALERT_RESOLVE: "Examiner et résoudre une alerte selon les compétences autorisées.",
  APPOINTMENT_VIEW: "Consulter les rendez-vous autorisés.",
  APPOINTMENT_CREATE: "Demander un rendez-vous pour un enfant autorisé.",
  APPOINTMENT_MANAGE: "Traiter les rendez-vous de structures autorisées.",
  COMMUNITY_VISIT_CREATE: "Enregistrer une visite communautaire autorisée.",
  REFERRAL_VIEW: "Consulter les orientations autorisées.",
  REFERRAL_CREATE: "Créer une orientation vers une structure autorisée.",
  REFERRAL_FOLLOW_UP: "Documenter le suivi d'une orientation autorisée.",
  NOTIFICATION_VIEW: "Consulter ses propres notifications.",
  NOTIFICATION_UPDATE: "Modifier l'état de ses propres notifications.",
} as const;

export type PermissionCode = keyof typeof PERMISSION_CATALOG;
export type ResourceScopeType = "child" | "health_center" | "health_zone" | "province";

export function mayAssignRole(actorRoles: readonly string[], targetRole: string): boolean {
  if (!Object.hasOwn(ROLE_PERMISSION_GRANTS, targetRole)) return false;
  if (targetRole === "administrator") return false;
  if (targetRole === "super_administrator") return actorRoles.includes("super_administrator");
  if (actorRoles.includes("super_administrator")) return true;
  const assignableByRole: Record<string, readonly string[]> = {
    national_administrator: ["provincial_administrator", "health_zone_administrator", "health_facility_administrator", "health_professional", "community_health_worker", "health_data_analyst"],
    provincial_administrator: ["health_zone_administrator", "health_facility_administrator", "health_professional", "community_health_worker", "health_data_analyst"],
    health_zone_administrator: ["health_facility_administrator", "health_professional", "community_health_worker"],
    health_facility_administrator: ["health_professional", "community_health_worker"],
    health_center_manager: ["health_professional", "community_health_worker"],
  };
  return actorRoles.some((role) => assignableByRole[role]?.includes(targetRole) ?? false);
}

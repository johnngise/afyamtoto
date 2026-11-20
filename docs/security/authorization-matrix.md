# Matrice d'autorisation

Légende : **oui** = accord explicite au rôle; **conditionnel** = permission, périmètre et contexte nécessaires; **non** = aucun octroi. Cette matrice est le catalogue `lib/rbac-catalog.ts`, complété par les contrôles ABAC.

| Acteur | Administration/comptes | Structures | Données cliniques | Rapports |
|---|---|---|---|---|
| Super administrateur | Oui, global | Oui, global | Non par défaut; accès exceptionnel à tracer | Oui |
| National | Oui, politiques nationales | Oui, périmètre national | Non | Agrégés nationaux |
| Provincial | Oui, périmètre provincial | Oui, provinces affectées | Non par défaut | Agrégés provinciaux |
| Zone de santé | Oui, périmètre de zone | Oui, zones affectées | Non par défaut | Agrégés de zone |
| Administration d'établissement | Comptes de son centre | Son centre | Non par défaut | Agrégés du centre |
| Professionnel | Non | Affectations | Conditionnel : spécialité, affectation, consentement | Rapports autorisés |
| Agent communautaire | Non | Affectations | Collecte permise par qualification valide; pas de diagnostic | Agrégés autorisés |
| Parent/tuteur | Non | Non | Ses enfants; relation et consentement | Non |
| Analyste | Non | Non | Non individuel | Agrégés anonymisés; seuil 5 |

Catalogue par domaine: gestion des comptes `USER_*`; rôles et privilèges `ROLE_MANAGE`, `PERMISSION_MANAGE`; structures `FACILITY_*`; enfant `CHILD_*`; croissance `GROWTH_*`; vaccination `VACCINATION_*`; dossier médical `MEDICAL_RECORD_*`; alertes `HEALTH_ALERT_*`; rendez-vous `APPOINTMENT_*`; nutrition `NUTRITION_RECORD_CREATE`; rapports `REPORT_*`/`AGGREGATE_REPORT_VIEW`; audit `AUDIT_VIEW`; consentement et demandes d'accès utilisent les modèles dédiés.

Le détail exécutable (permissions attribuées à chaque rôle) est `ROLE_PERMISSION_GRANTS` dans `lib/rbac-catalog.ts`; toute permission non listée pour un rôle est refusée.

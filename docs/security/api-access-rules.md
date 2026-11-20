# Règles d'accès des API

Les Route Handlers de `app/api/v1` utilisent la session serveur. Les endpoints d'authentification (login, logout, inscription publique, initialisation/récupération administrateur) sont les exceptions attendues et appliquent leurs propres contrôles. Les autres handlers doivent appeler `authorize` avant une lecture ou écriture protégée.

| Ressource | Permission courante | Contrôle ABAC |
|---|---|---|
| Enfants | `CHILD_VIEW`, `CHILD_CREATE`, `CHILD_UPDATE`, `CHILD_ARCHIVE` | Parent lié + consentement; professionnel/agent affecté |
| Croissance | `GROWTH_*` | Enfant, consentement, affectation, qualification/spécialité |
| Vaccinations | `VACCINATION_*` | Enfant, centre, consentement, qualification/spécialité |
| Alertes | `HEALTH_ALERT_*` | Centre et dossier autorisés |
| Rendez-vous | `APPOINTMENT_*` | Parent de l'enfant ou centre attribué |
| Orientations | `REFERRAL_*` | Centre, enfant et consentement |
| Utilisateurs | `USER_*` | Rôle attribuable et périmètre géographique |
| Structures | `FACILITY_*` | Province/zone/centre de l'acteur |
| Dashboard | permission liée au rôle | Seulement champs nécessaires; agrégats masqués sous le seuil |

401 indique l'absence de session valide; 403 l'absence de permission ou de portée; 404 masque les ressources non accessibles lorsque cela évite une divulgation. Les nouvelles API et mutations serveur doivent valider les corps, appliquer `authorize`, retourner des champs minimaux et tracer les opérations sensibles.

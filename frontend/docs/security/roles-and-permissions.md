# Rôles et permissions

La source de vérité des permissions est `lib/rbac-catalog.ts`; les rôles et permissions sont enregistrés dans `roles`, `permissions`, `role_permissions` et `user_roles`. Une absence de ligne d'octroi signifie refus.

| Rôle | Accès principal | Restriction clé |
|---|---|---|
| super_administrator | Gouvernance globale, comptes, configuration, audit, demandes d'accès | Pas d'accès implicite aux dossiers médicaux individuels |
| national_administrator | Gestion nationale et rapports agrégés | Pas d'accès médical individuel |
| provincial_administrator | Comptes, structures et indicateurs de ses provinces affectées | Province attribuée uniquement |
| health_zone_administrator | Gestion dans ses zones affectées | Zone attribuée uniquement |
| health_facility_administrator | Comptes et opérations administratives de ses centres | Centre affecté uniquement |
| health_professional | Activités cliniques selon spécialité et affectation | Consentement, centre et enfant autorisés |
| community_health_worker | Enrôlement/visites/orientations selon permissions | Affectation, consentement et qualifications à échéance |
| parent | Dossiers et démarches de ses enfants | Relation légale et consentement requis |
| health_data_analyst | Rapports agrégés | Pas d'identités d'enfants, seuil de cohorte de cinq |

Les matrices détaillées sont dans `authorization-matrix.md`. Pour changer un rôle, l'acteur doit disposer de l'autorisation de gestion et `mayAssignRole` doit autoriser la transition. Le rôle `super_administrator` ne peut être donné que par un super administrateur.

Les compétences d'agent communautaire sont `GROWTH_MEASUREMENT`, `VACCINATION_ADMINISTER` et `NUTRITION_SCREENING`; elles sont associées à l'utilisateur et peuvent expirer. Les permissions cliniques sont également filtrées par la spécialité déclarée du professionnel.

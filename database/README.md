# Schéma et migrations

## Compatibilité

Le client XAMPP détecté est MariaDB 10.4.32. Les scripts utilisent InnoDB,
`utf8mb4` et des contraintes `CHECK` prises en charge par cette version et par
MySQL 8.0.16+. Les versions de MySQL antérieures à 8.0.16 peuvent ignorer les
contraintes `CHECK` ; les mêmes règles devront donc aussi être validées par
l'application PHP.

Les colonnes `*_at_utc` contiennent des valeurs UTC sans fuseau embarqué.
L'application devra convertir les dates/heures à l'entrée et à la sortie.

## Importation rapide avec phpMyAdmin

Pour une base neuve, importez `database.sql` dans l'onglet **Importer** de
phpMyAdmin. Le script crée `child_health_platform`, ses tables et des entrées
de démonstration explicitement fictives. Il ne faut pas l'exécuter sur une
base qui contient déjà des données.

1. Démarrez Apache et MySQL depuis XAMPP, puis ouvrez
   `http://localhost/phpmyadmin`.
2. Sélectionnez **Importer** (aucune base préalable n'est nécessaire).
3. Choisissez `database/database.sql`, conservez le format SQL et lancez
   **Importer**.
4. Vérifiez que `child_health_platform` et ses tables apparaissent dans le
   panneau latéral.
5. Configurez `.env` avec `DB_NAME=child_health_platform` et les identifiants
   locaux appropriés.

Pour une installation manuelle, créez d'abord une base vide nommée
`child_health_platform` avec le jeu de caractères `utf8mb4`, puis appliquez
les migrations et les données de référence dans l'ordre ci-dessous.

## Ordre d'application

Sur une base vide configurée en `utf8mb4`, appliquer une seule fois, dans cet
ordre :

1. `migrations/001_identity_and_children.sql`
2. `migrations/002_growth_and_vaccinations.sql`
3. `migrations/003_audit_events.sql`
4. `migrations/004_health_records_and_consent.sql`
5. `migrations/005_auth_security.sql`
6. `migrations/006_appointment_requests.sql`
7. `migrations/007_community_workflows.sql`
8. `migrations/008_professional_and_facility_workflows.sql`
9. `seeds/001_roles.sql`
10. `seeds/002_demo.sql`

Les scripts ne constituent pas encore un runner de migrations et ne doivent
pas être réexécutés sur une base déjà initialisée. Ne pas appliquer ces
scripts à une base contenant des données sans procédure de sauvegarde et de
migration approuvée.

## Correspondance avec l'application

Le schéma existant est conservé pour ne pas casser l'authentification et ses
relations. Ainsi, `users.display_name`, `password_hash` et `is_active`
correspondent respectivement au nom, au hash de mot de passe et au statut
demandés ; les rôles sont normalisés dans `roles` et `user_roles`. Le rôle
`community_health_worker` est le nom déjà utilisé par l'application pour un
agent de santé communautaire. Les tuteurs, dont le parent principal, sont
représentés dans `child_guardians` plutôt que par une colonne `parent_id`
unique. Les mesures de croissance utilisent `growth_measurements`, et les
doses de vaccins `vaccine_doses` avec un calendrier versionné.

## Périmètre et limites

- Les coordonnées GPS de centres sont facultatives ; aucune coordonnée GPS
  d'enfant ou de foyer n'est stockée. Les adresses de profil et les chemins
  d'images d'enfants sont facultatifs et doivent être protégés comme données
  personnelles.
- Les mesures conservent uniquement les valeurs saisies et leurs unités. Aucun
  seuil OMS, score, diagnostic, alerte ou résultat calculé n'est inclus.
- Le calendrier vaccinal est représenté par des tables configurables, mais
  aucun vaccin, dose, calendrier ou recommandation n'est préchargé. Les règles
  structurées prévoient une fenêtre d'âge et, si nécessaire, un intervalle
  depuis une dose référencée dans le même calendrier.
- Le compte exige au moins un identifiant unique (e-mail ou numéro au format
  E.164) et un hash de mot de passe ; la normalisation et la validation
  restent à appliquer côté serveur.
- La table de permissions reste disponible pour une granularité future. Les
  espaces actuels appliquent côté serveur des contrôles de rôle, d'association
  du tuteur, de consentement et d'affectation aux centres via l'application.
- `audit_events` sert à la traçabilité technique et `activity_logs` aux
  actions utilisateur. Les descriptions des journaux ne doivent jamais
  contenir de données médicales ; `ip_address` est stockée sous forme binaire
  IPv4/IPv6 et nécessite une politique de conservation.
- Les alertes sont uniquement des demandes de revue/orientation par un
  professionnel. Elles ne constituent ni un diagnostic ni une décision
  automatisée.
- Les tables nutritionnelles et médicales contiennent des données sensibles :
  toute utilisation réelle exige des autorisations serveur strictes et des
  contrôles d'accès par rôle, tuteur et structure.
- Les champs de consentement sont un support de traçabilité, pas un mécanisme
  de consentement complet : l'application doit vérifier l'autorité du tuteur,
  bloquer les accès selon le statut et enregistrer tout retrait.
- Les profils inscrits par un agent communautaire restent en attente jusqu'à
  l'activation du consentement par le parent. Les visites et référencements
  sont stockés dans `community_visits` et `health_referrals`; la saisie hors
  connexion et la synchronisation différée ne sont pas implémentées.
- Les superviseurs sont affectés à des centres et ne voient que des indicateurs
  agrégés. Les groupes de moins de cinq enfants sont masqués; ce seuil réduit
  le risque de réidentification, sans remplacer une analyse de divulgation.
  Les rapports utilisent les villes et provinces textuelles, pas une carte
  géographique, car les coordonnées des centres sont facultatives.
- Les responsables d'établissement ne voient que les demandes et orientations
  des centres qui leur sont affectés et des enfants au consentement actif.
  L'administrateur peut gérer les profils de centres, les affectations et les
  codes vaccin/dose, mais aucun espace administrateur ne donne accès aux
  dossiers médicaux individuels.
- Les vaccins ajoutés par l'administration sont désactivés par défaut. Les
  calendriers, âges recommandés et règles de couverture ne sont pas générés;
  une autorité sanitaire doit valider ces références avant leur activation.
- Le moteur IA futur doit passer par l'API PHP autorisée et ne doit pas se
  connecter directement à MySQL.

Les données de démonstration ne contiennent aucun enfant ou dossier médical ;
le centre et ses services sont fictifs et marqués comme démo.

Ce schéma ne suffit pas à autoriser l'utilisation de données de santé réelles.
Le chiffrement, la conservation, la suppression, le consentement, les
autorisations détaillées et les validations cliniques doivent être conçus et
vérifiés avant le pilote.

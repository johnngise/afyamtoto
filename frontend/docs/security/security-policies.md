# Politiques de sécurité

- Refus par défaut : session active, rôle actif, permission enregistrée et contraintes contextuelles doivent toutes être satisfaites.
- Les écritures valident et normalisent les entrées côté serveur; ne pas considérer les contrôles du navigateur comme une barrière.
- Les identifiants d'objet sont rechargés en base et liés à la session et à ses affectations.
- Les tuteurs sont limités aux enfants liés; une consultation médicale exige le consentement accordé.
- Les profils cliniques sont filtrés par spécialité. Les actions de mesure, vaccination et dépistage d'un agent nécessitent une qualification valide.
- Les administrateurs de zone et d'établissement sont limités aux affectations; suspendre un compte invalide ses sessions lors des mises à jour de compte.
- Les consultations médicales réussies génèrent un événement d'audit minimal. Ne jamais journaliser mots de passe, jetons ou données médicales inutiles.
- Les analystes reçoivent des agrégats seulement; les cohortes de moins de cinq sont masquées.
- Les secrets et la chaîne PostgreSQL sont fournis par variables d'environnement, jamais codés en dur.
- Les migrations sont additives; sauvegarder la base avant déploiement et utiliser `prisma migrate deploy`.

À compléter au déploiement : chiffrement géré par KMS pour les champs/fichiers qui le nécessitent, journal externe immuable, rotation documentée des secrets, sauvegardes testées, échéancier légal de conservation/suppression et revue de conformité RDC.

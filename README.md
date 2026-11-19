# AFYAMTOTO AI

AFYAMTOTO AI est une plateforme de suivi préventif destinée aux enfants de 0 à 5 ans et aux équipes de santé qui les accompagnent.

## Application active

L’application active est dans [`frontend/`](./frontend/README.md) : Next.js pour les pages et les API, PostgreSQL pour les données, et Prisma ORM pour le schéma et les migrations. Les anciennes URL PHP d’Apache sont redirigées vers Next.js sur le port 3000 par `.htaccess`. Le code PHP reste présent dans le dépôt pendant la transition, mais il n’est plus le point d’entrée du site.

## Démarrage local avec XAMPP

1. Démarrer Apache dans XAMPP.
2. Dans `frontend/`, copier `.env.example` vers `.env.local` et renseigner `DATABASE_URL` avec une base PostgreSQL accessible.
3. Installer les dépendances avec `npm install`.
4. Générer Prisma et appliquer le schéma avec `npm run db:generate` puis `npm run db:migrate`.
5. Démarrer Next.js avec `npm run dev`.
6. Ouvrir `http://localhost/AFYAMTOTO%20AI/` ou directement `http://localhost:3000`.

La redirection Apache locale envoie les anciennes routes d’authentification et de tableaux de bord vers les pages Next. En production, servir Next.js derrière un reverse proxy HTTPS plutôt que d’exposer le port 3000.

## Données existantes

La base PostgreSQL locale du projet utilise le port `5433`; sa connexion est conservée dans `frontend/.env.local` et ses fichiers dans `frontend/.postgres-data/` (ignorés par Git). Le schéma Prisma a été appliqué et 32 lignes ont été transférées depuis MySQL. Une sauvegarde source est conservée dans `storage/mysql-pre-cutover-2026-11-13.sql`.

La base MySQL source et les fichiers PHP sont conservés pour permettre un retour arrière. Apache redirige désormais les anciennes URL vers Next.js. Pour un prochain import, utiliser d’abord la simulation `npm run db:import:mysql`, sauvegarder la source, puis appliquer `npm run db:import:mysql -- --apply` uniquement sur une base PostgreSQL vide.

Le backend protège les dossiers de santé par rôle et consentement. Les alertes assistent le suivi et ne constituent pas un diagnostic. Les flux cliniques et les règles de conservation doivent être approuvés par les parties prenantes compétentes avant usage réel.

# Architecture de contrôle des accès

## État de l'application

AFYAMTOTO AI utilise Next.js App Router et Route Handlers TypeScript, Prisma 7 et PostgreSQL. L'authentification applicative repose sur une session aléatoire stockée sous forme de digest SHA-256 dans PostgreSQL et envoyée dans un cookie HttpOnly, SameSite=Lax. Les comptes inactifs, sessions expirées et sessions sans rôle attribué sont rejetés. Les mots de passe sont hachés avec bcrypt.

## Décision d'accès

`lib/authorization.ts` centralise l'authentification, le rôle actif de la session, les permissions de `RolePermission`, la spécialité clinique, les qualifications communautaires, le consentement, les relations parent/tuteur, les affectations et le périmètre géographique. `lib/rbac-policy.ts` applique le refus par défaut et détermine les règles pures. Les Route Handlers protégés appellent `authorize` côté serveur; les API restent la frontière de confiance pour les composants React.

Les rôles multiples sont stockés via `UserRole`; chaque session choisit un rôle actif. Les rôles canoniques sont super/national/provincial/zone de santé/établissement/professionnel/agent communautaire/parent/analyste. Les anciens rôles restent supportés pour compatibilité et leurs attributions sont contrôlées.

## Données de santé

Le parent est limité à sa relation ChildGuardian et au consentement. Professionnels et agents doivent être affectés au dossier et disposer d'un consentement actif. Les accès médicaux réussis créent un `AuditEvent` sans recopier le contenu médical. Les vues analytiques masquent les cohortes inférieures à cinq et n'incluent pas les noms ni coordonnées.

## Limites connues

- Le schéma existant ne contient pas d'affectation directe enfant-établissement ni un modèle générique d'organisation; les statistiques par centre utilisent donc les activités/références liées au centre.
- La clé de chiffrement applicative et le chiffrement au repos dépendent de l'infrastructure de déploiement. Les journaux applicatifs ne constituent pas un stockage d'audit immuable.
- Les Server Actions métier ne sont pas le canal utilisé par les modules audités; toute nouvelle action serveur doit appeler les mêmes primitives d'autorisation.
- Une protection complète du cycle de vie, de l'export et de la conservation médicale demande une politique produit/légale et des services de stockage qui ne sont pas présents dans le dépôt.

Les migrations Prisma sont additives et conservatrices. Ne pas exécuter `migrate reset` sur un environnement contenant des données.

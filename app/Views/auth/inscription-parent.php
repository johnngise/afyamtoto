<?php

declare(strict_types=1);

$safeBase = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
$safeName = htmlspecialchars((string) ($oldInput['display_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$safeEmail = htmlspecialchars((string) ($oldInput['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$safePhone = htmlspecialchars((string) ($oldInput['phone_e164'] ?? ''), ENT_QUOTES, 'UTF-8');
$sessionRoleLabel = match ($authenticatedUser['role_code'] ?? null) {
    'parent' => 'parent',
    'community_health_worker' => 'agent de santé communautaire',
    'health_professional' => 'professionnel de santé',
    'administrator' => 'administrateur',
    default => 'utilisateur',
};
$sessionDashboard = match ($authenticatedUser['role_code'] ?? null) {
    'parent' => '/pages/espace-parent.php',
    'community_health_worker' => '/pages/espace-agent.php',
    'health_professional' => '/pages/espace-professionnel.php',
    'administrator' => '/pages/espace-administrateur.php',
    default => '/pages/connexion.php',
};

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F7FAFC">
    <title>Créer un compte parent — AFYAMTOTO AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $safeBase ?>/public/assets/css/design-system.css">
    <link rel="stylesheet" href="<?= $safeBase ?>/public/assets/css/auth.css?v=2">
    <script defer src="<?= $safeBase ?>/public/assets/js/auth.js"></script>
</head>
<body class="auth-page">
<main class="auth-layout">
    <section class="auth-story">
        <a class="auth-brand" href="<?= $safeBase ?>/index.php"><span aria-hidden="true">✚</span><strong>AFYAMTOTO <span>AI</span></strong></a>
        <div class="auth-story-copy"><p class="auth-eyebrow">Rejoindre le projet</p><h1>Un espace pensé pour accompagner les familles.</h1><p>Créez votre compte parent. Les comptes professionnels sont provisionnés par un administrateur.</p></div>
        <p class="auth-story-note">Prototype en développement · aucune donnée patient réelle</p>
    </section>
    <section aria-labelledby="auth-title" class="auth-panel">
        <div class="auth-card">
            <a class="auth-back-link" href="<?= $safeBase ?>/pages/connexion.php"><span aria-hidden="true">←</span> Déjà inscrit ? Se connecter</a>
            <p class="auth-kicker">Espace parent</p>
            <h2 id="auth-title">Créer mon compte</h2>
            <p class="auth-intro">L’inscription publique crée uniquement un compte parent. Aucun profil d’enfant ni renseignement médical n’est demandé ici.</p>
            <?php if (is_array($notice)): ?>
                <div class="alert alert-<?= $notice['type'] === 'success' ? 'success' : 'danger' ?>" role="status"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (is_string($systemError)): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($systemError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($errors !== []): ?>
                <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <?php if (is_array($authenticatedUser)): ?>
                <div class="auth-session-card" role="status"><span aria-hidden="true">✓</span><div><strong>Session ouverte · <?= htmlspecialchars($sessionRoleLabel, ENT_QUOTES, 'UTF-8') ?></strong><p><a href="<?= $safeBase . $sessionDashboard ?>">Ouvrir mon espace</a></p></div></div>
                <form action="<?= $safeBase ?>/pages/deconnexion.php" method="post"><input name="_csrf" type="hidden" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-outline-primary w-100" type="submit">Fermer la session</button></form>
            <?php else: ?>
                <form action="<?= $safeBase ?>/pages/inscription-parent.php" class="auth-form" method="post">
                    <input name="_csrf" type="hidden" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3"><label class="form-label" for="display_name">Nom complet</label><input autocomplete="name" class="form-control" id="display_name" maxlength="160" name="display_name" required type="text" value="<?= $safeName ?>"></div>
                    <div class="mb-3"><label class="form-label" for="email">Adresse e-mail</label><input autocomplete="email" class="form-control" id="email" maxlength="254" name="email" required type="email" value="<?= $safeEmail ?>"></div>
                    <div class="mb-3"><label class="form-label" for="phone_e164">Numéro de téléphone</label><input autocomplete="tel" class="form-control" id="phone_e164" maxlength="16" name="phone_e164" pattern="\+[1-9][0-9]{1,14}" placeholder="+243…" required type="tel" value="<?= $safePhone ?>"><div class="form-text">Format international, par exemple +243XXXXXXXXX.</div></div>
                    <div class="mb-3"><label class="form-label" for="password">Mot de passe</label><div class="auth-password-field"><input autocomplete="new-password" class="form-control" id="password" maxlength="72" minlength="12" name="password" required type="password"><button aria-label="Afficher le mot de passe" class="auth-password-toggle" data-password-toggle type="button">Afficher</button></div><div class="form-text">12 caractères avec trois types de caractères, ou une phrase secrète de 20 caractères minimum.</div></div>
                    <div class="mb-3"><label class="form-label" for="password_confirmation">Confirmer le mot de passe</label><input autocomplete="new-password" class="form-control" id="password_confirmation" maxlength="72" minlength="12" name="password_confirmation" required type="password"></div>
                    <button class="btn btn-primary w-100 auth-submit" data-loading-label="Création…" type="submit">Créer mon compte parent</button>
                    <p class="auth-terms">En créant un compte, vous comprenez que l’espace de suivi n’est pas encore disponible.</p>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>

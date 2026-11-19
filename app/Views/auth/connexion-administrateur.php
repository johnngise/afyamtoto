<?php

declare(strict_types=1);

$safeBase = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
$safeIdentifier = htmlspecialchars((string) ($oldInput['identifier'] ?? ''), ENT_QUOTES, 'UTF-8');
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
    default => '/pages/connexion-administrateur.php',
};

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F7FAFC">
    <title>Connexion administrateur — AFYAMTOTO AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $safeBase ?>/public/assets/css/design-system.css">
    <link rel="stylesheet" href="<?= $safeBase ?>/public/assets/css/auth.css?v=2">
    <script defer src="<?= $safeBase ?>/public/assets/js/auth.js"></script>
</head>
<body class="auth-page">
<main class="auth-layout auth-layout-professional">
    <section class="auth-story">
        <a class="auth-brand" href="<?= $safeBase ?>/index.php"><span aria-hidden="true">✚</span><strong>AFYAMTOTO <span>AI</span></strong></a>
        <div class="auth-story-copy"><p class="auth-eyebrow">Accès restreint</p><h1>Administration sécurisée.</h1><p>Cette connexion est réservée aux comptes portant le rôle administrateur déjà attribué.</p></div>
        <p class="auth-story-note">Aucune inscription publique ni attribution de rôle administrateur.</p>
    </section>
    <section aria-labelledby="auth-title" class="auth-panel">
        <div class="auth-card">
            <a class="auth-back-link" href="<?= $safeBase ?>/index.php"><span aria-hidden="true">←</span> Retour au site public</a>
            <p class="auth-kicker">Administration</p>
            <h2 id="auth-title">Connexion administrateur</h2>
            <p class="auth-intro">Utilisez le compte administrateur provisionné par votre organisation.</p>
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
                <form action="<?= $safeBase ?>/pages/connexion-administrateur.php" class="auth-form" method="post">
                    <input name="_csrf" type="hidden" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3"><label class="form-label" for="identifier">E-mail ou téléphone</label><input autocomplete="username" class="form-control" id="identifier" maxlength="254" name="identifier" required type="text" value="<?= $safeIdentifier ?>"></div>
                    <div class="mb-3"><label class="form-label" for="password">Mot de passe</label><div class="auth-password-field"><input autocomplete="current-password" class="form-control" id="password" maxlength="1024" name="password" required type="password"><button aria-label="Afficher le mot de passe" class="auth-password-toggle" data-password-toggle type="button">Afficher</button></div></div>
                    <button class="btn btn-primary w-100 auth-submit" data-loading-label="Connexion…" type="submit">Se connecter</button>
                </form>
                <div class="auth-links"><a href="<?= $safeBase ?>/pages/mot-de-passe-oublie.php">Mot de passe oublié ?</a></div>
                <div class="auth-links"><a href="<?= $safeBase ?>/pages/connexion-agent.php">Connexion agent de santé</a></div>
                <?php if ($canBootstrapAdministrator): ?>
                    <details class="mt-4 border rounded-3 p-3">
                        <summary class="fw-semibold">Créer le premier compte administrateur</summary>
                        <p class="auth-provider-note mt-2">Cette configuration est disponible une seule fois et exige le code secret défini sur le serveur.</p>
                        <form action="<?= $safeBase ?>/pages/connexion-administrateur.php" class="auth-form" method="post">
                            <input name="_csrf" type="hidden" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input name="action" type="hidden" value="initial-administrator-setup">
                            <div class="mb-3"><label class="form-label" for="setup-name">Nom complet</label><input autocomplete="name" class="form-control" id="setup-name" maxlength="160" name="display_name" required type="text" value="<?= htmlspecialchars((string) ($oldInput['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="mb-3"><label class="form-label" for="setup-email">Adresse e-mail</label><input autocomplete="email" class="form-control" id="setup-email" maxlength="254" name="email" required type="email" value="<?= htmlspecialchars((string) ($oldInput['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="mb-3"><label class="form-label" for="setup-phone">Téléphone (facultatif, format international)</label><input autocomplete="tel" class="form-control" id="setup-phone" maxlength="16" name="phone_e164" pattern="\+[1-9][0-9]{1,14}" placeholder="+243…" type="tel" value="<?= htmlspecialchars((string) ($oldInput['phone_e164'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="mb-3"><label class="form-label" for="setup-password">Mot de passe</label><div class="auth-password-field"><input autocomplete="new-password" class="form-control" id="setup-password" maxlength="72" minlength="12" name="password" required type="password"><button aria-label="Afficher le mot de passe" class="auth-password-toggle" data-password-toggle type="button">Afficher</button></div><div class="form-text">12 caractères avec au moins trois types de caractères, ou 20 caractères minimum.</div></div>
                            <div class="mb-3"><label class="form-label" for="setup-password-confirmation">Confirmer le mot de passe</label><input autocomplete="new-password" class="form-control" id="setup-password-confirmation" maxlength="72" minlength="12" name="password_confirmation" required type="password"></div>
                            <div class="mb-3"><label class="form-label" for="setup-secret">Code secret de configuration</label><input autocomplete="off" class="form-control" id="setup-secret" maxlength="256" name="setup_secret" required type="password"></div>
                            <button class="btn btn-outline-primary w-100" data-loading-label="Création…" type="submit">Créer le compte administrateur</button>
                        </form>
                    </details>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>

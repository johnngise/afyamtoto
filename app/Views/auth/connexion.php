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
    default => '/pages/connexion.php',
};

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F7FAFC">
    <title>Connexion — AFYAMTOTO AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $safeBase ?>/public/assets/css/design-system.css">
    <link rel="stylesheet" href="<?= $safeBase ?>/public/assets/css/auth.css?v=2">
    <script defer src="<?= $safeBase ?>/public/assets/js/auth.js"></script>
</head>
<body class="auth-page">
<main class="auth-layout">
    <section class="auth-story">
        <a class="auth-brand" href="<?= $safeBase ?>/index.php"><span aria-hidden="true">✚</span><strong>AFYAMTOTO <span>AI</span></strong></a>
        <div class="auth-story-copy"><p class="auth-eyebrow">Espace famille</p><h1>Grandir ensemble, en confiance.</h1><p>Connectez-vous pour accéder à l’espace parent associé à votre compte.</p></div>
        <p class="auth-story-note">Prototype en développement · aucune donnée patient réelle</p>
    </section>
    <section aria-labelledby="auth-title" class="auth-panel">
        <div class="auth-card">
            <a class="auth-back-link" href="<?= $safeBase ?>/index.php"><span aria-hidden="true">←</span> Retour à l’accueil</a>
            <p class="auth-kicker">Accès sécurisé</p>
            <h2 id="auth-title">Connexion à mon espace</h2>
            <p class="auth-intro">Utilisez les identifiants associés à votre compte.</p>
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
                <div class="auth-session-card" role="status">
                    <span aria-hidden="true">✓</span>
                    <div><strong>Session ouverte · <?= htmlspecialchars($sessionRoleLabel, ENT_QUOTES, 'UTF-8') ?></strong><p><a href="<?= $safeBase . $sessionDashboard ?>">Ouvrir mon espace</a></p></div>
                </div>
                <form action="<?= $safeBase ?>/pages/deconnexion.php" method="post">
                    <input name="_csrf" type="hidden" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-outline-primary w-100" type="submit">Fermer la session</button>
                </form>
            <?php else: ?>
                <form action="<?= $safeBase ?>/pages/connexion.php" class="auth-form" method="post">
                    <input name="_csrf" type="hidden" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3"><label class="form-label" for="identifier">E-mail ou téléphone</label><input autocomplete="username" class="form-control" id="identifier" maxlength="254" name="identifier" required type="text" value="<?= $safeIdentifier ?>"><div class="form-text">Pour un téléphone, utilisez le format international (+243…).</div></div>
                    <div class="mb-3"><label class="form-label" for="password">Mot de passe</label><div class="auth-password-field"><input autocomplete="current-password" class="form-control" id="password" maxlength="1024" name="password" required type="password"><button aria-label="Afficher le mot de passe" class="auth-password-toggle" data-password-toggle type="button">Afficher</button></div></div>
                    <button class="btn btn-primary w-100 auth-submit" data-loading-label="Connexion…" type="submit">Se connecter</button>
                </form>
                <div class="auth-links"><a href="<?= $safeBase ?>/pages/mot-de-passe-oublie.php">Mot de passe oublié ?</a><a href="<?= $safeBase ?>/pages/inscription-parent.php">Créer un compte parent</a><a href="<?= $safeBase ?>/pages/connexion-agent.php">Connexion professionnelle</a></div>
                <p class="auth-provider-note">La connexion nécessite une base configurée et les migrations initialisées.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>

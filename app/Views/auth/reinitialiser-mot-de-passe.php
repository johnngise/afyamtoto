<?php

declare(strict_types=1);

$safeBase = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
$safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
$validTokenFormat = preg_match('/^[a-f0-9]{64}$/D', $token) === 1;

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta name="theme-color" content="#F7FAFC">
    <meta name="description" content="Choisissez un nouveau mot de passe sécurisé pour votre compte AFYAMTOTO AI.">
    <title>Nouveau mot de passe — AFYAMTOTO AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjX0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $safeBase ?>/public/assets/css/design-system.css">
    <link rel="stylesheet" href="<?= $safeBase ?>/public/assets/css/auth.css?v=2">
    <script defer src="<?= $safeBase ?>/public/assets/js/auth.js"></script>
</head>
<body class="auth-page">
<main class="auth-layout">
    <section class="auth-story">
        <a class="auth-brand" href="<?= $safeBase ?>/index.php"><span aria-hidden="true">✚</span><strong>AFYAMTOTO <span>AI</span></strong></a>
        <div class="auth-story-copy"><p class="auth-eyebrow">Sécurité du compte</p><h1>Choisissez un nouveau mot de passe.</h1><p>Le lien est temporaire et ne peut servir qu’une seule fois.</p></div>
    </section>
    <section aria-labelledby="auth-title" class="auth-panel">
        <div class="auth-card">
            <a class="auth-back-link" href="<?= $safeBase ?>/pages/connexion.php"><span aria-hidden="true">←</span> Retour à la connexion</a>
            <p class="auth-kicker">Réinitialisation sécurisée</p>
            <h2 id="auth-title">Nouveau mot de passe</h2>
            <?php if (is_string($systemError)): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($systemError, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php if (!$validTokenFormat): ?>
                <div class="alert alert-danger" role="alert">Ce lien est invalide. Demandez un nouveau lien de réinitialisation.</div>
                <a class="btn btn-primary w-100" href="<?= $safeBase ?>/pages/mot-de-passe-oublie.php">Demander un nouveau lien</a>
            <?php else: ?>
                <form action="<?= $safeBase ?>/pages/reinitialiser-mot-de-passe.php" class="auth-form" method="post">
                    <input name="_csrf" type="hidden" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input name="token" type="hidden" value="<?= $safeToken ?>">
                    <div class="mb-3"><label class="form-label" for="password">Nouveau mot de passe</label><div class="auth-password-field"><input autocomplete="new-password" class="form-control" id="password" maxlength="72" minlength="12" name="password" required type="password"><button aria-label="Afficher le mot de passe" class="auth-password-toggle" data-password-toggle type="button">Afficher</button></div><div class="form-text">12 caractères avec trois types de caractères, ou une phrase secrète de 20 caractères minimum.</div></div>
                    <div class="mb-3"><label class="form-label" for="password_confirmation">Confirmer le mot de passe</label><input autocomplete="new-password" class="form-control" id="password_confirmation" maxlength="72" minlength="12" name="password_confirmation" required type="password"></div>
                    <button class="btn btn-primary w-100 auth-submit" type="submit">Modifier mon mot de passe</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>

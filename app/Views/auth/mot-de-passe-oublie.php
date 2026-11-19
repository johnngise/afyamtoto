<?php

declare(strict_types=1);

$safeBase = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
$safeEmail = htmlspecialchars((string) ($oldInput['email'] ?? ''), ENT_QUOTES, 'UTF-8');

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Mot de passe oublié — AFYAMTOTO AI</title>
    <meta name="theme-color" content="#F7FAFC">
    <meta name="description" content="Réinitialisation sécurisée du mot de passe AFYAMTOTO AI.">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $safeBase ?>/public/assets/css/design-system.css">
    <link rel="stylesheet" href="<?= $safeBase ?>/public/assets/css/auth.css?v=2">
</head>
<body class="auth-page">
<main class="auth-layout">
    <section class="auth-story">
        <a class="auth-brand" href="<?= $safeBase ?>/index.php"><span aria-hidden="true">✚</span><strong>AFYAMTOTO <span>AI</span></strong></a>
        <div class="auth-story-copy"><p class="auth-eyebrow">Sécurité du compte</p><h1>Retrouvez l’accès à votre espace.</h1><p>Un lien temporaire et à usage unique peut être envoyé à l’adresse associée au compte.</p></div>
    </section>
    <section aria-labelledby="auth-title" class="auth-panel">
        <div class="auth-card">
            <a class="auth-back-link" href="<?= $safeBase ?>/pages/connexion.php"><span aria-hidden="true">←</span> Retour à la connexion</a>
            <p class="auth-kicker">Réinitialisation sécurisée</p>
            <h2 id="auth-title">Mot de passe oublié ?</h2>
            <p class="auth-intro">Saisissez l’adresse e-mail de votre compte. La réponse ne révèle pas si un compte existe.</p>
            <?php if (is_array($notice)): ?><div class="alert alert-success" role="status"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?php if (is_string($systemError)): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($systemError, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form action="<?= $safeBase ?>/pages/mot-de-passe-oublie.php" class="auth-form" method="post">
                <input name="_csrf" type="hidden" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3"><label class="form-label" for="email">Adresse e-mail</label><input autocomplete="email" class="form-control" id="email" maxlength="254" name="email" required type="email" value="<?= $safeEmail ?>"></div>
                <button class="btn btn-primary w-100 auth-submit" type="submit">Recevoir un lien sécurisé</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>

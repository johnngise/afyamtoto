<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="dashboard-page-content">
    <section class="dashboard-welcome" id="overview">
        <div><p class="dashboard-eyebrow">Administration · établissements</p><h2>Centres de santé</h2><p>Gérez les coordonnées publiques des centres partenaires. Les affectations des professionnels se font dans la gestion des comptes.</p></div>
        <a class="btn btn-outline-primary" href="<?= $e($basePath) ?>/pages/gestion-utilisateurs.php">Gérer les affectations</a>
        <a class="btn btn-outline-primary" href="<?= $e($basePath) ?>/pages/gestion-referentiels.php">Référentiels vaccinaux</a>
    </section>
    <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><?php foreach ($errors as $error): ?><p class="mb-0"><?= $e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
    <?php if (is_array($notice ?? null)): ?><div class="alert alert-<?= $notice['type'] === 'success' ? 'success' : 'danger' ?>" role="status"><?= $e($notice['message']) ?></div><?php endif; ?>
    <section aria-labelledby="create-center-title" class="dashboard-section dashboard-section-panel">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Nouveau profil</p><h2 id="create-center-title">Créer un établissement</h2></div></div>
        <form action="<?= $e($basePath) ?>/pages/gestion-centres.php" class="row g-3" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>">
            <div class="col-md-6"><label class="form-label" for="center-name">Nom du centre</label><input class="form-control" id="center-name" maxlength="180" name="name" required></div>
            <div class="col-md-6"><label class="form-label" for="center-phone">Téléphone international</label><input class="form-control" id="center-phone" maxlength="16" name="phone_e164" pattern="\+[1-9][0-9]{1,14}" placeholder="+243…"></div>
            <div class="col-md-6"><label class="form-label" for="center-address">Adresse</label><input class="form-control" id="center-address" maxlength="500" name="address_text"></div>
            <div class="col-md-3"><label class="form-label" for="center-city">Ville</label><input class="form-control" id="center-city" maxlength="120" name="city"></div>
            <div class="col-md-3"><label class="form-label" for="center-province">Province</label><input class="form-control" id="center-province" maxlength="120" name="province"></div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Créer le centre</button></div>
        </form>
    </section>
    <section aria-labelledby="centers-list-title" class="dashboard-section" id="health-centers">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Établissements réels</p><h2 id="centers-list-title">Centres partenaires</h2></div><span class="dashboard-pill"><?= count($centers) ?> centres</span></div>
        <?php if ($centers === []): ?><div class="dashboard-empty-inline"><span aria-hidden="true">⌖</span><div><strong>Aucun centre partenaire renseigné</strong><p>Créez le profil d’un établissement pour pouvoir l’affecter aux comptes professionnels.</p></div></div><?php else: ?>
            <div class="table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Établissement</th><th>Localisation</th><th>Contact</th><th>Comptes affectés</th><th>Modifier</th></tr></thead><tbody>
                <?php foreach ($centers as $center): ?><tr><td><strong><?= $e($center['name']) ?></strong></td><td><?= $e(implode(', ', array_filter([$center['city'], $center['province']]))) ?><span class="dashboard-table-subtitle"><?= $e($center['address_text'] ?? '') ?></span></td><td><?php if ($center['phone_e164']): ?><a href="tel:<?= $e($center['phone_e164']) ?>"><?= $e($center['phone_e164']) ?></a><?php else: ?>—<?php endif; ?></td><td><?= (int) $center['assigned_users'] ?></td><td>
                    <details><summary class="btn btn-outline-primary btn-sm">Modifier</summary><form action="<?= $e($basePath) ?>/pages/gestion-centres.php" class="row g-2 mt-2" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="center_id" type="hidden" value="<?= (int) $center['id'] ?>"><div class="col-12"><label class="form-label" for="center-name-<?= (int) $center['id'] ?>">Nom</label><input class="form-control" id="center-name-<?= (int) $center['id'] ?>" maxlength="180" name="name" required value="<?= $e($center['name']) ?>"></div><div class="col-12"><label class="form-label" for="center-phone-<?= (int) $center['id'] ?>">Téléphone</label><input class="form-control" id="center-phone-<?= (int) $center['id'] ?>" maxlength="16" name="phone_e164" pattern="\+[1-9][0-9]{1,14}" value="<?= $e($center['phone_e164'] ?? '') ?>"></div><div class="col-12"><label class="form-label" for="center-address-<?= (int) $center['id'] ?>">Adresse</label><input class="form-control" id="center-address-<?= (int) $center['id'] ?>" maxlength="500" name="address_text" value="<?= $e($center['address_text'] ?? '') ?>"></div><div class="col-md-6"><label class="form-label" for="center-city-<?= (int) $center['id'] ?>">Ville</label><input class="form-control" id="center-city-<?= (int) $center['id'] ?>" maxlength="120" name="city" value="<?= $e($center['city'] ?? '') ?>"></div><div class="col-md-6"><label class="form-label" for="center-province-<?= (int) $center['id'] ?>">Province</label><input class="form-control" id="center-province-<?= (int) $center['id'] ?>" maxlength="120" name="province" value="<?= $e($center['province'] ?? '') ?>"></div><div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Enregistrer</button></div></form></details>
                </td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>
</div>

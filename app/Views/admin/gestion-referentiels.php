<?php

declare(strict_types=1);

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$groupedVaccines = [];
foreach ($vaccines as $vaccine) {
    $id = (int) $vaccine['vaccine_id'];
    if (!isset($groupedVaccines[$id])) {
        $groupedVaccines[$id] = [
            'id' => $id,
            'code' => $vaccine['vaccine_code'],
            'name' => $vaccine['vaccine_name'],
            'description' => $vaccine['description'],
            'active' => (int) $vaccine['is_active'] === 1,
            'doses' => [],
        ];
    }
    if ($vaccine['dose_id'] !== null) {
        $groupedVaccines[$id]['doses'][] = $vaccine['dose_name'];
    }
}
?>
<div class="dashboard-page-content">
    <section class="dashboard-welcome" id="overview">
        <div><p class="dashboard-eyebrow">Administration · référentiels</p><h2>Vaccins et doses</h2><p>Les références créées sont désactivées jusqu’à validation par l’autorité sanitaire compétente.</p></div>
    </section>
    <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><?php foreach ($errors as $error): ?><p class="mb-0"><?= $e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
    <?php if (is_array($notice ?? null)): ?><div class="alert alert-<?= $notice['type'] === 'success' ? 'success' : 'danger' ?>" role="status"><?= $e($notice['message']) ?></div><?php endif; ?>
    <section class="dashboard-section dashboard-section-panel" id="vaccines">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Nouveau référentiel</p><h2>Ajouter un vaccin et ses doses</h2></div></div>
        <form action="<?= $e($basePath) ?>/pages/gestion-referentiels.php#vaccines" class="row g-3" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="create_vaccine">
            <div class="col-md-4"><label class="form-label" for="vaccine-code">Code interne</label><input class="form-control" id="vaccine-code" maxlength="64" name="vaccine_code" pattern="[A-Za-z0-9][A-Za-z0-9._-]{1,63}" required></div>
            <div class="col-md-8"><label class="form-label" for="vaccine-name">Nom du vaccin</label><input class="form-control" id="vaccine-name" maxlength="160" name="display_name" required></div>
            <div class="col-12"><label class="form-label" for="vaccine-description">Description (facultatif)</label><textarea class="form-control" id="vaccine-description" maxlength="500" name="description" rows="2"></textarea></div>
            <div class="col-12"><h3 class="h6">Doses</h3><div data-dose-list><div class="row g-2 align-items-end mb-2" data-dose-row><div class="col-md-4"><label class="form-label">Code dose</label><input class="form-control" maxlength="64" name="dose_codes[]" required></div><div class="col-md-6"><label class="form-label">Nom dose</label><input class="form-control" maxlength="120" name="dose_names[]" required></div><div class="col-md-2"><button class="btn btn-outline-secondary w-100" data-remove-dose type="button" disabled aria-label="Retirer la dose">Retirer</button></div></div></div><button class="btn btn-outline-primary btn-sm" data-add-dose type="button">Ajouter une dose</button></div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Créer désactivé</button></div>
        </form>
    </section>
    <section aria-labelledby="reference-list-title" class="dashboard-section">
        <div class="dashboard-section-heading"><div><p class="dashboard-eyebrow">Référentiel existant</p><h2 id="reference-list-title">Vaccins configurés</h2></div></div>
        <div class="dashboard-card table-responsive"><table class="table dashboard-data-table mb-0"><thead><tr><th>Vaccin</th><th>Code</th><th>Doses</th><th>Disponibilité</th><th>Action</th></tr></thead><tbody>
            <?php foreach ($groupedVaccines as $vaccine): ?><tr><td><strong><?= $e($vaccine['name']) ?></strong><span class="dashboard-table-subtitle"><?= $e($vaccine['description'] ?? '') ?></span></td><td><?= $e($vaccine['code']) ?></td><td><?= $vaccine['doses'] !== [] ? $e(implode(', ', $vaccine['doses'])) : 'Aucune dose configurée' ?></td><td><span class="dashboard-status<?= $vaccine['active'] ? '' : ' is-inactive' ?>"><?= $vaccine['active'] ? 'Actif' : 'Désactivé' ?></span></td><td><form action="<?= $e($basePath) ?>/pages/gestion-referentiels.php#vaccines" method="post"><input name="_csrf" type="hidden" value="<?= $e($csrfToken) ?>"><input name="_action" type="hidden" value="set_vaccine_active"><input name="vaccine_id" type="hidden" value="<?= $vaccine['id'] ?>"><input name="is_active" type="hidden" value="<?= $vaccine['active'] ? '0' : '1' ?>"><button class="btn btn-outline-primary btn-sm" type="submit"><?= $vaccine['active'] ? 'Désactiver' : 'Activer après validation' ?></button></form></td></tr><?php endforeach; ?>
            <?php if ($groupedVaccines === []): ?><tr><td colspan="5">Aucun vaccin configuré.</td></tr><?php endif; ?>
        </tbody></table></div>
        <div class="dashboard-empty-inline mt-3"><span aria-hidden="true">!</span><div><strong>Validation clinique requise</strong><p>Aucun calendrier, âge recommandé ou seuil n’est généré. Validez le référentiel auprès de l’autorité sanitaire avant activation ou utilisation pour le suivi des enfants.</p></div></div>
    </section>
</div>
<script>
(() => {
    const list = document.querySelector('[data-dose-list]');
    const addButton = document.querySelector('[data-add-dose]');
    if (!(list instanceof HTMLElement) || !(addButton instanceof HTMLButtonElement)) return;

    const syncRemoveButtons = () => {
        const rows = Array.from(list.querySelectorAll('[data-dose-row]'));
        rows.forEach((row) => {
            const removeButton = row.querySelector('[data-remove-dose]');
            if (removeButton instanceof HTMLButtonElement) removeButton.disabled = rows.length === 1;
        });
    };

    addButton.addEventListener('click', () => {
        if (list.querySelectorAll('[data-dose-row]').length >= 20) return;
        const template = list.querySelector('[data-dose-row]');
        if (!(template instanceof HTMLElement)) return;
        const clone = template.cloneNode(true);
        clone.querySelectorAll('input').forEach((input) => { input.value = ''; });
        list.append(clone);
        syncRemoveButtons();
    });

    list.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;
        const button = target.closest('[data-remove-dose]');
        const row = button?.closest('[data-dose-row]');
        if (row && list.querySelectorAll('[data-dose-row]').length > 1) {
            row.remove();
            syncRemoveButtons();
        }
    });
    syncRemoveButtons();
})();
</script>

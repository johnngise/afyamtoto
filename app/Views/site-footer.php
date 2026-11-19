<?php if (($currentPage ?? '') === 'accueil'): ?>
    <?php $footerBase = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>
    <footer class="site-footer landing-footer">
        <div class="page-shell landing-footer-main">
            <div class="landing-footer-about">
                <a aria-label="AFYAMTOTO AI, accueil" class="landing-footer-brand" href="<?= $navigation['accueil'][1] ?>">
                    <span aria-hidden="true" class="landing-footer-mark">✚</span>
                    <strong>AFYAMTOTO <span>AI</span></strong>
                </a>
                <p>Une initiative en préparation pour accompagner la santé préventive des enfants de 0 à 5 ans en RDC.</p>
            </div>
            <div class="landing-footer-links">
                <h2>Explorer</h2>
                <nav aria-label="Liens de pied de page">
                    <a href="<?= $footerBase ?>/index.php#vision">Notre vision</a>
                    <a href="<?= $footerBase ?>/index.php#fonctionnalites">Fonctionnalités</a>
                    <a href="<?= $footerBase ?>/index.php#intelligence">Notre approche IA</a>
                    <a href="<?= $footerBase ?>/index.php#impact">Impact</a>
                </nav>
            </div>
            <div class="landing-footer-links">
                <h2>À propos</h2>
                <nav aria-label="Informations sur le projet">
                    <a href="<?= $footerBase ?>/pages/plateforme.php">La plateforme</a>
                    <a href="<?= $footerBase ?>/pages/approche.php">Nos principes</a>
                    <a href="<?= $footerBase ?>/pages/contact.php">En savoir plus</a>
                </nav>
            </div>
        </div>
        <div class="page-shell landing-footer-bottom">
            <p>© <?= date('Y') ?> AFYAMTOTO AI</p>
            <p class="landing-footer-disclaimer">Prototype en développement — aucun diagnostic ni suivi médical en ligne.</p>
        </div>
    </footer>
<?php else: ?>
    <footer class="site-footer">
        <div class="page-shell footer-inner">
            <a aria-label="AFYAMTOTO AI, accueil" class="footer-brand" href="<?= $navigation['accueil'][1] ?>">
                <span class="footer-brand-mark">✳</span><span>AFYAMTOTO <span>AI</span></span>
            </a>
            <p>Un projet en préparation pour le bien-être des enfants en RDC.</p>
            <span class="footer-note">© <?= date('Y') ?> AFYAMTOTO AI</span>
        </div>
    </footer>
<?php endif; ?>
</body>
</html>

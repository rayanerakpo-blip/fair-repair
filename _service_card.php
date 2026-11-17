<?php
/**
 * Carte de service réutilisable.
 * Attend une variable $svc : tableau de la requête services (avec prenom, nom, ville,
 * note_moyenne, nb_avis) et éventuellement $svc['avis'] (liste des avis récents).
 */
if (empty($svc)) return;
$note  = (float)($svc['note_moyenne'] ?? 0);
$nbAvis = (int)($svc['nb_avis'] ?? 0);
$image = !empty($svc['image']) ? 'uploads/' . htmlspecialchars(basename($svc['image'])) : null;
$proprietaire = (int)($svc['user_id'] ?? 0);
?>
<div class="card service-card">
    <?php if ($image): ?>
        <img class="service-img" src="<?= $image ?>" alt="<?= htmlspecialchars($svc['titre']) ?>" loading="lazy">
    <?php endif; ?>

    <h3><?= htmlspecialchars($svc['titre']) ?></h3>
    <p class="meta muted">
        Proposé par
        <a href="services.php?reparateur_id=<?= $proprietaire ?>">
            <?= htmlspecialchars(($svc['prenom'] ?? '') . ' ' . ($svc['nom'] ?? '')) ?>
        </a>
        (<?= htmlspecialchars($svc['ville'] ?? '') ?>)
    </p>
    <p><?= htmlspecialchars(mb_strimwidth($svc['description'], 0, 180, '...')) ?></p>

    <p class="stars">
        <?= etoiles($note) ?>
        <span class="muted">
            <?= $nbAvis > 0 ? '(' . $nbAvis . ' avis)' : '(aucun avis)' ?>
        </span>
    </p>

    <div class="price">
        <?= number_format((float)$svc['tarif'], 2, ',', ' ') ?> €
    </div>

    <div class="card-actions">
        <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'client'): ?>
            <a href="reserver.php?service_id=<?= (int)$svc['id'] ?>" class="btn">Réserver cette prestation</a>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <a href="login.php?retour=<?= urlencode('/reserver.php?service_id=' . (int)$svc['id']) ?>" class="btn">Se connecter pour réserver</a>
        <?php endif; ?>

        <?php if (!isset($_SESSION['user_id']) || $proprietaire !== (int)$_SESSION['user_id']): ?>
            <a href="signalement.php?type=service&id=<?= (int)$svc['id'] ?>" class="link-danger" style="align-self:center;">Signaler</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($svc['avis'])): ?>
        <div class="avis-list">
            <h4>Avis récents</h4>
            <?php foreach ($svc['avis'] as $a): ?>
                <div class="avis-item">
                    <div class="avis-head">
                        <span class="stars"><?= etoiles((float)$a['note']) ?></span>
                        <?php if (!isset($_SESSION['user_id']) || (int)($a['client_id'] ?? 0) !== (int)$_SESSION['user_id']): ?>
                            <a href="signalement.php?type=avis&id=<?= (int)$a['id'] ?>" class="link-danger">Signaler</a>
                        <?php endif; ?>
                    </div>
                    <p class="avis-commentaire"><?= htmlspecialchars(mb_strimwidth($a['commentaire'], 0, 220, '...')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
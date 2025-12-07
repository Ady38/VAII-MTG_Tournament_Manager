<?php

/** @var string $contentHTML */
/** @var \Framework\Auth\AppUser $user */
/** @var \Framework\Support\LinkGenerator $link */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= App\Configuration::APP_NAME ?></title>
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $link->asset('favicons/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $link->asset('favicons/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $link->asset('favicons/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= $link->asset('favicons/site.webmanifest') ?>">
    <link rel="shortcut icon" href="<?= $link->asset('favicons/favicon.ico') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php $cssFile = __DIR__ . '/../../../public/css/styl.css';
    $ver = is_file($cssFile) ? filemtime($cssFile) : time(); ?>
    <link rel="stylesheet" href="<?= $link->asset('css/styl.css') ?>?v=<?= $ver ?>">
    <script src="<?= $link->asset('js/script.js') ?>"></script>
    <script src="<?= $link->asset('js/tournament_sort.js') ?>"></script>
    <script src="<?= $link->asset('js/tournament_add_modal.js') ?>"></script>
    <script src="<?= $link->asset('js/tournament_edit_modal.js') ?>"></script>
    <script src="<?= $link->asset('js/tournament_sign_up_btn.js') ?>"></script>
</head>
<body>
<nav class="navbar navbar-expand-sm bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $link->url('home.index') ?>">
            <img src="<?= $link->asset('images/logo_empty.png') ?>" title="<?= App\Configuration::APP_NAME ?>" alt="Framework Logo">
        </a>
        <ul class="navbar-nav me-auto">
            <li class="nav-item">
                <a class="nav-link" href="<?= $link->url('Tournament.index') ?>">Tournaments</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= $link->url('Statistics.index') ?>">Statistics</a>
            </li>
        </ul>
        <?php if ($user->isLoggedIn()) { ?>
            <?php $identity = $user->getIdentity(); ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <!-- username links to the user's detail page -->
                    <a class="nav-link" href="<?= $link->url('User.detail', ['id' => $identity->user_id]) ?>">Logged in user: <b><?= htmlspecialchars($user->getName()) ?></b></a>
                </li>
                <li class="nav-item">
                    <!-- logout link next to username -->
                    <a class="nav-link" href="<?= $link->url('Auth.logout') ?>">Log out</a>
                </li>
            </ul>
        <?php } else { ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= App\Configuration::LOGIN_URL ?>">Log in</a>
                </li>
            </ul>
        <?php } ?>
    </div>
</nav>
<div class="container-fluid mt-3">
    <div class="web-content">
        <?= $contentHTML ?>
    </div>
</div>
</body>
</html>

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
    <link rel="stylesheet" href="<?= $link->asset('css/bootstrap.min.css') ?>">
    <script src="<?= $link->asset('js/bootstrap.bundle.min.js') ?>"></script>
    <link rel="stylesheet" href="<?= $link->asset('css/styl.css') ?>">
    <?php // tournament.css merged into styl.css ?>
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
        </ul>
        <?php if ($user->isLoggedIn()) { ?>
            <span class="navbar-text">Logged in user: <b><?= $user->getName() ?></b></span>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $link->url('auth.logout') ?>">Log out</a>
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

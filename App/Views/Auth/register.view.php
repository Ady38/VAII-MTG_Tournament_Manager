<?php

//Vytvorene s pomocou GitHub Copilot

/** @var string|null $message */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-9 col-md-7 col-lg-5">
            <div class="home-next-tournament-card card my-5">
                <div class="card-header text-center home-next-tournament-title">
                    <?= App\Configuration::APP_NAME ?> — Register
                </div>
                <div class="card-body">
                    <h5 class="card-title text-center">Create account</h5>

                    <?php if (!empty($message)): ?>
                        <div class="text-center text-danger mb-3"><?= $message ?></div>
                    <?php endif; ?>

                    <form class="form-signin" method="post" action="<?= $link->url('auth.register') ?>">
                        <div class="form-label-group mb-3">
                            <label for="username" class="filters-label">Username</label>
                            <input name="username" type="text" id="username" class="form-control filters-input" placeholder="Username" required autofocus>
                        </div>

                        <div class="form-label-group mb-3">
                            <label for="email" class="filters-label">Email</label>
                            <input name="email" type="email" id="email" class="form-control filters-input" placeholder="Email" required>
                        </div>

                        <div class="form-label-group mb-3">
                            <label for="password" class="filters-label">Password</label>
                            <input name="password" type="password" id="password" class="form-control filters-input" placeholder="Password" required>
                        </div>

                        <div class="form-label-group mb-3">
                            <label for="password2" class="filters-label">Repeat password</label>
                            <input name="password2" type="password" id="password2" class="form-control filters-input" placeholder="Repeat password" required>
                        </div>

                        <div class="text-center">
                            <button class="home-primary-btn w-100" type="submit" name="submit">Create account</button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        Already have an account? <a href="<?= $link->url('auth.login') ?>">Log in</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

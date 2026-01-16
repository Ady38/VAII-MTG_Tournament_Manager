<?php

/** @var string|null $message */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-9 col-md-7 col-lg-5">
            <!-- Use existing themed card class instead of introducing new styles -->
            <div class="home-next-tournament-card card my-5">
                <div class="card-header text-center home-next-tournament-title">
                    <?= App\Configuration::APP_NAME ?> — Login
                </div>
                <div class="card-body">
                    <h5 class="card-title text-center">Sign in</h5>

                    <div class="text-center text-danger mb-3">
                        <?= @$message ?>
                    </div>

                    <form class="form-signin" method="post" action="<?= $link->url("login") ?>">
                        <div class="form-label-group mb-3">
                            <label for="username" class="filters-label">Username</label>
                            <input name="username" type="text" id="username" class="form-control filters-input"
                                   placeholder="Username" required autofocus>
                        </div>

                        <div class="form-label-group mb-3">
                            <label for="password" class="filters-label">Password</label>
                            <input name="password" type="password" id="password" class="form-control filters-input"
                                   placeholder="Password" required>
                        </div>

                        <div class="text-center">
                            <!-- Use existing site button style and make it full width like other primary actions -->
                            <button class="home-primary-btn w-100" type="submit" name="submit">Log in</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

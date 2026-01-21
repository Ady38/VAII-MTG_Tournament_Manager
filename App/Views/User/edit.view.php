<?php

//Vytvorene s pomocou GitHub Copilot

/** @var \App\Models\User $profileUser */
/** @var array $errors */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser $user */
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-3 home-next-tournament-card">
            <div class="card-body text-center">
                <div class="profile-avatar"><?= htmlspecialchars(mb_substr($profileUser->username,0,1)) ?></div>
                <h4 class="card-title mb-0" style="color:#EAEAEA"><?= htmlspecialchars($profileUser->username) ?></h4>
                <div class="text-muted" style="color:#EAEAEA; opacity:0.8"><?= htmlspecialchars($profileUser->email) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-3 home-next-tournament-card">
            <div class="card-header" style="background:transparent; border-bottom:none; color:#d4af37; font-weight:600">Edit profile</div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?>
                            <div><?= htmlspecialchars($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= $link->url('User.edit') ?>">
                    <div class="mb-3">
                        <label for="username_input" class="form-label">Username</label>
                        <input id="username_input" type="text" name="username" class="form-control" value="<?= htmlspecialchars($profileUser->username) ?>" required autofocus style="background:#000;color:#EAEAEA;border:1px solid #D4AF37">
                    </div>

                    <div class="mb-3">
                        <label for="email_input" class="form-label">Email</label>
                        <input id="email_input" type="email" name="email" class="form-control" value="<?= htmlspecialchars($profileUser->email) ?>" required style="background:#000;color:#EAEAEA;border:1px solid #D4AF37">
                    </div>

                    <div class="mb-3">
                        <label for="password_input" class="form-label">New password</label>
                        <input id="password_input" type="password" name="password" class="form-control" placeholder="Leave empty to keep current" style="background:#000;color:#EAEAEA;border:1px solid #D4AF37">
                    </div>

                    <div class="mb-3">
                        <label for="password_confirm_input" class="form-label">Confirm new password</label>
                        <input id="password_confirm_input" type="password" name="password_confirm" class="form-control" placeholder="Repeat new password" style="background:#000;color:#EAEAEA;border:1px solid #D4AF37">
                    </div>

                    <div class="d-flex">
                        <button class="btn btn-primary home-primary-btn me-2" type="submit">Save changes</button>
                        <a class="btn btn-secondary home-primary-btn" href="<?= $link->url('User.detail', ['id' => $profileUser->user_id]) ?>">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

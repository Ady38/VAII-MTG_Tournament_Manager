<?php

//Vytvorene s pomocou GitHub Copilot

/** @var \App\Models\User $profileUser */
/** @var array $recentTournaments */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser $user */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-3 home-next-tournament-card">
            <div class="card-body text-center">
                <!-- simple avatar: first letter of username (dark avatar) -->
                <div class="profile-avatar"><?= htmlspecialchars(mb_substr($profileUser->username,0,1)) ?></div>
                <h4 class="card-title mb-0" style="color:#EAEAEA"><?= htmlspecialchars($profileUser->username) ?></h4>
                <div class="text-muted" style="color:#EAEAEA; opacity:0.8"><?= htmlspecialchars($profileUser->email) ?></div>
                <?php if ($user->isLoggedIn() && $user->getIdentity()->user_id == $profileUser->user_id): ?>
                    <div class="mt-3">
                        <a class="btn btn-primary btn-sm home-primary-btn" href="<?= $link->url('User.edit') ?>">Edit profile</a>
                        <!-- Admin-only user management button -->
                        <?php if (isset($user) && $user->isLoggedIn() && ($user->getIdentity()->role_id ?? 0) == 1): ?>
                            <a class="btn btn-warning btn-sm ms-2" href="<?= $link->url('Admin.users') ?>">User management</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-3 home-next-tournament-card">
            <div class="card-header">Recent tournaments</div>
            <div class="card-body">
                <?php if (!empty($recentTournaments)): ?>
                    <div class="list-group">
                        <?php foreach ($recentTournaments as $rt): ?>
                            <a class="list-group-item list-group-item-action" href="<?= $link->url('Tournament.detail', ['id' => $rt['tournament_id']]) ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?= htmlspecialchars($rt['name']) ?></h5>
                                    <small class="text-muted"><?= htmlspecialchars($rt['status'] ?? '') ?></small>
                                </div>
                                <p class="mb-1 text-muted"><?= $rt['start_date'] ? htmlspecialchars(substr($rt['start_date'],0,10)) : '—' ?></p>
                                <small>Points: <?= htmlspecialchars((string)($rt['points'] ?? 0)) ?><?php if (!empty($rt['rank_position'])): ?> • Rank: <?= htmlspecialchars((string)$rt['rank_position']) ?><?php endif; ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No tournament history available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<a class="btn btn-secondary home-primary-btn" href="<?= $link->url('Home.index') ?>">Back</a>

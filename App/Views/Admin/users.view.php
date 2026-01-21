<?php
/** @var \App\Models\User[] $users */
/** @var array $roles */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser $user */
?>

<div class="tournament-tabs-frame">
    <h4 style="color:#EAEAEA; margin-top:0; margin-bottom:12px;">User management</h4>

    <?php if (!empty($this->app->getSession()->get('flash_success'))): ?>
        <div class="alert alert-success"><?= htmlspecialchars($this->app->getSession()->get('flash_success')) ?></div>
        <?php $this->app->getSession()->set('flash_success', null); ?>
    <?php endif; ?>
    <?php if (!empty($this->app->getSession()->get('flash_error'))): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($this->app->getSession()->get('flash_error')) ?></div>
        <?php $this->app->getSession()->set('flash_error', null); ?>
    <?php endif; ?>

    <div class="table-responsive">
        <?php if (empty($users)): ?>
            <p>No users found.</p>
        <?php else: ?>
            <table class="tournament-table" aria-label="Users table">
                <thead>
                    <tr>
                        <th style="width:32%">Username</th>
                        <th style="width:46%">Email</th>
                        <th style="width:12%">Role</th>
                        <th style="width:10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr class="tournament-row">
                            <td style="vertical-align:middle; text-align:left; width:32%;"><?= htmlspecialchars($u->username) ?></td>
                            <td style="vertical-align:middle; text-align:left; width:46%;"><?= htmlspecialchars($u->email) ?></td>
                            <td style="vertical-align:middle; text-align:center; width:12%;">
                                <form method="post" action="<?= $link->url('Admin.updateUser') ?>" style="display:inline-flex; gap:8px; align-items:center;">
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($u->user_id) ?>">
                                    <input type="hidden" name="username" value="<?= htmlspecialchars($u->username) ?>">
                                    <input type="hidden" name="email" value="<?= htmlspecialchars($u->email) ?>">

                                    <select name="role_id" class="edit-modal-select" required aria-label="Role">
                                        <?php foreach ($roles as $rid => $rname): ?>
                                            <option value="<?= htmlspecialchars($rid) ?>" <?= ($u->role_id == $rid) ? 'selected' : '' ?>><?= htmlspecialchars($rname) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit" class="edit-modal-save">Save</button>
                                </form>
                            </td>
                            <td style="text-align:center; vertical-align:middle; width:10%;">
                                <?php if ($user->isLoggedIn() && $user->getIdentity()->user_id != $u->user_id): ?>
                                    <form method="post" action="<?= $link->url('Admin.deleteUser') ?>" style="display:inline-block;">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($u->user_id) ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete user <?= htmlspecialchars($u->username) ?>?');">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<a class="btn btn-secondary" href="<?= $link->url('Admin.index') ?>">Back</a>

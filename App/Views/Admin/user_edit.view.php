<?php
/** @var \App\Models\User $editUser */
/** @var array $errors */
/** @var \Framework\Support\LinkGenerator $link */
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="tournament-tabs-frame">
    <h4 style="color:#EAEAEA; margin:6px 0 8px 0;">User management</h4>

    <div class="table-responsive">
        <form method="post" action="<?= $link->url('Admin.updateUser') ?>">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($editUser->user_id) ?>">
            <table class="tournament-table" aria-label="Edit user">
                <thead>
                    <tr>
                        <th style="width:32%">Username</th>
                        <th style="width:46%">Email</th>
                        <th style="width:12%">Role</th>
                        <th style="width:10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="tournament-row">
                        <td style="vertical-align:middle; text-align:left; width:32%;">
                            <label class="visually-hidden" for="username_input">Username</label>
                            <input id="username_input" type="text" name="username" class="edit-modal-input" value="<?= htmlspecialchars($editUser->username) ?>" required>
                        </td>
                        <td style="vertical-align:middle; text-align:left; width:46%;">
                            <label class="visually-hidden" for="email_input">Email</label>
                            <input id="email_input" type="email" name="email" class="edit-modal-input" value="<?= htmlspecialchars($editUser->email) ?>" required>
                        </td>
                        <td style="vertical-align:middle; text-align:center; width:12%;">
                            <label class="visually-hidden" for="role_input">Role</label>
                            <select id="role_input" name="role_id" class="edit-modal-select">
                                <option value="1" <?= ($editUser->role_id == 1) ? 'selected' : '' ?>>Admin</option>
                                <option value="2" <?= ($editUser->role_id == 2) ? 'selected' : '' ?>>Organizer</option>
                                <option value="3" <?= ($editUser->role_id == 3) ? 'selected' : '' ?>>Player</option>
                            </select>
                        </td>
                        <td class="actions-cell" style="text-align:center; vertical-align:middle; width:10%;">
                            <button type="submit" class="edit-modal-save">Save</button>
                            <a class="btn btn-secondary" href="<?= $link->url('Admin.users') ?>">Cancel</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>

<?php
/** @var array $tournaments */

$addFormData = [
    'name' => '',
    'location' => '',
    'start_date' => '',
    'end_date' => '',
    'status' => 'planned',
];
if (!empty($_SESSION['add_form_data'])) {
    $addFormData = array_merge($addFormData, $_SESSION['add_form_data']);
    unset($_SESSION['add_form_data']);
}

if (!empty($_SESSION['add_errors'])) {
    echo '<script>window.addModalErrors = ' . json_encode(array_map('htmlspecialchars', $_SESSION['add_errors'])) . ';</script>';
    unset($_SESSION['add_errors']);
}

// Helper to format datetime for datetime-local input
function format_datetime_local($dt) {
    if (!$dt) return '';
    $t = strtotime($dt);
    if (!$t) return '';
    return date('Y-m-d\TH:i', $t);
}
?>

<h1 class="tournaments-title">Tournaments</h1>

<!-- Centered filters form wrapper -->
<div class="filters-wrapper">
    <!-- Filters form -->
    <form method="get" action="" class="filters-form">
        <input type="hidden" name="c" value="Tournament">
        <input type="hidden" name="a" value="index">
        <div class="filters-row">
            <div>
                <label for="filter_name" class="filters-label">Name</label>
                <input type="text" id="filter_name" name="name" value="<?= htmlspecialchars($filters['name'] ?? '', ENT_QUOTES) ?>"
                       class="filters-input">
            </div>
            <div>
                <label for="filter_location" class="filters-label">Location</label>
                <input type="text" id="filter_location" name="location" value="<?= htmlspecialchars($filters['location'] ?? '', ENT_QUOTES) ?>"
                       class="filters-input">
            </div>
            <div>
                <label for="filter_date" class="filters-label">Date</label>
                <input type="date" id="filter_date" name="date" value="<?= htmlspecialchars($filters['date'] ?? '', ENT_QUOTES) ?>"
                       class="filters-input filters-select">
            </div>
            <div>
                <label for="filter_status" class="filters-label">Status</label>
                <select id="filter_status" name="status" class="filters-select">
                    <?php $currentStatus = $filters['status'] ?? 'all'; ?>
                    <option value="all" <?= $currentStatus === 'all' || $currentStatus === '' ? 'selected' : '' ?>>All</option>
                    <option value="planned" <?= $currentStatus === 'planned' ? 'selected' : '' ?>>Planned</option>
                    <option value="ongoing" <?= $currentStatus === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="finished" <?= $currentStatus === 'finished' ? 'selected' : '' ?>>Finished</option>
                </select>
            </div>
            <div class="filters-actions">
                <button type="submit" class="filters-button">Filter</button>
                <a href="?c=Tournament&a=index" class="filters-reset">Reset</a>
            </div>
        </div>
    </form>
    <?php if ($user->isLoggedIn()): ?>
        <button id="openAddModal" class="filters-button tournament-create-btn" type="button" title="Add tournament">Create tournament</button>
    <?php endif; ?>
</div>

<?php if ($user->isLoggedIn()): ?>
<!-- Add Tournament Modal -->
<div id="addModal" class="edit-modal-overlay">
    <div class="edit-modal-content">
        <button type="button" id="closeAddModal" class="edit-modal-close">&times;</button>
        <h2 class="edit-modal-title">Add tournament</h2>
        <form id="addTournamentForm" method="post" action="?c=Tournament&a=add">
            <div class="edit-modal-field">
                <label for="add_name" class="edit-modal-label">Name</label>
                <input type="text" name="name" id="add_name" class="edit-modal-input" required value="<?= htmlspecialchars($addFormData['name']) ?>">
            </div>
            <div class="edit-modal-field">
                <label for="add_location" class="edit-modal-label">Location</label>
                <input type="text" name="location" id="add_location" class="edit-modal-input" required value="<?= htmlspecialchars($addFormData['location']) ?>">
            </div>
            <div class="edit-modal-row">
                <div>
                    <label for="add_start_date" class="edit-modal-label">Start date & time</label>
                    <input type="datetime-local" name="start_date" id="add_start_date" class="edit-modal-input edit-modal-input-date" required value="<?= format_datetime_local($addFormData['start_date']) ?>">
                </div>
                <div>
                    <label for="add_end_date" class="edit-modal-label">End date & time</label>
                    <input type="datetime-local" name="end_date" id="add_end_date" class="edit-modal-input edit-modal-input-date" required value="<?= format_datetime_local($addFormData['end_date']) ?>">
                </div>
            </div>
            <div class="edit-modal-field">
                <label for="add_status" class="edit-modal-label">Status</label>
                <select name="status" id="add_status" class="edit-modal-select" required>
                    <option value="planned" <?= $addFormData['status'] === 'planned' ? 'selected' : '' ?>>Planned</option>
                    <option value="ongoing" <?= $addFormData['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="finished" <?= $addFormData['status'] === 'finished' ? 'selected' : '' ?>>Finished</option>
                </select>
            </div>
            <div class="edit-modal-actions">
                <button type="button" id="cancelAdd" class="edit-modal-cancel">Cancel</button>
                <button type="submit" class="edit-modal-save">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Simple centered modal -->
<div id="editModal" class="edit-modal-overlay">
    <div class="edit-modal-content">
        <!-- close button -->
        <button type="button" id="closeEditModal" class="edit-modal-close">&times;</button>

        <h2 class="edit-modal-title">Edit tournament</h2>

        <!-- edit form inside modal -->
        <form id="editTournamentForm" method="post" action="?c=Tournament&a=edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="edit-modal-field">
                <label for="edit_name" class="edit-modal-label">Name</label>
                <input type="text" name="name" id="edit_name" class="edit-modal-input">
            </div>

            <div class="edit-modal-field">
                <label for="edit_location" class="edit-modal-label">Location</label>
                <input type="text" name="location" id="edit_location" class="edit-modal-input">
            </div>

            <div class="edit-modal-row">
                <div>
                    <label for="edit_start_date" class="edit-modal-label">Start date & time</label>
                    <input type="datetime-local" name="start_date" id="edit_start_date" class="edit-modal-input edit-modal-input-date">
                </div>
                <div>
                    <label for="edit_end_date" class="edit-modal-label">End date & time</label>
                    <input type="datetime-local" name="end_date" id="edit_end_date" class="edit-modal-input edit-modal-input-date">
                </div>
            </div>

            <div class="edit-modal-field">
                <label for="edit_status" class="edit-modal-label">Status</label>
                <select name="status" id="edit_status" class="edit-modal-select">
                    <option value="planned">Planned</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="finished">Finished</option>
                </select>
            </div>

            <div class="edit-modal-actions">
                <button type="button" id="cancelEdit" class="edit-modal-cancel">Cancel</button>
                <button type="submit" class="edit-modal-save">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<table id="tournamentTable" class="tournament-table">
    <thead>
        <tr>
            <th data-sort="text">Name</th>
            <th data-sort="text">Location</th>
            <th data-sort="date">Start Date</th>
            <th data-sort="date">End Date</th>
            <th data-sort="text">Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tournaments as $tournament): ?>
            <tr class="tournament-row">
                <td><?= htmlspecialchars($tournament->name) ?></td>
                <td><?= htmlspecialchars($tournament->location) ?></td>
                <td><?= htmlspecialchars($tournament->start_date) ?></td>
                <td><?= htmlspecialchars($tournament->end_date) ?></td>
                <td><?= htmlspecialchars($tournament->status) ?></td>
                <td>
                    <?php if ($user->isLoggedIn()): ?>
                        <a href="#" class="edit-link tournament-action-edit"
                           data-id="<?= $tournament->tournament_id ?>"
                           data-name="<?= htmlspecialchars($tournament->name, ENT_QUOTES) ?>"
                           data-location="<?= htmlspecialchars($tournament->location, ENT_QUOTES) ?>"
                           data-start_date="<?= htmlspecialchars(format_datetime_local($tournament->start_date), ENT_QUOTES) ?>"
                           data-end_date="<?= htmlspecialchars(format_datetime_local($tournament->end_date), ENT_QUOTES) ?>"
                           data-status="<?= htmlspecialchars($tournament->status, ENT_QUOTES) ?>">
                            Edit
                        </a>
                        <span class="tournament-action-separator">|</span>
                        <a href="?c=Tournament&a=delete&id=<?= $tournament->tournament_id ?>" class="tournament-action-delete" onclick="return confirm('Are you sure?')">Delete</a>
                        <span class="tournament-action-separator">|</span>
                    <?php endif; ?>
                    <a href="?c=Tournament&a=detail&id=<?= $tournament->tournament_id ?>" class="tournament-action-detail">Detail</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<style>
.tournament-create-btn {
    font-size: 0.9em;
    padding: 0.3em 0.8em;
    margin-top: 0.5em;
    margin-left: 0.5em;
    margin-bottom: 0.5em;
    border-radius: 6px;
}
</style>

<script src="/js/tournaments.js"></script>
<script src="/js/tournament_sort.js"></script>
<script src="/js/tournament_add_modal.js"></script>

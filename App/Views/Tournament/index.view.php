<?php /** @var array $tournaments */ ?>

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
                    <label for="edit_start_date" class="edit-modal-label">Start date</label>
                    <input type="date" name="start_date" id="edit_start_date" class="edit-modal-input edit-modal-input-date">
                </div>
                <div>
                    <label for="edit_end_date" class="edit-modal-label">End date</label>
                    <input type="date" name="end_date" id="edit_end_date" class="edit-modal-input edit-modal-input-date">
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
                    <a href="#" class="edit-link tournament-action-edit"
                       data-id="<?= $tournament->tournament_id ?>"
                       data-name="<?= htmlspecialchars($tournament->name, ENT_QUOTES) ?>"
                       data-location="<?= htmlspecialchars($tournament->location, ENT_QUOTES) ?>"
                       data-start_date="<?= htmlspecialchars($tournament->start_date, ENT_QUOTES) ?>"
                       data-end_date="<?= htmlspecialchars($tournament->end_date, ENT_QUOTES) ?>"
                       data-status="<?= htmlspecialchars($tournament->status, ENT_QUOTES) ?>">
                        Edit
                    </a>
                    <span class="tournament-action-separator">|</span>
                    <a href="?c=Tournament&a=delete&id=<?= $tournament->tournament_id ?>" class="tournament-action-delete" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="/js/tournaments.js"></script>

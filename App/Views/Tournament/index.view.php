<?php /** @var array $tournaments */ ?>

<link rel="stylesheet" href="/css/tournament.css">

<h1 style="text-align: center; font-family: 'Garamond', serif; color: #FFD700;">Tournaments</h1>

<!-- Centered filters form wrapper -->
<div style="display:flex; justify-content:center; margin-bottom:15px;">
    <!-- Filters form -->
    <form method="get" action="" style="margin:0; font-family:'Garamond', serif; color:#EAEAEA; background:#111; padding:10px 15px; border:1px solid #D4AF37; border-radius:4px; max-width:900px; width:100%;">
        <input type="hidden" name="c" value="Tournament">
        <input type="hidden" name="a" value="index">
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; justify-content:center;">
            <div>
                <label for="filter_name" style="display:block; margin-bottom:3px;">Name</label>
                <input type="text" id="filter_name" name="name" value="<?= htmlspecialchars($filters['name'] ?? '', ENT_QUOTES) ?>"
                       style="padding:4px; border:1px solid #D4AF37; background:#000; color:#EAEAEA;">
            </div>
            <div>
                <label for="filter_location" style="display:block; margin-bottom:3px;">Location</label>
                <input type="text" id="filter_location" name="location" value="<?= htmlspecialchars($filters['location'] ?? '', ENT_QUOTES) ?>"
                       style="padding:4px; border:1px solid #D4AF37; background:#000; color:#EAEAEA;">
            </div>
            <div>
                <label for="filter_date" style="display:block; margin-bottom:3px;">Date</label>
                <input type="date" id="filter_date" name="date" value="<?= htmlspecialchars($filters['date'] ?? '', ENT_QUOTES) ?>"
                       style="padding:4px; border:1px solid #D4AF37; background:#000; color:#EAEAEA; color-scheme: dark;">
            </div>
            <div>
                <label for="filter_status" style="display:block; margin-bottom:3px;">Status</label>
                <select id="filter_status" name="status"
                        style="padding:4px; border:1px solid #D4AF37; background:#000; color:#EAEAEA; color-scheme: dark;">
                    <?php $currentStatus = $filters['status'] ?? 'all'; ?>
                    <option value="all" <?= $currentStatus === 'all' || $currentStatus === '' ? 'selected' : '' ?>>All</option>
                    <option value="planned" <?= $currentStatus === 'planned' ? 'selected' : '' ?>>Planned</option>
                    <option value="ongoing" <?= $currentStatus === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="finished" <?= $currentStatus === 'finished' ? 'selected' : '' ?>>Finished</option>
                </select>
            </div>
            <div style="display:flex; gap:5px; align-items:center; margin-top:4px;">
                <button type="submit" style="padding:6px 12px; background:#FFD700; border:1px solid #D4AF37; color:#000; font-weight:bold; cursor:pointer; border-radius:4px;">Filter</button>
                <a href="?c=Tournament&a=index" style="padding:6px 12px; background:#333; border:1px solid #777; color:#EAEAEA; text-decoration:none; border-radius:4px;">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Simple centered modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#111; border:1px solid #D4AF37; padding:20px; min-width:350px; color:#EAEAEA; font-family:'Garamond', serif; position:relative;">
        <!-- close button -->
        <button type="button" id="closeEditModal" style="position:absolute; top:8px; right:8px; background:none; border:none; color:#FFD700; font-size:18px; cursor:pointer;">&times;</button>

        <h2 style="margin-top:0; margin-bottom:15px; color:#FFD700; text-align:center;">Edit tournament</h2>

        <!-- edit form inside modal -->
        <form id="editTournamentForm" method="post" action="?c=Tournament&a=edit">
            <input type="hidden" name="id" id="edit_id">

            <div style="margin-bottom:10px;">
                <label for="edit_name" style="display:block; margin-bottom:4px;">Name</label>
                <input type="text" name="name" id="edit_name" style="width:100%; padding:6px; border:1px solid #D4AF37; background:#000; color:#EAEAEA;">
            </div>

            <div style="margin-bottom:10px;">
                <label for="edit_location" style="display:block; margin-bottom:4px;">Location</label>
                <input type="text" name="location" id="edit_location" style="width:100%; padding:6px; border:1px solid #D4AF37; background:#000; color:#EAEAEA;">
            </div>

            <div style="margin-bottom:10px; display:flex; gap:8px;">
                <div style="flex:1;">
                    <label for="edit_start_date" style="display:block; margin-bottom:4px;">Start date</label>
                    <input type="date" name="start_date" id="edit_start_date" style="width:100%; padding:6px; border:1px solid #D4AF37; background:#000; color:#EAEAEA; color-scheme: dark">
                </div>
                <div style="flex:1;">
                    <label for="edit_end_date" style="display:block; margin-bottom:4px;">End date</label>
                    <input type="date" name="end_date" id="edit_end_date" style="width:100%; padding:6px; border:1px solid #D4AF37; background:#000; color:#EAEAEA; color-scheme: dark">
                </div>
            </div>

            <div style="margin-bottom:10px;">
                <label for="edit_status" style="display:block; margin-bottom:4px;">Status</label>
                <select name="status" id="edit_status" style="width:100%; padding:6px; border:1px solid #D4AF37; background:#000; color:#EAEAEA; color-scheme: dark">
                    <option value="planned">Planned</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="finished">Finished</option>
                </select>
            </div>

            <div style="text-align:right; margin-top:15px;">
                <button type="button" id="cancelEdit" style="padding:6px 12px; margin-right:6px; background:#333; border:1px solid #777; color:#EAEAEA; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:6px 12px; background:#FFD700; border:1px solid #D4AF37; color:#000; font-weight:bold; cursor:pointer;">Save</button>
            </div>
        </form>
    </div>
</div>

<table id="tournamentTable" style="width: 100%; border-collapse: collapse; font-family: 'Garamond', serif; background-color: #0D0D0D; color: #EAEAEA;">
    <thead>
        <tr style="background-color: #3A3A3A; color: #FFD700; text-align: center;">
            <th data-sort="text" style="border: 1px solid #D4AF37; padding: 8px; cursor:pointer;">Name</th>
            <th data-sort="text" style="border: 1px solid #D4AF37; padding: 8px; cursor:pointer;">Location</th>
            <th data-sort="date" style="border: 1px solid #D4AF37; padding: 8px; cursor:pointer;">Start Date</th>
            <th data-sort="date" style="border: 1px solid #D4AF37; padding: 8px; cursor:pointer;">End Date</th>
            <th data-sort="text" style="border: 1px solid #D4AF37; padding: 8px; cursor:pointer;">Status</th>
            <th style="border: 1px solid #D4AF37; padding: 8px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tournaments as $tournament): ?>
            <tr style="text-align: center; background-color: #1E1E1E;">
                <td style="border: 1px solid #D4AF37; padding: 8px;"><?= htmlspecialchars($tournament->name) ?></td>
                <td style="border: 1px solid #D4AF37; padding: 8px;"><?= htmlspecialchars($tournament->location) ?></td>
                <td style="border: 1px solid #D4AF37; padding: 8px;"><?= htmlspecialchars($tournament->start_date) ?></td>
                <td style="border: 1px solid #D4AF37; padding: 8px;"><?= htmlspecialchars($tournament->end_date) ?></td>
                <td style="border: 1px solid #D4AF37; padding: 8px;"><?= htmlspecialchars($tournament->status) ?></td>
                <td style="border: 1px solid #D4AF37; padding: 8px;">
                    <a href="#" class="edit-link"
                       data-id="<?= $tournament->tournament_id ?>"
                       data-name="<?= htmlspecialchars($tournament->name, ENT_QUOTES) ?>"
                       data-location="<?= htmlspecialchars($tournament->location, ENT_QUOTES) ?>"
                       data-start_date="<?= htmlspecialchars($tournament->start_date, ENT_QUOTES) ?>"
                       data-end_date="<?= htmlspecialchars($tournament->end_date, ENT_QUOTES) ?>"
                       data-status="<?= htmlspecialchars($tournament->status, ENT_QUOTES) ?>"
                       style="color: #1E90FF; text-decoration: none;">Edit</a>
                    <span style="margin: 0 5px; color: #FFD700;">|</span>
                    <a href="?c=Tournament&a=delete&id=<?= $tournament->tournament_id ?>" style="color: #FF0000; text-decoration: none;" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('editModal');
        var closeBtn = document.getElementById('closeEditModal');
        var cancelBtn = document.getElementById('cancelEdit');

        var idInput = document.getElementById('edit_id');
        var nameInput = document.getElementById('edit_name');
        var locationInput = document.getElementById('edit_location');
        var startDateInput = document.getElementById('edit_start_date');
        var endDateInput = document.getElementById('edit_end_date');
        var statusSelect = document.getElementById('edit_status');

        function openModal() {
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // open modal on Edit click and prefill form
        document.querySelectorAll('.edit-link').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();

                idInput.value = this.getAttribute('data-id') || '';
                nameInput.value = this.getAttribute('data-name') || '';
                locationInput.value = this.getAttribute('data-location') || '';
                startDateInput.value = (this.getAttribute('data-start_date') || '').substring(0, 10);
                endDateInput.value = (this.getAttribute('data-end_date') || '').substring(0, 10);
                statusSelect.value = this.getAttribute('data-status') || 'planned';

                openModal();
            });
        });

        // close modal on X or Cancel click
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal();
        });

        // close when clicking outside modal content
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Sorting logic
        const table = document.getElementById('tournamentTable');
        if (table) {
            const headers = table.querySelectorAll('thead th[data-sort]');
            const tbody = table.querySelector('tbody');
            let sortState = {}; // columnIndex -> 'asc' | 'desc'

            function compareValues(a, b, type, direction) {
                if (type === 'date') {
                    const da = a ? new Date(a) : null;
                    const db = b ? new Date(b) : null;
                    const va = da ? da.getTime() : 0;
                    const vb = db ? db.getTime() : 0;
                    return direction === 'asc' ? va - vb : vb - va;
                } else { // text
                    const va = (a || '').toString().toLowerCase();
                    const vb = (b || '').toString().toLowerCase();
                    if (va < vb) return direction === 'asc' ? -1 : 1;
                    if (va > vb) return direction === 'asc' ? 1 : -1;
                    return 0;
                }
            }

            headers.forEach(function (header, index) {
                header.addEventListener('click', function () {
                    const type = header.getAttribute('data-sort') || 'text';
                    const current = sortState[index] === 'asc' ? 'desc' : 'asc';
                    sortState = {}; // reset other columns
                    sortState[index] = current;

                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    rows.sort(function (rowA, rowB) {
                        const cellA = rowA.children[index].innerText.trim();
                        const cellB = rowB.children[index].innerText.trim();
                        return compareValues(cellA, cellB, type, current);
                    });

                    // Re-append sorted rows
                    rows.forEach(function (row) { tbody.appendChild(row); });
                });
            });
        }
    });
</script>

<?php
/** @var object $tournament */
?>
<h1>Tournament Detail</h1>
<table class="tournament-detail-table">
    <tr><th>Name</th><td><?= htmlspecialchars($tournament->name) ?></td></tr>
    <tr><th>Location</th><td><?= htmlspecialchars($tournament->location) ?></td></tr>
    <tr><th>Start Date</th><td><?= htmlspecialchars($tournament->start_date) ?></td></tr>
    <tr><th>End Date</th><td><?= htmlspecialchars($tournament->end_date) ?></td></tr>
    <tr><th>Status</th><td><?= htmlspecialchars($tournament->status) ?></td></tr>
</table>
<a href="?c=Tournament&a=index">Back to tournaments</a>


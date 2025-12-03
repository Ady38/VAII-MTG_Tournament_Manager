<?php /** @var array $tournaments */ ?>

<h1>Tournaments</h1>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Location</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tournaments as $tournament): ?>
            <tr>
                <td><?= htmlspecialchars($tournament->name) ?></td>
                <td><?= htmlspecialchars($tournament->location) ?></td>
                <td><?= htmlspecialchars($tournament->start_date) ?></td>
                <td><?= htmlspecialchars($tournament->end_date) ?></td>
                <td><?= htmlspecialchars($tournament->status) ?></td>
                <td>
                    <a href="?c=Tournament&a=edit&id=<?= $tournament->tournament_id ?>">Edit</a>
                    <a href="?c=Tournament&a=delete&id=<?= $tournament->tournament_id ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

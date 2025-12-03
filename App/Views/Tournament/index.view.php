<?php /** @var array $tournaments */ ?>

<link rel="stylesheet" href="/css/tournament.css">

<h1 style="text-align: center; font-family: 'Garamond', serif; color: #FFD700;">Tournaments</h1>

<table style="width: 100%; border-collapse: collapse; font-family: 'Garamond', serif; background-color: #0D0D0D; color: #EAEAEA;">
    <thead>
        <tr style="background-color: #3A3A3A; color: #FFD700; text-align: center;">
            <th style="border: 1px solid #D4AF37; padding: 8px;">Name</th>
            <th style="border: 1px solid #D4AF37; padding: 8px;">Location</th>
            <th style="border: 1px solid #D4AF37; padding: 8px;">Start Date</th>
            <th style="border: 1px solid #D4AF37; padding: 8px;">End Date</th>
            <th style="border: 1px solid #D4AF37; padding: 8px;">Status</th>
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
                    <a href="?c=Tournament&a=edit&id=<?= $tournament->tournament_id ?>" style="color: #1E90FF; text-decoration: none;">Edit</a>
                    <span style="margin: 0 5px; color: #FFD700;">|</span>
                    <a href="?c=Tournament&a=delete&id=<?= $tournament->tournament_id ?>" style="color: #FF0000; text-decoration: none;" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

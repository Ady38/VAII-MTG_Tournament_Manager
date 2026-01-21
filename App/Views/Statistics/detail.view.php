<?php

//Vytvorene s pomocou GitHub Copilot

/** @var \Framework\Support\LinkGenerator $link */
/** @var string $commander */
/** @var array $entries */
?>
<h1>Commander: <?= htmlspecialchars($commander) ?></h1>
<p><a href="?c=Statistics&a=index">Back to statistics</a></p>
<?php if (empty($entries)): ?>
    <p>No decklists found for this commander in top-8s.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="tournament-table text-center">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tournament</th>
                    <th>Player</th>
                    <th>Uploaded</th>
                    <th>Decklist</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($entries as $e): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($e['tournament_name']) ?></td>
                        <td><?= htmlspecialchars($e['username']) ?></td>
                        <td><?= htmlspecialchars($e['uploaded_at']) ?></td>
                        <td><a href="<?= $link->asset($e['file_path']) ?>" download>Download</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script src="<?= $link->asset('js/scryfall_commander_tooltip.js') ?>"></script>

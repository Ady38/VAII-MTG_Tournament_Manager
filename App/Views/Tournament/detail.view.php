<?php
/** @var object $tournament */
/** @var bool $isRegistered */
/** @var bool $isLogged */
/** @var array $rankings */
?>
<h1>Tournament Detail</h1>

<div class="tournament-detail-row">
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">Name</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->name) ?></div>
    </div>
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">Location</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->location) ?></div>
    </div>
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">Start Date</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->start_date) ?></div>
    </div>
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">End Date</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->end_date) ?></div>
    </div>
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">Status</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->status) ?></div>
    </div>
</div>
<a href="?c=Tournament&a=index">Back to tournaments</a>

<hr>
<?php $showAllTabs = in_array($tournament->status, ['ongoing', 'finished']); ?>
<div class="tournament-tabs">
    <button class="tournament-tab-btn active" data-tab="signups">Sign Up</button>
    <?php if ($showAllTabs): ?>
        <button class="tournament-tab-btn" data-tab="pairings">Pairings</button>
        <button class="tournament-tab-btn" data-tab="standings">Rankings</button>
    <?php endif; ?>
</div>

<div class="tournament-tabs-frame">
    <div class="tournament-tab-content" id="tab-signups">
        <?php if ($isLogged): ?>
            <form method="post" action="?c=Tournament&a=<?= $isRegistered ? 'leave' : 'join' ?>">
                <input type="hidden" name="tournament_id" value="<?= htmlspecialchars($tournament->tournament_id) ?>">
                <button type="submit" class="btn btn-primary btn-lg home-primary-btn mt-2">
                    <?= $isRegistered ? 'Odhlásiť sa z turnaja' : 'Prihlásiť sa na turnaj' ?>
                </button>
            </form>
        <?php else: ?>
            <p>Pre prihlásenie na turnaj sa prosím <a href="?c=Auth&a=login">prihláste</a>.</p>
        <?php endif; ?>
    </div>
    <?php if ($showAllTabs): ?>
        <div class="tournament-tab-content" id="tab-pairings" style="display:none">
            <p>Tu bude párovanie hráčov/zápasov.</p>
        </div>
        <div class="tournament-tab-content" id="tab-standings" style="display:none">
            <?php if (!empty($rankings)): ?>
                <table class="tournament-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Hráč</th>
                        <th>Body</th>
                        <th>Pozícia</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; foreach ($rankings as $row): ?>
                        <tr class="tournament-row">
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
                            <td><?= htmlspecialchars((string)($row['points'] ?? 0)) ?></td>
                            <td><?= htmlspecialchars($row['rank_position'] !== null ? (string)$row['rank_position'] : '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Ešte nie sú k dispozícii žiadne výsledky tohto turnaja.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="/js/tournament_sign_up_btn.js"></script>

<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var object $tournament */
/** @var bool $isRegistered */
/** @var bool $isLogged */
/** @var array $rankings */
/** @var array $commanders */
/** @var object|null $user_decklist */

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
        <div class="tournament-detail-label">Start</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->start_date) ?></div>
    </div>
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">End</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->end_date) ?></div>
    </div>
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">Status</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->status) ?></div>
    </div>
</div>

<a href="?c=Tournament&a=index">Back to tournaments</a>

<hr>
<?php
    $showAllTabs = in_array($tournament->status, ['ongoing', 'finished']);
    // If tournament started and user is NOT registered, hide Sign Up tab
    $signUpVisible = !($showAllTabs && !$isRegistered);
    // default active tab: signups if visible, otherwise pairings
    $defaultTab = $signUpVisible ? 'signups' : 'pairings';
?>
<div class="tournament-tabs">
    <?php if ($signUpVisible): ?>
        <button class="tournament-tab-btn <?= $defaultTab === 'signups' ? 'active' : '' ?>" data-tab="signups">Sign Up</button>
    <?php endif; ?>
    <?php if ($showAllTabs): ?>
        <button class="tournament-tab-btn <?= $defaultTab === 'pairings' ? 'active' : '' ?>" data-tab="pairings">Pairings</button>
        <button class="tournament-tab-btn <?= $defaultTab === 'standings' ? 'active' : '' ?>" data-tab="standings">Rankings</button>
    <?php endif; ?>
</div>

<div class="tournament-tabs-frame">
    <div class="tournament-tab-content" id="tab-signups" style="<?= $defaultTab === 'signups' ? '' : 'display:none' ?>">
        <?php if ($isLogged): ?>
            <?php if (!empty($_SESSION['deck_upload_errors'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($_SESSION['deck_upload_errors'] as $err): ?>
                        <div><?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
                <?php unset($_SESSION['deck_upload_errors']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['leave_errors'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($_SESSION['leave_errors'] as $err): ?>
                        <div><?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
                <?php unset($_SESSION['leave_errors']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['deck_upload_success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['deck_upload_success']) ?></div>
                <?php unset($_SESSION['deck_upload_success']); ?>
            <?php endif; ?>

            <?php if (!$isRegistered): ?>
                <!-- Unregistered users: simple register button (no deck upload) -->
                <form method="post" action="?c=Tournament&a=join">
                    <input type="hidden" name="tournament_id" value="<?= htmlspecialchars($tournament->tournament_id) ?>">
                    <button type="submit" class="btn btn-primary btn-lg home-primary-btn mt-2">Register for tournament</button>
                </form>
            <?php else: ?>
                <!-- Registered users: show decklist upload form + unregister button -->
                <div>
                    <?php if (!empty($user_decklist) && !empty($user_decklist->file_path)): ?>
                        <div style="margin-bottom:8px;">
                            <strong>Your decklist:</strong>
                            <a href="<?= $link->asset($user_decklist->file_path) ?>" target="_blank">Download</a>
                        </div>
                        <?php if (!in_array($tournament->status, ['ongoing', 'finished'])): ?>
                            <form method="post" action="?c=Tournament&a=deckDelete" style="display:inline-block; margin-right:10px;">
                                <input type="hidden" name="tournament_id" value="<?= htmlspecialchars($tournament->tournament_id) ?>">
                                <button type="submit" class="btn btn-warning">Delete decklist</button>
                            </form>
                        <?php else: ?>
                            <div class="text-muted" style="display:inline-block; margin-left:8px;">(Decklist nie je možné odstrániť po začatí turnaja)</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- No existing decklist: show upload form -->
                        <form method="post" action="?c=Tournament&a=join" enctype="multipart/form-data" style="display:inline-block; margin-right:10px;">
                            <input type="hidden" name="tournament_id" value="<?= htmlspecialchars($tournament->tournament_id) ?>">
                            <label for="decklist">Upload decklist (.txt, max 100 non-empty lines):</label>
                            <input type="file" id="decklist" name="decklist" accept=".txt,text/plain">
                            <button type="submit" class="btn btn-secondary mt-2">Upload decklist</button>
                        </form>
                    <?php endif; ?>

                    <?php if (!in_array($tournament->status, ['ongoing', 'finished'])): ?>
                        <form method="post" action="?c=Tournament&a=leave" style="display:inline-block;">
                            <input type="hidden" name="tournament_id" value="<?= htmlspecialchars($tournament->tournament_id) ?>">
                            <button type="submit" class="btn btn-danger mt-2">Unregister from tournament</button>
                        </form>
                    <?php else: ?>
                        <div class="text-muted" style="display:inline-block; margin-left:8px;">(Nie je možné odhlásiť sa po začatí turnaja)</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>Please <a href="?c=Auth&a=login">log in</a> to register for this tournament.</p>
        <?php endif; ?>
    </div>
    <?php if ($showAllTabs): ?>
        <div class="tournament-tab-content" id="tab-pairings" style="display:<?= $defaultTab === 'pairings' ? '' : 'none' ?>">
            <p>Player/match pairings will be displayed here.</p>
        </div>
        <div class="tournament-tab-content" id="tab-standings" style="display:none">
            <?php if (!empty($rankings)): ?>
                <table class="tournament-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Player</th>
                        <th>Points</th>
                        <th>Commander</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; foreach ($rankings as $row): ?>
                        <tr class="tournament-row">
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
                            <td><?= htmlspecialchars((string)($row['points'] ?? 0)) ?></td>
                            <?php $comm = $commanders[$row['user_id']] ?? ''; ?>
                            <td class="commander-cell">
                                <?php if ($comm): ?>
                                    <span class="commander-link" data-card-name="<?= htmlspecialchars($comm) ?>"><?= htmlspecialchars($comm) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No standings are available for this tournament yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="<?= $link->asset('js/tournament_sign_up_btn.js') ?>"></script>
<script src="<?= $link->asset('js/scryfall_commander_tooltip.js') ?>"></script>

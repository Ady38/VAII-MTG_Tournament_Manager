<?php

//Vytvorene s pomocou GitHub Copilot

/** @var \Framework\Support\LinkGenerator $link */
/** @var object $tournament */
/** @var bool $isRegistered */
/** @var bool $isLogged */
/** @var bool $isOrganizer */
/** @var array $rankings */
/** @var array $commanders */
/** @var object|null $user_decklist */
/** @var array $pairings */
/** @var int[] $availableRounds */
/** @var int|null $selectedRound */
/** @var string $activeTab */
/** @var array $usernames */

?>
<h1 style="margin-top:0;">Tournament Detail</h1>

<div class="tournament-detail-row">
    <div class="home-next-tournament-card card mb-2" style="min-width:180px; max-width:320px;">
        <div class="card-body tournament-detail-card">
            <div class="tournament-detail-label">Name</div>
            <div class="tournament-detail-value"><?= htmlspecialchars($tournament->name) ?></div>
        </div>
    </div>

    <div class="home-next-tournament-card card mb-2" style="min-width:180px; max-width:320px;">
        <div class="card-body tournament-detail-card">
            <div class="tournament-detail-label">Location</div>
            <div class="tournament-detail-value"><?= htmlspecialchars($tournament->location) ?></div>
        </div>
    </div>

    <div class="home-next-tournament-card card mb-2" style="min-width:180px; max-width:320px;">
        <div class="card-body tournament-detail-card">
            <div class="tournament-detail-label">Start</div>
            <div class="tournament-detail-value"><?= htmlspecialchars($tournament->start_date) ?></div>
        </div>
    </div>

    <div class="home-next-tournament-card card mb-2" style="min-width:180px; max-width:320px;">
        <div class="card-body tournament-detail-card">
            <div class="tournament-detail-label">End</div>
            <div class="tournament-detail-value"><?= htmlspecialchars($tournament->end_date) ?></div>
        </div>
    </div>

    <div class="home-next-tournament-card card mb-2" style="min-width:180px; max-width:220px;">
        <div class="card-body tournament-detail-card">
            <div class="tournament-detail-label">Status</div>
            <div class="tournament-detail-value"><?= htmlspecialchars($tournament->status) ?></div>
        </div>
    </div>
</div>

<div style="margin-top:12px;">
    <a href="?c=Tournament&a=index" class="btn home-primary-btn" style="display:inline-block;">Back to tournaments</a>
</div>

<hr>
<?php
    $showAllTabs = in_array($tournament->status, ['ongoing', 'finished']);
    // If tournament started and user is NOT registered, hide Sign Up tab
    $signUpVisible = !($showAllTabs && !$isRegistered);
    // active tab provided by controller
?>
<div class="tournament-tabs">
    <?php if ($signUpVisible): ?>
        <button class="tournament-tab-btn <?= $activeTab === 'signups' ? 'active' : '' ?>" data-tab="signups">Sign Up</button>
    <?php endif; ?>
    <?php if ($showAllTabs || $isOrganizer): ?>
        <button class="tournament-tab-btn <?= $activeTab === 'pairings' ? 'active' : '' ?>" data-tab="pairings">Pairings</button>
        <button class="tournament-tab-btn <?= $activeTab === 'standings' ? 'active' : '' ?>" data-tab="standings">Rankings</button>
    <?php endif; ?>
</div>

<div class="tournament-tabs-frame">
    <div class="tournament-tab-content" id="tab-signups" style="<?= $activeTab === 'signups' ? '' : 'display:none' ?>">
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

    <?php if ($showAllTabs || $isOrganizer): ?>
        <div class="tournament-tab-content" id="tab-pairings" style="display:<?= $activeTab === 'pairings' ? '' : 'none' ?>">
            <?php if (!empty($_SESSION['pairing_errors'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($_SESSION['pairing_errors'] as $err): ?>
                        <div><?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
                <?php unset($_SESSION['pairing_errors']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['pairing_success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['pairing_success']) ?></div>
                <?php unset($_SESSION['pairing_success']); ?>
            <?php endif; ?>

            <!-- Round Timer Panel -->
            <div id="round-timer-panel" class="mb-3" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <div style="font-weight:600;">Round timer:</div>
                <div id="timer-display" style="font-size:1.2rem; min-width:120px;">Not running</div>

                <?php if ($isOrganizer): ?>
                    <div style="display:inline-flex; align-items:center; gap:8px;">
                        <div id="timer-round-display" style="margin-right:6px;"><?php if ($selectedRound) echo 'Round ' . htmlspecialchars((string)$selectedRound); else echo 'Round: —'; ?></div>
                        <button id="start-timer-btn" type="button" class="edit-modal-save">Start 50m</button>
                        <button id="reset-timer-btn" type="button" class="btn btn-danger">Reset</button>
                    </div>
                <?php else: ?>
                    <div class="text-muted">(Visible to all — only organizer can control)</div>
                <?php endif; ?>
            </div>

            <?php if ($isOrganizer): ?>
                <form method="post" action="?c=Tournament&a=generatePairings" style="margin-bottom:12px; display:inline-block;">
                    <input type="hidden" name="tournament_id" value="<?= htmlspecialchars($tournament->tournament_id) ?>">
                    <button type="submit" class="btn btn-primary btn-lg home-primary-btn mt-2">Generate Pairings (Swiss)</button>
                </form>
            <?php endif; ?>

            <?php if (!empty($availableRounds)): ?>
                <div style="margin-top:12px; margin-bottom:12px;">
                    <label for="round-select">Show round:</label>
                    <select id="round-select" onchange="location.href='?c=Tournament&a=detail&id=<?= htmlspecialchars($tournament->tournament_id) ?>&tab=pairings&round='+this.value">
                        <?php foreach ($availableRounds as $r): ?>
                            <option value="<?= htmlspecialchars((string)$r) ?>" <?= ($selectedRound == $r) ? 'selected' : '' ?>>Round <?= htmlspecialchars((string)$r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php
                    // Filter pairings for selected round
                    $selectedMatches = [];
                    foreach ($pairings as $row) {
                        $rnd = isset($row['round_number']) ? (int)$row['round_number'] : 1;
                        if ($rnd === (int)$selectedRound) $selectedMatches[] = $row;
                    }
                ?>

                <?php if (!empty($selectedMatches)): ?>
                    <div class="table-responsive">
                        <table class="tournament-table">
                            <thead>
                            <tr>
                                <th>Match</th>
                                <th>Player 1</th>
                                <th>Player 2</th>
                                <th>Result</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $mi = 1; foreach ($selectedMatches as $m): ?>
                                <?php
                                    $p1 = (int)$m['player1_id'];
                                    $p2 = (int)$m['player2_id'];
                                    $name1 = $usernames[$p1] ?? (string)$p1;
                                    $name2 = $usernames[$p2] ?? (string)$p2;
                                ?>
                                <tr>
                                    <td><?= $mi++ ?></td>
                                    <td><?= htmlspecialchars($name1) ?><?= ($p1 === $p2) ? ' <em>(BYE)</em>' : '' ?></td>
                                    <td><?= htmlspecialchars($name2) ?></td>
                                    <td>
                                        <?php
                                            $current = (string)($m['result'] ?? 'unplayed');
                                            // map token to friendly label
                                            $label = $current;
                                            $tokenLabels = [
                                                'p1_2_0' => $name1 . ' 2-0',
                                                'p1_2_1' => $name1 . ' 2-1',
                                                'p2_2_0' => $name2 . ' 2-0',
                                                'p2_2_1' => $name2 . ' 2-1',
                                                'unplayed' => 'Unplayed',
                                                'BYE' => 'BYE',
                                                'draw' => 'Draw (1-1)'
                                            ];
                                            if (isset($tokenLabels[strtolower($current)])) {
                                                $label = $tokenLabels[strtolower($current)];
                                            } elseif (isset($tokenLabels[strtoupper($current)])) {
                                                $label = $tokenLabels[strtoupper($current)];
                                            }
                                        ?>
                                        <span class="result-label" data-match-id="<?= htmlspecialchars((string)$m['match_id']) ?>"><?= htmlspecialchars($label) ?></span>

                                        <?php
                                            // Only allow editing results for the latest round (no subsequent round started yet)
                                            $matchRound = isset($m['round_number']) ? (int)$m['round_number'] : 1;
                                            $maxR = isset($maxRound) ? (int)$maxRound : 0;
                                            $canEdit = ($isOrganizer && $p1 !== $p2 && $matchRound === $maxR);
                                        ?>
                                        <?php if ($canEdit): ?>
                                            <form method="post" action="?c=Tournament&a=saveMatchResult" class="result-form" data-match-id="<?= htmlspecialchars((string)$m['match_id']) ?>" style="display:inline-block; margin-left:8px;">
                                                <input type="hidden" name="match_id" value="<?= htmlspecialchars((string)$m['match_id']) ?>">
                                                <input type="hidden" name="tournament_id" value="<?= htmlspecialchars((string)$tournament->tournament_id) ?>">
                                                <select name="result" class="result-select" aria-label="Match result">
                                                    <?php
                                                        $opts = [
                                                            'unplayed' => 'Unplayed',
                                                            'p1_2_0' => $name1 . ' 2-0',
                                                            'p1_2_1' => $name1 . ' 2-1',
                                                            'draw' => 'Draw (1-1)',
                                                            'p2_2_1' => $name2 . ' 2-1',
                                                            'p2_2_0' => $name2 . ' 2-0',
                                                        ];
                                                        foreach ($opts as $val => $text) {
                                                            $sel = ($current === $val) ? 'selected' : '';
                                                            echo '<option value="' . htmlspecialchars($val) . '" ' . $sel . '>' . htmlspecialchars($text) . '</option>';
                                                        }
                                                    ?>
                                                </select>
                                                <noscript><button type="submit" class="btn btn-sm btn-primary">Save</button></noscript>
                                            </form>
                                        <?php else: ?>
                                            <!-- Not editable because it's BYE, not organizer, or a previous round; show disabled select for clarity -->
                                            <select disabled class="result-select" aria-label="Match result (read only)" style="margin-left:8px;">
                                                <?php
                                                    // Use the same options but mark the current one selected and keep disabled
                                                    $opts = [
                                                        'unplayed' => 'Unplayed',
                                                        'p1_2_0' => $name1 . ' 2-0',
                                                        'p1_2_1' => $name1 . ' 2-1',
                                                        'draw' => 'Draw (1-1)',
                                                        'p2_2_1' => $name2 . ' 2-1',
                                                        'p2_2_0' => $name2 . ' 2-0',
                                                    ];
                                                    foreach ($opts as $val => $text) {
                                                        $sel = ($current === $val) ? 'selected' : '';
                                                        echo '<option value="' . htmlspecialchars($val) . '" ' . $sel . '>' . htmlspecialchars($text) . '</option>';
                                                    }
                                                ?>
                                            </select>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No matches were recorded for selected round.</p>
                <?php endif; ?>

            <?php else: ?>
                <p>No pairings yet.</p>
            <?php endif; ?>
        </div>

        <div class="tournament-tab-content" id="tab-standings" style="display:<?= $activeTab === 'standings' ? '' : 'none' ?>">
            <?php if (!empty($rankings)): ?>
                <div class="table-responsive">
                    <table class="tournament-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Player</th>
                            <th>Points</th>
                            <th>GWP</th>
                            <th>OGWP</th>
                            <th>Commander</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; foreach ($rankings as $row): ?>
                            <tr class="tournament-row">
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
                                <?php $pointsText = number_format((float)($row['points'] ?? 0), 2, '.', ''); ?>
                                <td class="points-cell" data-user-id="<?= htmlspecialchars((string)$row['user_id']) ?>"><?= htmlspecialchars($pointsText) ?></td>
                                <?php
                                    // format tiebreakers as percentage strings, fallback to '-' when not available
                                    $gwpVal = $row['gwp'] ?? null;
                                    $gwpText = ($gwpVal === null || $gwpVal === '') ? '-' : number_format((float)$gwpVal * 100, 2) . '%';
                                    $ogwpVal = $row['ogwp'] ?? null;
                                    $ogwpText = ($ogwpVal === null || $ogwpVal === '') ? '-' : number_format((float)$ogwpVal * 100, 2) . '%';
                                ?>
                                <td class="gwp-cell" data-user-id="<?= htmlspecialchars((string)$row['user_id']) ?>"><?= htmlspecialchars($gwpText) ?></td>
                                <td class="ogwp-cell" data-user-id="<?= htmlspecialchars((string)$row['user_id']) ?>"><?= htmlspecialchars($ogwpText) ?></td>
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
                </div>
            <?php else : ?>
                <p>No standings are available for this tournament yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Pass config via hidden div to avoid inline scripts
$cfg = [
    'tournamentId' => (int)$tournament->tournament_id,
    'selectedRound' => $selectedRound ? (int)$selectedRound : 0,
    'timerPollInterval' => 5000,
];
echo '<div id="tournament-detail-config" data-config="' . htmlspecialchars(json_encode($cfg), ENT_QUOTES, 'UTF-8') . '" style="display:none"></div>';
?>

<script src="<?= $link->asset('js/tournament_detail_loader.js') ?>"></script>
<script src="<?= $link->asset('js/scryfall_commander_tooltip.js') ?>"></script>
<script src="<?= $link->asset('js/tournament_detail.js') ?>"></script>
<script src="<?= $link->asset('js/tournament_timer.js') ?>"></script>

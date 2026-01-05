<?php
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
                                                <select name="result" class="result-select">
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
                                            <select disabled class="result-select" style="margin-left:8px;">
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
                                <td class="points-cell" data-user-id="<?= htmlspecialchars((string)$row['user_id']) ?>"><?= htmlspecialchars((string)round((float)($row['points'] ?? 0), 2)) ?></td>
                                <?php
                                    // format tiebreakers as percentage strings, fallback to '-' when not available
                                    $fmt = function($v){ return ($v === null || $v === '') ? '-' : number_format((float)$v * 100, 2) . '%'; };
                                ?>
                                <td class="gwp-cell" data-user-id="<?= htmlspecialchars((string)$row['user_id']) ?>"><?= htmlspecialchars($fmt($row['gwp'] ?? null)) ?></td>
                                <td class="ogwp-cell" data-user-id="<?= htmlspecialchars((string)$row['user_id']) ?>"><?= htmlspecialchars($fmt($row['ogwp'] ?? null)) ?></td>
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

<script src="<?= $link->asset('js/tournament_sign_up_btn.js') ?>"></script>
<script src="<?= $link->asset('js/scryfall_commander_tooltip.js') ?>"></script>

<script>
// Small tab switching behavior to keep UI interactive
document.querySelectorAll('.tournament-tab-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var tab = btn.getAttribute('data-tab');
        document.querySelectorAll('.tournament-tab-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.tournament-tab-content').forEach(function(c){
            c.style.display = c.id === 'tab-'+tab ? '' : 'none';
        });
    });
});

// Helper: rebuild the standings table body from server-sent rankings and commanders
function updateStandings(rankings, commanders) {
    try {
        var tbody = document.querySelector('#tab-standings .table-responsive table.tournament-table tbody');
        if (!tbody) return;
        // Clear existing rows
        while (tbody.firstChild) tbody.removeChild(tbody.firstChild);

        var fmtPercent = function(v){
            if (v === null || v === undefined || v === '') return '-';
            var num = Number(v);
            if (isNaN(num)) return '-';
            return (num * 100).toFixed(2) + '%';
        };

        for (var i = 0; i < rankings.length; i++) {
            var r = rankings[i];
            var tr = document.createElement('tr'); tr.className = 'tournament-row';

            // #
            var tdIdx = document.createElement('td'); tdIdx.textContent = (i+1).toString(); tr.appendChild(tdIdx);

            // Player
            var tdPlayer = document.createElement('td'); tdPlayer.textContent = r.username || r.user_id; tr.appendChild(tdPlayer);

            // Points
            var tdPoints = document.createElement('td'); tdPoints.className = 'points-cell'; tdPoints.setAttribute('data-user-id', String(r.user_id));
            tdPoints.textContent = (typeof r.points !== 'undefined' && r.points !== null) ? Number(r.points).toFixed(2) : '0.00';
            tr.appendChild(tdPoints);

            // GWP
            var tdGwp = document.createElement('td'); tdGwp.className = 'gwp-cell'; tdGwp.setAttribute('data-user-id', String(r.user_id));
            tdGwp.textContent = fmtPercent(r.gwp);
            tr.appendChild(tdGwp);

            // OGWP
            var tdOgwp = document.createElement('td'); tdOgwp.className = 'ogwp-cell'; tdOgwp.setAttribute('data-user-id', String(r.user_id));
            tdOgwp.textContent = fmtPercent(r.ogwp);
            tr.appendChild(tdOgwp);

            // Commander
            var tdComm = document.createElement('td'); tdComm.className = 'commander-cell';
            var comm = (commanders && commanders[String(r.user_id)]) ? commanders[String(r.user_id)] : '';
            if (comm) {
                var span = document.createElement('span'); span.className = 'commander-link'; span.setAttribute('data-card-name', comm); span.textContent = comm;
                tdComm.appendChild(span);
            }
            tr.appendChild(tdComm);

            tbody.appendChild(tr);
        }
    } catch (e) {
        console.error('updateStandings error', e);
    }
}

// AJAX result save: send result via fetch and update UI
document.addEventListener('DOMContentLoaded', function(){
    // Attach handler to each result form
    document.querySelectorAll('.result-form').forEach(function(form){
        var select = form.querySelector('.result-select');
        if (!select) return;
        // Prefer explicit attribute so we don't rely on dataset normalization
        var matchId = form.getAttribute('data-match-id') || form.dataset.matchId || '';

        select.addEventListener('change', function(e){
            var fd = new FormData(form);
            fd.append('ajax', '1');
            // disable while saving
            select.disabled = true;

            var url = form.getAttribute('action') || form.action;

            fetch(url, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function(resp){
                if (!resp.ok) {
                    return resp.text().then(function(text){
                        throw new Error('Server returned HTTP ' + resp.status + '. First 200 chars: ' + text.substring(0,200));
                    });
                }
                // If server returned a JSON response, parse it. Otherwise read text and fail with a helpful error.
                var ct = resp.headers.get('content-type') || '';
                if (ct.indexOf('application/json') !== -1) {
                    return resp.json();
                }
                return resp.text().then(function(text){
                    throw new Error('Server returned non-JSON response. First 200 chars: ' + text.substring(0,200));
                });
            })
            .then(function(json){
                if (json && json.success) {
                    // update result label for this match
                    var lbl = document.querySelector('.result-label[data-match-id="' + matchId + '"]');
                    if (lbl) {
                        // if server returned canonical result token, map friendly label, otherwise fall back to select text
                        if (json.result === 'unplayed') {
                            lbl.textContent = 'Unplayed';
                        } else if (json.result === 'draw') {
                            lbl.textContent = 'Draw (1-1)';
                        } else if (json.result) {
                            // use the select option's visible text (already localized)
                            lbl.textContent = select.options[select.selectedIndex].text;
                        } else {
                            lbl.textContent = select.options[select.selectedIndex].text;
                        }
                        // brief visual feedback: add success class and remove after 700ms
                        lbl.classList.add('result-saved');
                        setTimeout(function(){ lbl.classList.remove('result-saved'); }, 700);
                    }

                    // update points cells if provided
                    if (json.points) {
                        Object.keys(json.points).forEach(function(uid){
                            var cell = document.querySelector('.points-cell[data-user-id="' + uid + '"]');
                            if (cell) {
                                cell.textContent = json.points[uid];
                            }
                        });
                    }

                    // If server returned full rankings data, update the standings table live
                    if (json.rankings) {
                        updateStandings(json.rankings, json.commanders || {});
                    }

                    // Re-enable the select so the organizer can change the result again immediately
                    select.disabled = false;
                } else {
                    // handle known error cases
                    var err = json && json.error ? json.error : 'Unknown error';
                    var lbl = document.querySelector('.result-label[data-match-id="' + matchId + '"]');
                    if (lbl) lbl.textContent = 'Error: ' + err;
                }
            })
            .catch(function(err){
                console.error('Fetch error:', err);
                // Re-enable select element if there was an error
                select.disabled = false;
                alert('Error saving result: ' + err.message);
            });
        });
    });
});
</script>

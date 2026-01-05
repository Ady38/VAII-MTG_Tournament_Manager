<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use App\Models\Decklist;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\ViewResponse;
use Framework\Support\LinkGenerator;

class TournamentController extends BaseController
{
    public function index(Request $request): Response
    {
        // Read filter parameters from query string
        $name = trim((string)$request->get('name'));
        $location = trim((string)$request->get('location'));
        $status = trim((string)$request->get('status'));
        $date = trim((string)$request->get('date'));

        // Get all tournaments first
        $tournaments = Tournament::getAll();

        // Filter in PHP according to requested criteria
        $filtered = array_filter($tournaments, function (Tournament $tournament)
         use ($name, $location, $status, $date) {
            // Filter by name (case-insensitive substring)
            if ($name !== '' && stripos($tournament->name, $name) === false) {
                return false;
            }

            // Filter by location (case-insensitive substring)
            if ($location !== '' && stripos((string)$tournament->location, $location) === false) {
                return false;
            }

            // Filter by status (exact match)
            if ($status !== '' && $status !== 'all' && $tournament->status !== $status) {
                return false;
            }

            // Filter by date in range [start_date, end_date]
            if ($date !== '') {
                $d = substr($date, 0, 10);
                $start = $tournament->start_date ? substr($tournament->start_date, 0, 10) : null;
                $end = $tournament->end_date ? substr($tournament->end_date, 0, 10) : null;

                if ($start && $end) {
                    if ($d < $start || $d > $end) {
                        return false;
                    }
                } elseif ($start) {
                    if ($d < $start) {
                        return false;
                    }
                } elseif ($end) {
                    if ($d > $end) {
                        return false;
                    }
                } else {
                    // No dates on tournament, can't match date filter
                    return false;
                }
            }

            return true;
        });

        return $this->html([
            'tournaments' => array_values($filtered),
            'filters' => [
                'name' => $name,
                'location' => $location,
                'status' => $status,
                'date' => $date,
            ],
        ]);
    }

    private function processTournamentForm(Tournament $tournament, Request $request): array
    {
        $errors = [];
        if ($request->isPost()) {
            $tournament->name = trim($request->post('name'));
            $tournament->location = trim($request->post('location'));
            $tournament->start_date = $request->post('start_date');
            $tournament->end_date = $request->post('end_date');
            $tournament->status = trim($request->post('status'));

            // Bude treba nahradit skutecnym organizatorom z prihlaseneho uzivatela
            $tournament->organizer_id = 1;

            if (!$tournament->name) {
                $errors[] = 'Name is required.';
            }
            if (!$tournament->start_date) {
                $errors[] = 'Start date is required.';
            }
            if (!$tournament->end_date) {
                $errors[] = 'End date is required.';
            }
            // Kontrola: End date musí byť rovnaký alebo neskorší ako start date
            if ($tournament->start_date && $tournament->end_date) {
                if ($tournament->end_date < $tournament->start_date) {
                    $errors[] = 'End date must be the same as or later than start date.';
                }
            }

            if (empty($errors)) {
                $tournament->save();
                return ['redirect' => true];
            }
        }
        return ['errors' => $errors, 'tournament' => $tournament];
    }

    public function add(Request $request): Response
    {
        $tournament = new Tournament();
        $result = $this->processTournamentForm($tournament, $request);
        if (!empty($result['redirect'])) {
            return $this->redirect('?c=Tournament&a=index');
        }
        // Ak sú chyby, zobraz ich cez alert v index.view.php cez session a presmeruj späť na index
        if (!empty($result['errors'])) {
            $_SESSION['add_errors'] = $result['errors'];
            // Uloz POST data pre predvyplnenie
            $_SESSION['add_form_data'] = [
                'name' => $request->post('name'),
                'location' => $request->post('location'),
                'start_date' => $request->post('start_date'),
                'end_date' => $request->post('end_date'),
                'status' => $request->post('status'),
            ];
        }
        return $this->redirect('?c=Tournament&a=index');
    }

    public function edit(Request $request): Response
    {
        // Pri ulozeni z modalu ide ID v POST, pri klasickom otvoreni edit stranky v GET
        if ($request->isPost()) {
            $id = $request->post('id');
        } else {
            $id = $request->get('id');
        }

        $tournament = Tournament::getOne($id);
        if (!$tournament) {
            return $this->redirect('?c=Tournament&a=index');
        }

        $result = $this->processTournamentForm($tournament, $request);
        if (!empty($result['redirect'])) {
            return $this->redirect('?c=Tournament&a=index');
        }
        return $this->html(['tournament' => $result['tournament'], 'errors' => $result['errors']], 'edit');
    }

    public function delete(Request $request): Response
    {
        $id = $request->get('id');
        $tournament = Tournament::getOne($id);
        if ($tournament) {
            $tournament->delete();
        }
        return $this->redirect('?c=Tournament&a=index');
    }

    public function detail(Request $request): Response
    {
        $id = $request->get('id');
        $tournament = Tournament::getOne($id);
        if (!$tournament) {
            return $this->redirect('?c=Tournament&a=index');
        }

        $identity = $this->user->getIdentity();
        $isRegistered = false;
        $userDecklist = null;
        if ($identity) {
            $registrations = TournamentPlayer::getAll(
                "tournament_id = ? AND user_id = ?",
                [$tournament->tournament_id, $identity->user_id]
            );
            $isRegistered = !empty($registrations);

            // load current user's decklist for this tournament (latest)
            $deckRows = Decklist::getAll('tournament_id = ? AND user_id = ?', [$tournament->tournament_id, $identity->user_id], 'uploaded_at DESC', 1);
            $userDecklist = !empty($deckRows) ? $deckRows[0] : null;
        }

        $isLogged = $this->user->isLoggedIn();

        // Rankings: load players for this tournament ordered by points / rank_position
        $rankings = TournamentPlayer::getRankingsForTournament((int)$tournament->tournament_id);

        // Load commanders from decklists uploaded for this tournament
        $commanders = [];
        try {
            $decklists = Decklist::getAll('tournament_id = ?', [$tournament->tournament_id], 'uploaded_at DESC');
            // We want the latest decklist per user; decklists ordered by uploaded_at DESC so first occurrence wins
            foreach ($decklists as $dl) {
                $uid = $dl->user_id ?? null;
                if ($uid === null) continue;
                if (isset($commanders[$uid])) continue; // already have latest
                $fileRel = $dl->file_path ?? '';
                if (!$fileRel) { $commanders[$uid] = ''; continue; }
                $fullPath = realpath(__DIR__ . '/../../public/' . $fileRel) ?: (__DIR__ . '/../../public/' . $fileRel);
                if (!is_readable($fullPath)) { $commanders[$uid] = ''; continue; }
                $lines = @file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines === false) { $commanders[$uid] = ''; continue; }
                $found = '';
                foreach ($lines as $line) {
                    if (preg_match('/^\s*SB:\s*(.*)/i', $line, $m)) {
                        $found = trim($m[1]);
                        // Remove leading quantity like "1 ", "2x ", "2 × " so only the card name remains
                        $found = preg_replace('/^\s*\d+\s*[x×]?\s*/i', '', $found);
                        break;
                    }
                }
                $commanders[$uid] = $found;
            }
        } catch (\Exception $e) {
            // ignore and leave commanders empty on error
        }

        // Load existing pairings for this tournament (all rounds)
        $pairings = [];
        try {
            $pairings = TournamentPlayer::executeRawSQL('SELECT * FROM match_ WHERE tournament_id = ? ORDER BY round_number ASC, match_id ASC', [$tournament->tournament_id]);
        } catch (\Exception $e) {
            // ignore
        }

        // Recalculate rankings and tiebreakers from match results
        try {
            // Build initial username map from existing rankings (safe source available earlier)
            $initialUsernames = [];
            foreach ($rankings as $rk) {
                if (isset($rk['user_id'])) $initialUsernames[(int)$rk['user_id']] = $rk['username'] ?? '';
            }
            // Initialize players map from tournament_player table (ensure all players are present)
            $tpRows = TournamentPlayer::executeRawSQL('SELECT user_id, points FROM tournament_player WHERE tournament_id = ?', [$tournament->tournament_id]);
            $players = [];
            foreach ($tpRows as $tr) {
                $uid = (int)$tr['user_id'];
                $players[$uid] = [
                    'user_id' => $uid,
                    'username' => $initialUsernames[$uid] ?? '',
                    'match_points' => 0.0,
                    'matches_played' => 0,
                    'games_won' => 0,
                    'games_played' => 0,
                    // rounds_won: counts wins as 1.0, draws as 0.5, losses as 0.0
                    'rounds_won' => 0.0,
                    'opponents' => [],
                ];
            }

            // Helper to ensure player exists in map
            $ensurePlayer = function($uid) use (&$players) {
                $uid = (int)$uid;
                if (!isset($players[$uid])) {
                    $players[$uid] = ['user_id'=>$uid,'username'=>'','match_points'=>0.0,'matches_played'=>0,'games_won'=>0,'games_played'=>0,'rounds_won'=>0.0,'opponents'=>[]];
                }
            };

            // Parse each match row and accumulate stats
            foreach ($pairings as $m) {
                $p1 = isset($m['player1_id']) ? (int)$m['player1_id'] : null;
                $p2 = isset($m['player2_id']) ? (int)$m['player2_id'] : null;
                $res = isset($m['result']) ? (string)$m['result'] : '';
                // Normalize
                $resNorm = trim(strtolower((string)$res));

                if ($p1 === null || $p2 === null) continue;
                $ensurePlayer($p1); $ensurePlayer($p2);

                // BYE handling: player plays themself and gets a match point; do not count games or opponent
                if ($p1 === $p2 || strtoupper($res) === 'BYE') {
                    // award match points for BYE (win = 3 points) and count as a round win
                    if (strtoupper($res) === 'BYE') {
                        $players[$p1]['match_points'] += 3.0;
                        $players[$p1]['matches_played'] += 1;
                        $players[$p1]['rounds_won'] += 1.0;
                    }
                    continue;
                }

                // Skip unplayed results
                if ($resNorm === '' || $resNorm === 'unplayed') {
                    continue;
                }

                // Determine match points and games
                if ($resNorm === 'draw') {
                    // Treat draw as 1 / 1 (match points), games as 1-1
                    // rounds_won gets 0.5 for both players
                    $players[$p1]['match_points'] += 1.0;
                    $players[$p2]['match_points'] += 1.0;
                    $players[$p1]['matches_played'] += 1;
                    $players[$p2]['matches_played'] += 1;
                    $players[$p1]['games_won'] += 1;
                    $players[$p2]['games_won'] += 1;
                    $players[$p1]['games_played'] += 2;
                    $players[$p2]['games_played'] += 2;
                    $players[$p1]['rounds_won'] += 0.5;
                    $players[$p2]['rounds_won'] += 0.5;
                    // record opponents
                    $players[$p1]['opponents'][] = $p2;
                    $players[$p2]['opponents'][] = $p1;
                    continue;
                }

                // Expected tokens like p1_2_0, p2_2_1 etc. Parse with regex
                if (preg_match('/^p([12])_(\d+)_(\d+)$/i', $resNorm, $mm)) {
                    $winnerSide = (int)$mm[1];
                    $g1 = (int)$mm[2];
                    $g2 = (int)$mm[3];
                    // assign game wins accordingly
                    // g1 corresponds to player1's games, g2 to player2's games
                    $players[$p1]['games_won'] += $g1;
                    $players[$p2]['games_won'] += $g2;
                    $players[$p1]['games_played'] += ($g1 + $g2);
                    $players[$p2]['games_played'] += ($g1 + $g2);
                    $players[$p1]['matches_played'] += 1;
                    $players[$p2]['matches_played'] += 1;
                    $players[$p1]['opponents'][] = $p2;
                    $players[$p2]['opponents'][] = $p1;
                    // match points: winner gets 3, loser 0; track rounds_won (1 for win, 0.5 for draw)
                    if ($g1 > $g2) {
                        $players[$p1]['match_points'] += 3.0;
                        $players[$p1]['rounds_won'] += 1.0;
                    } elseif ($g2 > $g1) {
                        $players[$p2]['match_points'] += 3.0;
                        $players[$p2]['rounds_won'] += 1.0;
                    } else {
                        // fallback: if equal but token wasn't draw, treat as draw -> 1 point each and 0.5 rounds
                        $players[$p1]['match_points'] += 1.0;
                        $players[$p2]['match_points'] += 1.0;
                        $players[$p1]['rounds_won'] += 0.5;
                        $players[$p2]['rounds_won'] += 0.5;
                    }
                    continue;
                }

                // Unknown result token: ignore this match for tiebreakers but try to interpret simple cases
                // If result contains a dash like '2-1' or '1-2', parse it
                if (preg_match('/^(\d+)-(\d+)$/', $res, $mm2)) {
                    $g1 = (int)$mm2[1];
                    $g2 = (int)$mm2[2];
                    $players[$p1]['games_won'] += $g1;
                    $players[$p2]['games_won'] += $g2;
                    $players[$p1]['games_played'] += ($g1 + $g2);
                    $players[$p2]['games_played'] += ($g1 + $g2);
                    $players[$p1]['matches_played'] += 1;
                    $players[$p2]['matches_played'] += 1;
                    $players[$p1]['opponents'][] = $p2;
                    $players[$p2]['opponents'][] = $p1;
                    if ($g1 > $g2) {
                        $players[$p1]['match_points'] += 3.0;
                        $players[$p1]['rounds_won'] += 1.0;
                    } elseif ($g2 > $g1) {
                        $players[$p2]['match_points'] += 3.0;
                        $players[$p2]['rounds_won'] += 1.0;
                    } else {
                        $players[$p1]['match_points'] += 1.0;
                        $players[$p2]['match_points'] += 1.0;
                        $players[$p1]['rounds_won'] += 0.5;
                        $players[$p2]['rounds_won'] += 0.5;
                    }
                }
            }

            foreach ($players as $uid => &$pd) {
                $pd['gwp'] = ($pd['games_played'] > 0) ? ($pd['games_won'] / $pd['games_played']) : 0.0;
                // MWP should be rounds won divided by rounds played (matches_played)
                $pd['mwp'] = ($pd['matches_played'] > 0) ? ($pd['rounds_won'] / $pd['matches_played']) : 0.0;
            }
            unset($pd);

            // Compute opponent-based tiebreakers (OGWP = avg of opponents' GWP)
            foreach ($players as $uid => &$pd) {
                $oppCount = count($pd['opponents']);
                if ($oppCount === 0) {
                    $pd['ogwp'] = 0.0;
                    continue;
                }
                $sumOGWP = 0.0;
                foreach ($pd['opponents'] as $opp) {
                    if (isset($players[$opp])) {
                        $sumOGWP += $players[$opp]['gwp'];
                    }
                }
                $pd['ogwp'] = $sumOGWP / $oppCount;
            }
            unset($pd);

            // Convert players map to rankings array and sort by match_points, then gwp, then ogwp
            $newRankings = array_values($players);
            usort($newRankings, function($a, $b) {
                // primary: match_points desc
                if ($a['match_points'] !== $b['match_points']) return ($a['match_points'] > $b['match_points']) ? -1 : 1;
                // secondary: game win percentage desc
                if ($a['gwp'] !== $b['gwp']) return ($a['gwp'] > $b['gwp']) ? -1 : 1;
                // tertiary: opponent game win percentage desc
                if ($a['ogwp'] !== $b['ogwp']) return ($a['ogwp'] > $b['ogwp']) ? -1 : 1;
                // fallback: username
                return strcasecmp($a['username'] ?? '', $b['username'] ?? '');
            });

            // Build the $rankings structure expected by the view (keep keys like 'user_id','username','points')
            $rankings = [];
            foreach ($newRankings as $r) {
                $rankings[] = [
                    'user_id' => $r['user_id'],
                    'username' => $r['username'] ?? '',
                    'points' => $r['match_points'],
                    'gwp' => $r['gwp'],
                    'ogwp' => $r['ogwp'],
                ];
            }
        } catch (\Exception $e) {
            // On error, keep existing rankings loaded earlier
        }

        // Determine available rounds (distinct round_number) and selected round
        $availableRounds = [];
        foreach ($pairings as $row) {
            $rnd = isset($row['round_number']) ? (int)$row['round_number'] : 1;
            if (!in_array($rnd, $availableRounds, true)) $availableRounds[] = $rnd;
        }
        sort($availableRounds, SORT_NUMERIC);

        // Maximum round number currently present (0 when none)
        $maxRound = !empty($availableRounds) ? max($availableRounds) : 0;

        $requestedTab = trim((string)$request->get('tab')) ?: '';
        $requestedRound = $request->get('round');
        $selectedRound = null;
        if ($requestedRound !== null && $requestedRound !== '') {
            $r = (int)$requestedRound;
            if (in_array($r, $availableRounds, true)) {
                $selectedRound = $r;
            }
        }
        if ($selectedRound === null && !empty($availableRounds)) {
            // default to last (most recent) round
            $selectedRound = end($availableRounds);
        }

        // Determine if current user is organizer
        $isOrganizer = ($identity && isset($identity->user_id) && $identity->user_id == $tournament->organizer_id);

        // Build username map from rankings for display convenience
        $usernames = [];
        foreach ($rankings as $r) {
            if (isset($r['user_id'])) $usernames[(int)$r['user_id']] = $r['username'] ?? '';
        }

        // Decide active tab: request param 'tab' > pairing success flash > default rules
        if ($requestedTab) {
            $activeTab = $requestedTab;
        } elseif (!empty($_SESSION['pairing_success'])) {
            $activeTab = 'pairings';
        } else {
            $showAllTabs = in_array($tournament->status, ['ongoing', 'finished']);
            $signUpVisible = !($showAllTabs && !$isRegistered);
            $activeTab = $signUpVisible ? 'signups' : 'pairings';
        }

        return $this->html([
            'tournament' => $tournament,
            'isRegistered' => $isRegistered,
            'isLogged' => $isLogged,
            'rankings' => $rankings,
            'commanders' => $commanders,
            'user_decklist' => $userDecklist,
            'isOrganizer' => $isOrganizer,
            'pairings' => $pairings,
            'availableRounds' => $availableRounds,
            'selectedRound' => $selectedRound,
            'activeTab' => $activeTab,
            'usernames' => $usernames,
            'maxRound' => $maxRound,
        ], 'detail');
    }

    public function generatePairings(Request $request): Response
    {
        $user = $this->user->getIdentity();
        if (!$user) {
            return $this->redirect('?c=Auth&a=login');
        }

        $tournamentId = (int)$request->post('tournament_id');
        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            return $this->redirect('?c=Tournament&a=index');
        }

        // Only organizer may generate pairings
        if ($user->user_id != $tournament->organizer_id) {
            $_SESSION['pairing_errors'] = ['Only tournament organizer can generate pairings.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // Load players (use rankings which contains user_id and points)
        $players = TournamentPlayer::getRankingsForTournament($tournamentId);
        $count = count($players);
        if ($count < 2) {
            $_SESSION['pairing_errors'] = ['Not enough players to create pairings.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // Load existing played map to detect rematches
        $playedRows = TournamentPlayer::executeRawSQL('SELECT player1_id, player2_id FROM match_ WHERE tournament_id = ?', [$tournamentId]);
        $played = [];
        foreach ($playedRows as $r) {
            $p1 = (int)$r['player1_id'];
            $p2 = (int)$r['player2_id'];
            $played[$p1][$p2] = true;
            $played[$p2][$p1] = true;
        }

        // NEW: disallow generating new pairings if there are unfinished matches (result NULL/empty/'unplayed')
        try {
            $unfinishedRows = TournamentPlayer::executeRawSQL("SELECT COUNT(*) AS cnt FROM match_ WHERE tournament_id = ? AND (result IS NULL OR TRIM(result) = '' OR LOWER(result) = 'unplayed')", [$tournamentId]);
            $unfinishedCount = isset($unfinishedRows[0]['cnt']) ? (int)$unfinishedRows[0]['cnt'] : 0;
            if ($unfinishedCount > 0) {
                $_SESSION['pairing_errors'] = ['Cannot generate new pairings while previous matches are not finished.'];
                return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
            }
        } catch (\Exception $e) {
            // ignore DB check error and continue with pairing generation
        }

        // Use SwissPairing service to compute pairs
        try {
            $res = \App\Services\SwissPairing::generate($tournamentId, $players);
            $pairs = $res['pairs'] ?? [];
            $bye = $res['bye'] ?? null;
        } catch (\Exception $e) {
            $_SESSION['pairing_errors'] = ['Pairing failed: ' . $e->getMessage()];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // If all generated pairs are duplicates (rematches), abort and show 'Maximum number of rounds reached'
        $allDup = true;
        if (!empty($pairs)) {
            foreach ($pairs as $p) {
                $p1 = (int)$p[0];
                $p2 = (int)$p[1];
                if (!isset($played[$p1][$p2])) {
                    $allDup = false;
                    break;
                }
            }
        } else {
            $allDup = false; // no pairs means nothing to judge (bye-only handled below)
        }

        if ($allDup) {
            $_SESSION['pairing_errors'] = ['Maximum number of rounds reached (no valid new pairings without rematches).'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // Determine next round number (max existing round + 1). Fallback to 1 when no rounds exist or on error.
        try {
            $maxRow = TournamentPlayer::executeRawSQL('SELECT MAX(round_number) AS m FROM match_ WHERE tournament_id = ?', [$tournamentId]);
            $maxRound = isset($maxRow[0]['m']) && $maxRow[0]['m'] !== null ? (int)$maxRow[0]['m'] : 0;
            $nextRound = $maxRound + 1;
        } catch (\Exception $e) {
            $nextRound = 1;
        }

        // Insert generated pairs into match_ table
        try {
            foreach ($pairs as $p) {
                $p1 = (int)$p[0];
                $p2 = (int)$p[1];
                TournamentPlayer::executeRawSQL('INSERT INTO match_ (tournament_id, round_number, player1_id, player2_id) VALUES (:t, :r, :p1, :p2)', [':t' => $tournamentId, ':r' => $nextRound, ':p1' => $p1, ':p2' => $p2]);
            }
            // Handle bye: create special match_ record and award automatic win (3 points)
            if ($bye !== null) {
                $b = (int)$bye;
                // Insert bye record (player plays themselves) with result 'BYE'
                TournamentPlayer::executeRawSQL('INSERT INTO match_ (tournament_id, round_number, player1_id, player2_id, result) VALUES (:t, :r, :p1, :p2, :res)', [':t' => $tournamentId, ':r' => $nextRound, ':p1' => $b, ':p2' => $b, ':res' => 'BYE']);
                // Award 3 points to bye player in tournament_player (win = 3)
                TournamentPlayer::executeRawSQL('UPDATE tournament_player SET points = COALESCE(points,0) + 3 WHERE tournament_id = ? AND user_id = ?', [$tournamentId, $b]);
            }
            $_SESSION['pairing_success'] = 'Pairings generated for round ' . $nextRound;
            // optionally mark tournament as ongoing
            if ($tournament->status === 'planned') {
                $tournament->status = 'ongoing';
                $tournament->save();
            }
        } catch (\Exception $e) {
            $_SESSION['pairing_errors'] = ['Failed to save pairings: ' . $e->getMessage()];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // Redirect to details with pairings tab open and selected to the new round
        return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings&round=' . $nextRound);
    }

    public function join(Request $request): Response
    {
        $user = $this->user->getIdentity();
        if (!$user) {
            return $this->redirect('?c=Auth&a=login');
        }

        $tournamentId = (int)$request->post('tournament_id');
        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            return $this->redirect('?c=Tournament&a=index');
        }

        $existing = TournamentPlayer::getAll("tournament_id = ? AND user_id = ?", [$tournamentId, $user->user_id]);
        if (empty($existing)) {
            $tp = new TournamentPlayer();
            $tp->tournament_id = $tournamentId;
            $tp->user_id = $user->user_id;
            $tp->save();
        }

        // Handle optional decklist upload
        $file = $request->file('decklist');
        if ($file !== null) {
            // Server-side: disallow replacing existing decklist. Require explicit delete first.
            $existingDecksCheck = Decklist::getAll('tournament_id = ? AND user_id = ?', [$tournamentId, $user->user_id]);
            if (!empty($existingDecksCheck)) {
                $_SESSION['deck_upload_errors'] = ['You already have a decklist uploaded for this tournament. Delete it before uploading a new one.'];
                return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
            }
            $errors = [];
            if (!$file->isOk()) {
                $errors[] = $file->getErrorMessage() ?? 'File upload failed.';
            } else {
                $name = $file->getName();
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if ($ext !== 'txt') {
                    $errors[] = 'Only .txt files are accepted for decklists.';
                }
                // Read temporary file and validate lines
                $tmp = $file->getFileTempPath();
                $contents = @file($tmp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($contents === false) {
                    $errors[] = 'Unable to read uploaded file.';
                } else {
                    if (count($contents) > 100) {
                        $errors[] = 'Decklist may contain at most 100 non-empty lines.';
                    }
                    // NEW: require that either the last or the second-last non-empty line starts with 'SB:'
                    if (is_array($contents) && count($contents) > 0) {
                        $len = count($contents);
                        $hasSB = false;
                        $lastLine = trim((string)$contents[$len - 1]);
                        if (preg_match('/^\s*SB:/i', $lastLine, $m)) {
                            $hasSB = true;
                        }
                        if (!$hasSB && $len >= 2) {
                            $secondLast = trim((string)$contents[$len - 2]);
                            if (preg_match('/^\s*SB:/i', $secondLast, $m)) {
                                $hasSB = true;
                            }
                        }
                        if (!$hasSB) {
                            $errors[] = "Decklist must have a line starting with 'SB:' in the last one or two non-empty lines.";
                        }
                    }
                }

                if (empty($errors)) {
                    // create a safe slug from tournament name and include tournament id to avoid collisions
                    $slug = preg_replace('/[^a-z0-9\-]/i', '-', trim((string)$tournament->name));
                    // reduce consecutive dashes and trim
                    $slug = preg_replace('/-+/', '-', strtolower($slug));
                    $slug = trim($slug, '-');
                    // fallback if empty
                    if ($slug === '') $slug = 'tournament-' . $tournamentId;

                    $uploadsDir = __DIR__ . '/../../public/uploads/' . $tournamentId . '_' . $slug;
                    if (!is_dir($uploadsDir)) {
                        @mkdir($uploadsDir, 0777, true);
                    }
                    $safeName = preg_replace('/[^a-z0-9\-_.]/i', '_', pathinfo($name, PATHINFO_FILENAME));
                    $finalName = sprintf('%s_%d_%d.txt', $safeName, $user->user_id, time());
                    $dest = $uploadsDir . DIRECTORY_SEPARATOR . $finalName;
                    if ($file->store($dest)) {
                        // Save decklist record (only create new record — replacing is disallowed by UI/server-side)
                        $deckModelClass = '\\App\\Models\\Decklist';
                        if (class_exists($deckModelClass)) {
                            $deck = new $deckModelClass();
                            $deck->user_id = $user->user_id;
                            $deck->tournament_id = $tournamentId;
                            // store relative web path: uploads/{tournamentId}_{slug}/{filename}
                            $deck->file_path = 'uploads/' . $tournamentId . '_' . $slug . '/' . $finalName;
                            $deck->uploaded_at = date('Y-m-d H:i:s');
                            $deck->save();
                        }
                    } else {
                        $errors[] = 'Failed to move uploaded file.';
                    }
                }
            }

            if (!empty($errors)) {
                $_SESSION['deck_upload_errors'] = $errors;
            } else {
                $_SESSION['deck_upload_success'] = 'Decklist uploaded successfully.';
            }
        }

        return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
    }

    public function deckDelete(Request $request): Response
    {
        $user = $this->user->getIdentity();
        if (!$user) {
            return $this->redirect('?c=Auth&a=login');
        }
        $tournamentId = (int)$request->post('tournament_id');
        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            return $this->redirect('?c=Tournament&a=index');
        }
        // Prevent deletion if tournament has started or finished
        if (in_array($tournament->status, ['ongoing', 'finished'])) {
            $_SESSION['deck_upload_errors'] = ['Decklist nie je možné odstrániť po začatí turnaja.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
        }
        // find decklist(s) for user & tournament
        $deckrows = Decklist::getAll('tournament_id = ? AND user_id = ?', [$tournamentId, $user->user_id]);
        foreach ($deckrows as $d) {
            if (!empty($d->file_path)) {
                $full = __DIR__ . '/../../public/' . $d->file_path;
                if (is_file($full)) @unlink($full);
            }
            // delete record
            try {
                $d->delete();
            } catch (\Exception $e) {
                // ignore
            }
        }
        $_SESSION['deck_upload_success'] = 'Decklist deleted.';
        return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
    }

    public function leave(Request $request): Response
    {
        $user = $this->user->getIdentity();
        if (!$user) {
            return $this->redirect('?c=Auth&a=login');
        }

        $tournamentId = (int)$request->post('tournament_id');
        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            return $this->redirect('?c=Tournament&a=index');
        }

        // Prevent unregistering if tournament has started or finished
        if (in_array($tournament->status, ['ongoing', 'finished'])) {
            $_SESSION['leave_errors'] = ['Nie je možné odhlásiť sa po začatí turnaja.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
        }

        // Remove tournament player record(s) for this user
        try {
            TournamentPlayer::deleteByTournamentAndUser($tournamentId, $user->user_id);
        } catch (\Exception $e) {
            // fallback: try deleting any returned models
            $rows = TournamentPlayer::getAll('tournament_id = ? AND user_id = ?', [$tournamentId, $user->user_id]);
            foreach ($rows as $r) {
                try { $r->delete(); } catch (\Exception $e) { /* ignore */ }
            }
        }

        // Also delete any decklists uploaded by this user for this tournament
        $deckrows = Decklist::getAll('tournament_id = ? AND user_id = ?', [$tournamentId, $user->user_id]);
        foreach ($deckrows as $d) {
            if (!empty($d->file_path)) {
                $full = __DIR__ . '/../../public/' . $d->file_path;
                if (is_file($full)) @unlink($full);
            }
            try { $d->delete(); } catch (\Exception $e) { /* ignore */ }
        }

        $_SESSION['leave_success'] = 'You have been unregistered from the tournament.';
        return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
    }

    public function saveMatchResult(Request $request): Response
    {
        $user = $this->user->getIdentity();
        $isAjax = (string)$request->post('ajax') === '1';
        if (!$user) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
                exit;
            }
            return $this->redirect('?c=Auth&a=login');
        }

        $matchId = (int)$request->post('match_id');
        $tournamentId = (int)$request->post('tournament_id');
        $token = (string)$request->post('result'); // e.g. 'p1_2_0', 'p2_2_1', 'unplayed'
        // $isAjax already set above

        // Load match
        $rows = TournamentPlayer::executeRawSQL('SELECT * FROM match_ WHERE match_id = ? AND tournament_id = ?', [$matchId, $tournamentId]);
        if (empty($rows)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Match not found.']);
                exit;
            }
            $_SESSION['pairing_errors'] = ['Match not found.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }
        $match = $rows[0];
        $p1 = (int)$match['player1_id'];
        $p2 = (int)$match['player2_id'];

        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Tournament not found.']);
                exit;
            }
            $_SESSION['pairing_errors'] = ['Tournament not found.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // Only organizer may set results
        if ($user->user_id != $tournament->organizer_id) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Only tournament organizer can set match results.']);
                exit;
            }
            $_SESSION['pairing_errors'] = ['Only tournament organizer can set match results.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // Do not allow editing BYE matches
        $existingResult = isset($match['result']) ? (string)$match['result'] : '';
        if ($p1 === $p2 || strtoupper($existingResult) === 'BYE') {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'BYE matches cannot be edited.']);
                exit;
            }
            $_SESSION['pairing_errors'] = ['BYE matches cannot be edited.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // Disallow editing this match if a later round has already been started (i.e. next round's pairings were generated)
        try {
            $laterRows = TournamentPlayer::executeRawSQL('SELECT COUNT(*) AS cnt FROM match_ WHERE tournament_id = ? AND round_number > ?', [$tournamentId, isset($match['round_number']) ? (int)$match['round_number'] : 0]);
            $laterCount = isset($laterRows[0]['cnt']) ? (int)$laterRows[0]['cnt'] : 0;
            if ($laterCount > 0) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Cannot change result: a subsequent round has already started.']);
                    exit;
                }
                $_SESSION['pairing_errors'] = ['Cannot change result: a subsequent round has already started.'];
                return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
            }
        } catch (\Exception $e) {
            // If DB check fails, be conservative and allow editing (or alternatively block). We'll allow editing to avoid accidental lockout.
        }

        // Map token to winner/draw: null for unplayed
        $winner = null; // user_id or null for draw/unplayed
        $isDraw = false;
        $resLabel = '';
        switch ($token) {
            case 'p1_2_0': $winner = $p1; $resLabel = '2-0'; break;
            case 'p1_2_1': $winner = $p1; $resLabel = '2-1'; break;
            case 'p2_2_0': $winner = $p2; $resLabel = '0-2'; break;
            case 'p2_2_1': $winner = $p2; $resLabel = '1-2'; break;
            case 'draw': $isDraw = true; $resLabel = 'DRAW'; break;
            case 'unplayed': $winner = null; $resLabel = 'UNPLAYED'; break;
            default:
                $_SESSION['pairing_errors'] = ['Invalid result chosen.'];
                if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Invalid result chosen.']); exit; }
                return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // Points system: win = 3, draw = 1, loss = 0
        $winPoints = 3.0;
        $drawPoints = 1.0;

        try {
            // Reverse previous result points if any
            if (!empty($existingResult) && strtoupper($existingResult) !== 'UNPLAYED') {
                $prevWinnerId = null;
                $prevWasDraw = false;
                // Determine prev winner from stored label or token
                $er = strtoupper($existingResult);
                if ($er === 'DRAW') {
                    $prevWasDraw = true;
                } elseif (str_starts_with($er, '2-0') || str_starts_with($er, '2-1') || str_starts_with($er, 'P1')) {
                    $prevWinnerId = $p1;
                } elseif (str_starts_with($er, '0-2') || str_starts_with($er, '1-2') || str_starts_with($er, 'P2')) {
                    $prevWinnerId = $p2;
                }
                if ($prevWasDraw) {
                    // subtract draw points from both
                    TournamentPlayer::executeRawSQL('UPDATE tournament_player SET points = GREATEST(COALESCE(points,0) - ?, 0) WHERE tournament_id = ? AND user_id IN (?, ?)', [$drawPoints, $tournamentId, $p1, $p2]);
                } elseif ($prevWinnerId !== null) {
                    TournamentPlayer::executeRawSQL('UPDATE tournament_player SET points = GREATEST(COALESCE(points,0) - ?, 0) WHERE tournament_id = ? AND user_id = ?', [$winPoints, $tournamentId, $prevWinnerId]);
                }
            }

            // Apply new result: award points
            if ($isDraw) {
                TournamentPlayer::executeRawSQL('UPDATE tournament_player SET points = COALESCE(points,0) + ? WHERE tournament_id = ? AND user_id IN (?, ?)', [$drawPoints, $tournamentId, $p1, $p2]);
            } elseif ($winner !== null) {
                TournamentPlayer::executeRawSQL('UPDATE tournament_player SET points = COALESCE(points,0) + ? WHERE tournament_id = ? AND user_id = ?', [$winPoints, $tournamentId, $winner]);
            }

            // Save result token/label into match_. We'll store token for clarity
            $saveVal = $token;
            TournamentPlayer::executeRawSQL('UPDATE match_ SET result = ? WHERE match_id = ?', [$saveVal, $matchId]);

            $_SESSION['pairing_success'] = 'Match result saved.';

            if ($isAjax) {
                // return updated points for both players
                $ptsRows = TournamentPlayer::executeRawSQL('SELECT user_id, points FROM tournament_player WHERE tournament_id = ? AND user_id IN (?, ?)', [$tournamentId, $p1, $p2]);
                $pointsMap = [];
                foreach ($ptsRows as $pr) {
                    $pointsMap[(int)$pr['user_id']] = (float)$pr['points'];
                }
                // NEW: compute rankings and include in response
                $existingRank = TournamentPlayer::getRankingsForTournament($tournamentId);
                $usernames = [];
                foreach ($existingRank as $rk) {
                    if (isset($rk['user_id'])) $usernames[(int)$rk['user_id']] = $rk['username'] ?? '';
                }
                $newRankings = $this->computeRankings($tournamentId, $usernames);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Match result saved.', 'points' => $pointsMap, 'result' => $saveVal, 'rankings' => $newRankings]);
                exit;
            }
        } catch (\Exception $e) {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Failed to save match result: ' . $e->getMessage()]); exit; }
            $_SESSION['pairing_errors'] = ['Failed to save match result: ' . $e->getMessage()];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // Redirect back to the same round and pairings tab
        $rnd = isset($match['round_number']) ? (int)$match['round_number'] : 1;
        return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings&round=' . $rnd);
    }

    private function computeRankings(int $tournamentId, array $initialUsernames = []): array
    {
        $rankings = [];
        try {
            $pairings = TournamentPlayer::executeRawSQL('SELECT * FROM match_ WHERE tournament_id = ? ORDER BY round_number ASC, match_id ASC', [$tournamentId]);

            $tpRows = TournamentPlayer::executeRawSQL('SELECT user_id, points FROM tournament_player WHERE tournament_id = ?', [$tournamentId]);
            $players = [];
            foreach ($tpRows as $tr) {
                $uid = (int)$tr['user_id'];
                $players[$uid] = [
                    'user_id' => $uid,
                    'username' => $initialUsernames[$uid] ?? '',
                    'match_points' => 0.0,
                    'matches_played' => 0,
                    'games_won' => 0,
                    'games_played' => 0,
                    'rounds_won' => 0.0,
                    'opponents' => [],
                ];
            }

            $ensurePlayer = function($uid) use (&$players) {
                $uid = (int)$uid;
                if (!isset($players[$uid])) {
                    $players[$uid] = ['user_id'=>$uid,'username'=>'','match_points'=>0.0,'matches_played'=>0,'games_won'=>0,'games_played'=>0,'rounds_won'=>0.0,'opponents'=>[]];
                }
            };

            foreach ($pairings as $m) {
                $p1 = isset($m['player1_id']) ? (int)$m['player1_id'] : null;
                $p2 = isset($m['player2_id']) ? (int)$m['player2_id'] : null;
                $res = isset($m['result']) ? (string)$m['result'] : '';
                $resNorm = trim(strtolower((string)$res));

                if ($p1 === null || $p2 === null) continue;
                $ensurePlayer($p1); $ensurePlayer($p2);

                if ($p1 === $p2 || strtoupper($res) === 'BYE') {
                    if (strtoupper($res) === 'BYE') {
                        $players[$p1]['match_points'] += 3.0;
                        $players[$p1]['matches_played'] += 1;
                        $players[$p1]['rounds_won'] += 1.0;
                    }
                    continue;
                }

                if ($resNorm === '' || $resNorm === 'unplayed') continue;

                if ($resNorm === 'draw') {
                    $players[$p1]['match_points'] += 1.0;
                    $players[$p2]['match_points'] += 1.0;
                    $players[$p1]['matches_played'] += 1;
                    $players[$p2]['matches_played'] += 1;
                    $players[$p1]['games_won'] += 1;
                    $players[$p2]['games_won'] += 1;
                    $players[$p1]['games_played'] += 2;
                    $players[$p2]['games_played'] += 2;
                    $players[$p1]['rounds_won'] += 0.5;
                    $players[$p2]['rounds_won'] += 0.5;
                    $players[$p1]['opponents'][] = $p2;
                    $players[$p2]['opponents'][] = $p1;
                    continue;
                }

                if (preg_match('/^p([12])_(\d+)_(\d+)$/i', $resNorm, $mm)) {
                    $g1 = (int)$mm[2];
                    $g2 = (int)$mm[3];
                    $players[$p1]['games_won'] += $g1;
                    $players[$p2]['games_won'] += $g2;
                    $players[$p1]['games_played'] += ($g1 + $g2);
                    $players[$p2]['games_played'] += ($g1 + $g2);
                    $players[$p1]['matches_played'] += 1;
                    $players[$p2]['matches_played'] += 1;
                    $players[$p1]['opponents'][] = $p2;
                    $players[$p2]['opponents'][] = $p1;
                    if ($g1 > $g2) {
                        $players[$p1]['match_points'] += 3.0;
                        $players[$p1]['rounds_won'] += 1.0;
                    } elseif ($g2 > $g1) {
                        $players[$p2]['match_points'] += 3.0;
                        $players[$p2]['rounds_won'] += 1.0;
                    } else {
                        $players[$p1]['match_points'] += 1.0;
                        $players[$p2]['match_points'] += 1.0;
                        $players[$p1]['rounds_won'] += 0.5;
                        $players[$p2]['rounds_won'] += 0.5;
                    }
                    continue;
                }

                if (preg_match('/^(\d+)-(\d+)$/', $res, $mm2)) {
                    $g1 = (int)$mm2[1];
                    $g2 = (int)$mm2[2];
                    $players[$p1]['games_won'] += $g1;
                    $players[$p2]['games_won'] += $g2;
                    $players[$p1]['games_played'] += ($g1 + $g2);
                    $players[$p2]['games_played'] += ($g1 + $g2);
                    $players[$p1]['matches_played'] += 1;
                    $players[$p2]['matches_played'] += 1;
                    $players[$p1]['opponents'][] = $p2;
                    $players[$p2]['opponents'][] = $p1;
                    if ($g1 > $g2) {
                        $players[$p1]['match_points'] += 3.0;
                        $players[$p1]['rounds_won'] += 1.0;
                    } elseif ($g2 > $g1) {
                        $players[$p2]['match_points'] += 3.0;
                        $players[$p2]['rounds_won'] += 1.0;
                    } else {
                        $players[$p1]['match_points'] += 1.0;
                        $players[$p2]['match_points'] += 1.0;
                        $players[$p1]['rounds_won'] += 0.5;
                        $players[$p2]['rounds_won'] += 0.5;
                    }
                }
            }

            foreach ($players as $uid => &$pd) {
                $pd['gwp'] = ($pd['games_played'] > 0) ? ($pd['games_won'] / $pd['games_played']) : 0.0;
                // MWP should be rounds won divided by rounds played (matches_played)
                $pd['mwp'] = ($pd['matches_played'] > 0) ? ($pd['rounds_won'] / $pd['matches_played']) : 0.0;
            }
            unset($pd);

            // Compute opponent-based tiebreakers (OGWP = avg of opponents' GWP)
            foreach ($players as $uid => &$pd) {
                $oppCount = count($pd['opponents']);
                if ($oppCount === 0) {
                    $pd['ogwp'] = 0.0;
                    continue;
                }
                $sumOGWP = 0.0;
                foreach ($pd['opponents'] as $opp) {
                    if (isset($players[$opp])) {
                        $sumOGWP += $players[$opp]['gwp'];
                    }
                }
                $pd['ogwp'] = $sumOGWP / $oppCount;
            }
            unset($pd);

            $newRankings = array_values($players);
            usort($newRankings, function($a, $b) {
                if ($a['match_points'] !== $b['match_points']) return ($a['match_points'] > $b['match_points']) ? -1 : 1;
                if ($a['gwp'] !== $b['gwp']) return ($a['gwp'] > $b['gwp']) ? -1 : 1;
                if ($a['ogwp'] !== $b['ogwp']) return ($a['ogwp'] > $b['ogwp']) ? -1 : 1;
                return strcasecmp($a['username'] ?? '', $b['username'] ?? '');
            });

            foreach ($newRankings as $r) {
                $rankings[] = [
                    'user_id' => $r['user_id'],
                    'username' => $r['username'] ?? '',
                    'points' => $r['match_points'],
                    'gwp' => $r['gwp'],
                    'ogwp' => $r['ogwp'],
                ];
            }
        } catch (\Exception $e) {
            // on error return empty rankings
        }
        return $rankings;
    }
}

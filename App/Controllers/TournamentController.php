<?php

//Vytvorene s pomocou GitHub Copilot

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
        // read filter params
        $name = trim((string)$request->get('name'));
        $location = trim((string)$request->get('location'));
        $status = trim((string)$request->get('status'));
        $date = trim((string)$request->get('date'));

        // load all tournaments
        $tournaments = Tournament::getAll();

        // filter tournaments in PHP
        $filtered = array_filter($tournaments, function (Tournament $tournament)
         use ($name, $location, $status, $date) {
            if ($name !== '' && stripos($tournament->name, $name) === false) return false;
            if ($location !== '' && stripos((string)$tournament->location, $location) === false) return false;
            if ($status !== '' && $status !== 'all' && $tournament->status !== $status) return false;

            // date filter compares YYYY-MM-DD parts
            if ($date !== '') {
                $d = substr($date, 0, 10);
                $start = $tournament->start_date ? substr($tournament->start_date, 0, 10) : null;
                $end = $tournament->end_date ? substr($tournament->end_date, 0, 10) : null;
                if ($start && $end) {
                    if ($d < $start || $d > $end) return false;
                } elseif ($start) {
                    if ($d < $start) return false;
                } elseif ($end) {
                    if ($d > $end) return false;
                } else {
                    return false;
                }
            }

            return true;
        });

        return $this->html([
            'tournaments' => array_values($filtered),
            'filters' => [ 'name' => $name, 'location' => $location, 'status' => $status, 'date' => $date ],
        ]);
    }

    private function processTournamentForm(Tournament $tournament, Request $request): array
    {
        $errors = [];
        if ($request->isPost()) {
            // populate model from POST
            $tournament->name = trim($request->post('name'));
            $tournament->location = trim($request->post('location'));
            $tournament->start_date = $request->post('start_date');
            $tournament->end_date = $request->post('end_date');
            $tournament->status = trim($request->post('status'));

            // assign organizer for new tournament
            if (empty($tournament->tournament_id)) {
                $identity = $this->user->getIdentity();
                $tournament->organizer_id = $identity ? $identity->user_id : null;
            }

            // validation
            if (!$tournament->name) $errors[] = 'Name is required.';
            if (!$tournament->start_date) $errors[] = 'Start date is required.';
            if (!$tournament->end_date) $errors[] = 'End date is required.';
            if ($tournament->start_date && $tournament->end_date && $tournament->end_date < $tournament->start_date) {
                $errors[] = 'End date must be the same as or later than start date.';
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
        // auth: organizers/admins only
        $identity = $this->user->getIdentity();
        if (!$identity || !in_array((int)$identity->role_id, [1, 2], true)) {
            $this->app->getSession()->set('flash_error', 'Only organizers may create tournaments.');
            return $this->redirect('?c=Tournament&a=index');
        }

        $tournament = new Tournament();
        $result = $this->processTournamentForm($tournament, $request);
        if (!empty($result['redirect'])) {
            return $this->redirect('?c=Tournament&a=index');
        }
        // on errors save to session for display and redirect
        if (!empty($result['errors'])) {
            $_SESSION['add_errors'] = $result['errors'];
            $_SESSION['add_form_data'] = [
                'name' => $request->post('name'), 'location' => $request->post('location'),
                'start_date' => $request->post('start_date'), 'end_date' => $request->post('end_date'), 'status' => $request->post('status'),
            ];
        }
        return $this->redirect('?c=Tournament&a=index');
    }

    public function edit(Request $request): Response
    {
        // accept id from POST (modal) or GET
        if ($request->isPost()) { $id = $request->post('id'); } else { $id = $request->get('id'); }

        $tournament = Tournament::getOne($id);
        if (!$tournament) return $this->redirect('?c=Tournament&a=index');

        // auth: only admins or organizer may edit
        $identity = $this->user->getIdentity();
        $isAdmin = $identity && isset($identity->role_id) && (int)$identity->role_id === 1;
        $isOrganizerOwner = $identity && isset($identity->user_id) && ((int)$identity->user_id === (int)$tournament->organizer_id);
        if (!$isAdmin && !$isOrganizerOwner) {
            $this->app->getSession()->set('flash_error', 'You are not allowed to edit this tournament.');
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
        // find tournament and check delete auth
        $id = $request->get('id');
        $tournament = Tournament::getOne($id);
        if ($tournament) {
            $identity = $this->user->getIdentity();
            $isAdmin = $identity && isset($identity->role_id) && (int)$identity->role_id === 1;
            $isOrganizerOwner = $identity && isset($identity->user_id) && ((int)$identity->user_id === (int)$tournament->organizer_id);
            if ($isAdmin || $isOrganizerOwner) {
                $tournament->delete();
            } else {
                $this->app->getSession()->set('flash_error', 'You are not allowed to delete this tournament.');
            }
        }
        return $this->redirect('?c=Tournament&a=index');
    }

    public function detail(Request $request): Response
    {
        // load tournament
        $id = $request->get('id');
        $tournament = Tournament::getOne($id);
        if (!$tournament) return $this->redirect('?c=Tournament&a=index');

        // check registration status and user decklist
        $identity = $this->user->getIdentity();
        $isRegistered = false;
        $userDecklist = null;
        if ($identity) {
            $registrations = TournamentPlayer::getAll("tournament_id = ? AND user_id = ?", [$tournament->tournament_id, $identity->user_id]);
            $isRegistered = !empty($registrations);

            // load current user's decklist for this tournament (latest)
            $deckRows = Decklist::getAll('tournament_id = ? AND user_id = ?', [$tournament->tournament_id, $identity->user_id], 'uploaded_at DESC', 1);
            $userDecklist = !empty($deckRows) ? $deckRows[0] : null;
        }

        $isLogged = $this->user->isLoggedIn();

        // load rankings
        $rankings = TournamentPlayer::getRankingsForTournament((int)$tournament->tournament_id);

        // load commanders from decklists
        $commanders = [];
        try {
            $decklists = Decklist::getAll('tournament_id = ?', [$tournament->tournament_id], 'uploaded_at DESC');
            foreach ($decklists as $dl) {
                $uid = $dl->user_id ?? null;
                if ($uid === null) continue;
                if (isset($commanders[$uid])) continue;
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
                        $found = preg_replace('/^\s*\d+\s*[x×]?\s*/i', '', $found);
                        break;
                    }
                }
                $commanders[$uid] = $found;
            }
        } catch (\Exception $e) {
            // ignore and leave commanders empty on error
        }

        // load pairings
        $pairings = [];
        try {
            $pairings = TournamentPlayer::executeRawSQL('SELECT * FROM match_ WHERE tournament_id = ? ORDER BY round_number ASC, match_id ASC', [$tournament->tournament_id]);
        } catch (\Exception $e) {
            // ignore
        }

        // recalculate rankings and tiebreakers from match results
        try {
            // build initial username map
            $initialUsernames = [];
            foreach ($rankings as $rk) { if (isset($rk['user_id'])) $initialUsernames[(int)$rk['user_id']] = $rk['username'] ?? ''; }
            // init players map
            $tpRows = TournamentPlayer::executeRawSQL('SELECT user_id, points FROM tournament_player WHERE tournament_id = ?', [$tournament->tournament_id]);
            $players = [];
            foreach ($tpRows as $tr) {
                $uid = (int)$tr['user_id'];
                $players[$uid] = [ 'user_id' => $uid, 'username' => $initialUsernames[$uid] ?? '', 'match_points' => 0.0, 'matches_played' => 0, 'games_won' => 0, 'games_played' => 0, 'rounds_won' => 0.0, 'opponents' => [] ];
            }

            // helper ensurePlayer
            $ensurePlayer = function($uid) use (&$players) { $uid = (int)$uid; if (!isset($players[$uid])) { $players[$uid] = ['user_id'=>$uid,'username'=>'','match_points'=>0.0,'matches_played'=>0,'games_won'=>0,'games_played'=>0,'rounds_won'=>0.0,'opponents'=>[]]; } };

            // parse matches and compute stats
            foreach ($pairings as $m) {
                $p1 = isset($m['player1_id']) ? (int)$m['player1_id'] : null;
                $p2 = isset($m['player2_id']) ? (int)$m['player2_id'] : null;
                $res = isset($m['result']) ? (string)$m['result'] : '';
                $resNorm = trim(strtolower((string)$res));

                if ($p1 === null || $p2 === null) continue;
                $ensurePlayer($p1); $ensurePlayer($p2);

                if ($p1 === $p2 || strtoupper($res) === 'BYE') {
                    if (strtoupper($res) === 'BYE') { $players[$p1]['match_points'] += 3.0; $players[$p1]['matches_played'] += 1; $players[$p1]['rounds_won'] += 1.0; }
                    continue;
                }

                if ($resNorm === '' || $resNorm === 'unplayed') continue;

                if ($resNorm === 'draw') {
                    $players[$p1]['match_points'] += 1.0; $players[$p2]['match_points'] += 1.0;
                    $players[$p1]['matches_played'] += 1; $players[$p2]['matches_played'] += 1;
                    $players[$p1]['games_won'] += 1; $players[$p2]['games_won'] += 1;
                    $players[$p1]['games_played'] += 2; $players[$p2]['games_played'] += 2;
                    $players[$p1]['rounds_won'] += 0.5; $players[$p2]['rounds_won'] += 0.5;
                    $players[$p1]['opponents'][] = $p2; $players[$p2]['opponents'][] = $p1;
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
        // require login
        $user = $this->user->getIdentity();
        if (!$user) {
            return $this->redirect('?c=Auth&a=login');
        }

        // load tournament
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

        // load players (rankings)
        $players = TournamentPlayer::getRankingsForTournament($tournamentId);
        $count = count($players);
        if ($count < 2) {
            $_SESSION['pairing_errors'] = ['Not enough players to create pairings.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // build rematch map from existing matches
        $playedRows = TournamentPlayer::executeRawSQL('SELECT player1_id, player2_id FROM match_ WHERE tournament_id = ?', [$tournamentId]);
        $played = [];
        foreach ($playedRows as $r) {
            $p1 = (int)$r['player1_id'];
            $p2 = (int)$r['player2_id'];
            $played[$p1][$p2] = true;
            $played[$p2][$p1] = true;
        }

        // prevent new pairings if unfinished matches exist
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

        // compute pairs using SwissPairing service
        try {
            $res = \App\Services\SwissPairing::generate($tournamentId, $players);
            $pairs = $res['pairs'] ?? [];
            $bye = $res['bye'] ?? null;
        } catch (\Exception $e) {
            $_SESSION['pairing_errors'] = ['Pairing failed: ' . $e->getMessage()];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // check if all pairs would be rematches
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

        // compute next round number
        try {
            $maxRow = TournamentPlayer::executeRawSQL('SELECT MAX(round_number) AS m FROM match_ WHERE tournament_id = ?', [$tournamentId]);
            $maxRound = isset($maxRow[0]['m']) && $maxRow[0]['m'] !== null ? (int)$maxRow[0]['m'] : 0;
            $nextRound = $maxRound + 1;
        } catch (\Exception $e) {
            $nextRound = 1;
        }

        // save generated pairs and handle bye
        try {
            foreach ($pairs as $p) {
                $p1 = (int)$p[0];
                $p2 = (int)$p[1];
                TournamentPlayer::executeRawSQL('INSERT INTO match_ (tournament_id, round_number, player1_id, player2_id) VALUES (:t, :r, :p1, :p2)', [':t' => $tournamentId, ':r' => $nextRound, ':p1' => $p1, ':p2' => $p2]);
            }
            if ($bye !== null) {
                $b = (int)$bye;
                TournamentPlayer::executeRawSQL('INSERT INTO match_ (tournament_id, round_number, player1_id, player2_id, result) VALUES (:t, :r, :p1, :p2, :res)', [':t' => $tournamentId, ':r' => $nextRound, ':p1' => $b, ':p2' => $b, ':res' => 'BYE']);
                TournamentPlayer::executeRawSQL('UPDATE tournament_player SET points = COALESCE(points,0) + 3 WHERE tournament_id = ? AND user_id = ?', [$tournamentId, $b]);
            }
            $_SESSION['pairing_success'] = 'Pairings generated for round ' . $nextRound;
            if ($tournament->status === 'planned') {
                $tournament->status = 'ongoing';
                $tournament->save();
            }
        } catch (\Exception $e) {
            $_SESSION['pairing_errors'] = ['Failed to save pairings: ' . $e->getMessage()];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // redirect to detail view
        return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings&round=' . $nextRound);
    }

    public function join(Request $request): Response
    {
        // require login
        $user = $this->user->getIdentity();
        if (!$user) {
            return $this->redirect('?c=Auth&a=login');
        }

        // load tournament
        $tournamentId = (int)$request->post('tournament_id');
        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            return $this->redirect('?c=Tournament&a=index');
        }

        // register user if not already
        $existing = TournamentPlayer::getAll("tournament_id = ? AND user_id = ?", [$tournamentId, $user->user_id]);
        if (empty($existing)) {
            $tp = new TournamentPlayer();
            $tp->tournament_id = $tournamentId;
            $tp->user_id = $user->user_id;
            $tp->save();
        }

        // handle deck upload if provided
        $file = $request->file('decklist');
        if ($file !== null) {
            // disallow replacing existing decklist
            $existingDecksCheck = Decklist::getAll('tournament_id = ? AND user_id = ?', [$tournamentId, $user->user_id]);
            if (!empty($existingDecksCheck)) {
                $_SESSION['deck_upload_errors'] = ['You already have a decklist uploaded for this tournament. Delete it before uploading a new one.'];
                return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
            }

            // validate upload and contents
            $errors = [];
            if (!$file->isOk()) {
                $errors[] = $file->getErrorMessage() ?? 'File upload failed.';
            } else {
                $name = $file->getName();
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if ($ext !== 'txt') {
                    $errors[] = 'Only .txt files are accepted for decklists.';
                }
                $tmp = $file->getFileTempPath();
                $contents = @file($tmp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($contents === false) {
                    $errors[] = 'Unable to read uploaded file.';
                } else {
                    if (count($contents) > 100) {
                        $errors[] = 'Decklist may contain at most 100 non-empty lines.';
                    }
                    // require SB line near end
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

                // prepare storage path and save file
                if (empty($errors)) {
                    // create slug and uploads directory
                    $slug = preg_replace('/[^a-z0-9\-]/i', '-', trim((string)$tournament->name));
                    $slug = preg_replace('/-+/', '-', strtolower($slug));
                    $slug = trim($slug, '-');
                    if ($slug === '') $slug = 'tournament-' . $tournamentId;

                    $uploadsDir = __DIR__ . '/../../public/uploads/' . $tournamentId . '_' . $slug;
                    if (!is_dir($uploadsDir)) {
                        @mkdir($uploadsDir, 0777, true);
                    }
                    $safeName = preg_replace('/[^a-z0-9\-_.]/i', '_', pathinfo($name, PATHINFO_FILENAME));
                    $finalName = sprintf('%s_%d_%d.txt', $safeName, $user->user_id, time());
                    $dest = $uploadsDir . DIRECTORY_SEPARATOR . $finalName;
                    if ($file->store($dest)) {
                        // save decklist record
                        $deckModelClass = '\\App\\Models\\Decklist';
                        if (class_exists($deckModelClass)) {
                            $deck = new $deckModelClass();
                            $deck->user_id = $user->user_id;
                            $deck->tournament_id = $tournamentId;
                            $deck->file_path = 'uploads/' . $tournamentId . '_' . $slug . '/' . $finalName;
                            $deck->uploaded_at = date('Y-m-d H:i:s');
                            $deck->save();
                        }
                    } else {
                        $errors[] = 'Failed to move uploaded file.';
                    }
                }
            }

            // set session messages for upload
            if (!empty($errors)) {
                $_SESSION['deck_upload_errors'] = $errors;
            } else {
                $_SESSION['deck_upload_success'] = 'Decklist uploaded successfully.';
            }
        }

        // redirect back to tournament detail
        return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
    }

    public function deckDelete(Request $request): Response
    {
        // require login
        $user = $this->user->getIdentity();
        if (!$user) {
            return $this->redirect('?c=Auth&a=login');
        }

        // load tournament
        $tournamentId = (int)$request->post('tournament_id');
        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            return $this->redirect('?c=Tournament&a=index');
        }

        // prevent deletion after start
        if (in_array($tournament->status, ['ongoing', 'finished'])) {
            $_SESSION['deck_upload_errors'] = ['Decklist nie je možné odstrániť po začatí turnaja.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
        }

        // find and delete deck files and records
        $deckrows = Decklist::getAll('tournament_id = ? AND user_id = ?', [$tournamentId, $user->user_id]);
        foreach ($deckrows as $d) {
            if (!empty($d->file_path)) {
                $full = __DIR__ . '/../../public/' . $d->file_path;
                if (is_file($full)) @unlink($full);
            }
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
        // require login
        $user = $this->user->getIdentity();
        if (!$user) {
            return $this->redirect('?c=Auth&a=login');
        }

        // load tournament
        $tournamentId = (int)$request->post('tournament_id');
        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            return $this->redirect('?c=Tournament&a=index');
        }

        // prevent unregistering after start
        if (in_array($tournament->status, ['ongoing', 'finished'])) {
            $_SESSION['leave_errors'] = ['Nie je možné odhlásiť sa po začatí turnaja.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
        }

        // remove registration
        try {
            TournamentPlayer::deleteByTournamentAndUser($tournamentId, $user->user_id);
        } catch (\Exception $e) {
            // fallback delete
            $rows = TournamentPlayer::getAll('tournament_id = ? AND user_id = ?', [$tournamentId, $user->user_id]);
            foreach ($rows as $r) {
                try { $r->delete(); } catch (\Exception $e) { /* ignore */ }
            }
        }

        // delete associated decklists
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
        // require login, support ajax
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

        // load match
        $matchId = (int)$request->post('match_id');
        $tournamentId = (int)$request->post('tournament_id');
        $token = (string)$request->post('result'); // e.g. 'p1_2_0', 'p2_2_1', 'unplayed'

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

        // load tournament and check organizer
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

        if ($user->user_id != $tournament->organizer_id) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Only tournament organizer can set match results.']);
                exit;
            }
            $_SESSION['pairing_errors'] = ['Only tournament organizer can set match results.'];
            return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings');
        }

        // prevent editing BYE matches
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

        // prevent editing if later rounds exist
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
            // allow editing if DB check fails
        }

        // parse result token
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

        // points values
        $winPoints = 3.0;
        $drawPoints = 1.0;

        try {
            // reverse previous result points if any
            if (!empty($existingResult) && strtoupper($existingResult) !== 'UNPLAYED') {
                $prevWinnerId = null;
                $prevWasDraw = false;
                $er = strtoupper($existingResult);
                if ($er === 'DRAW') {
                    $prevWasDraw = true;
                } elseif (str_starts_with($er, '2-0') || str_starts_with($er, '2-1') || str_starts_with($er, 'P1')) {
                    $prevWinnerId = $p1;
                } elseif (str_starts_with($er, '0-2') || str_starts_with($er, '1-2') || str_starts_with($er, 'P2')) {
                    $prevWinnerId = $p2;
                }
                if ($prevWasDraw) {
                    TournamentPlayer::executeRawSQL('UPDATE tournament_player SET points = GREATEST(COALESCE(points,0) - ?, 0) WHERE tournament_id = ? AND user_id IN (?, ?)', [$drawPoints, $tournamentId, $p1, $p2]);
                } elseif ($prevWinnerId !== null) {
                    TournamentPlayer::executeRawSQL('UPDATE tournament_player SET points = GREATEST(COALESCE(points,0) - ?, 0) WHERE tournament_id = ? AND user_id = ?', [$winPoints, $tournamentId, $prevWinnerId]);
                }
            }

            // apply new result points
            if ($isDraw) {
                TournamentPlayer::executeRawSQL('UPDATE tournament_player SET points = COALESCE(points,0) + ? WHERE tournament_id = ? AND user_id IN (?, ?)', [$drawPoints, $tournamentId, $p1, $p2]);
            } elseif ($winner !== null) {
                TournamentPlayer::executeRawSQL('UPDATE tournament_player SET points = COALESCE(points,0) + ? WHERE tournament_id = ? AND user_id = ?', [$winPoints, $tournamentId, $winner]);
            }

            // save match result token
            $saveVal = $token;
            TournamentPlayer::executeRawSQL('UPDATE match_ SET result = ? WHERE match_id = ?', [$saveVal, $matchId]);

            $_SESSION['pairing_success'] = 'Match result saved.';

            // if ajax, return updated points and rankings
            if ($isAjax) {
                $ptsRows = TournamentPlayer::executeRawSQL('SELECT user_id, points FROM tournament_player WHERE tournament_id = ? AND user_id IN (?, ?)', [$tournamentId, $p1, $p2]);
                $pointsMap = [];
                foreach ($ptsRows as $pr) {
                    $pointsMap[(int)$pr['user_id']] = (float)$pr['points'];
                }
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

        // redirect back to pairings tab and round
        $rnd = isset($match['round_number']) ? (int)$match['round_number'] : 1;
        return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId . '&tab=pairings&round=' . $rnd);
    }

    private function computeRankings(int $tournamentId, array $initialUsernames = []): array
    {
        // load pairings and rebuild ranking stats from results
        $rankings = [];
        try {
            $pairings = TournamentPlayer::executeRawSQL('SELECT * FROM match_ WHERE tournament_id = ? ORDER BY round_number ASC, match_id ASC', [$tournamentId]);

            // init players from tournament_player table
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

            // helper to ensure a player exists in the map
            $ensurePlayer = function($uid) use (&$players) {
                $uid = (int)$uid;
                if (!isset($players[$uid])) {
                    $players[$uid] = ['user_id'=>$uid,'username'=>'','match_points'=>0.0,'matches_played'=>0,'games_won'=>0,'games_played'=>0,'rounds_won'=>0.0,'opponents'=>[]];
                }
            };

            // process each match to compute match_points, games, opponents, etc.
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

            // finalize percentages
            foreach ($players as $uid => &$pd) {
                $pd['gwp'] = ($pd['games_played'] > 0) ? ($pd['games_won'] / $pd['games_played']) : 0.0;
                $pd['mwp'] = ($pd['matches_played'] > 0) ? ($pd['rounds_won'] / $pd['matches_played']) : 0.0;
            }
            unset($pd);

            // compute opponent-based tiebreakers
            foreach ($players as $uid => &$pd) {
                $oppCount = count($pd['opponents']);
                if ($oppCount === 0) { $pd['ogwp'] = 0.0; continue; }
                $sumOGWP = 0.0;
                foreach ($pd['opponents'] as $opp) {
                    if (isset($players[$opp])) $sumOGWP += $players[$opp]['gwp'];
                }
                $pd['ogwp'] = $sumOGWP / $oppCount;
            }
            unset($pd);

            // sort and build rankings array
            $newRankings = array_values($players);
            usort($newRankings, function($a, $b) {
                if ($a['match_points'] !== $b['match_points']) return ($a['match_points'] > $b['match_points']) ? -1 : 1;
                if ($a['gwp'] !== $b['gwp']) return ($a['gwp'] > $b['gwp']) ? -1 : 1;
                if ($a['ogwp'] !== $b['ogwp']) return ($a['ogwp'] > $b['ogwp']) ? -1 : 1;
                return strcasecmp($a['username'] ?? '', $b['username'] ?? '');
            });

            foreach ($newRankings as $r) {
                $rankings[] = ['user_id' => $r['user_id'], 'username' => $r['username'] ?? '', 'points' => $r['match_points'], 'gwp' => $r['gwp'], 'ogwp' => $r['ogwp']];
            }
        } catch (\Exception $e) {
            // on error return empty rankings
        }
        return $rankings;
    }

    public function timerStatus(Request $request): Response
    {
        // return current timer state for a tournament (JSON)
        $tournamentId = (int)$request->get('tournament_id');
        $round = $request->get('round');

        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Tournament not found.']);
            exit;
        }

        $file = $this->getTimerFilePath($tournamentId);
        if (!file_exists($file)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'active' => false]);
            exit;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unable to read timer file.']);
            exit;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['end_time'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'active' => false]);
            exit;
        }

        $end = strtotime($data['end_time']);
        $now = time();
        $remaining = max(0, $end - $now);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'active' => ($remaining > 0),
            'end_time' => $data['end_time'],
            'remaining_seconds' => $remaining,
            'round' => $data['round'] ?? null,
            'started_by' => $data['started_by'] ?? null,
        ]);
        exit;
    }

    public function startTimer(Request $request): Response
    {
        // start a timer for a round (organizer/admin only)
        $user = $this->user->getIdentity();
        if (!$user) {
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit;
        }

        $tournamentId = (int)$request->post('tournament_id');
        $round = (int)$request->post('round');

        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Tournament not found']); exit;
        }

        if ($round <= 0) {
            try {
                $r = TournamentPlayer::executeRawSQL('SELECT MAX(round_number) AS maxr FROM match_ WHERE tournament_id = ?', [$tournamentId]);
                $maxr = isset($r[0]['maxr']) ? (int)$r[0]['maxr'] : 0;
                $round = ($maxr > 0) ? $maxr : 1;
            } catch (\Exception $e) {
                $round = 1;
            }
        }

        $isAdmin = isset($user->role_id) && (int)$user->role_id === 1;
        $isOrganizerOwner = isset($user->user_id) && ((int)$user->user_id === (int)$tournament->organizer_id);
        if (!$isAdmin && !$isOrganizerOwner) {
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Only organizer may start the timer']); exit;
        }

        $durationMinutes = 50;
        $endTime = date('c', time() + $durationMinutes * 60);
        $data = [
            'tournament_id' => $tournamentId,
            'round' => $round,
            'started_by' => $user->user_id ?? null,
            'started_at' => date('c'),
            'duration_minutes' => $durationMinutes,
            'end_time' => $endTime,
        ];

        $file = $this->getTimerFilePath($tournamentId);
        $dir = dirname($file);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $ok = @file_put_contents($file, json_encode($data));
        if ($ok === false) {
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Failed to write timer file']); exit;
        }

        header('Content-Type: application/json'); echo json_encode(['success'=>true,'end_time'=>$endTime,'round'=>$round]); exit;
    }

    public function resetTimer(Request $request): Response
    {
        // reset timer (organizer/admin only)
        $user = $this->user->getIdentity();
        if (!$user) {
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit;
        }

        $tournamentId = (int)$request->post('tournament_id');
        $tournament = Tournament::getOne($tournamentId);
        if (!$tournament) {
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Tournament not found']); exit;
        }

        $isAdmin = isset($user->role_id) && (int)$user->role_id === 1;
        $isOrganizerOwner = isset($user->user_id) && ((int)$user->user_id === (int)$tournament->organizer_id);
        if (!$isAdmin && !$isOrganizerOwner) {
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Only organizer may reset the timer']); exit;
        }

        $file = $this->getTimerFilePath($tournamentId);
        if (file_exists($file)) @unlink($file);

        header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit;
    }

    private function getTimerFilePath(int $tournamentId): string
    {
        // store timer state in project data directory: App/../data/timers/{id}.json
        $base = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
        $dataDir = $base . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'timers';
        return $dataDir . DIRECTORY_SEPARATOR . 'tournament_' . $tournamentId . '.json';
    }
}

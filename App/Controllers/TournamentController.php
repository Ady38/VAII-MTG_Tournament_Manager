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
                        $found = preg_replace('/^\s*\d+\s*(?:x|×)?\s*/i', '', $found);
                        break;
                    }
                }
                $commanders[$uid] = $found;
            }
        } catch (\Exception $e) {
            // ignore and leave commanders empty on error
        }

        return $this->html([
            'tournament' => $tournament,
            'isRegistered' => $isRegistered,
            'isLogged' => $isLogged,
            'rankings' => $rankings,
            'commanders' => $commanders,
            'user_decklist' => $userDecklist,
        ], 'detail');
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
                        if (preg_match('/^\s*SB:/i', $lastLine)) {
                            $hasSB = true;
                        }
                        if (!$hasSB && $len >= 2) {
                            $secondLast = trim((string)$contents[$len - 2]);
                            if (preg_match('/^\s*SB:/i', $secondLast)) {
                                $hasSB = true;
                            }
                        }
                        if (!$hasSB) {
                            $errors[] = "Decklist must have a line starting with 'SB:' in the last one or two non-empty lines.";
                        }
                    }
                }

                if (empty($errors)) {
                    $uploadsDir = __DIR__ . '/../../public/uploads/decklists';
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
                            $deck->file_path = 'uploads/decklists/' . $finalName;
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

}


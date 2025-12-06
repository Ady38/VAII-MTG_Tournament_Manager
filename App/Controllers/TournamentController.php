<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
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
        $filtered = array_filter($tournaments, function (Tournament $tournament) use ($name, $location, $status, $date) {
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
        if ($identity) {
            $registrations = TournamentPlayer::getAll(
                "tournament_id = ? AND user_id = ?",
                [$tournament->tournament_id, $identity->user_id]
            );
            $isRegistered = !empty($registrations);
        }

        $isLogged = $this->user->isLoggedIn();

        // Rankings: load players for this tournament ordered by points / rank_position
        $rankings = TournamentPlayer::getRankingsForTournament((int)$tournament->tournament_id);

        return $this->html([
            'tournament' => $tournament,
            'isRegistered' => $isRegistered,
            'isLogged' => $isLogged,
            'rankings' => $rankings,
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

        TournamentPlayer::deleteByTournamentAndUser($tournamentId, $user->user_id);

        return $this->redirect('?c=Tournament&a=detail&id=' . $tournamentId);
    }
}

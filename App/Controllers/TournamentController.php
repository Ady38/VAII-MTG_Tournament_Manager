<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use App\Models\Tournament;
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

            if (!$tournament->name) {
                $errors[] = 'Name is required.';
            }
            if (!$tournament->start_date) {
                $errors[] = 'Start date is required.';
            }
            if (!$tournament->end_date) {
                $errors[] = 'End date is required.';
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
        return $this->html(['errors' => $result['errors'], 'tournament' => $result['tournament']], 'add');
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
}

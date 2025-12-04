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
        $tournaments = Tournament::getAll();
        return $this->html(['tournaments' => $tournaments]);
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

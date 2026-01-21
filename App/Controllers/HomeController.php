<?php

namespace App\Controllers;

use App\Models\Tournament;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

class HomeController extends BaseController
{
    // allow all actions
    public function authorize(Request $request, string $action): bool
    {
        return true;
    }

    // show home page with next tournament
    public function index(Request $request): Response
    {
        $today = date('Y-m-d');
        $tournaments = Tournament::getAll('start_date >= ?', [$today], 'start_date ASC', 1);
        $nextTournament = $tournaments[0] ?? null;
        return $this->html(['nextTournament' => $nextTournament]);
    }

    // contact page
    public function contact(Request $request): Response
    {
        return $this->html();
    }
}

<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

class UserController extends BaseController
{
    public function index(Request $request): Response
    {
        // Default index for UserController - redirect to home
        return $this->redirect('?c=Home&a=index');
    }

    public function detail(Request $request): Response
    {
        $id = $request->get('id');
        if (!$id) {
            return $this->redirect('?c=Home&a=index');
        }

        $user = User::getOne($id);
        if (!$user) {
            return $this->redirect('?c=Home&a=index');
        }

        // build recent tournaments history for this user
        $recentTournaments = [];
        try {
            $tps = TournamentPlayer::getAll('user_id = ?', [$user->user_id]);
            foreach ($tps as $tp) {
                $t = Tournament::getOne($tp->tournament_id);
                if (!$t) continue;
                $recentTournaments[] = [
                    'tournament_id' => $t->tournament_id ?? null,
                    'name' => $t->name ?? '',
                    'start_date' => $t->start_date ?? null,
                    'status' => $t->status ?? null,
                    'points' => $tp->points ?? 0,
                    'rank_position' => $tp->rank_position ?? null,
                ];
            }
            // sort by start_date desc (nulls last)
            usort($recentTournaments, function ($a, $b) {
                $da = $a['start_date'] ? strtotime($a['start_date']) : 0;
                $db = $b['start_date'] ? strtotime($b['start_date']) : 0;
                return $db <=> $da;
            });
            // keep only latest 5
            $recentTournaments = array_slice($recentTournaments, 0, 5);
        } catch (\Exception $e) {
            $recentTournaments = [];
        }

        // pass under a different name to avoid collision with framework AppUser ($user) in layout
        return $this->html(['profileUser' => $user, 'recentTournaments' => $recentTournaments]);
    }

    public function edit(Request $request): Response
    {
        // Only logged in users can edit their profile
        if (!$this->user->isLoggedIn()) {
            return $this->redirect('?c=Auth&a=login');
        }

        $identity = $this->user->getIdentity();
        $id = (int)$identity->user_id;

        $user = User::getOne($id);
        if (!$user) {
            return $this->redirect('?c=Home&a=index');
        }

        $errors = [];
        if ($request->isPost()) {
            $username = trim((string)$request->post('username'));
            $email = trim((string)$request->post('email'));
            $password = $request->post('password');
            $password_confirm = $request->post('password_confirm');

            if ($username === '') $errors[] = 'Username is required.';
            if ($email === '') $errors[] = 'Email is required.';

            // Validate email format
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format.';
            }

            // If password provided, ensure confirmation matches
            if (!empty($password) && $password !== $password_confirm) {
                $errors[] = 'Password confirmation does not match.';
            }

            // Check unique username
            $rows = User::getAll('username = ?', [$username]);
            if (!empty($rows) && ($rows[0]->user_id ?? 0) !== $user->user_id) {
                $errors[] = 'Username is already taken.';
            }
            // Check unique email
            $rows2 = User::getAll('email = ?', [$email]);
            if (!empty($rows2) && ($rows2[0]->user_id ?? 0) !== $user->user_id) {
                $errors[] = 'Email is already used.';
            }

            if (empty($errors)) {
                $user->username = $username;
                $user->email = $email;
                if (!empty($password)) {
                    $user->password_hash = password_hash($password, PASSWORD_DEFAULT);
                }
                try {
                    $user->save();
                    $_SESSION['flash_success'] = 'Profile updated successfully.';
                    return $this->redirect('?c=User&a=detail&id=' . $user->user_id);
                } catch (\Exception $e) {
                    $errors[] = 'Unable to save changes.';
                }
            }
        }

        return $this->html(['profileUser' => $user, 'errors' => $errors]);
    }
}

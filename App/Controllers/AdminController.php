<?php

namespace App\Controllers;

use App\Models\User;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Class AdminController
 *
 * This controller manages admin-related actions within the application.It extends the base controller functionality
 * provided by BaseController.
 *
 * @package App\Controllers
 */
class AdminController extends BaseController
{
    // only admin users allowed
    public function authorize(Request $request, string $action): bool
    {
        if (!$this->user->isLoggedIn()) return false;
        $identity = $this->user->getIdentity();
        return isset($identity->role_id) && (int)$identity->role_id === 1;
    }

    // admin dashboard
    public function index(Request $request): Response
    {
        return $this->html();
    }

    // list users
    public function users(Request $request): Response
    {
        $users = [];
        try { $users = User::getAll(null, [], 'username ASC'); } catch (\Exception $e) { $users = []; }

        $roles = [1 => 'Admin', 2 => 'Organizer', 3 => 'Player'];
        return $this->html(['users' => $users, 'roles' => $roles]);
    }

    // show user edit form
    public function editUser(Request $request): Response
    {
        $id = $request->get('id'); if (!$id) return $this->redirect($this->url('Admin.users'));
        $user = User::getOne($id); if (!$user) return $this->redirect($this->url('Admin.users'));
        return $this->html(['editUser' => $user]);
    }

    // update user
    public function updateUser(Request $request): Response
    {
        if (!$request->isPost()) return $this->redirect($this->url('Admin.users'));
        $id = $request->post('user_id'); $user = User::getOne($id); if (!$user) return $this->redirect($this->url('Admin.users'));

        $errors = [];
        $role_id = $request->post('role_id');
        $email = trim((string)$request->post('email'));
        $username = trim((string)$request->post('username'));

        if ($username === '') $errors[] = 'Username is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

        // uniqueness checks
        $existing = User::findOne(['username' => $username]);
        if ($existing && $existing->user_id != $user->user_id) $errors[] = 'Username already taken.';
        $existing2 = User::findOne(['email' => $email]);
        if ($existing2 && $existing2->user_id != $user->user_id) $errors[] = 'Email already used.';

        if (empty($errors)) {
            $user->username = $username;
            $user->email = $email;
            $user->role_id = (int)$role_id;
            try {
                $user->save();
                $this->app->getSession()->set('flash_success', 'User updated successfully.');
                return $this->redirect($this->url('Admin.users'));
            } catch (\Exception $e) {
                $errors[] = 'Unable to save user.';
            }
        }

        return $this->html(['editUser' => $user, 'errors' => $errors]);
    }

    // delete user (prevent self-deletion)
    public function deleteUser(Request $request): Response
    {
        if (!$request->isPost()) return $this->redirect($this->url('Admin.users'));
        $id = $request->post('user_id'); $user = User::getOne($id); if (!$user) return $this->redirect($this->url('Admin.users'));

        $identity = $this->user->getIdentity();
        if ($identity && $identity->user_id == $user->user_id) {
            $this->app->getSession()->set('flash_error', 'Cannot delete yourself.');
            return $this->redirect($this->url('Admin.users'));
        }

        try { $user->delete(); $this->app->getSession()->set('flash_success', 'User deleted.'); }
        catch (\Exception $e) { $this->app->getSession()->set('flash_error', 'Failed to delete user.'); }

        return $this->redirect($this->url('Admin.users'));
    }
}

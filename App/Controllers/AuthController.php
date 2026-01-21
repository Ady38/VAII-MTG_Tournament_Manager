<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\User;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\ViewResponse;

class AuthController extends BaseController
{
    // redirect to configured login URL
    public function index(Request $request): Response
    {
        return $this->redirect(Configuration::LOGIN_URL);
    }

    // handle login form
    public function login(Request $request): Response
    {
        $logged = null;
        if ($request->hasValue('submit')) {
            $user = User::authenticate($request->value('username'), $request->value('password'));
            if ($user) {
                // store identity in session
                $this->app->getSession()->set(\App\Configuration::IDENTITY_SESSION_KEY, $user);
                return $this->redirect($this->url("home.index"));
            } else {
                $logged = false;
            }
        }

        $message = $logged === false ? 'Bad username or password' : null;
        return $this->html(compact("message"));
    }

    // handle registration
    public function register(Request $request): Response
    {
        $message = null;
        if ($request->hasValue('submit')) {
            $username = trim((string)$request->value('username'));
            $email = trim((string)$request->value('email'));
            $password = (string)$request->value('password');
            $password2 = (string)$request->value('password2');

            $errors = [];
            if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email address.';
            if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
            if ($password !== $password2) $errors[] = 'Passwords do not match.';

            // Check uniqueness
            if (User::findOne(['username' => $username])) $errors[] = 'Username already exists.';
            if (User::findOne(['email' => $email])) $errors[] = 'Email is already in use.';

            if (empty($errors)) {
                $user = new User();
                $user->username = $username;
                $user->email = $email;
                $user->password_hash = password_hash($password, PASSWORD_DEFAULT);
                // default role: player (role_id = 3)
                $user->role_id = 3;
                $user->save();

                $this->app->getSession()->set('register_success', 'Account created successfully. Please log in.');
                return $this->redirect(Configuration::LOGIN_URL);
            }

            $message = implode('<br>', $errors);
        }

        return $this->html(compact('message'));
    }

    // logout current user
    public function logout(Request $request): Response
    {
        $auth = $this->app->getAuthenticator();
        if ($auth !== null) {
            try {
                $auth->logout();
            } catch (\Throwable $e) {
                $this->app->getSession()->set(\App\Configuration::IDENTITY_SESSION_KEY, null);
            }
        } else {
            $this->app->getSession()->set(\App\Configuration::IDENTITY_SESSION_KEY, null);
        }

        return $this->redirect($this->url('Home.index'));
    }
}

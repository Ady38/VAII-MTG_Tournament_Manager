<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\User;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\ViewResponse;

/**
 * Class AuthController
 *
 * This controller handles authentication actions such as login, logout, and redirection to the login page. It manages
 * user sessions and interactions with the authentication system.
 *
 * @package App\Controllers
 */
class AuthController extends BaseController
{
    /**
     * Redirects to the login page.
     *
     * This action serves as the default landing point for the authentication section of the application, directing
     * users to the login URL specified in the configuration.
     *
     * @return Response The response object for the redirection to the login page.
     */
    public function index(Request $request): Response
    {
        return $this->redirect(Configuration::LOGIN_URL);
    }

    /**
     * Authenticates a user and processes the login request.
     *
     * This action handles user login attempts. If the login form is submitted, it attempts to authenticate the user
     * with the provided credentials. Upon successful login, the user is redirected to the admin dashboard.
     * If authentication fails, an error message is displayed on the login page.
     *
     * @return Response The response object which can either redirect on success or render the login view with
     *                  an error message on failure.
     * @throws Exception If the parameter for the URL generator is invalid throws an exception.
     */
    public function login(Request $request): Response
    {
        $logged = null;
        if ($request->hasValue('submit')) {
            $user = User::authenticate($request->value('username'), $request->value('password'));
            if ($user) {
                // Store identity object in session for framework
                $this->app->getSession()->set(\App\Configuration::IDENTITY_SESSION_KEY, $user);
                return $this->redirect($this->url("home.index"));
            } else {
                $logged = false;
            }
        }

        $message = $logged === false ? 'Bad username or password' : null;
        return $this->html(compact("message"));
    }

    /**
     * Registers a new user account.
     * Handles GET (show form) and POST (process registration) requests.
     */
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

                // flash message via session
                $this->app->getSession()->set('register_success', 'Account created successfully. Please log in.');
                return $this->redirect(Configuration::LOGIN_URL);
            }

            $message = implode('<br>', $errors);
        }

        return $this->html(compact('message'));
    }

    /**
     * Logs out the current user.
     *
     * This action terminates the user's session and redirects them to the home page.
     * It clears any authentication tokens or session data associated with the user.
     *
     * @return Response The redirect response to the home page.
     */
    public function logout(Request $request): Response
    {
        // If an authenticator is configured, use it to logout (it may destroy the session)
        $auth = $this->app->getAuthenticator();
        if ($auth !== null) {
            try {
                $auth->logout();
            } catch (\Throwable $e) {
                // Fallback: ensure identity session key is cleared
                $this->app->getSession()->set(\App\Configuration::IDENTITY_SESSION_KEY, null);
            }
        } else {
            // No authenticator: clear the stored identity directly
            $this->app->getSession()->set(\App\Configuration::IDENTITY_SESSION_KEY, null);
        }

        // Redirect to home page after logout
        return $this->redirect($this->url('Home.index'));
    }
}

<?php

namespace Cloud\Slim\Middleware;

use Slim\Middleware;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

/**
 * Session and Authentication
 */
class Session extends Middleware
{
    /**
     * @var User|null
     */
    protected $user;

    /**
     * @var Company|null
     */
    protected $company;

    /**
     * Middleware Call
     */
    public function call()
    {
        $app = $this->app;

        /*
         * TODO: X-XSRF-TOKEN
         *  - Response for cloudxxx-ng/index.html should set XSRF-TOKEN cookie
         *  - Cookie contains timestamped signed token
         *  - Value is passed as header to API
         *  - This class verifies signature is valid
         */

        // $app->session
        $app->session = function () {
            return $this;
        };

        $this->next->call();
    }

    /**
     * Get the the authenticated user
     *
     * @return User|null
     */
    public function user()
    {
        if (isset($_SESSION['session.user.id'])) {
            return $this->app->em->find('cx:user', $_SESSION['session.user.id']);
        }

        return null;
    }

    /**
     * Get the company of the authenticated user
     *
     * @return Company|null
     */
    public function company()
    {
        return $this->user() ? $this->user()->getCompany() : null;
    }

    /**
     * Checks if the session has an authenticated user
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return (bool) $this->user();
    }

    /**
     * Authenticate with the given identity and password
     *
     * @param  array  $identity
     * @param  string $password
     * @return User|null
     */
    public function login(array $identity, $password)
    {
        $app = $this->app;

        // fetch user

        $users = $app->em
            ->getRepository('cx:user')
            ->findBy($identity);

        if (count($users) < 1) {
            $app->log->info(sprintf(
                'No users matching indentity %s',
                json_encode($identity)
            ));

            return null;
        } elseif (count($users) > 1) {
            // TODO: exception or $app->error?
            $app->log->error(sprintf(
                'Multiple users matching indentity %s, got %d',
                json_encode($identity), count($users)
            ));

            return null;
        }

        $user = $users[0];

        // verify password

        if (!$user->verifyPassword($password)) {
            $app->log->notice(sprintf(
                'Authentication failed: password mismatch for User#%d (%s)',
                $user->getId(), json_encode($identity)
            ));

            return null;
        }

        // session

        $app->em->transactional(function ($em) use ($user) {
            $user->setLastLoginAt();
        });

        session_regenerate_id(true);

        $_SESSION['session.user.id'] = $user->getId();

        $app->log->info(sprintf(
            'Authentication successful for User#%d (%s)',
            $user->getId(), json_encode($identity)
        ));

        return $user;
    }

    /**
     * Destroy authenticated session
     */
    public function logout()
    {
        session_destroy();
        session_start();
        session_regenerate_id();
        return true;
    }

    public function authorize($scope = null)
    {
        return function ($route) {
        };
    }
}


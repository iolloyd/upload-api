<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */

namespace Cloud\Silex\Application;

use Cloud\Model\User;
use Cloud\Model\Company;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

trait SecurityTrait
{
    /**
     * Gets a user from the Security Context
     *
     * @return User|null
     */
    public function user()
    {
        return $this['user'];
    }

    /**
     * Gets a user's company from the Security Context
     *
     * @return Company|null
     */
    public function company()
    {
        return $this['company'];
    }

    /**
     * Encodes the raw password.
     *
     * @param UserInterface $user     A UserInterface instance
     * @param string        $password The password to encode
     *
     * @return string The encoded password
     *
     * @throws \RuntimeException when no password encoder could be found for the user
     */
    public function encodePassword(UserInterface $user, $password)
    {
        return $this['security.encoder_factory']->getEncoder($user)->encodePassword($password, $user->getSalt());
    }
}

<?php
namespace CloudTest\Mock;

class MockUser
{
    public static function get()
    {
        $company = MockCompany::get();
        $user = new \Cloud\Model\User();
        $user->setCompany($company);
        $user->setEmail('fun@bags.com');
        $user->setPassword('root');
        return $user;
    }
}


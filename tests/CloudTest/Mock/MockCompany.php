<?php
namespace Tests\Mock;

class MockCompany
{
    public static function get()
    {
        $entity = new \Cloud\Model\Company();
        $entity->setTitle('I am a company');
        return $entity;
    }
}


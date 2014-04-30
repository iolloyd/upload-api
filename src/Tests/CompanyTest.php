<?php
namespace Tests;

use Cloud\Model\Company;
use Tests\Mock\MockUser;
use Tests\Mock\MockCompany;

class CompanyTest extends Model
{
    public function testSave()
    {
        $em = $this->entityManager;
        $company = MockCompany::get();
        $em->persist($company);
        $em->flush();
        $companies = $em->getRepository(
            "Cloud\Model\Company")->findAll();

        $expected = 1;
        $actual = count($company);
        $this->assertEquals($expected, $actual);
    }

    public function testAddUser()
    {
        $em = $this->entityManager;
        $user = MockUser::get();
        $company = MockCompany::get();
        $company->addUser($user);

        $em->persist($company);
        $em->flush();

        $users = $company->getUsers();
        $expected = $user;
        $actual = $users[0];

        $this->assertEquals($expected, $actual);
    }
}


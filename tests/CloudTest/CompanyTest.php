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
        $company = $this->getCompany();
        $em->persist($company);
        $em->flush();
        $companies = $em->getRepository(
            "Cloud\Model\Company")->findAll();

        $this->assertEquals(1, count($company));
    }

    public function testAddUser()
    {
        $em = $this->entityManager;

        $user = MockUser::get();

        $company = MockCompany::get();
        $company->addUser($user);
        $em->persist($company);

        $em->flush();
    }

    protected function getCompany()
    {
        $company = new Company();
        $company->setTitle('I am a Company');

        return $company;
    }

}


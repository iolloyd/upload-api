<?php

namespace Cloud\Model\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Cloud\Model\User;

/**
 * Load test users
 */
class UserFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $user1 = new User();
        $user1->setName('Test User');
        $user1->setEmail('alan@cloud.xxx');
        $user1->setPassword('nail');
        $user1->setCompany($this->getReference('cumulus'));

        $user2 = new User();
        $user2->setName('Another User');
        $user2->setEmail('bill@cloud.xxx');
        $user2->setPassword('lib');
        $user2->setCompany($this->getReference('cumulus'));

        $user3 = new User();
        $user3->setEmail('chris@cloud.xxx');
        $user3->setPassword('sirk');
        $user3->setCompany($this->getReference('foobar'));

        $em->persist($user1);
        $em->persist($user2);
        $em->persist($user3);
        $em->flush();
    }

    /**
     * Get the other fixtures this one is dependent on
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\CompanyFixture',
        ];
    }
}

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


namespace Cloud\Model\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Cloud\Model\Site;

/**
 * Loads test sites for companies
 */
class SiteFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $site1 = new Site();
        $site1->setTitle('HDPOV');
        $site1->setColor('#8b3bdc');
        $site1->setInitials('HD');
        $site1->setCompany($this->getReference('cumulus'));

        $site2 = new Site();
        $site2->setTitle('Dane Jones');
        $site2->setColor('#fac71f');
        $site2->setInitials('DJ');
        $site2->setCompany($this->getReference('cumulus'));

        $site3 = new Site();
        $site3->setTitle('FooSite.com');
        $site3->setColor('#fa1f4d');
        $site3->setInitials('FS');
        $site3->setCompany($this->getReference('foobar'));

        $em->persist($site1);
        $em->persist($site2);
        $em->persist($site3);
        $em->flush();

        $this->addReference('site-hdpov', $site1);
        $this->addReference('site-danejones', $site2);
        $this->addReference('site-foo', $site3);
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

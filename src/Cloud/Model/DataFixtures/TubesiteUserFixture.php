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

use Cloud\Model\TubesiteUser;

/**
 * Loads sample users without the credentials
 */
class TubesiteUserFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $youpornUser = new TubesiteUser(
            $this->getReference('youporn'),
            $this->getReference('cumulus')
        );
        $youpornUser->setSite($this->getReference('site-danejones'));
        $youpornUser->setUsername('DaneJones');
        $youpornUser->setPassword('...'); // update db manually
        $youpornUser->setExternalId(4864148);
        $youpornUser->setParam('content_partner_site_id', 2242);

        $xhamsterUser = new TubesiteUser(
            $this->getReference('xhamster'),
            $this->getReference('cumulus')
        );
        $xhamsterUser->setSite($this->getReference('site-danejones'));
        $xhamsterUser->setUsername('Ruseful2011');
        $xhamsterUser->setPassword('...'); // update db manually
        $xhamsterUser->setExternalId(2021166);
        $xhamsterUser->setParam('site', [
            'id' => 4265,
            'title' => 'Kissing HD',
            'description' => 'KissingHD.com',
        ]);

        $xvideosUser = new TubesiteUser(
            $this->getReference('xvideos'),
            $this->getReference('cumulus')
        );
        $xvideosUser->setSite($this->getReference('site-danejones'));
        $xvideosUser->setUsername('reggie@ruseful.com');
        $xvideosUser->setPassword('...'); // update db manually
        $xvideosUser->setExternalId(8302147);
        $xvideosUser->setParam('site', 'hdpov');
        $xvideosUser->setParam('channel', 'chan_2724');

        $em->persist($youpornUser);
        $em->persist($xhamsterUser);
        $em->persist($xvideosUser);
        $em->flush();

        $this->addReference('youporn-user', $youpornUser);
        $this->addReference('xhamster-user', $xhamsterUser);
        $this->addReference('xvideos-user', $xvideosUser);
    }

    /**
     * Get the other fixtures this one is dependent on
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\TubesiteFixture',
            __NAMESPACE__ . '\CompanyFixture',
        ];
    }
}

<?php

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

        $youpornUser->setUsername('DaneJones');
        $youpornUser->setPassword('...'); // update db manually
        $youpornUser->setExternalId(4864148);
        $youpornUser->setParam('content_partner_site_id', 2242);

        $em->persist($youpornUser);
        $em->flush();

        $this->addReference('youporn-user', $youpornUser);
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

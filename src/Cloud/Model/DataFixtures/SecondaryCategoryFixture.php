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
use Doctrine\Common\Persistence\ObjectManager;

use Cloud\Model\Category;

/**
 * Loads all standard video categories
 */
class SecondaryCategoryFixture extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
<<<<<<< HEAD:src/Cloud/Model/DataFixtures/TagFixture.php
        $tags = [
=======
        // Based on YouPorn:
        $secondaryCategories = [
            'Anal', 'Asian', 'BBW', 'Bear', 'Big Butt',
            'Big Tits', 'Bisexual', 'Blonde', 'Blowjob', 'Brunette', 'Coed',
            'Compilation', 'Couples', 'Creampie', 'Cumshots', 'Cunnilingus',
            'Dildos/Toys', 'DILF', 'DP', 'Ebony', 'European', 'Facial', 'Fantasy',
            'Fetish', 'Fingering', 'Funny', 'Gay', 'German', 'Gonzo', 'Group Sex',
            'Hairy', 'Handjob', 'HD', 'Hentai', 'Instructional', 'Interracial',
            'Interview', 'Kissing', 'Latina', 'Latino', 'Lesbian', 'Massage',
            'Masturbate', 'Mature', 'MILF', 'Panties', 'Pantyhose', 'POV',
            'Public', 'Redhead', 'Rimming', 'Romantic', 'Shaved', 'Shemale',
            'Solo Girl', 'Solo Male', 'Squirting', 'Straight Sex', 'Swallow', 'Teen',
            'Threesome', 'Twink', 'Underwear', 'Videos', 'Vincategorye', 'Voyeur',
            'Webcam', 'Young/Old',
>>>>>>> Refactoring:src/Cloud/Model/DataFixtures/SecondaryCategoryFixture.php
        ];

        foreach ($secondaryCategories as $index => $title) {
            $category = new Category();
            $category->setTitle($title);
            $em->persist($category);
            echo "category-" . $index;
            $this->setReference("category-" . $index, $category);
        }

        $em->flush();
    }
}

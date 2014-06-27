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
 * Loads Cloud.xxx master categories
 */
class CategoryFixture extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $tags = [
            'Amateur',
            'Anal',
            'Asian',
            'BBW',
            'Big Butt',
            'Big Tits',
            'Bisexual',
            'Blonde',
            'Blowjob',
            'Brunette',
            'Coed',
            'Compilation',
            'Couples',
            'Creampie',
            'Cumshots',
            'Cunnilingus',
            'DP',
            'Dildos/Toys',
            'Ebony',
            'European',
            'Facial',
            'Fantasy',
            'Female Friendly',
            'Fetish',
            'Fingering',
            'Funny',
            'Gay',
            'German',
            'Gonzo',
            'Group Sex',
            'Hairy',
            'Handjob',
            'Hentai',
            'Instructional',
            'Interracial',
            'Interview',
            'Kissing',
            'Latina',
            'Lesbian',
            'MILF',
            'Massage',
            'Masturbate',
            'Mature',
            'POV',
            'Panties',
            'Pantyhose',
            'Public',
            'Redhead',
            'Rimming',
            'Romantic',
            'Shaved',
            'Shemale',
            'Solo Male',
            'Solo Girl',
            'Squirting',
            'Straight Sex',
            'Swallow',
            'Teen',
            'Threesome',
            'Vintage',
            'Voyeur',
            'Webcam',
            'Young/Old',
            '3D',
        ];

        foreach ($tags as $id => $title) {
            $cat = new Category();
            //$cat->setId($id);
            $cat->setTitle($title);
            $em->persist($cat);
        }

        $em->flush();
    }
}

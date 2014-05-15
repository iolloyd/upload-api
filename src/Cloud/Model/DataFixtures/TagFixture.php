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

use Cloud\Model\Tag;

/**
 * Loads all standard video tags
 */
class TagFixture extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        // Based on YouPorn:
        $tags = [
            'Amateur', 'Anal', 'Asian', 'BBW', 'Bear', 'Big Butt',
            'Big Tits', 'Bisexual', 'Blonde', 'Blowjob', 'Brunette', 'Coed',
            'Compilation', 'Couples', 'Creampie', 'Cumshots', 'Cunnilingus',
            'Dildos/Toys', 'DILF', 'DP', 'Ebony', 'European', 'Facial', 'Fantasy',
            'Fetish', 'Fingering', 'Funny', 'Gay', 'German', 'Gonzo', 'Group Sex',
            'Hairy', 'Handjob', 'HD', 'Hentai', 'Instructional', 'Interracial',
            'Interview', 'Kissing', 'Latina', 'Latino', 'Lesbian', 'Massage',
            'Masturbate', 'Mature', 'MILF', 'Panties', 'Pantyhose', 'POV',
            'Public', 'Redhead', 'Rimming', 'Romantic', 'Shaved', 'Shemale',
            'Solo Girl', 'Solo Male', 'Squirting', 'Straight Sex', 'Swallow', 'Teen',
            'Threesome', 'Twink', 'Underwear', 'Videos', 'Vintage', 'Voyeur',
            'Webcam', 'Young/Old',
        ];

        foreach ($tags as $title) {
            $tag = new Tag();
            $tag->setTitle($title);
            $em->persist($tag);
        }

        $em->flush();
    }
}

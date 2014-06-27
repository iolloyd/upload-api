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
        $tags = [
        ];

        foreach ($tags as $title) {
            $tag = new Tag();
            $tag->setTitle($title);
            $em->persist($tag);
        }

        $em->flush();
    }
}

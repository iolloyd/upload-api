<?php
namespace CloudTest;

use Cloud\Model\Tag;

class TagTest extends Model
{
    public function testSave()
    {
        $em = $this->entityManager;
        $tag = $this->getTag();
        $em->persist($tag);
        $em->flush();
        $tags = $em->getRepository(
            "Cloud\Model\Tag")->findAll();

        $this->assertEquals(1, count($tag));
    }

    protected function getTag()
    {
        $tag = new Tag();
        $tag->setTitle('I am a tag');

        return $tag;
    }

}


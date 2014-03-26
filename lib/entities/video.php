<?php

/**
 * @Entity @Table(name="videos")
 */
class Video
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;

    /** @Column(length=255) */
    private $title;

    /** @Column(length=255) */
    private $description;

    /** @Column(length=255) */
    private $path;

}


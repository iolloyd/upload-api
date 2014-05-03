<?php

namespace Cloud\Console\Helper;

use Cloud\Slim\SlimAwareInterface;
use Cloud\Slim\SlimAwareTrait;
use Symfony\Component\Console\Helper\Helper;

/**
 * Slim application CLI helper
 */
class SlimHelper extends Helper implements SlimAwareInterface
{
    use SlimAwareTrait;

    /**
     * Constructor
     *
     * @param \Slim\Slim $app
     */
    public function __construct(\Slim\Slim $app)
    {
        $this->setSlim($app);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'slim';
    }
}

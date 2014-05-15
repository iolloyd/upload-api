<?php

namespace Cloud\Console\Helper;

use Cloud\Silex\SilexAwareInterface;
use Cloud\Silex\SilexAwareTrait;
use Symfony\Component\Console\Helper\Helper;

/**
 * Silex application CLI helper
 */
class ApplicationHelper extends Helper implements SilexAwareInterface
{
    use SilexAwareTrait;

    /**
     * Constructor
     *
     * @param \Cloud\Silex\Application $app
     */
    public function __construct(\Cloud\Silex\Application $app)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'silex';
    }
}

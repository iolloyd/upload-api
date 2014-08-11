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

namespace CloudEncoder;

use GuzzleHttp\Client;
use Exception;

/**
 * Class S3VideoDownloader
 */
class S3VideoDownloader
{
    /**
     * @param $s3
     * @param $bucket
     * @param $input
     * @return mixed
     * @throws \Exception
     */
    public function process($s3, $bucket, $input)
    {
        
        $inputUrl = $s3->getObjectUrl($bucket, $input, '+1 hour');
        $client = new Client();
        $result = $client->get($inputUrl, [ 'save_to' => $input]);
        if (!$result) {
            throw new Exception("could not retrieve " . $input);
        }

        return $input;
    }

}

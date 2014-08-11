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

/**
 * Class S3VideoDownload
 */
class S3VideoDownloader
{
    /**
     * @param $s3
     * @param $bucket
     * @param $input
     * @param $output
     */
    public function process($s3, $bucket, $input, $output)
    {
        
        $inputUrl = $s3->getObjectUrl($bucket, $input, '+1 hour');
        $client = new Client();
        $client->get($inputUrl, [ 'save_to' => $output ]);
    }

}

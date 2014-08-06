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

class VideoDownload
{
    public function process()
    {
        
        $client = new Client([
            'base_url' => [
                'https://s3.amazonaws.com/cldsys-{version}/',
                ['version' => 'dev']
            ],
            'defaults' => [
                'timeout'         => 10,
                'allow_redirects' => false,
                'proxy'           => '192.168.16.1:10'
            ]
        ]);

        // Fetch video from amazon or wherever
        $client = new GuzzleHttp\Client();
        $output = $client->get($input)->setResponseBody($output);

    }

}


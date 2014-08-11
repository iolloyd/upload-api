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

use Exception;

/**
 * Class S3VideoUploader
 */
class S3VideoUploader
{
    /**
     * @param $client
     * @param $bucket
     * @param $input
     * @return mixed
     * @throws \Exception
     */
    public function process($client, $bucket, $input)
    {
        $result = $client->putObject([
            'Key'        => $input,
            'Bucket'     => $bucket,
            'SourceFile' => $input,
        ]);

        if (!$result) {
            throw new Exception('Could not upload ' . $input);
        }
        return $result;
    }
}


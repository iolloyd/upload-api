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

namespace Cloud\Aws\S3\Model;

use Aws\S3\Model\PostObject as BasePostObject;

/**
 * Extends the S3 PostObject model with support for temporary credentials from
 * AWS STS
 *
 * @see http://docs.aws.amazon.com/AmazonS3/latest/dev/HTTPPOSTForms.html
 * @see http://docs.aws.amazon.com/aws-sdk-php/guide/latest/credentials.html#using-temporary-credentials-from-aws-sts
 */
class PostObject extends BasePostObject
{
    /**
     * {@inheritDoc}
     */
    public function prepareData()
    {
        $token = $this->client->getCredentials()->getSecurityToken();

        if ($token) {
            $this->data['x-amz-security-token'] = $token;
        }

        $result = parent::prepareData();

        if ($token) {
            unset($this->data['x-amz-security-token']);
        }

        return $result;
    }
}

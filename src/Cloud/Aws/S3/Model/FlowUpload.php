<?php

namespace Cloud\Aws\S3\Model;

use InvalidArgumentException;
use UnexpectedValueException;
use Aws\S3\S3Client;
use Guzzle\Common\Collection;
use Guzzle\Service\Exception\CommandTransferException;

/**
 * Handle flow.js chunked file uploads, verify them, and combine them
 * into a single file
 */
class FlowUpload extends Collection
{
    /**
     * @var S3Client
     */
    protected $client;

    /**
     * @var string  Bucket name where the chunked objects were posted
     */
    protected $bucket;

    /**
     * @var string  Prefix of the directory containing the chunked objects
     */
    protected $prefix;

    /**
     * @var array  Chunked object metadata
     */
    protected $chunks;

    /**
     * @var array  Common metadata of the chunked objects
     */
    protected $metadata;

    /**
     * @var bool  Cached validation result
     */
    protected $isValid;

    /**
     * Constructs the PostObject
     *
     * The options array accepts the following keys:
     *
     * - acl:                          The access control setting to apply to the uploaded file. Accepts any of the
     *                                 CannedAcl constants
     * - Cache-Control:                The Cache-Control HTTP header value to apply to the uploaded file
     * - Content-Disposition:          The Content-Disposition HTTP header value to apply to the uploaded file
     * - Content-Encoding:             The Content-Encoding HTTP header value to apply to the uploaded file
     * - Content-Type:                 The Content-Type HTTP header value to apply to the uploaded file. The default
     *                                 value is `application/octet-stream`
     * - Expires:                      The Expires HTTP header value to apply to the uploaded file
     * - key:                          The location where the file should be uploaded to. The default value is
     *                                 `^${filename}` which will use the name of the uploaded file
     * - policy:                       A raw policy in JSON format. By default, the PostObject creates one for you
     * - success_action_redirect:      The URI for Amazon S3 to redirect to upon successful upload
     * - success_action_status:        The status code for Amazon S3 to return upon successful upload
     * - ttd:                          The expiration time for the generated upload form data
     * - x-amz-server-side-encryption: The server-side encryption mechanism to use
     * - x-amz-storage-class:          The storage setting to apply to the object
     * - x-amz-meta-*:                 Any custom meta tag that should be set to the object
     *
     * For the Cache-Control, Content-Disposition, Content-Encoding,
     * Content-Type, Expires, and key options, to use a "starts-with" comparison
     * instead of an equals comparison, prefix the value with a ^ (carat)
     * character
     *
     * @param S3Client $client
     * @param $bucket
     * @param array $options
     */
    public function __construct(S3Client $client, $bucket, $prefix, array $options = array())
    {
        $this->setClient($client);
        $this->setBucket($bucket);
        $this->setPrefix($prefix);
        parent::__construct($options);
    }

    public function isValid()
    {
        if ($this->isValid === null) {
            try {
                $this->validate();
            } catch (RuntimeException $e) {
            }
        }

        return $this->isValid;
    }

    /**
     * Get an array of chunk objects indexed by key. Fetches objects and
     * metadata from S3 on first call.
     *
     * @return array
     */
    protected function getChunks()
    {
        if (!$this->chunks) {
            $this->chunks = [];

            // list directory
            $chunks = $this->getClient()->getIterator('ListObjects', [
                'Bucket' => $this->getBucket(),
                'Prefix' => $this->getPrefix(),
            ]);

            // fetch metadata
            foreach ($chunks as $chunk) {
                $this->chunks[$chunk['Key']] = $this->getClient()->headObject([
                    'Bucket' => $this->getBucket(),
                    'Key'    => $chunk['Key'],
                ]);
            }

            // sort by chunk number
            uasort($this->chunks, function ($a, $b) {
                $na = (int) $a['Metadata']['flowchunknumber'];
                $nb = (int) $b['Metadata']['flowchunknumber'];
                return ($na < $nb) ? -1 : (($na == $nb) ? 0 : 1);
            });
        }

        return $this->chunks;
    }

    /**
     * Validate the uploaded chunks for count, metadata and completeness
     */
    public function validate()
    {
        $this->isValid = false;

        $expectedIdentifier = null;
        $expectedTotalSize = 0;
        $expectedTotalChunks = 0;

        $totalSize = 0;
        $totalChunks = 0;
        $maxChunkNumber = 0;

        $chunks = $this->getChunks();

        foreach ($chunks as $key => $chunkMeta) {
            //if ($video != $chunkMeta['Metadata']['video']) {
                //throw new \Exception(sprintf(
                    //'Video ID mismatch for chunk `%s`, expected `%s`, got `%s`',
                    //$chunk['Key'],
                    //$video,
                    //$chunkMeta['Metadata']['video']
                //));
            //}

            if (!$chunkMeta['Metadata']['flowidentifier']) {
                throw new UnexpectedValueException(sprintf(
                    'Missing required metadata field `flowidentifier` for chunk `%s`',
                    $key
                ));
            }

            if (!$expectedIdentifier) {
                $expectedIdentifier  = $chunkMeta['Metadata']['flowidentifier'];
                $expectedTotalSize   = (int) $chunkMeta['Metadata']['flowtotalsize'];
                $expectedTotalChunks = (int) $chunkMeta['Metadata']['flowtotalchunks'];
            } elseif ($expectedIdentifier != $chunkMeta['Metadata']['flowidentifier']) {
                throw new UnexpectedValueException(sprintf(
                    'Identifier mismatch for chunk `%s`, expected `%s`, got `%s`',
                    $key,
                    $expectedIdentifier,
                    $chunkMeta['Metadata']['flowidentifier']
                ));
            }

            if ($chunkMeta['Metadata']['flowcurrentchunksize'] != $chunkMeta['ContentLength']) {
                throw new UnexpectedValueException(sprintf(
                    'Content length mismatch for chunk `%s`, expected `%s`, got `%s`',
                    $key,
                    $chunkMeta['Metadata']['flowcurrentchunksize'],
                    $chunkMeta['ContentLength']
                ));
            }

            $totalSize += (int) $chunkMeta['Metadata']['flowcurrentchunksize'];
            $totalChunks += 1;
            $maxChunkNumber = max($maxChunkNumber, (int) $chunkMeta['Metadata']['flowchunknumber']);
        }

        if ($totalChunks < 1) {
            throw new UnexpectedValueException(sprintf(
                'No chunks for upload `%s`', $this->getPrefix()
            ), 404);
        }

        if ($expectedTotalChunks != $totalChunks) {
            throw new UnexpectedValueException(sprintf(
                'Total count mismatch for upload `%s`, expected `%s`, got `%s`',
                $this->getPrefix(), $expectedTotalChunks, $totalChunks
            ));
        }

        if ($maxChunkNumber != $totalChunks - 1) {
            throw new UnexpectedValueException(sprintf(
                'Max chunk number does not match count for upload `%s`, expected `%s`, got `%s`',
                $this->getPrefix(), $totalChunks - 1, $maxChunkNumber
            ));
        }

        if ($expectedTotalSize != $totalSize) {
            throw new UnexpectedValueException(sprintf(
                'Total size mismatch for upload `%s`, expected `%s`, got `%s`',
                $this->getPrefix(), $expectedTotalSize, $totalSize
            ));
        }

        $this->metadata = reset($chunks)['Metadata'];
        unset($this->metadata['flowchunknumber']);
        unset($this->metadata['flowcurrentchunksize']);

        $this->isValid = true;

        return $this;
    }

    /**
     * Concatenate all chunk objects and copy them to the given key
     *
     * @param string $targetKey
     * @return \Guzzle\Service\Resource\Model
     */
    public function copyToObject($key, array $options = [])
    {
        $client = $this->getClient();

        if (strpos($key, $this->getPrefix()) === 0) {
            throw new InvalidArgumentException(sprintf(
                'Target object `%s` must be outside chunk upload directory `%s`',
                $key, $this->getPrefix()
            ));
        }

        /*
         * create aggregate
         */

        $defaults = [
            'Bucket' => $this->getBucket(),
        ];

        $options = array_merge($defaults, $options, [
            'Key' => $key,
        ]);

        $multipartUpload = $client->createMultipartUpload($options);

        /*
         * copy chunks to aggregate in parallel
         */

        $commands = [];

        foreach ($this->getChunks() as $key => $chunkMeta) {
            $partNumber = ((int) $chunkMeta['Metadata']['flowchunknumber']) + 1;

            $commands[] = $client->getCommand('UploadPartCopy', [
                'UploadId'   => $multipartUpload['UploadId'],
                'Bucket'     => $multipartUpload['Bucket'],
                'Key'        => $multipartUpload['Key'],
                'CopySource' => '/' . $this->getBucket() . '/' . $key, // TODO clean up path
                'PartNumber' => $partNumber,
            ]);
        }

        try {
            $client->execute($commands);
        } catch (CommandTransferException $e) {
            $client->abortMultipartUpload([
                'UploadId' => $multipartUpload['UploadId'],
                'Bucket'   => $multipartUpload['Bucket'],
                'Key'      => $multipartUpload['Key'],
            ]);

            throw $e;
        }

        $parts = [];

        foreach ($commands as $command) {
            $query = $command->getRequest()->getQuery();
            $response = $command->getResult();

            $parts[] = [
                'ETag'       => $response['ETag'],
                'PartNumber' => $query['partNumber'],
            ];
        }

        /*
         * complete aggregate
         */

        return $client->completeMultipartUpload([
            'UploadId' => $multipartUpload['UploadId'],
            'Bucket'   => $multipartUpload['Bucket'],
            'Key'      => $multipartUpload['Key'],
            'Parts'    => $parts,
        ]);
    }

    /**
     * Delete the chunk objects from the bucket
     *
     * @return \Guzzle\Service\Resource\Model
     */
    public function deleteChunks()
    {
        $objects = array_map(
            function ($d) {
                return [ 'Key' => $d ];
            },
            array_keys($this->getChunks())
        );

        return $this->getClient()->deleteObjects([
            'Bucket'  => $this->getBucket(),
            'Objects' => $objects,
        ]);
    }

    /**
     * Get common metadata of the chunked objects
     *
     * @return array
     */
    public function getMetadata()
    {
        if (!$this->isValid()) {
            return [];
        }

        return $this->metadata;
    }

    /**
     * Sets the S3 client
     *
     * @param S3Client $client
     *
     * @return FlowUpload
     */
    public function setClient(S3Client $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Gets the S3 client
     *
     * @return S3Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the bucket and makes sure it is a valid bucket name
     *
     * @param string $bucket
     *
     * @return FlowUpload
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
        return $this;
    }

    /**
     * Gets the bucket name
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Sets the directory prefix
     *
     * @param string $prefix
     * @return FlowUpload
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Gets the directory prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}

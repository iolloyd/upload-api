<?php
namespace Cloud\Worker;

use GuzzleHttp\Client;
use GuzzleHttp\Post\Postfile;

class FileUpload
{
    public function perform()
    {
        $this->upload(
            $this->args['source'], 
            $this->args['destination'],
            $this->args['filename']
        );
    }

    protected function upload($source, $destination, $filename)
    {
        $client = new Client();
        $request = $client->createRequest('POST', $destination);
        $body = $request->getBody();
        $body->setField('filename', $filename);
        $body->addFile(new PostFile('video', fopen($source, 'r')));
        $response = $client->send($request);
        return $response;
    }
}


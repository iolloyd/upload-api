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
            $this->args['destination']
        );
    }

    protected function upload($source, $destination)
    {
        $client = new Client();
        $request = $client->createRequest('POST', $destination);
        $body = $request->getBody();
        $body->addFile(new PostFile('video', fopen($source, 'r')));
        $response = $client->send($request);
        return $response;
    }
}


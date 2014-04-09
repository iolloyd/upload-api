<?php
namespace Cloud\Worker;

class FileUpload
{
    public function perform()
    {
        $this->upload(
            $this->args['source'],
            $this->args['destination']
        );
    }

    protected function upload($args)
    {
        $client = new Guzzle\Http\Client($destination);
        $client->put('/', null, fopen($source, 'r'))->send();
    }
}


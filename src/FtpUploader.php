<?php
namespace Cloud;
require_once 'FileUploader.php';

class FtpUploader extends FileUploader
{
    public function perform()
    {
        $this->upload(
            $this->args['path'],
            $this->args['destination'],
            $this->args['user']
        );
    }

    protected function upload($source, $destination, $user)
    {
        if (!$connection = getFtpLogin($usename)) {
            throw new Exception("Could not connect: $username\n");
        }

        if (!ftp_put($connection, $destination, $source, $mode)) {
            throw new Exception("Could not send to ' . $destination\n");
        }

        ftp_close($connection);
    }
}


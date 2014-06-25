<?php

namespace Cloud\Zencoder;

class Encoder 
{
    
    protected $encoder;
    protected $videoFile;

    public function __construct(VideoFile $videoFile, $encoder)
    {
        $this->videoFile = $videoFile;
        $this->encoder = $encoder;
    }

    /*
     * Creates an encoding job based on parameters defined.
     * See the documentation at https://app.zencoder.com/docs/api/encoding/job
     * for a complete list.
     *
     * @param string $input  Location of the file to be encoded
     * @param array  $output Optional array of parameters for the encoding job
     *
     * @return Services_Zencoder_Jobs $encodingJob
     */
    public function creatEncodingJob($input, $outputs = [])
    {
        $encodingJob = $this->encoder->jobs->create([
            "input"   => $input,
            "outputs" => $outputs,
        ]);

        $this->videoFile->setZencoderId($encodingJob->id);

        return $encodingJob;
    }
}

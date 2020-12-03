<?php


class CloudConvert
{

    const TIMEOUT = 300;

    private $apikey;
    private $url;

    public function __construct($inputformat, $outputformat, $apikey)
    {
        $this->apikey = $apikey;

        $data = $this->req('https://api.cloudconvert.com/v2', array(
            'inputformat' => $inputformat,
            'outputformat' => $outputformat,
            'apikey' => $apikey
        ));

       $this->url = 'https://api.cloudconvert.com/v2';


    }

    public function convert($filepath, $outputformat, $target)
    {
        $file = pathinfo($filepath);
        $this->req($this->url, array(
            'input' => 'upload',
            'format' => $outputformat,
            'file' => '@' . $filepath
        ));
        $time = 0;
        
        while ($time <= self::TIMEOUT) {
            sleep(1);
            $time++;
            $data = $this->req($this->url);
            if ($data->step == 'error') {
                throw new Exception($data->message);
                return false;
            } elseif ($data->step == 'finished' && isset($data->output) && isset($data->output->url)) {
                if (strpos($data->output->url, 'http') === false)
                    $data->output->url = "https:" . $data->output->url;
                $this->download($data->output->url, $target);
                return true;
            }
        }
        throw new Exception('Timeout');
        return false;
    }

    public function getURL()
    {
        return $this->url;
    }

    private function req($url, $post = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        $return = curl_exec($ch);

        if ($return === FALSE) {
            throw new Exception(curl_error($ch));
        } else {
            $json = json_decode($return);
            if (isset($json->error))
                throw new Exception($json->error);
            return $json;
        }
        curl_close($ch);
    }

    private function download($url, $target)
    {
        $fp = fopen($target, 'w+');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (!curl_exec($ch)) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);
        fclose($fp);
    }
}


?>
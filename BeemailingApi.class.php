<?php

/**
 * Class BeemailingApiClient
 * Beemailing API wrapper
 * @author Joffrey LETUVE <joffrey@letuve.com>
 */

class BeemailingApiClient {

    private $url = 'https://api.beemailing.com';
    private $api_key = '';
    private $sessionid = '';


    function __construct()
        {
        $array_parameters = func_get_args();

        if(isset($array_parameters[0]) && is_string($array_parameters[0]))
            {
            $this->api_key($array_parameters[0]);
            }

        if(isset($array_parameters[1]) && is_string($array_parameters[1]))
            {
            $this->url($array_parameters[1]);
            }
        
        if(isset($array_parameters[0]) && is_array($array_parameters[0]))
            {
            if (isset($array_parameters[0]['endpoint']))
                $this->url($array_parameters[0]['endpoint']);
    
            if (isset($array_parameters[0]['key']))
                $this->api_key($array_parameters[0]['key']);
            }
        }

    function api_key($api_key)
        {
        $this->api_key = $api_key;
        }

    function url($url)
        {
        $this->url = $url;
        }


    /**
     * Return an API return array data
     */
    function call($command, $parameters=array())
        {
        if(!preg_match('#(https?://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#i', $this->url))
        {
            throw new Exception ('Invalid URL ('.$this->url.')');
        }
        
        $api_parameters     = [];
        $api_headers        = [];

        // Default API call parameters
        $api_headers =  [
            'X-API-Key: ' . $this->api_key,
            'X-API-Response-Format: ' . 'JSON'
        ];

        if(!empty($this->sessionid))
        {
        $api_headers[] = 'X-API-Session-ID: '.$this->sessionid;
        }

        foreach($parameters as $pname=>$pvalue)
        {
            if(!isset($api_parameters[$pname]))
                {
                if(($pname=='ImportFileLocal' || $pname=='UploadFile') && file_exists(realpath($pvalue)))
                    {
                    $UploadFilePath = realpath($pvalue);
                    $api_parameters[$pname] = new CURLFile($UploadFilePath, mime_content_type($UploadFilePath), basename($UploadFilePath));
                    }
                elseif(is_array($pvalue))
                    {
                    $api_parameters[$pname] = json_encode($pvalue);
                    }
                else
                    {
                    $api_parameters[$pname] = $pvalue;
                    }
                }
        };
        
        $call_url = $this->url.'/'.$command.'/';
        
        // Get Json data
        $ch = curl_init($call_url);
        
        curl_setopt($ch, CURLOPT_URL, $call_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $api_headers);
        curl_setopt($ch, CURLOPT_POST, count($api_parameters));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $api_parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        
        //execute post
        $array_return = [
            'Success' => false,
            'Message' => '',
            'Data' => []
        ];
        $http_return = curl_exec($ch);
        if($http_return==false)
            {
            $array_return['Message'] = 'HTTP ERROR : '.curl_error($ch) .' #'. curl_errno($ch);
            return $array_return;
            }
        else
            {
            $http_return = trim($http_return);
            $http_return = utf8_encode($http_return);

            try
                {
                $api_response_array = json_decode($http_return, TRUE, 512, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR);
                }
            catch(Exception $e)
                {
                $array_return['Message'] = 'JSON Error #'.$e->getCode().' : '.$e->getMessage();
                $array_return['Data'] = ['Raw'=>$http_return];
                
                return $array_return;
                }
            
            return $api_response_array;
            }

        return $array_return;
        }


    function __call($name, $arguments)
        {
        $api_command = str_replace(' ','.',ucwords(str_replace('_',' ',$name)));

        $api_command_parameters = array();
        if(isset($arguments[0]) && is_array($arguments[0]))
            $api_command_parameters = $arguments[0];

        $return = $this->call($api_command, $api_command_parameters);

        if(isset($return['SessionID']))
        {
            $this->sessionid = $return['SessionID'];
        }

        return $return;
        }


    }

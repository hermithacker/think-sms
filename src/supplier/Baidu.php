<?php

namespace think\sms\supplier;
use think\Config;

class Baidu extends Supplier
{
    protected $accessKey;
    protected $secretAccessKey;
    protected $invokeId;
    protected $endPoint;
    protected $apiUrl = [
        'smsV1'=>[
            'path'=>'/v1/message',
            'method'=>'GET',
        ],
        'template'=>[
            'path'=>'/v1/template',
            'method'=>'GET',
        ],
        'getTemplate'=>[
            'path'=>'/v1/template/',
            'method'=>'GET',
        ],
        'deleteTemplate'=>[
            'path'=>'/v1/template/',
            'method'=>'DELETE',
        ],
        'getMessage'=>[
            'path'=>'/v1/message/',
            'method'=>'GET',
        ],
        'message'=>[
            'path'=>'/bce/v2/message',
            'method'=>'POST',
        ],
        'applyTemplate'=>[
            'path'=>'/bce/v2/applyTemplate',
            'method'=>'POST',
        ],
        'quota'=>[
            'path'=>'/v1/quota',
            'method'=>'GET',
        ],
        'receiver'=>[
            'path'=>'/v1/receiver/',
            'method'=>'GET',
        ],
    ];

    public function __construct()
    {
        $config = Config::get('sms.baidu');
        $this->accessKey = isset($config['accessKey'])?$config['accessKey']:'';
        $this->secretAccessKey = isset($config['secretAccessKey'])?$config['secretAccessKey']:'';
        $this->invokeId = isset($config['invokeId'])?$config['invokeId']:'';
        $this->endPoint = isset($config['endPoint']) ? $config['endPoint'] : 'sms.bj.baidubce.com';
    }

    /**
     * 请求头信息
     * @param $api
     * @return array
     */
    private function setHeader($api){
        $CanonicalURI = $api['path'];
        $method = $api['method'];
        date_default_timezone_set('UTC');
        $timestamp = date("Y-m-d")."T".date("H:i:s")."Z";
        $expire = "3600";
        $authStringPrefix = "bce-auth-v1"."/".$this->accessKey."/".$timestamp."/".$expire;
        $SigningKey=hash_hmac('SHA256',$authStringPrefix,$this->secretAccessKey);
        $CanonicalHeaders1 = "host;x-bce-date";
        $CanonicalHeaders2 = "host:{$this->endPoint}\n"."x-bce-date:".urlencode($timestamp);
        $CanonicalString = "";
        $CanonicalRequest = $method."\n".$CanonicalURI."\n".$CanonicalString."\n".$CanonicalHeaders2;
        $Signature = hash_hmac('SHA256',$CanonicalRequest,$SigningKey);
        $Authorization = "bce-auth-v1/{$this->accessKey}/".$timestamp."/{$expire}/{$CanonicalHeaders1}/{$Signature}";
        return [
            "Content-Type:application/json",
            "x-bce-date:{$timestamp}",
            "x-bce-content-sha256:{$SigningKey}",
            "Authorization:{$Authorization}",
        ];
    }

    /**
     * 获取请求结果
     * @param $api
     * @param array $data
     * @return mixed
     */
    public function getRequset($api,$data=[]){
        $url = "http://{$this->endPoint}{$api['path']}";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER,$this->setHeader($api));
        switch(strtoupper($api['method'])){
            case 'GET':
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                $data_string = json_encode($data);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_PUT, true);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        $output = json_decode($output,true);
        return $output;
    }

    /**
     * 模板列表
     * @return mixed
     */
    public function template(){
        return $this->getRequset($this->apiUrl[__FUNCTION__]);
    }

    /**
     * 申请模板
     * @param $name
     * @param $content
     * @return mixed
     */
    public function applyTemplate($name,$content){
        return $this->getRequset($this->apiUrl[__FUNCTION__],[
            'invokeId'=>$this->invokeId,
            'name'=>$name,
            'content'=>$content,
        ]);
    }

    /**
     * 删除模板
     * @param $templateCode
     * @return mixed
     */
    public function deleteTemplate($templateCode){
        $url = $this->apiUrl[__FUNCTION__];
        $url['path'] .= urlencode($templateCode);
        return $this->getRequset($url);
    }

    /**
     * 获取模板信息
     * @param $templateCode
     * @return mixed
     */
    public function getTemplate($templateCode){
        $url = $this->apiUrl[__FUNCTION__];
        $url['path'] .= urlencode($templateCode);
        return $this->getRequset($url);
    }

    /**
     * 发送短信
     * @param $mobile
     * @param $templateCode
     * @param $data
     * @return mixed
     */
    public function message($mobile,$templateCode,$data){
        return $this->getRequset($this->apiUrl[__FUNCTION__],[
            'invokeId'=>$this->invokeId,
            'phoneNumber'=>$mobile,
            'templateCode'=>$templateCode,
            "contentVar"=>$data,
        ]);
    }

    /**
     * 查询发送配额
     * @return mixed
     */
    public function quota(){
        return $this->getRequset($this->apiUrl[__FUNCTION__]);
    }

    /**
     * 查询单终端用户短信接收信息
     * @param $mobile
     * @return mixed
     */
    public function receiver($mobile){
        $url = $this->apiUrl[__FUNCTION__];
        $url['path'] .= urlencode($mobile);
        return $this->getRequset($url);
    }

    /**
     * 查询短信流水信息
     * @param $messageId
     * @return mixed
     */
    public function getMessage($messageId){
        $url = $this->apiUrl[__FUNCTION__];
        $url['path'] .= urlencode($messageId);
        return $this->getRequset($url);
    }
}
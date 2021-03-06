<?php

namespace app\index\service;
use think\Model;
use think\Db;
/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class Jssdk extends Model
{

    private $appId;
    private $appSecret;

    public function __construct($appId, $appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }
    // 签名
    public function getSignPackage() {
        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "rawString" => $string,
            "signature" => $signature

        );
        return $signPackage;
    }
// 随机字符串
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


    /**
     * 根据 access_token 获取 icket
     * @return type
     */
    public function getJsApiTicket(){
        $access_token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$access_token}&type=jsapi";
        $return = httpRequest($url,'GET');
        $return = json_decode($return,1);
        if(isset($return['ticket']))
            S('ticket',$return['ticket'],7000);
        return $return['ticket'];
    }


    /**
     * 获取 网页授权登录access token
     * @return type
     */
    public function getAccessToken(){
        //判断是否过了缓存期
        $access_token = S('access_token');
        if(!empty($access_token))
            return $access_token;

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
        $return = httpRequest($url,'GET');
        $return = json_decode($return,1);
        S('access_token',$return['access_token'],7000);
        return $return['access_token'];
    }

    // 获取一般的 access_token
    public function get_access_token($name='kan'){
        $redis = new \Redis();
        $redis->connect('127.0.0.1', '6379');
        $token = $redis->get("access_token_$name");
        if($token){
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
        $return = httpRequest($url,'GET');
        $return = json_decode($return,1);
        $redis->setex("access_token_$name", 7000, $return['access_token']);
        return $return['access_token'];
    }

    /*
     * 向用户推送消息
     */
    public function push_msg($openid,$content){
        $access_token = $this->get_access_token();
        $url ="https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}";
        $post_arr = array(
            'touser'=>$openid,
            'msgtype'=>'text',
            'text'=>array(
                'content'=>$content,
            )
        );
        $post_str = json_encode($post_arr,JSON_UNESCAPED_UNICODE);
        $return = httpRequest($url,'POST',$post_str);
        $return = json_decode($return,true);
    }

}
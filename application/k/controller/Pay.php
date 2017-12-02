<?php
namespace app\k\controller;
use think\Session;
require_once (PROJECT_PATH.'plugins/payment/weixin/weixin.class.php');
use app\k\service\ProdctService;

class Pay
{
    public function __construct()
    {
        Session::start();
        header("Cache-control: private");  // history.back返回后输入框值丢失问题 参考文章 http://www.tp-shop.cn/article_id_1465.html  http://blog.csdn.net/qinchaoguang123456/article/details/29852881
        $this->session_id = session_id(); // 当前的 session_id
    }
    public function Index() {
        if(!isset($_SESSION['openid'])) {
            exit('请在微信客户端进行支付');
        }

        $user = M('user')->where('id', $_SESSION['uid'])->find();
        //嘉善圈授权
        if(!$user['pay_openid']) {
            if (strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                $this->weixin_config = C('wx_pay');
                $openid = $this->GetOpenid();
                $set = array(
                    'pay_openid' => $openid
                );
                $_SESSION['user']['pay_openid'] = $openid;
                M('user')->where("id={$_SESSION['uid']}")->update($set);
            } else {
                return array('status'=>'fail', 'msg'=> '红包发放只能在微信客服端进行');
            }
        }

        $type = I('type', 0);

        $code = '\\weixin';
        $wexin = new $code();
        $user = M('user')->where('id', $_SESSION['uid'])->find();
        if($type == 1) {
            $order = array(
                'type'=> 1,
                'shop_id' => $_SESSION['uid'],
                'user_id' => $_SESSION['uid'],
                'name' => $user['name'],
                'mobile' => $user['contact'],
                'order_sn' => 'kan' . time() . $rand = rand(1000, 9999),
                'order_amount' => C('money'),
                'total_amount' => C('money'),
            );
            M('orders')->insert($order);
            $go_url = "/k/index/wechat?from=pay";
            $back_url = "/k/shop/product_list?from=pay";
        } else {
            //判断产品是否售完，过期，未付款
            $expire = isset($user['expire_time']) && $user['expire_time'] > time()? 0: 1;
            $id = I('product_id', '');
            $product = M('product')->where('id', $id)->find();
            $prodct_service = new ProdctService();
            $msg = $prodct_service->check(null, $product, $expire);
            if($msg)
                exit($msg);

            $go_url = "/k/?id=".I('product_id')."&hisuid={$this->uid}&from=pay";
            $back_url = "/k/?id=".I('product_id')."&hisuid={$this->uid}&from=pay";
            $query = array(
                'product_id' => $id,
                'user_id' => $_SESSION['uid'],
            );
            $order = M('orders')->where($query)->find();
            if(!$order || $order['type'] != 101)
                exit('订单不存在');
        }

        $notify_url = "/k/pay/callback";
        $res = $wexin->getJSAPI($order, $go_url, $back_url, $notify_url);
        exit($res);
    }


    public function Callback() {
        $code = '\\weixin';
        $wexin = new $code();
        $wexin->response();
    }
    // 网页授权登录获取 OpendId
    public function GetOpenid()
    {
        //通过code获得openid
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            //$baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            $baseUrl = urlencode($this->get_url());
            $url = $this->__CreateOauthUrlForCode($baseUrl); // 获取 code地址
            Header("Location: $url"); // 跳转到微信授权页面 需要用户确认登录的页面
            exit();
        } else {
            //上面获取到code后这里跳转回来
            $code = $_GET['code'];
            $data = $this->GetOpenidFromMp($code);//获取网页授权access_token和用户openid
            if(isset($data['errcode']))
                exit($data['errcode'] . ':' . $data['errmsg']);
            return $data['openid'];

        }
    }

    /**
     * 获取当前的url 地址
     * @return type
     */
    private function get_url()
    {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
        $url = $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
        return 'http://oauth.tikyy.com/wx/redirect?redirect=' . $url;
    }

    /**
     *
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     *
     * @return openid
     */
    public function GetOpenidFromMp($code)
    {
        //通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。
        //1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；
        //2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。
        $url = $this->__CreateOauthUrlForOpenid($code);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);//运行curl，结果以jason形式返回
        $data = json_decode($res, true);
        curl_close($ch);
        return $data;
    }

    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {

        $urlObj["appid"] = $this->weixin_config['appid'];

        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = "STATE" ;
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }

    /**
     *
     * 构造获取open和access_toke的url地址
     * @param string $code ，微信跳转带回的code
     *
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->weixin_config['appid'];
        $urlObj["secret"] = $this->weixin_config['appsecret'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?";

        $bizString = $this->ToUrlParams($urlObj);
        return $url . $bizString;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

}

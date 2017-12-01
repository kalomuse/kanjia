<?php
namespace app\k\controller;
require_once (PROJECT_PATH.'plugins/payment/weixin/weixin.class.php');
use app\k\service\ProdctService;
class Pay extends Base
{
    public function __construct()
    {
        parent::__construct();
        $user = M('user')->where('id', $this->uid)->find();
        $this->expire = $user['expire_time'] && $user['expire_time'] > time()? 0: 1;
        $this->assign('expire', $this->expire);
    }
    public function Index() {
        if(!isset($_SESSION['openid'])) {
            exit('请在微信客户端进行支付');
        }

        $type = I('type', 0);

        $code = '\\weixin';
        $wexin = new $code();
        $user = M('user')->where('id', $this->uid)->find();
        if($type == 1) {
            $order = array(
                'type'=> 1,
                'shop_id' => $this->uid,
                'user_id' => $this->uid,
                'name' => $user['name'],
                'mobile' => $user['contact'],
                'order_sn' => 'kan' . time() . $rand = rand(1000, 9999),
                'order_amount' => C('money'),
                'total_amount' => C('money'),
            );
            M('orders')->insert($order);
            $go_url = "/k/shop/product_list?from=pay";
            $back_url = "/k/shop/product_list?from=pay";
        } else {
            //判断产品是否售完，过期，未付款
            $expire = isset($user['expire_time']) && $user['expire_time'] > time()? 0: 1;
            $id = I('product_id', '');
            $product = M('product')->where('id', $id);
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
}

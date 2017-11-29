<?php
namespace app\k\controller;
require_once (PROJECT_PATH.'plugins/payment/weixin/weixin.class.php');
class Pay extends Base
{
    public function Index() {
        if(!isset($_SESSION['openid'])) {
            exit('请在微信客户端进行支付');
        }
        $type = I('type', 0);

        $code = '\\weixin';
        $wexin = new $code();
        if($type == 1) {
            $user = M('user')->where('id', $this->uid)->find();
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
            $go_url = "/k/?id=".I('product_id')."&hisuid={$this->uid}&from=pay";
            $back_url = "/k/?id=".I('product_id')."&hisuid={$this->uid}&from=pay";
            $query = array(
                'product_id' => I('product_id', ''),
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

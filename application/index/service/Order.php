<?php
namespace app\index\service;
use think\Db;
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 17/7/8
 * Time: 下午3:39
 */
class Order
{
    function add_order($order) {
        $set = array(
            'pay_time'=> time(),
            'pay_status' => 1,
            'total_amount' => $order['total_fee'] / 100,
            'transaction_id' => $order['transaction_id'],
        );
        $query = array(
            'order_sn' => $order['out_trade_no'],
        );
        M('orders')->where($query)->update($set);
        $order =  M('orders')->where($query)->find();
        if($order['type'] == 1) {
            //所有商品更新为已支付
            $query = array(
                'id'=> $order['shop_id'],
            );
            $user = M('user')->where($query)->find();
            $set = array(
                'expire_time' => 0
            );
            if($user['expire_time'] < time())
                $set['expire_time'] = time() + 14 * 24 * 3600;
            else
                $set['expire_time'] += 14 * 24 * 3600;

            M('user')->where($query)->update($set);
        }

    }
}
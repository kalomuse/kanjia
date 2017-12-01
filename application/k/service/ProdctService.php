<?php


namespace app\k\service;
use think\Model;
use think\Page;
/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class ProdctService extends Model
{
    /*
     * 判断商品是否支付，过期，售完
     */
    public function check($left, $product, $expire){
        if($left == null) {
            $count = $this->sale_count($product['id']);
            $left = $product['number'] - $count;
        }
        $msg = "";
        if($left == 0) {
            $msg = "商品全部卖完啦，看看其他商品吧";
        } else if(intval(strtotime($product['end_time'])) < intval(time())) {
            $msg = "活动已结束";
        } else if($expire) {
            $msg = "活动尚未开始";
        }
        return $msg;
    }

    /*
    * 当前已售出的商品数量
    */
    public function sale_count($id) {
        $query = array(
            'type' => 101,
            'product_id' => $id,
            'pay_status' => 1
        );
        $count = M('orders')->where($query)->count();
        return $count;
    }
    /*
     * 判断当前用户是否已加入砍价
     */
    public function has_join($id, $uid)
    {
        $has_join = 0;
        $query = array(
            'product_id' => $id,
            'user_id' => $uid
        );
        $order = M('orders')->where($query)->find();
        if ($order)
            $has_join = 1;
        return $has_join;
    }

    /*
    * 判断当前用户是否已加入砍价
    */
    public function has_kan($id, $uid, $hisuid) {
        $is_kan = 0;
        if ($hisuid) {
            $query = array(
                'hisuid' => $hisuid,
                'myuid' => $uid,
                'product_id'=> $id
            );
            $res = M('kan')->where($query)->find();
            if ($res)
                $is_kan = 1;

            return $is_kan;
        };
    }


    /*
    * 帮他砍价的人数统计
    */
    public function kan_count($id, $hisuid) {
        $kan_count = 0;
        if ($hisuid) {
            $query = array(
                'hisuid' => $hisuid,
                'product_id' => $id
            );
            $kan_count = M('kan')->where($query)->count();
        }
        return $kan_count;
    }


}
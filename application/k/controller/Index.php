<?php
namespace app\k\controller;
class Index extends Base
{
    public function Index()
    {
        $id = I('id', 0);
        $is_kan = 0;
        $has_join = 0;
        $kan_count = 0;
        if($id) {
            $hisuid = I('hisuid', 0);
            $product = M('product')->where('id', $id)->find();
            if($product) {
                $rule= explode("\n", $product['rule']);
                $product['rule'] = "<p>".implode("</p><p>", $rule). "</p>";
                $product['pic'] = explode(',', $product['pic']);

                $shop = M('user')->where('id', $product['uid'])->find();
                $his = M('user')->where('id', $hisuid)->find();

                //倒计时
                $endtime = strtotime($product['end_time']);
                //到底价用户统计
                $query = array(
                    'low_price' => ['exp', '=order_amount'],
                    'type' => 101,
                    'deleted' => 0,
                );
                $low_order = M('orders')->where($query)->select();

                //判断当前用户是否已加入砍价
                $query = array(
                    'product_id' => $id,
                    'user_id' => $this->uid
                );
                $order = M('orders')->where($query)->find();
                if ($order)
                    $has_join = 1;

                //判断当前用户是否已砍价
                if ($hisuid) {
                    $query = array(
                        'hisuid' => $hisuid,
                        'myuid' => $this->uid,
                    );
                    $res = M('kan')->where($query)->find();
                    if ($res)
                        $is_kan = 1;

                    //帮他砍价的人数统计
                    $query = array(
                        'hisuid' => $hisuid,
                        'product_id' => $id
                    );
                    $kan_count = M('kan')->where($query)->count();
                };

                //总砍价人数统计
                $query = array(
                    'product_id' => $id
                );
                $kan_total_count = M('kan')->where($query)->count();

                //总报名人数统计
                $query = array(
                    'type' => 101,
                    'product_id' => $id,
                );
                $join_count = M('orders')->where($query)->count();


                //当前已售出的商品数量
                $query = array(
                    'type' => 101,
                    'product_id' => $id,
                    'pay_status' => 1
                );
                $count = M('orders')->where($query)->count();
                $left = $product['number'] - $count;

                //当前订单
                $query = array(
                    'product_id' => $id,
                    'user_id' => $hisuid
                );
                $order = M('orders')->where($query)->find();

                $this->assign('endtime', $endtime);
                $this->assign('low_order', $low_order);
                $this->assign('join_count', $join_count);
                $this->assign('kan_total_count', $kan_total_count);
                $this->assign('kan_count', $kan_count);
                $this->assign('left', $left);
                $this->assign('his_name', $his['nickname']);
                $this->assign('is_pay', isset($order)? $order['pay_status']: 0);
                $this->assign('price', isset($order['order_amount'])?$order['order_amount']: $product['old_price']); //现价
                $this->assign('has_join', $has_join);
                $this->assign('is_kan', $is_kan);
                $this->assign('shop', $shop);
                $this->assign('product', $product);

                //微信分享赋值
                $website = $this->get_website();
                $this->signPackage['img'] = $website . $product['first_pic'];
                $this->signPackage['link'] = $website . "/k?id=$id";
                $fromuid = 0;
                if(isset($_GET['hisuid']))
                    $fromuid = $_GET['hisuid'];
                if($has_join)
                    $fromuid = $this->uid;
                if($fromuid)
                    $this->signPackage['link'] .= "&hisuid={$fromuid}";

                
                $this->signPackage['desc'] = $product['desc'];
                $this->signPackage['title'] = $product['title'];
                $this->assign('signPackage', $this->signPackage);
                return $this->fetch();
            }
        }
        return $this->ajaxReturn(array(
            'msg'=>'商品不存在',
        ));

    }

    public function lists()
    {
        $shop_id = I('shop_id', 0);
        if(!$shop_id)
            exit('该店铺不存在');

        $query = array(
            'uid'=> intval(I('shop_id')),
            'is_pay'=> 1,
            'deleted' => 0
        );
        $products = M('product')->where($query)->select();
        $this->assign('products', $products);

        $user = M('user')->where('id', $shop_id)->find();
        //微信分享赋值
        $website = $this->get_website();
        $this->signPackage['img'] = $products? $products[0]['first_pic'] : ($website . $user['file']);
        $this->signPackage['link'] = $website . "/k/index/lists?shop_id=$shop_id";
        $this->signPackage['desc'] = "【{$user['name']}】抢购火热进行中";
        $this->signPackage['title'] =  "【{$user['name']}】抢购火热进行中";
        $this->assign('signPackage', $this->signPackage);
        return $this->fetch();
    }

    public function join() {
        $t = time();
        $rand = rand(1000, 9999);
        $product = M('product')->where('id', I('product_id', 0))->find();
        if($product) {
            $set = array(
                'type'=> 101,
                'product_id' => I('product_id', 0),
                'shop_id' => I('shop_id', 0),
                'user_id' => $this->uid,
                'name' => I('name', ''),
                'mobile' => I('mobile', ''),
                'order_sn' => 'kan' . strval($t) . strval($rand),
                'goods_price' => $product['old_price'],
                'low_price' => $product['low_price'],
                'order_amount' => $product['old_price'],
                'total_amount' => $product['old_price'],
            );
            M('orders')->insert($set);
        }
        return $this->ajaxReturn(array(
            'status'=>'ok',
        ));
    }
    public function kan() {
        $product = M('product')->where('id', $_POST['product_id'])->find();
        //产品总数
        $count = $product['count'];
        //已卖出的数量
        $query = array(
            'type' => 101,
            'pay_status' => 1,
            'product_id' => $_POST['product_id'],
        );
        $left = $count - M('orders')->where($query)->count();


        $query = array(
            'user_id'=> $_POST['hisuid'],
            'product_id'=> $_POST['product_id']
        ) ;
        $order = M('orders')->where($query)->find();
        if(!$order)
            return $this->ajaxReturn('该用户未参与砍价活动');

        if($left > 1) {
            $rand = rand(2, 18) / 10;
            $kan_price = ($order['order_amount'] - $order['low_price']) / $left * $rand;  //每次抢购的价格=剩余可砍的价格/剩余人数
            $kan_price = round($kan_price,2);
            if ($kan_price < 0.01) {
                $kan_price = 0.01;
            }
        } else if($left == 1){
            $kan_price = $order['order_amount'] - $order['low_price'];
        } else if($left < 1) {
            return $this->ajaxReturn('已到底价');
        }

        $set = array(
            'order_amount' => $order['order_amount'] - $kan_price,
            'modified_time' => date("Y-m-d H:i:s")
        );
        M('orders')->where('user_id', $_POST['hisuid'])->update($set);
        M('kan')->insert($_POST);
        return $this->ajaxReturn(array(
            'left'=> $order['order_amount'] - $kan_price,
            'kan_price'=> $kan_price,
            'status'=>'ok',
        ));
    }
}

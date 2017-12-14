<?php
namespace app\k\controller;
use app\k\service\ProdctService;

class Index extends Base
{
    public function Index()
    {
        $id = I('id', 0);
        if($id) {
            $hisuid = I('hisuid', 0);
            $product = M('product')->where('id', $id)->find();
            if($product) {
                $prodct_service = new ProdctService();

                $desc = explode("\n", $product['desc']);
                $product['desc'] = "<p>".implode("</p><p>", $desc). "</p>";
                foreach($desc as &$d)
                    $d = str_replace(" ", "", $d);
                $share_desc = implode(" ", $desc);

                $rule= explode("\n", $product['rule']);
                $product['rule'] = "<p>".implode("</p><p>", $rule). "</p>";
                $product['pic'] = explode(',', $product['pic']);

                $shop = M('user')->where('id', $product['uid'])->find();
                //判断当前用户是否已加入砍价
                $has_join = $prodct_service->has_join($id, $this->uid);
                if(!I('hisuid', 0) && $has_join) { //如果是商家展示页面，但是参加了这个活动，则默认显示我的抢购页面
                    $_GET['hisuid'] = $this->uid;
                    $hisuid = $this->uid;
                }

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


                //判断当前用户是否已砍价
                $is_kan = $prodct_service->has_kan($id, $this->uid, $hisuid);

                //帮他砍价的人数统计
                $kan_count = $prodct_service->kan_count($id, $hisuid);

                //当前已售出的商品数量
                $count = $prodct_service->sale_count($id);
                $left = $product['number'] - $count;

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


                //当前订单
                $query = array(
                    'product_id' => $id,
                    'user_id' => $hisuid
                );
                $order = M('orders')->where($query)->find();

                $user = M('user')->where('id', $product['uid'])->find();
                $expire = $user['expire_time'] && $user['expire_time'] > time()? 0: 1;
                $this->assign('expire', $expire);
                $msg = $prodct_service->check($left, $product, $expire);
                //商家图片
                $shop['pic'] = explode(',', $shop['pic']);


                $this->assign('msg', $msg);
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

                
                $this->signPackage['desc'] = $share_desc;
                $this->signPackage['title'] = $product['title'];
                $this->assign('signPackage', $this->signPackage);
                return $this->fetch();
            }
        }
        return $this->ajaxReturn(array(
            'msg'=>'商品不存在',
        ));

    }

    public function all_activity() {
        $query = array(
            'deleted' => 0,
            'end_time' => array('gt', date('Y-m-d'))
        );
        $products = M('product')->where($query)->select();

        $this->assign('products', $products);
        $this->assign('noshare', 1);
        $this->assign('page_type', 'all_activity');
        return $this->fetch('lists');

    }

    public function my_activity() {
        $order = M('orders')->where('user_id', $this->uid)->select();
        $pids = [];
        foreach($order as $o) {
            if($o['product_id'])
                $pids[] = $o['product_id'];
        }
        $query = array(
            'deleted' => 0,
            'end_time' => array('gt', date('Y-m-d')),
            'id' => array('in', $pids),
        );
        $products = M('product')->where($query)->select();
        $this->assign('products', $products);
        $this->assign('noshare', 1);
        $this->assign('uid', $_SESSION['uid']);
        $this->assign('page_type', 'my_activity');
        return $this->fetch('lists');
    }

    public function lists()
    {
        $shop_id = I('shop_id', 0);
        $products = [];
        if(!$shop_id)
            exit('店铺不存在');

        $user = M('user')->where('id',  $shop_id)->find();
        $expire = $user['expire_time'] && $user['expire_time'] > time()? 0: 1;
        $this->assign('expire', $expire);

        if($expire && !isset($_SESSION['admin'])) { //如果商户没付钱或有效期已过
            $this->assign('products', $products);
        } else {
            $query = array(
                'uid' => $shop_id,
                'deleted' => 0
            );
            $products = M('product')->where($query)->select();
            $this->assign('products', $products);
        }

        $this->assign('page_type', 'lists');
        $this->assign('noshare', 0);
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

    public function tousu() {
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

        $query = array(
            'user_id'=> $_POST['hisuid'],
            'product_id'=> $_POST['product_id']
        ) ;
        $order = M('orders')->where($query)->find();
        if(!$order)
            return $this->ajaxReturn('该用户未参与砍价活动');
        $query = array(
            'hisuid'=> $_POST['hisuid'],
            'product_id'=> $_POST['product_id']
        ) ;
        $count = M('kan')->where($query)->count();
        $left = $product['count'] - $count;

        if($left > 1 && $order['order_amount']  > $order['low_price']) {
            $rand = rand(2, 18) / 10;
            $kan_price = ($order['order_amount'] - $order['low_price']) / $left * $rand;  //每次抢购的价格=剩余可砍的价格/剩余人数
            $kan_price = round($kan_price,2);
            if ($kan_price < 0.01) {
                $kan_price = 0.01;
            }
        } else if($left == 1 && $order['order_amount']  > $order['low_price']){
            $kan_price = $order['order_amount'] - $order['low_price'];
        } else if($left < 1 || $order['order_amount']  == $order['low_price']) {
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
    public function wechat() {
        return $this->fetch();
    }
}

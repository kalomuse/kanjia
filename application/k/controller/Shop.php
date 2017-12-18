<?php
namespace app\k\controller;
use app\k\validate\UserValidate;
use app\k\validate\ProductValidate;
class Shop extends Base
{
    public function __construct()
    {
        parent::__construct();
        $user = M('user')->where('id', $this->uid)->find();
        $this->expire = $user['expire_time'] && $user['expire_time'] > time()? 0: 1;
        $this->is_pay = $user['is_pay'];
        $this->uesr = $user;
        $this->assign('is_pay', $this->is_pay);
        $this->assign('expire', $this->expire);
    }
    public function pack() {
        $query = array(
            'shop_id' => $this->uid,
            'type' => 101,
            'pay_status'=> 1
        );
        $money = M('orders')->where($query)->sum('order_amount');
        $withdrawals = M('withdrawals')->where('user_id', $this->uid)->select();
        $this->assign('withdrawals', $withdrawals);
        $this->assign('time', time());
        $this->assign('money', $money?$money:0);

        return $this->fetch();
    }

    public function withdrawals() {
        $query = array(
            'shop_id' => $this->uid,
            'type' => 101,
            'pay_status'=> 1,
            'created_time'=> array('lt', date('Y-m-d', I('time')))
        );
        $money = M('orders')->where($query)->sum('order_amount');
        if(IS_GET) {
            $this->assign('time', time());
            $this->assign('money', $money?$money:0);
            return $this->fetch();
        }
        $query = array(
            'user_id' => $this->uid,
            'is_pay' => 0
        );
        if(M('withdrawals')->where($query)->find()) {
            return $this->ajaxReturn(array(
                'status' =>  'err',
                'msg' => '您的提现申请已经提交过啦，请耐心等待'
            ));
        }
        $orders = M('orders')->where($query)->select();
        $id_list = array();
        foreach ($orders as $o) {
            $id_list[] = $o['id'];
        }
        $set = array(
            'user_id'=> $this->uid,
            'user_name'=> $_SESSION['user']['name'],
            'order_list'=> count($id_list)?implode(',',$id_list): '',
            'money'=> $money,
            'order_time'=> I('time'),
        );
        M('withdrawals')->insert($set);
        return $this->ajaxReturn(array(
            'status' =>  'ok',
        ));
    }

    public function Join()
    {
        if(IS_GET) {
            $user = M('user')->where('openid', $this->session)->find();
            $pic = explode(',', trim($user['pic'], ','));
            $this->assign('pic', $pic);
            $this->assign('pic_str', trim($user['pic'], ','));
            $this->assign('name', $user['name']?$user['name']: '');
            $this->assign('address', $user['address']?$user['address']: '');
            $this->assign('contact', $user['contact']?$user['contact']: '');
            //$this->assign('is_reg', $user['name']?0: 1);
            return $this->fetch();
        }
        $validate = new UserValidate();
        if (!$validate->check($_POST)) {
            $err = $validate->getError();
            return $this->ajaxReturn($err);
        }

        $address = I('address');
        $user = M('user')->where('id', $this->uid)->find();
        $query = array(
            'address' => array('like',"%{$address}%"),
        );
        $has = M('user')->where($query)->find();
        if($user['first_use'] && !$has) {
            $set = array(
                'is_pay' => 1,
                'expire_time' => 0,
                'first_use' => 0,
                'name' => I('name'),
                'contact' => I('contact'),
                'address' => $address,
                'pic' => I('pic'),
                'role' => 'shop',
            );
        } else {
            $set = array(
                'name' => I('name'),
                'contact' => I('contact'),
                'address' => $address,
                'pic' => I('pic'),
                'role' => 'shop',
            );
        }
        M('user')->where('id', $this->uid)->update($set);
        return $this->ajaxReturn(array(
            'status' =>  'ok'
        ));
    }

    public function complete() {
        if($this->is_pay && ($this->uesr['review'] == 0 || $this->uesr['review'] == 3)) {
            $set = array(
                'review' => 1,
            );
            M('user')->where('id', $this->uid)->update($set);
            return $this->ajaxReturn(array(
                'status' =>  'ok',
                'msg' => '本次活动已提交审核，请耐心等待，审核通过后即可报名参与'
            ));
        }
        return $this->ajaxReturn(array(
            'status' =>  'ok',
        ));

    }

    public function center()
    {
        $user = M('user')->where('openid', $this->session)->find();
        $head = explode('||', $user['file'])[0];
        $this->assign('role', $user['role']);
        $this->assign('head', $head);
        $this->assign('review', $user['review']);
        $this->assign('reason', $user['reason']);
        return $this->fetch();
    }
    public function product_new() {
        if(IS_GET)
            return $this->fetch();

        $validate = new ProductValidate();
        if (!$validate->check($_POST)) {
            $err = $validate->getError();
            return $this->ajaxReturn($err);
        }
        $_POST['uid'] = $this->uid;
        if($this->uid) {
            $id = M('product')->insert($_POST, 0, 1);
            return $this->ajaxReturn(array(
                'status' => 'ok',
                'product_id' => $id
            ));
        }
    }

    //删除商品图片
    public function delimg() {
        $img = I('src');
        $table = I('table');
        if($table == 'product') {
            $id = I('pid');
            $user = M($table)->where('id', $id)->find();
            $pics = explode(',', $user['pic']);
            foreach ($pics as $k => $p) {
                if ($p == $img) {
                    unset($pics[$k]);
                    break;
                }
            }
            $set = array(
                'pic' => implode(',', $pics),
            );
            M($table)->where('id', $id)->update($set);
        } else if($table == 'user') {
            $user = M($table)->where('id', $this->uid)->find();
            $pics = explode(',', $user['pic']);
            foreach ($pics as $k => $p) {
                if ($p == $img) {
                    unset($pics[$k]);
                    break;
                }
            }
            $set = array(
                'pic' => implode(',', $pics),
            );
            M($table)->where('id', $this->uid)->update($set);
        }
        return $this->ajaxReturn(array(
            'status' => 'ok',
        ));


    }

    public function product_delete() {
        $set = array(
            'deleted' => 1,
        );
        M('product')->where('id', I('id'))->update($set);
        return $this->ajaxReturn(array(
            'status' =>  'ok',
        ));

    }

    public function product_edit()
    {
        if(IS_GET) {
            $product = M('product')->where('id', I('id'))->find();
            $product['pic_str'] = $product['pic'];
            $product['pic'] = explode(',', $product['pic']);
            $this->assign('product', $product);
            return $this->fetch();
        }
        $validate = new ProductValidate();
        if (!$validate->check($_POST)) {
            $err = $validate->getError();
            return $this->ajaxReturn($err);
        }
        $id = I('id');
        $_POST['uid'] = $this->uid;
        M('product')->where('id', $id)->update($_POST);

        if($this->is_pay &&  $this->uesr['review'] == 3) {
            $set = array(
                'review' => 1,
            );
            M('user')->where('id', $this->uid)->update($set);
        }
        return $this->ajaxReturn(array(
            'status' =>  'ok'
        ));
    }
    public function product_list()
    {
        $query = array(
            'uid' => $this->uid,
            'deleted' => 0,
        );
        $first_product = M('product')->where('uid', $this->uid)->find();
        $this->assign('bg_music', $first_product['bg_music']);
        $this->assign('first_img', $first_product['first_pic']);
        $products = M('product')->where($query)->select();
        $this->assign('product', $products);

        //微信分享赋值
        $user = M('user')->where('id', $this->uid)->find();
        $website = $this->get_website();
        $this->signPackage['img'] = $products? $products[0]['first_pic'] : ($website . $user['file']);
        $this->signPackage['link'] = $website . "/k/index/lists?shop_id=$this->uid";
        $this->signPackage['desc'] = "【{$user['name']}】抢购火热进行中";
        $this->signPackage['title'] =  "【{$user['name']}】抢购火热进行中";
        $this->assign('signPackage', $this->signPackage);
        return $this->fetch();
    }
    public function order_list()
    {
        if(IS_GET) {
            $query = array(
                'shop_id' => $this->uid,
                'type' => 101,
                'pay_status'=> 1
            );
            $orders = M('orders')->where($query)->select();
            $this->assign('orders', $orders);
            return $this->fetch();
        }

    }
    public function tip() {
        return $this->fetch();
    }
}
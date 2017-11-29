<?php
namespace app\k\controller;
use app\k\validate\UserValidate;
use app\k\validate\ProductValidate;
class Shop extends Base
{
    public function Join()
    {
        if(IS_GET) {
            $user = M('user')->where('openid', $this->session)->find();
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
        $set = array(
            'name' => I('name'),
            'contact' => I('contact'),
            'address' => I('address'),
        );
        M('user')->where('openid', $this->session)->update($set);
        return $this->ajaxReturn(array(
            'status' =>  'ok'
        ));
    }

    public function center()
    {
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
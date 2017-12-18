<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 17/6/12
 * Time: 上午9:18
 */
return array(
    array('str'=>'店铺产品审核', 'name'=>'shop', 'icon'=>'fa-desktop', 'child'=>array(
        array('str'=>'用户列表', 'action'=>'review'),
    )),
    array('str'=>'账单管理', 'name'=>'bill', 'icon'=>'fa-desktop', 'child'=>array(
        array('str'=>'查询', 'action'=>'list'),
        array('str'=>'提现申请', 'action'=>'withdrawals'),
    )),
    array('str'=>'权限', 'name'=>'role', 'icon'=>'fa-desktop', 'child'=>array(
        array('str'=>'管理员列表', 'action'=>'list'),
        array('str'=>'角色分配', 'action'=>'to'),
    )),
);


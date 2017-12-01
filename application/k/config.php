<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 17/7/23
 * Time: 下午2:08
 */
 return [
    'money' => 0.01,
    //'appid' => 'wxe5d91c42cf4d0458', //登录授权
    //'appsecret' => '91122e221dc366ec6177ce51c6e055a',
    'appid' => 'wx91cf8fac065c66d1',
    'appsecret' => 'a391deb4fdfaaddef3d7a87e5e84bdb8',
    'wx_pay' => array(//支付授权
        'appid' => 'wx91cf8fac065c66d1',
        'appsecret' => 'a391deb4fdfaaddef3d7a87e5e84bdb8',
    ),

    'template' => [
       'view_path'    => '',
       'layout_on'     =>  false,
       'layout_name'   =>  'layout',
      ],
       'view_replace_str'  =>  [
            '__STATIC__' => '/application/k/view/static',
            //'__IMG__' => '/public/img/local/'
      ],


 ];
<?php
namespace app\k\validate;

use think\Validate;



class UserValidate extends Validate
{
    protected $rule = [
        'name' => 'require',
        'contact' => 'require',
        'address' => 'require',
    ];

}
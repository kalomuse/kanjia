<?php
namespace app\k\validate;

use think\Validate;



class ProductValidate extends Validate
{
    protected $rule = [
        'title' => 'require',
        'rule' => 'require',
        'count' => 'require',
        'name' => 'require',
        'number' => 'require',
        'old_price' => 'require',
        'low_price' => 'require',
        'start_time' => 'require',
        'end_time' => 'require',
        'desc' => 'require',
        'first_pic' => 'require',
        'pic' => 'require',
    ];

}
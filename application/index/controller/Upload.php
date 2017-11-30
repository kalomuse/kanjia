<?php
namespace app\index\controller;
use think\Controller;
use think\Session;

class Upload extends Controller
{

    public function img()
    {
        Session::start();
        $file = request()->file('file');
        if($file) {
            $base_path = '/public/img/upload/';
            $info = $file->move(ROOT_PATH . $base_path);
            $file_name = $file->getInfo()['name'] ;
            $file_path = $base_path . $info->getSaveName();// . '||' . $file_name;
            if(isset($_GET['type']) && $_GET['type'] == 'return_first_pic') {
                Header('Content-type:application/json; charset=UTF-8');
                exit(json_encode(array(
                    'path' => $file_path,
                    'type' => 'first_pic'
                )));
            } else if(isset($_GET['type']) && $_GET['type'] == 'return_pic') {
                Header('Content-type:application/json; charset=UTF-8');
                exit(json_encode(array(
                    'path' => $file_path,
                    'type' => 'pic'
                )));
            }
        }
    }
}

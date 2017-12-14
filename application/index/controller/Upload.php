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
    public function file()
    {
        Session::start();
        $file = request()->file('file');
        $file_name = $file->getInfo()['name'];
        $name = explode('.', $file_name);
        $file_ext = $name[count($name)-1];
        if ($file && $file_ext == 'mp3') {
            $base_path = '/public/music/';
            $info = $file->move(ROOT_PATH . $base_path);
            $file_path = $base_path . $info->getSaveName();
            Header('Content-type:application/json; charset=UTF-8');
            exit(json_encode(array(
                'path' => $file_path,
                'type' => 'pic'
            )));
        } else {
            exit(json_encode(array(
                'msg' => "格式错误，请添加后缀为mp3格式，该格式为{$file_ext}"
            )));
        }
    }
}

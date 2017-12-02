<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 17/7/27
 * Time: 下午1:32
 */
namespace app\admin\controller;
use app\admin\service\TableService;

class Shop extends Base
{
    private $tableService;
    public function _initialize()
    {
        parent::_initialize();
        $this->table = I('type', 'user');
        $this->tableService = new TableService($this->table);
    }
    protected function get_website()
    {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $url = $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
        return $url;
    }

    public function whynot()
    {
        $id = I('id');
        $content = I('content');
        $query = array(
            'id' => $id,
        );
        $set = array(
            'reason' => $content
        );
        M('user')->where($query)->update($set);
        return $this->ajaxReturn(
            array(
                'status' => 'ok',
            )
        );
    }

    public function review() {
        $this->assign('website', $this->get_website());
        return $this->fetch();
    }

    public function order() {
        return $this->fetch();
    }

    public function ajax_fetch()
    {
        $query = array(
            'is_pay' => 1,
        );
        $data = $this->tableService->query($query);
        return $this->ajaxReturn($data);
    }

    public function ajax_cell()
    {
        $this->tableService->update_cell();
        return true;

    }

    public function ajax_edit()
    {
        $data = $this->tableService->edit();
        return $this->ajaxReturn($data);

    }

}
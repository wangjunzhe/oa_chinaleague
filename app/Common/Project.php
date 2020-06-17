<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/30 0030
 * Time: 15:02
 */

namespace App\Common;
use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Model\Group;
use App\Common\Admin as AdminCommon;
use App\Common\Customer as CustomerCommon;

class Project extends NotORM
{
    protected $di;
    protected $prefix;
    public function __construct()
    {
        $this->di = \PhalApi\DI()->notorm;
        $this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
    }


    /**
     *  项目方登录处理
     * @author Dai Ming
     * @DateTime 17:45 2020/3/30 0030
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * @return string msg 提示信息
     */
    public function ProLogin($uid)
    {
            //获取用户名判断是否是项目方
            //如果是项目方
            //如果不是项目方

    }

}
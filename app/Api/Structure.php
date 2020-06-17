<?php

namespace App\Api;

use App\Common\Category;
use app\crm\controller\Setting;
use PhalApi\Api;
use PhalApi\Exception\BadRequestException;
use App\Domain\Structure as Do_structure;
use App\Domain\Group;
use App\Domain\Rule;
use App\Domain\Tree;
use App\Common\Pinyin;
use App\Common\Customer;
use App\Api\User;
use App\Model\Structure as NewStr;
use \PhalApi\Crypt\MultiMcryptCrypt;
use App\Domain\Xiaoming as Domain_Xiaoming;
use App\Common\Yesapiphpsdk;


/**
 * 部门列表接口
 */
Class Structure extends Api
{

    public function getRules()
    {
        return array(
            'insert' => array(
                'uid' => array('name' => 'uid', 'require' => true, 'min' => 1, 'max' => 50, 'desc' => '用户id'),
                'pid' => array('name' => 'pid', 'require' => true, 'min' => 1, 'max' => 50, 'desc' => '父级id'),
                'name' => array('name' => 'name', 'require' => true, 'min' => 6, 'max' => 50, 'desc' => '部门名称'),
                'director' => array('name' => 'director', 'type' => 'array', 'require' => false, 'desc' => '部门主管的id', 'default' => ''),
                'share' => array('name' => 'share', 'require' => false,'on_after_parse' => 'trim|strtolower','desc' => '公海可控部门,只存选择的部门id用,隔开(需要多个全部的按钮,传参为all)'),
                'create_time' => array('name' => 'create_time',  'min' => 6, 'max' => 50, 'desc' => '创建时间','default' => time()),
                'nrepeat' => array('name' => 'nrepeat', 'require' => false, 'type' => 'int', 'default' => '1', 'desc' => '撞单验证字段1是手机号 2是微信号'),
                'is_sea' => array('name' => 'is_sea', 'require' => false, 'type' => 'int','default' => '0',  'desc' => '0: 有独立公海 1: 没有独立公海(不显示公海)'),
                'sea_type' => array('name' => 'sea_type', 'require' => false, 'type' => 'int',  'desc' => '0: 查看公海 1: 合并重复数据并查看公海'),
                'project_num' => array('name' => 'project_num', 'require' => false, 'type' => 'int', 'default' => '0', 'desc' => '所属公司ID(项目方id)'),
                'capacity' => array('name' => 'capacity', 'require' => true, 'type' => 'int',  'desc' => '部门类型1:中台 2:前台 3:渠道 4:后台 5:数据中心'),
            ),
            'SetShareCustomerData'=>array(
                'uid' => array('name' => 'uid', 'require' => true, 'min' => 1, 'max' => 50, 'desc' => '用户id'),
                'group' => array('name' => 'group', 'require' => true, 'min' => 6, 'max' => 50, 'desc' => '部门id字符串'),

            ),
            'edit' => array(
                'id' => array('name' => 'id', 'require' => true, 'desc' => '部门ID'),
                'uid' => array('name' => 'uid', 'require' => true, 'min' => 1, 'max' => 50, 'desc' => '用户id'),
                'pid' => array('name' => 'pid', 'require' => true,  'desc' => '父级id'),
                'name' => array('name' => 'name', 'require' => true, 'min' => 6, 'max' => 50, 'desc' => '部门名称'),
                'director' => array('name' => 'director', 'type' => 'array', 'require' => false, 'desc' => '部门主管的id', ),
                'share' => array('name' => 'share', 'require' => false, 'desc' => '公海可控部门,只存选择的部门id用,隔开(需要多个全部的按钮,传参为all)'),
                'nrepeat' => array('name' => 'nrepeat', 'require' => false, 'type' => 'int', 'default' => '1', 'desc' => '撞单验证字段1是手机号 2是微信号'),
                'is_sea' => array('name' => 'is_sea', 'require' => false, 'type' => 'int','default' => '0',  'desc' => '0: 有独立公海 1: 没有独立公海(不显示公海)'),
                'sea_type' => array('name' => 'sea_type', 'require' => false, 'type' => 'int',  'desc' => '0: 查看公海 1: 合并重复数据并查看公海'),
                'project_num' => array('name' => 'project_num', 'require' => false, 'type' => 'int', 'default' => '0', 'desc' => '所属公司ID(项目方id)'),
                'capacity' => array('name' => 'capacity', 'require' => false, 'type' => 'int',  'desc' => '部门类型1:中台 2:前台 3:渠道 4:后台 5:数据中心'),
            ),
            'delStruDir' => array(
                'id' => array('name' => 'id', 'require' => true, 'desc' => '部门ID'),
                'pid' => array('name' => 'pid', 'require' => true, 'desc' => '上级部门ID'),
                'uid' => array('name' => 'uid', 'require' => true, 'desc' => '操作人id'),
                'director' => array('name' => 'director', 'type' => 'array', 'require' => false, 'desc' => '需要删除的部门主管',),
            ),
            'del' => array(
                'id' => array('name' => 'id', 'require' => true, 'min' => 1, 'max' => 50, 'desc' => 'id'),
            ),
            'GetGroupList' => array(
                'id' => array('name' => 'id', 'require' => false, 'min' => 1, 'max' => 50, 'desc' => 'id'),
                'pageno' => array('name' => 'pageno', 'type' => 'int', 'min' => 1, 'require' => false, 'desc' => '当前页码（不必填）'),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int', 'min' => 1, 'require' => false, 'desc' => '条数限制（不必填）'),
            ),

            'AddGroup' => array(
                'title' => array('name' => 'name', 'require' => true, 'type' => 'string', 'desc' => '角色名称(字符串必填)'),
                'rules' => array('name' => 'rules', 'require' => true, 'type' => 'array', 'desc' => '权限列表 例如：1,2,3', 'default' => ''),
                'description' => array('name' => 'description', 'require' => false, 'type' => 'string', 'desc' => '说明', 'default' => ''),
            ),
            'EditeGroup' => array(
                'id' => array('name' => 'id', 'source' => 'post', 'require' => false, 'type' => 'string', 'desc' => '角色ID(字符串必填)'),
                'rules' => array('name' => 'rules', 'require' => false, 'type' => 'string', 'desc' => '权限列表 例如：1,2,3', 'default' => ''),
                'name' => array('name' => 'name', 'require' => false, 'type' => 'string', 'desc' => '角色名称(字符串必填)'),
                'description' => array('name' => 'description', 'require' => false, 'type' => 'string', 'desc' => '说明', 'default' => ''),
            ),
            'DeleGroup' => array(
                'id' => array('name' => 'id', 'require' => true, 'type' => 'string', 'desc' => '角色id'),
            ),
            // 4-20小哲加
            'StationList' => array(
                'uid' => array('name' => 'uid', 'type' => 'int', 'require' => false, 'desc' => '用户id', 'default' => '1'),
                'project_id'=>array('name'=>'project_id','require'=>false,'type' => 'int','desc'=>'项目方id(不必填)', 'default' => '1'),
            ),
            'AddStation'=>array(
                'project_id'=>array('name'=>'project_id','require'=>false,'type' => 'int','desc'=>'项目方id(字符串必填)'),
                'title'=>array('name'=>'title','require'=>true,'type' => 'string','min'=>'4','max'=>'20','desc'=>'岗位名称(字符串必填)'),
            ),
            'DelStation' => array(
                'id' => array('name' => 'id', 'require' => true, 'type' => 'string', 'desc' => '岗位id'),

            ),
            'EditStation' => array(
                'id' => array('name' => 'id', 'require' => true, 'type' => 'string', 'desc' => '岗位id'),
                'title' => array('name' => 'title', 'require' => true, 'type' => 'string', 'min' => '4', 'max' => '20', 'desc' => '岗位名称(字符串必填)'),
            ),
            'GetUserList' => array(
                'page' => array('name' => 'page', 'type' => 'int', 'min' => 1, 'default' => 1, 'desc' => '第几页'),
                'perpage' => array('name' => 'perpage', 'type' => 'int', 'min' => 1, 'max' => 20, 'default' => 10, 'desc' => '分页数量'),
                'where' => array('name' => 'where', 'require' => false, 'type' => 'string', 'default' => '', 'desc' => '查询条件 例如:zhangsan 可选')

            ),
            'AddUser' => array(
                'uid' => array('name' => 'uid', 'type' => 'int', 'require' => true, 'desc' => '用户id', 'default' => '1'),
                'id' => array('name' => 'id', 'type' => 'string', 'require' => false, 'min' => 1, 'max' => 20, 'desc' => '如果要编辑,则加上id字段即可,否则不加'),
                'username' => array('name' => 'username', 'type' => 'string', 'require' => true, 'min' => 1, 'max' => 20, 'desc' => '登录账号'),
                'img' => array('name' => 'img', 'type' => 'string', 'default' => '', 'desc' => '头像图片路径'),
                'realname' => array('name' => 'realname', 'type' => 'string', 'require' => true, 'default' => '', 'require' => true, 'desc' => '真是姓名'),
                'email' => array('name' => 'email', 'type' => 'string', 'require' => false, 'desc' => '邮箱'),
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'require' => false, 'desc' => '手机号'),
                'sex' => array('name' => 'sex', 'type' => 'string', 'default' => '男', 'desc' => '性别'),
                'structure_id' => array('name' => 'structure_id', 'type' => 'string', 'require' => true, 'desc' => '部门id数组'),
                'post' => array('name' => 'post', 'type' => 'string', 'require' => true, 'desc' => '岗位'),
                'rztime' => array('name' => 'rztime', 'type' => 'string', 'require' => true, 'desc' => '入职时间例:2019-08-12 15:56:31'),
                'zztime' => array('name' => 'zztime', 'type' => 'string', 'require' => true, 'desc' => '转正职时间例:2019-08-12 15:56:31'),
                'status' => array('name' => 'status', 'type' => 'int', 'require' => true, 'desc' => '0是解冻 1是冻结'),
                'worker' => array('name' => 'worker', 'type' => 'int', 'require' => true, 'desc' => '0是未转正 1是已转正'),
                'adress' => array('name' => 'adress', 'type' => 'string', 'require' => true, 'desc' => '办公地点'),
                'password' => array('name' => 'password', 'type' => 'string', 'require' => false, 'desc' => '密码'),
                'creatid' => array('name' => 'creatid', 'type' => 'int', 'require' => true, 'desc' => '创建人id'),
                'group_id' => array('name' => 'group_id', 'type' => 'int', 'require' => true, 'desc' => '角色id'),
                'type' => array('name' => 'type', 'type' => 'int', 'require' => false, 'desc' => '人员性质0系统人员1项目方2代理商', 'default' => '0'),
                'type_id' => array('name' => 'type_id', 'type' => 'int', 'require' => false, 'desc' => '所属项目方/代理商', 'default' => ''),
                'status' => array('name' => 'status', 'type' => 'int', 'require' => true, 'desc' => '人员状态', 'default' => '1'),
                'new_groupid' => array('name' => 'new_groupid', 'type' => 'int', 'require' => true, 'desc' => '部门最后一个id', 'default' => '1'),
                'url' => array('name' => 'url', 'type' => 'string', 'require' => false, 'desc' => '访问链接'),
            ),
            'delUser' => array(
                'id' => array('name' => 'id', 'require' => true, 'type' => 'string', 'desc' => '用户id'),

            ),
            'AddColumnList' => array(
                'pid' => array('name' => 'pid', 'require' => true, 'type' => 'string', 'default' => '0', 'desc' => '默认是0,为顶级分类.如不选择则为顶级分类'),
                'id' => array('name' => 'id', 'require' => false, 'type' => 'string', 'default' => '0', 'desc' => '类的id,如果*编辑*操作的话,*必传'),
                'title' => array('name' => 'title', 'require' => true, 'type' => 'string', 'desc' => '栏目名称'),
                'path' => array('name' => 'path', 'require' => true, 'type' => 'string', 'desc' => '定义'),
                'component' => array('name' => 'component', 'require' => true, 'type' => 'string', 'desc' => '注册路径'),
                'level' => array('name' => 'level', 'require' => true, 'type' => 'string', 'default' => '1', 'desc' => '级别。1模块,2控制器,3操作'),
                'roles' => array('name' => 'roles', 'require' => false, 'type' => 'string', 'desc' => '例如:[\'admin\',\'structure\',\'del\']'),

            ),
            'delColumn' => array(
                'id' => array('name' => 'id', 'require' => true, 'type' => 'string', 'default' => '0', 'desc' => '栏目ID'),
                'pid' => array('name' => 'pid', 'require' => true, 'type' => 'string', 'default' => '0', 'desc' => '栏目父级ID'),

            ),
            'Mycustomer' => array(
                'uid' => array('name' => 'uid', 'require' => true, 'type' => 'int','desc' => 'ID'),
                'groupid' => array('name' => 'groupid', 'require' => true, 'type' => 'int','desc' => 'ID'),
            ),
            'getperson' => array(
                'type' => array('name' => 'type', 'require' => true, 'type' => 'string', 'default' => '0', 'desc' => '0是部门 1是岗位'),
                'id' => array('name' => 'id', 'require' => true, 'type' => 'string', 'desc' => '0是部门id 1是岗位id'),
                'pid' => array('name' => 'pid', 'require' => false, 'type' => 'string', 'desc' => 'pid 只能是部门的父级id'),
            ),
            'CusExport' => array(
                'uid' => array('name' => 'uid', 'require' => true, 'type' => 'int', 'desc' => '操作人id'),
                'cid' => array('name' => 'cid', 'require' => false, 'type' => 'string', 'desc' => '导出id号,隔开'),
                'type' => array('name' => 'type', 'require' => false, 'type' => 'int', 'desc' => '1是私海2是公海客户默认1'),
                'quantity' => array('name' => 'quantity', 'require' => false, 'type' => 'int', 'desc' => '导出数量'),
            ),
            'GetCusExport' => array(
                'uid' => array('name' => 'uid', 'require' => true, 'type' => 'int', 'desc' => '操作人id'),
                'type' => array('name' => 'type', 'require' => false, 'type' => 'int', 'desc' => '1是私海2是公海客户默认1'),
                'pageno' => array('name' => 'pageno', 'type' => 'int', 'min' => 1, 'require' => false, 'desc' => '当前页码（不必填）'),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int', 'min' => 1, 'require' => false, 'desc' => '条数限制（不必填）'),
            ),
            'ChangeImg' => array(
                'type' => array('name' => 'uid', 'require' => true, 'type' => 'int', 'desc' => '类型'),
                'id' => array('name' => 'cid', 'require' => false, 'type' => 'int', 'desc' => 'id'),
                'uid' => array('name' => 'uid', 'require' => false, 'type' => 'int', 'desc' => '操作人id'),
                'img' => array('name' => 'img', 'type' => 'string', 'default' => '', 'desc' => '头像图片路径'),
            ),
            'ReqeatUserList' => array(
                'uid' => array('name' => 'uid', 'require' => false, 'type' => 'int', 'desc' => '操作人id'),
                'cid' => array('name' => 'cid', 'require' => false, 'type' => 'int', 'desc' => '客户id'),
                'pageno' => array('name' => 'pageno', 'type' => 'int', 'min' => 1, 'require' => false, 'desc' => '当前页码（不必填）'),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int', 'min' => 1, 'require' => false, 'desc' => '条数限制（不必填）'),
            ),
            'GetPhoneRepeat' => array(
                'uid' => array('name' => 'uid', 'require' => true, 'type' => 'string', 'desc' => '用户id'),
                'phone' => array('name' => 'phone', 'require' => false, 'type' => 'string', 'desc' => '手机号码'),
                'wx' => array('name' => 'wx', 'require' => false, 'type' => 'string', 'desc' => '微信'),
                'group' => array('name' => 'group', 'require' => false, 'type' => 'array', 'desc' => '例如:["81"]或["74","75"]'),

            ),
            'GetPhoneRepeatList' => array(
                'uid' => array('name' => 'uid', 'require' => true, 'type' => 'string', 'desc' => '用户id'),
                'group' => array('name' => 'group', 'require' => true, 'type' => 'string', 'desc' => '部门id'),
                'phone' => array('name' => 'phone', 'require' => true, 'type' => 'string', 'desc' => '手机号码'),
            ),
            'GetPinYin' => array(
                'content' => array('name' => 'content', 'require' => true, 'type' => 'string', 'desc' => '中文内容'),

            ),
            'GetLeibie' => array(
                'number' => array('name' => 'number', 'require' => false, 'type' => 'string', 'desc' => '代理商number'),
                'id' => array('name' => 'id', 'require' => false, 'type' => 'string', 'desc' => '代理商id'),

            ),
            'GetToken'=>array(
                'uid'=>array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
            ),
            'AddUserData'=>array(
                'uid' => array('name' => 'uid', 'type' => 'int', 'require' => true, 'desc' => '用户id'),
                'realname' => array('name' => 'realname', 'type' => 'string', 'require' => true, 'default' => '', 'require' => true, 'desc' => '真是姓名'),
                'username' => array('name' => 'username', 'type' => 'string', 'require' => true, 'min' => 1, 'max' => 20, 'desc' => '登录账号'),
                'img' => array('name' => 'img', 'type' => 'string', 'default' => '', 'desc' => '头像图片路径'),
                'email' => array('name' => 'email', 'type' => 'string', 'require' => false, 'desc' => '邮箱'),
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'require' => false, 'desc' => '手机号'),
                'sex' => array('name' => 'sex', 'type' => 'string', 'default' => '男', 'desc' => '性别'),
                'structure_id' => array('name' => 'structure_id', 'type' => 'array', 'require' => false, 'desc' => '部门id数组'),
                'post' => array('name' => 'post', 'type' => 'string', 'require' => false, 'desc' => '岗位'),
                'rztime' => array('name' => 'rztime', 'type' => 'string', 'require' => true, 'desc' => '入职时间例:2019-08-12 15:56:31'),
                'zztime' => array('name' => 'zztime', 'type' => 'string', 'require' => true, 'desc' => '转正职时间例:2019-08-12 15:56:31'),
                'status' => array('name' => 'status', 'type' => 'int', 'require' => true, 'desc' => '0是解冻 1是冻结'),
                'worker' => array('name' => 'worker', 'type' => 'int', 'require' => true, 'desc' => '0是未转正 1是已转正'),
                'adress' => array('name' => 'adress', 'type' => 'string', 'require' => true, 'desc' => '办公地点'),
                'password' => array('name' => 'password', 'type' => 'string', 'require' => false, 'desc' => '密码'),
                'group_id' => array('name' => 'group_id', 'type' => 'int', 'require' => true, 'desc' => '角色id'),
                'type' => array('name' => 'type', 'type' => 'int', 'require' => false, 'desc' => '人员性质0系统人员1项目方2代理商', 'default' => '0'),
                'type_id' => array('name' => 'type_id', 'type' => 'int', 'require' => false, 'desc' => '所属项目方/代理商', 'default' => ''),
                'status' => array('name' => 'status', 'type' => 'int', 'require' => true, 'desc' => '人员状态', 'default' => '1'),
                'token' => array('name' => 'token', 'type' => 'string', 'require' => true, 'desc' => '表单令牌'),
            ),




        );
    }

    /**
     * <b style="color:red;font-size:5px;">new</b>用户添加
     * @author Dai Ming
     * @DateTime 16:01 2020/4/20 0020
     * @desc <b style="color:red;font-size:5px;">new</b> 用户添加（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * @return string msg 提示信息
     */
    public function AddUserData()
    {
        $list = new Do_structure();
        $salt = substr(md5(rand(1000000, time())), 0, 4);
        $newdata = array(
            'username' => $this->username,
            'img' => $this->img,
            'realname' => $this->realname,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'sex' => $this->sex,
            'structure_id' => $this->structure_id,
            'post' => $this->post,
            'rztime' => $this->rztime,
            'zztime' => $this->zztime,
            'create_time' => time(),
            'type' => $this->type,
            'type_id' => $this->type_id,
            'status' => $this->status,
            'status' => $this->status,
            'worker' => $this->worker,
            'adress' => $this->adress,
            'creatid' => $this->uid,
            'token' => $this->token,
            'salt' => $salt,
            'group_id' => $this->group_id,
            'password' => \App\user_md5($this->password, $salt, $this->username),
        );
        $pw = $this->password;
        if ($pw == '' ) {
            $newdata['password'] = \App\user_md5('zklmzk', $salt, $this->username);
        }
        $data = $list->AddUserData($this->uid,$newdata);
        return $data;

    }

    /**
     *22-获取代理商类别
     * @author Dai Ming
     * @DateTime 09:29 2020/1/15 0015
     * @desc   获取代理商类别（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     **/
    public function GetLeibie()
    {


        $leibie = \PhalApi\DI()->notorm->agent->where('id', $this->id)->or('number', $this->number)->fetchOne('leibie');
//            $leibie=\PhalApi\DI()->notorm->agent->where('number',$this->number)->fetchOne('leibie');
        $rs = array('code' => 1, 'msg' => '查询成功', 'data' => $leibie, 'info' => array());
        return $rs;
    }

    /**
     *20-中文转拼音
     * @author Dai Ming
     * @DateTime 09:29 2020/1/15 0015
     * @desc   中文转拼音（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     **/
    public function GetPinYin()
    {
        $pinyin = new Pinyin();
        $data = $pinyin->get_all_py($this->content);
        $status = \PhalApi\DI()->notorm->user->where('username', $data)->fetchOne();
        $zui = array('0' => '_good', '1' => 'yes', '2' => 'no1', '3' => 'ok', '4' => 'first', '5' => 'go', '6' => '01', '7' => '02', '8' => '007', '9' => 'best');
        $rs = array('code' => 1, 'msg' => '转换成功!', 'data' => $data, 'info' => array());
        if ($status) {
            $data = $data . $zui[rand(0, 9)];
            $rs = array('code' => 1, 'msg' => '已存在该用户名,已为您重新挑选一个!', 'data' => $data, 'info' => array());
        }
        if ($data == '') {
            $rs = array('code' => 1, 'msg' => '当前存在生僻字,无法为您自动转换!', 'data' => $data, 'info' => array());
        }
        return $rs;

    }
    /**
     * 28-获取表单认证令牌
     * @author Dai Ming
     * @DateTime 16:57 2020/4/13 0013
     * @desc    获取表单认证令牌（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetToken()
    {


        $Domain_Xiaoming=new Domain_Xiaoming;
        return  $Domain_Xiaoming->GetAddUserStatus($this->uid);
    }
    /**
     * 19- 搜索查看手机号是否撞单
     * @author Dai Ming
     * @DateTime 09:29 2020/1/15 0015
     * @desc   搜索查看手机号是否撞单（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetPhoneRepeat()
    {
        $list = new Do_structure();
        $msg = $list->GetPhoneRepeat($this->uid, $this->phone, $this->wx, $this->group);
        return $msg;
    }

    /**
     * 13-查看导出日志详情
     * @author Dai Ming
     * @DateTime 14:31 2019/11/1 0001
     * @desc  查看导出日志详情（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetCusExport()
    {
        $structure = new Do_structure();
        return $structure->GetCusExport($this->uid, $this->type, $this->pageno, $this->pagesize);
    }

    /**
     * 19-删除部门负责人接口
     * @author Dai Ming
     * @DateTime 15:41 2019/12/10 0010
     * @desc   删除部门负责人接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function delStruDir()
    {
        $structure = new Do_structure();
        return $structure->delStruDir($this->id, $this->pid, $this->uid, $this->director);
    }

    /**
     * 16-撞单记录列表
     * @author Dai Ming
     * @DateTime 09:40 2019/11/1 0001
     * @desc  撞单记录列表（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * @return string msg 提示信息
     */
    public function ReqeatUserList()
    {
        $structure = new Do_structure();
        return $structure->ReqeatUserList($this->uid, $this->cid, $this->pageno, $this->pagesize);
    }

    /**
     * 05-修改头像
     * @author Dai Ming
     * @DateTime 09:40 2019/10/31 0031
     * @desc    修改头像（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function ChangeImg()
    {
        $structure = new Do_structure();
        return $structure->ChangeImg($this->type, $this->uid, $this->id, $this->img);
    }

    /**
     * 07-导出日志记录
     * @author Dai Ming
     * @DateTime 09:30 2019/10/31 0031
     * @desc 导出日志记录（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function CusExport()
    {
        $structure = new Do_structure();
        return $structure->CusExport($this->uid, $this->cid, $this->type, $this->quantity);
    }

    /**
     * 01-部门列表接口
     * @desc 部门列表接口 （已完成）
     * @return int id ID
     * @return int pid 上级ID
     * @return string name 部门名称
     * @return string director 主管
     * @return int create_time 当前时间戳
     * @exception 400 非法请求，参数传递错误
     */

    public function getList()
    {

        $structure = new Do_structure();
//        $data=\App\Getkey(1,['realname','is_leader']);

        return $structure->getDataList();
    }



    /**
     * 03-部门列表删除数据
     * @desc 部门列表删除数据 （已完成）
     * @exception code 等于1为成功，其他都是失败
     * @return int code 操作码，1表示成功，其他都表示失败
     */
    public function del()
    {
        $domain = new Do_structure();
        $data = $domain->deldata($this->id);
        return $data;
    }
    /**
     * <b style="color:red;font-size:5px;">new</b>部门列表插入数据
     * @desc <b style="color:red;font-size:5px;">new</b>新增部门子部门 （已完成）
     * @return int id 新增的ID
     * @exception 400 非法请求，参数传递错误
     */
    public function insert()
    {
        $rs = array();
        $newData = array(
            'pid' => $this->pid,
            'name' => $this->name,
            'director' => $this->director,
            'share' => $this->share,
            'create_time' => time(),
            'nrepeat' => $this->nrepeat,
            'is_sea' => $this->is_sea,
            'sea_type' => $this->sea_type,
            'project_num' => $this->project_num,
            'capacity' => $this->capacity,
        );

        $domain = new Do_structure();
        $data = $domain->insertdata($newData);
        return $data;

    }
    /**
     * 部门列表编辑数据
     * @desc 部门列表编辑数据  （已完成）
     * @exception 400 非法请求，参数传递错误
     * @return int code 操作码，1表示成功，其他都表示失败
     */
    public function edit()
    {
        $rs = array();
        $newData = array(
            'pid'=>$this->pid,
            'name'=>$this->name,
            'director'=>$this->director,
            'share'=>$this->share,
            'nrepeat'=>$this->nrepeat,
            'is_sea'=>$this->is_sea,
            'sea_type'=>$this->sea_type,
            'project_num'=>$this->project_num,
            'capacity'=>$this->capacity
        );
        $domain = new Do_structure();
        $data = $domain->edit($this->id,$this->uid, $newData);
        return $data;
    }

    /**
     * 05 - 总权限列表
     * @desc 所有权限的列表信息 （已完成）
     * @return int code 操作码，1表示成功，其他都表示失败
     * @exception 400 非法请求
     */
    public function GetRule()
    {
        $rule = new Rule();
        $list = $rule->getDataList();
        return $list;
    }


    /**
     * 06 - 角色权限列表
     * @Author Dai Ming
     * @Date  2019/8/1 0001 17:12
     * @desc   角色权限列表    （已完成）
     * @return array data 接口请求成功后返回的数据
     */
    public function GetGroupList()
    {
        $group = new Group();
        $list = $group->GetGroupList($this->pageno, $this->pagesize, $this->id);
        return $list;
    }

    /**
     * 07-添加角色
     * @desc 添加角色及角色权限  （已完成）
     *
     */
    public function AddGroup()
    {
        $group = new Group();
        $newDate = array(
            'title' => $this->title,
            'rules' => \App\arrayToString($this->rules),
            'description' => $this->description,
        );
        $list = $group->insertdb($newDate);
        return $list;
    }

    /**
     * 08-删除角色
     * @desc 删除角色及角色权限  （已完成）
     *
     */
    public function DeleGroup()
    {
        $group = new Group();
        $id = $this->id;
        $list = $group->deldb($id);
        return $list;

    }

    /**
     * 09-编辑角色
     * @desc 编辑角色及角色权限  （已完成）
     *
     */
    public function EditeGroup()
    {
        $group = new Group();
        $params = \PhalApi\DI()->request->getAll();
        $id = $params['id'];
        $newDate = array(
            'rules' => \App\arrayToString($params['rules']),
            'description' => $params['description'],
            'title' => $params['name'],
        );

        $list = $group->editdb($id, $newDate);
        return $list;

    }


    /**
     * <b style="color:red;font-size:5px;">new</b> 岗位添加
     * @desc 岗位添加  （已完成）
     */
    public function AddStation()
    {
        $list = new Do_structure();
        $data = array(
            'title' => $this->title,
            'project_num' => $this->project_id,
            'creat_time' => time()
        );
        $data = $list->instation($data);
        return $data;
    }



    /**
     * <b style="color:red;font-size:5px;">new</b> 岗位列表
     * @desc 岗位列表  （已完成）
     */
    public function StationList()
    {
        $list = new  Do_structure();
        $project_id=empty($this->project_id) || !isset($this->project_id)?1:$this->project_id;
        $this->uid=empty($this->uid) || !isset($this->uid)?1:$this->uid;
        $data = $list->getStationList($project_id,$this->uid);
        return $data;
    }

    /**
     * 12- 岗位删除
     * @desc 岗位删除  （已完成）
     */
    public function DelStation()
    {
        $list = new  Do_structure();
        $data = $list->DelStation($this->id);
        return $data;
    }

    /**
     * 13- 岗位编辑
     * @desc 岗位编辑  （已完成）
     */
    public function EditStation()
    {
        $list = new  Do_structure();
        $data = $list->editStation($this->id, $this->title);
        return $data;
    }

    /**
     * 14-人员列表
     * @desc 人员列表 带条件模糊查询
     * @return array data 接口请求成功后返回的数据
     */
    public function GetUserList()
    {
        $list = new Do_structure();
        $page = $this->page;
        $perpage = $this->perpage;
        $data = $list->GetUserList($page, $perpage, $this->where);
        return $data;
    }

    /**
     * 15-栏目列表
     * @desc 栏目的列表
     * @desc   已完成
     * @return array data 接口请求成功后返回的数据
     */
    public function GetColumnList()
    {
        $list = new Rule();
        $data = $list->GetColumnList();
        return $data;
    }

    /**
     * 16-新建子栏目
     * @desc 新建和编辑子栏目
     * @return code  1为成功其他为失败
     * @return msg   为消息内容
     */
    public function AddColumnList()
    {
        $list = new Rule();
        $newdata = [
            'title' => $this->title,
            'pid' => $this->pid,
            'path' => $this->path,
            'level' => $this->level,
            'component' => $this->component,
            'roles' => $this->roles,
            'types' => '0'
        ];
        $data = $list->AddColumnList($newdata, $this->id);
        return $data;
    }

    /**
     * 17-子栏目删除
     * @Date  2019/8/7 0007 16:48
     * @Author Dai Ming
     * @desc   已完成
     * @return string msg 提示信息
     */

    public function delColumn()
    {
        $list = new Rule();
        $data = $list->delColumn($this->pid, $this->id);
        return $data;
    }


    /**
     * 18-用户删除
     * @desc  用户删除
     * @Author Dai Ming
     * @return  code string 1为删除成功
     * @return  msg  streing 消息
     */
    public function delUser()
    {
        $list = new Do_structure();
        $data = $list->delUser($this->id);
        return $data;
    }

    /**
     * 19-用户添加
     * @desc 人员添加
     * @return string   code   1为成功其他失败
     * @return string  sign 未加密密码
     */
    public function AddUser()
    {
        $list = new Do_structure();
        $salt = substr(md5(rand(1000000, time())), 0, 4);
        $newdata = array(
            'username' => $this->username,
            'img' => $this->img,
            'realname' => $this->realname,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'sex' => $this->sex,
            'structure_id' => $this->structure_id,
            'post' => $this->post,
            'rztime' => $this->rztime,
            'zztime' => $this->zztime,
            'create_time' => time(),
            'type' => $this->type,
            'type_id' => $this->type_id,
            'status' => $this->status,
            'status' => $this->status,
            'worker' => $this->worker,
            'adress' => $this->adress,
            'creatid' => $this->creatid,
            'url' => $this->url,
            'salt' => $salt,
            'group_id' => $this->group_id,
            'new_groupid' => $this->new_groupid,
            'password' => \App\user_md5($this->password, $salt, $this->username),
        );
        $pw = $this->password;
        if ($pw == '' && $this->id == '') {
            $newdata['password'] = \App\user_md5('zklmzk', $salt, $this->username);

        }
        else if ($pw == '' && $this->id != '') {
            unset($newdata['password']);
            unset($newdata['salt']);
        }

        $data = $list->AddUser($this->uid, $this->id, $newdata);
        return $data;
    }

    /**
     * 20-获取指定部门或岗位人员列表
     * @desc  获取岗位或者部门的人员
     * @Date  2019/8/28 0028 17:39
     * @desc           状态：已完成
     * @return mixed
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data_list  人员数据列表
     * @return int    data_list.pid 父级ID
     * @return string data_list.pname 父级名称
     * @return string data_list.status 状态【1正常0已禁用】
     */
    public function getperson()
    {
        $list = new Do_structure();
        $msg = $list->getperson($this->type, $this->id, $this->pid);
        return $msg;
    }

    /**
     * 21-清空缓存
     * @author Dai Ming
     * @DateTime 17:08 2019/9/17 0017
     * @desc 清空缓存 （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function ClearRides()
    {
        $this->redis = \PhalApi\DI()->redis;
        $this->redis->flushAll();
        $rs = array('code'=>1,'msg'=>'清除缓存成功!','data'=>array(),'info'=>array());
        return  $rs;
    }

    /**
     *  加密测试
     * @author Dai Ming
     * @DateTime 16:24 2020/4/24 0024
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function Crtrdata()
    {
        $keyG = new \PhalApi\Crypt\RSA\KeyGenerator();
        $privkey = $keyG->getPriKey();
        $pubkey = $keyG->getPubKey();
        \PhalApi\DI()->crypt = new \PhalApi\Crypt\RSA\MultiPri2PubCrypt();
        $data = 'AHA! I have $2.22 dollars!';
        $encryptData = \PhalApi\DI()->crypt->encrypt($data, $privkey);
        $decryptData = \PhalApi\DI()->crypt->decrypt($encryptData, $pubkey);
        var_dump($encryptData);die;
    }


    /**
     * 07-公海超时回收机制
     * @author Dai Ming
     * @DateTime 14:31 2020/1/10 0010
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function ToCusGoCommon()
    {


//        $list= \PhalApi\DI()->notorm->customer_log->select('cid,id')->where('id <= 14044')->and('id >= 7961')->fetchAll();
//        foreach ($list as $k=>$v){
//            $sea_type= \PhalApi\DI()->notorm->customer->where('id',$v['cid'])->fetchOne('sea_type');
//            if($sea_type==1){
//                \PhalApi\DI()->notorm->customer->where('id',$v['cid'])->update(['sea_type'=>0]);
//                \PhalApi\DI()->notorm->customer_log->where('id',$v['id'])->delete();
//            }else{
//                \PhalApi\DI()->notorm->customer_log->where('id',$v['id'])->delete();
//            }
//
//        }

    }

    /**
     * 98-自定义规则
     * @author Dai Ming
     * @DateTime 16:30 2020/1/15 0015
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function Mycustomer()
    {
        
        $sql = "SELECT
                    c.cid as id
                FROM
                    crm_customer_data c
                LEFT JOIN crm_customer d ON d.id = c.cid
                WHERE
                    c.agent_name = '朱武'
                
                AND d.creatid = 1
                AND NOT FIND_IN_SET('54',d.charge_person)";
        $data=\PhalApi\DI()->notorm->crm_user->queryAll($sql, array());
        $list = new Do_structure();
        
        foreach ($data as $k=>$v){

         $cid_arr[]=$v['id'];
        }
     
        return $list->PostTransferAll(1,54,$cid_arr,1);
//       $this->redis = \PhalApi\DI()->redis;
        // $sql="select m.* from (select t.id from crm_user t LEFT JOIN crm_structure s ON s.id=t.new_groupid WHERE s.capacity = 3) k LEFT JOIN crm_customer m ON k.id=m.creatid where m.sea_type =1 ORDER BY m.id desc LIMIT 500";
        // $sql = "SELECT
        //     m.*
        // FROM
        //     (
        //         SELECT
        //             t.id
        //         FROM
        //             crm_user t
        //         LEFT JOIN crm_structure s ON s.id = t.new_groupid
        //         WHERE
        //             s.capacity = 3
        //     ) k
        // LEFT JOIN crm_customer m ON k.id = m.creatid
        // WHERE
        //     m.sea_type = 0
        // AND m.charge_person = CONCAT(m.creatid,',') limit 500 ";
        // $data=\PhalApi\DI()->notorm->crm_user->queryAll($sql, array());
        // $customer=new Customer();
        // $customer_share=new CustomerNew();
        // $share_group = "111,112,113,114,115,116";
        // foreach ($data as $k=>$v){
        //     $phone_data = \PhalApi\DI()->notorm->customer_data->select("cphone,cphonetwo,cphonethree,telephone,wxnum")->where(array("cid"=>$v['id']))->fetchOne();
        //     // 判断是否共享
        //     $if_share = $customer->GetStructByPhone($phone_data,$share_group);
        //     //共享
        //     if (empty($if_share['sh_arr']) && empty($if_share['gx_arr'])) {
        //         var_dump($v);die;
        //         //共享
        //         $customer->DoShareCustomerNew($v,$v['id'],$v['creatid'],$this->uid,$this->groupid);
        //         // $cid_arr=\PhalApi\DI()->notorm->share_join->where('creat_id',$v['creatid'])->and('beshare_uid',$this->uid)->and('cid',$v['id'])->fetchPairs('cid','bid');
        //         $postsea=['uid'=>1,'cid'=>$v['id'],'cancle_uid'=>$this->uid];
        //         // $customer_share->PostReturnToSea($postsea);
        //         \App\setlog(1,17,'复制公海',json_encode($postsea),'成功',$this->uid.'复制客户成功');
        //     }
        // }

        //   foreach ($order as $k=>$v){
        //     $groupid=end(json_decode($v['structure_id']));
        //     \PhalApi\DI()->notorm->user->where('id',$v['id'])->update(['new_groupid'=>$groupid]);
        //   }
        /****根据uid查询当前用户的客户数量****/
//        $user_list =  \PhalApi\DI()->notorm->user->where(array("is_delete"=>0))->fetchPairs("id","realname");
//        $customer_num = array();
//        foreach ($user_list as $key => $value) {
//            // $sql = "SELECT "
//            $num1 = \PhalApi\DI()->notorm->customer->where("creatid = {$key} AND sea_type = 0")->count("id");
//            $num2 =  \PhalApi\DI()->notorm->share_join->where("creat_id <> {$key} AND beshare_uid = {$key} AND sea_type = 0")->count("id");
//            $num = $num1 + $num2;
//             \PhalApi\DI()->notorm->user->where(array("id"=>$key))->update(array("getlimit"=>$num));
//            // $customer_list = $this->di->customer->select("a.id,a.cname,u.realname")->alias("a")->leftJoin("user","u","a.creatid = u.id")->where("a.creatid = {$uid}")->limit(0,10)->fetchAll();
//        }

//       var_dump($order);die;
        //        $this->redis->set_time('9101','9101token',600,'token');
//        $this->redis->set_time('9102','9102user',600,'user');
//        $this->redis->set_time('9103','9103code',600,'code');
//        $this->redis->set_time('9104','9104list',600,'list');

//        $this->redis->flushDB('user');
//        $this->redis->flushDB('code');
//        $this->redis->flushDB('list');
//        $data=\PhalApi\DI()->notorm->follw->where('uid',1)->fetchAll();
//        $this->redis->set_lPushx(94,$data,9);
//        $this->redis->set_forever(94,$data,9);
//                $this->redis->flushDB(9);

//        $payload='1';
//        $ad= \PhalApi\DI()->jwt->encodeJwt($payload);
//        $this->redis->set('1','321',600,'user');
//        $this->redis->switchDB('8','');
//        $this->redis->flushDB('customer');

//        $ads=  $this->redis->get_time('202','token');
//        $ads=  $this->redis->info();

//           var_dump($ad);die;
//        $sql="select c.creatid,t.uid,t.cid from  (select cid,uid from crm_customer_log where action = '私海新增' GROUP BY cid) t LEFT JOIN  crm_customer c ON t.cid=c.id where c.creatid <> t.uid";
//        $data=\PhalApi\DI()->notorm->crm_customer->queryAll($sql, array());
//        foreach ($data as $k=>$v){
//            $status=\PhalApi\DI()->notorm->customer_data->where('cid',$v['cid'])->and('ocreatid <> ?',$v['uid'])->fetchOne('cid');
//            var_dump($status);
//        }

//        $token=\PhalApi\DI()->jwt->encodeJwt(123);
//        var_dump($token);

//        $uid=$this->uid;
//        $url='http://cs.chinaleague.org/public/?service=App.Site.PublicAction';
//        $url_data=['uid'=>$uid];
//        $data=\App\curl_file_get_contents($url,$url_data);
//        $new_data=json_decode($data, true);
//        $get_data=$new_data['data'];
//        if(!empty($get_data)){
//            foreach ($get_data as $k=>$v){
//
//                    $unique = array('id' => $v['id']);
//                    $insert = $v;
//                    $update = $v;
//                    \PhalApi\DI()->notorm->$v[$k]->insert_update($unique, $insert, $update);
//
//            }
//        }
    }

    /**
     * 30-手机号查重
     * @author Dai Ming
     * @DateTime 15:05 2020/4/13 0013
     * @desc    手机号查重（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetPhoneRepeatList()
    {
        $list = new Do_structure();
        $msg = $list->GetPhoneRepeatList($this->uid,$this->group,$this->phone);
        return $msg;
    }

    /**
     *生成随机头像
     * @author Dai Ming
     * @DateTime 11:53 2020/5/13 0013
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function SETImg()
    {   $user_name= \PhalApi\DI()->notorm->customer->fetchAll();
        $adress=new Yesapiphpsdk();
        foreach ($user_name as $k=>$m){
//            $data="http://hn216.api.yesapi.cn/?s=Ext.Avatar.Show&nickname=".$m['cname']."&size=100&app_key=D02BC095C7031E8FDA3594D080BEF508&sign=D952F96B0202511490235D26E6E96335";
            \PhalApi\DI()->notorm->customer_data->where('cid',$m['id'])->update(['cimg'=>'']);

        }

//        $rs = array('code'=>1,'msg'=>'000000','data'=>$newData,'info'=>$newData);
//        return $rs;
    }

    /**
     * 指定部门客户公海共享合并
     * @author Dai Ming
     * @DateTime 14:09 2020/5/13 0013
     * @desc    （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function SetShareCustomerData()
    {
        $list = new Category();
        $msg = $list->SetShareCustomerData($this->uid,$this->group);
        return $msg;
    }


}
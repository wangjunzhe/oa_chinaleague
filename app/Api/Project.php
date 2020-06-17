<?php
namespace App\Api;

use PhalApi\Api;
use App\Domain\Project as Domain_Project;
/**
 * 产品模块接口服务
 */

class Project extends Api {
    public function getRules() {
        return array(
            'PostAddClass' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'class_name' => array('name' => 'class_name', 'type' => 'string','require' => true, 'desc' => '班级名称（必填）'),
                'sort' => array('name' => 'sort', 'type' => 'int','require' => false, 'desc' => '排序（不必填）'),
                'field_list' => array('name' => 'field_list', 'type' => 'array','min' => 1,'require' => false, 'desc' => '灵活字段（不必填）'),
            ),
            'GetClassList' => array(
                'uid' => array('name' => 'uid', 'type' => 'int','require' => false, 'desc' => '当前用户uid（不必填）' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetClassListNew' => array(
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetClassInfo' => array(
                'class_id' => array('name' => 'class_id', 'type' => 'int','require' => true, 'desc' => '班级ID（必填）'),
            ),
            'PostEditClass' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'class_id' => array('name' => 'class_id', 'type' => 'int','require' => true, 'desc' => '班级ID（必填）'),
                'class_name' => array('name' => 'class_name', 'type' => 'string','require' => true, 'desc' => '班级名称（必填）'),
                'sort' => array('name' => 'sort', 'type' => 'int','require' => false, 'desc' => '排序（不必填）'),
                'field_list' => array('name' => 'field_list', 'type' => 'array','min' => 1,'require' => false, 'desc' => '灵活字段（不必填）'),
            ),
            'DeleteClassInfo' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'class_id' => array('name' => 'class_id', 'type' => 'int','require' => true, 'desc' => '班级ID（必填）'),
            ),
            'PostAddSchool' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'title' => array('name' => 'title', 'type' => 'string','require' => true, 'desc' => '院校名称（必填）','default' => '' ),
                'area' => array('name' => 'area', 'type' => 'array','require' => false, 'desc' => '省/市/县（必填）' ),
                'address' => array('name' => 'address', 'type' => 'string','require' => true, 'desc' => '详细地址（必填）','default' => '' ),
                'school_motto' => array('name' => 'school_motto', 'type' => 'string','require' => true, 'desc' => '校训（必填）','default' => '' ),
                'content' => array('name' => 'content', 'type' => 'string','require' => true, 'desc' => '内容（必填）','default' => '' ),
                'remark' => array('name' => 'remark', 'type' => 'string','require' => true, 'desc' => '摘要（必填）','default' => '' ),
                'file' => array('name' => 'file', 'type' => 'string','require' => false, 'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','min' => 1,'require' => false, 'desc' => '灵活字段（不必填）'),
                'sort' => array('name' => 'sort', 'type' => 'int','require' => false, 'desc' => '排序（不必填）'),
            ),
            'GetSchoolList' => array(
                'uid' => array('name' => 'uid', 'type' => 'int','require' => false, 'desc' => '当前用户uid（不必填）' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetSchoolInfo' => array(
                'id' => array('name' => 'id', 'type' => 'int','min' => 1,'require' => true, 'desc' => '院校ID（必填）'),
            ),
            'PostEditSchool' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'int','require' => true, 'min' => 1, 'desc' => '院校ID（必填）','default' => '' ),
                'title' => array('name' => 'title', 'type' => 'string','require' => true, 'desc' => '院校名称（必填）','default' => '' ),
                'area' => array('name' => 'area', 'type' => 'array','require' => false, 'desc' => '省/市/县（必填）' ),
                'address' => array('name' => 'address', 'type' => 'string','require' => true, 'desc' => '详细地址（必填）','default' => '' ),
                'school_motto' => array('name' => 'school_motto', 'type' => 'string','require' => true, 'desc' => '校训（必填）','default' => '' ),
                'content' => array('name' => 'content', 'type' => 'string','require' => true, 'desc' => '内容（必填）','default' => '' ),
                'remark' => array('name' => 'remark', 'type' => 'string','require' => true, 'desc' => '摘要（必填）','default' => '' ),
                'file' => array('name' => 'file', 'type' => 'string','require' => false, 'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','min' => 1,'require' => false, 'desc' => '灵活字段（不必填）'),
                'sort' => array('name' => 'sort', 'type' => 'int','require' => false, 'desc' => '排序（不必填）'),

            ),
            'DeleteSchoolInfo' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'int','min' => 1,'require' => true, 'desc' => '院校ID（必填）'),
            ),
            'PostMajorAdd' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'title' => array('name' => 'title', 'type' => 'string','require' => true, 'desc' => '专业名称（必填）','default' => '' ),
                'sort' => array('name' => 'sort', 'type' => 'int','require' => false, 'desc' => '排序（不必填）'),
            ),
            'GetMajorList' => array(
                'uid' => array('name' => 'uid', 'type' => 'int','require' => false, 'desc' => '当前用户uid（不必填）' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostEditMajor' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'int','min' => 1,'require' => true, 'desc' => '专业ID（必填）'),
                'title' => array('name' => 'title', 'type' => 'string','require' => true, 'desc' => '专业名称（必填）','default' => '' ),
                'sort' => array('name' => 'sort', 'type' => 'int','require' => false, 'desc' => '排序（不必填）'),
            ),
            'DeleteMajorInfo' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'int','min' => 1,'require' => true, 'desc' => '专业ID（必填）'),
            ),
            'PostAddMajorDirection' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'title' => array('name' => 'title', 'type' => 'string','require' => true, 'desc' => '专业方向名称（必填）','default' => '' ),
                'major_id' => array('name' => 'major_id', 'type' => 'int','require' => true, 'desc' => '所属专业（必填）','default' => '' ),
                'content' => array('name' => 'content', 'type' => 'string','require' => true, 'desc' => '内容（必填）','default' => '' ),
                'remark' => array('name' => 'remark', 'type' => 'string','require' => true, 'desc' => '摘要（必填）','default' => '' ),
                'file' => array('name' => 'file', 'type' => 'string','require' => false, 'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','min' => 1,'require' => false, 'desc' => '灵活字段（不必填）'),
                'sort' => array('name' => 'sort', 'type' => 'int','require' => false, 'desc' => '排序（不必填）'),
            ),
            'MajorDirectList' => array(
                'uid' => array('name' => 'uid', 'type' => 'int','require' => false, 'desc' => '当前用户uid（不必填）' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'EditMajorDirection' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'int','require' => true, 'min' => 1, 'desc' => '专业方向ID（必填）','default' => '' ),
                'title' => array('name' => 'title', 'type' => 'string','require' => true, 'desc' => '专业方向名称（必填）','default' => '' ),
                'major_id' => array('name' => 'major_id', 'type' => 'int','require' => true, 'desc' => '所属专业（必填）','default' => '' ),
                'content' => array('name' => 'content', 'type' => 'string','require' => true, 'desc' => '内容（必填）','default' => '' ),
                'remark' => array('name' => 'remark', 'type' => 'string','require' => true, 'desc' => '摘要（必填）','default' => '' ),
                'file' => array('name' => 'file', 'type' => 'string','require' => false, 'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','min' => 1,'require' => false, 'desc' => '灵活字段（不必填）'),
                'sort' => array('name' => 'sort', 'type' => 'int','require' => false, 'desc' => '排序（不必填）'),
            ),
            'DeleteMajorDirection' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'int','min' => 1,'require' => true, 'desc' => '专业方向ID（必填）'),
            ),
            'GetMajorDirectionInfo' => array(
                'id' => array('name' => 'id', 'type' => 'int','min' => 1,'require' => true, 'desc' => '专业方向ID（必填）'),
            ),
            'PostAddGeneralRules' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'title' => array('name' => 'title', 'type' => 'string','require' => true, 'desc' => '简章名称（必填）','default' => '' ),
                'school_id' => array('name' => 'school_id', 'type' => 'int','require' => true, 'desc' => '所属院校ID（必填）','default' => '' ),
                'major_id' => array('name' => 'major_id', 'type' => 'int','require' => true, 'desc' => '所属专业ID（必填）','default' => '' ),
                'redirect_id' => array('name' => 'redirect_id', 'type' => 'int','require' => false, 'desc' => '专业方向ID（必填）','default' => '' ),
                'xuezhi' => array('name' => 'xuezhi', 'type' => 'int','require' => true, 'desc' => '学制（必填）','default' => '' ),
                'xuefei' => array('name' => 'xuefei', 'type' => 'int','require' => true, 'desc' => '学费（必填）','default' => '' ),
                'bm_price' => array('name' => 'bm_price', 'type' => 'int','require' => false, 'desc' => '报名费（不必填）','default' => '' ),
                'zl_price' => array('name' => 'zl_price', 'type' => 'int','require' => false, 'desc' => '资料费（不必填）','default' => '' ),
                'cl_price' => array('name' => 'cl_price', 'type' => 'int','require' => false, 'desc' => '材料费（不必填）','default' => '' ),
                'sq_price' => array('name' => 'sq_price', 'type' => 'int','require' => false, 'desc' => '申请费（不必填）','default' => '' ),
                'area' => array('name' => 'area', 'type' => 'array','require' => false, 'desc' => '省/市/县（必填）' ),
                'content' => array('name' => 'content', 'type' => 'string','require' => true, 'desc' => '内容（必填）','default' => '' ),
                'remark' => array('name' => 'remark', 'type' => 'string','require' => true, 'desc' => '摘要（必填）','default' => '' ),
                'file' => array('name' => 'file', 'type' => 'string','require' => false, 'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','min' => 1,'require' => false, 'desc' => '灵活字段（不必填）'),
                'sort' => array('name' => 'sort', 'type' => 'int','require' => false, 'desc' => '排序（不必填）'),
            ),
            'GetGerenalList' => array(
                'uid' => array('name' => 'uid', 'type' => 'int','require' => false, 'desc' => '当前用户uid（不必填）' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostEditGerenal' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'int','require' => true, 'min' => 1, 'desc' => '简章ID（必填）','default' => '' ),
                'title' => array('name' => 'title', 'type' => 'string','require' => true, 'desc' => '简章名称（必填）','default' => '' ),
                'school_id' => array('name' => 'school_id', 'type' => 'int','require' => true, 'desc' => '所属院校ID（必填）','default' => '' ),
                'major_id' => array('name' => 'major_id', 'type' => 'int','require' => true, 'desc' => '所属专业ID（必填）','default' => '' ),
                'redirect_id' => array('name' => 'redirect_id', 'type' => 'int','require' => false, 'desc' => '专业方向ID（必填）','default' => '' ),
                'xuezhi' => array('name' => 'xuezhi', 'type' => 'int','require' => true, 'desc' => '学制（必填）','default' => '' ),
                'xuefei' => array('name' => 'xuefei', 'type' => 'int','require' => true, 'desc' => '学费（必填）','default' => '' ),
                'bm_price' => array('name' => 'bm_price', 'type' => 'int','require' => false, 'desc' => '报名费（不必填）','default' => '' ),
                'zl_price' => array('name' => 'zl_price', 'type' => 'int','require' => false, 'desc' => '资料费（不必填）','default' => '' ),
                'cl_price' => array('name' => 'cl_price', 'type' => 'int','require' => false, 'desc' => '材料费（不必填）','default' => '' ),
                'sq_price' => array('name' => 'sq_price', 'type' => 'int','require' => false, 'desc' => '申请费（不必填）','default' => '' ),
                'area' => array('name' => 'area', 'type' => 'array','require' => false, 'desc' => '省/市/县（必填）'),
                'content' => array('name' => 'content', 'type' => 'string','require' => true, 'desc' => '内容（必填）','default' => '' ),
                'remark' => array('name' => 'remark', 'type' => 'string','require' => true, 'desc' => '摘要（必填）','default' => '' ),
                'file' => array('name' => 'file', 'type' => 'string','require' => false, 'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','min' => 1,'require' => false, 'desc' => '灵活字段（不必填）'),
                'sort' => array('name' => 'sort', 'type' => 'int','require' => false, 'desc' => '排序（不必填）'),
            ),
            'DeleteGeneralInfo' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'int','require' => true, 'min' => 1, 'desc' => '简章ID（必填）','default' => '' ),
            ),
            'GetGeneralInfo' => array(
                'id' => array('name' => 'id', 'type' => 'int','require' => true, 'min' => 1, 'desc' => '简章ID（必填）','default' => '' ),
            ),
            'ProjectLogin' =>array(
                'username' => array('name' => 'username', 'require' => true, 'min' => 1, 'max' => 50, 'desc' => '用户名'),
                'password' => array('name' => 'password', 'require' => true, 'min' => 6, 'max' => 20, 'desc' => '密码'),
                'sign' => array('name' => 'sign', 'require' => true, 'min' => 6, 'max' => 20, 'desc' => '验证参数'),
            ),

        );
    }

    /**
     * 项目方登录
     * @author Dai Ming
     * @DateTime 10:26 2020/4/20 0020
     * @desc    项目方登录(已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function ProjectLogin()
    {
        $domain = new Domain_Project();
        $info = $domain->ProjectLogin($this->username,$this->password,$this->sign);
        return $info;
    }

    /**
     * 1 添加班级接口
     * @author Wang Junzhe
     * @DateTime 2019-08-01T11:26:03+0800
     * @desc 添加班级数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostAddClass() {
        $newData = array(
            'class_name' => $this->class_name,
            'sort' => $this->sort,
            'field_list' => $this->field_list
        );

        $domain = new Domain_Project();
        $info = $domain->PostAddClass($newData,$this->uid);
        return $info;
    }

    /**
     * 1-2 班级列表接口
     * @author Wang Junzhe
     * @DateTime 2019-08-05T09:45:37+0800
     * @desc 班级列表数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return array data.class_list 班级数据列表
     * @return int class_list.id 班级ID
     * @return string class_list.title 班级名称
     * @return string class_list.sort 排序
     * @return string class_list.status 状态【1正常0已删除】
     * @return string class_list.publisher 发布人
     * @return string class_list.addtime 添加时间
     * @return string class_list.updatetime 更新时间
     * @return string class_list.updateman 更新人
     * @return string class_list.year 年级
     * @return string class_list.courseLocation 上课地址
     * 
     * @return string data.num 班级总数
     * 
     * @return string msg 提示信息
     */
    public function GetClassList() {
        $domain = new Domain_Project();
        $info = $domain->GetClassList($this->uid,$this->keywords,$this->pageno,$this->pagesize);
        return $info;
    }

    /**
     * 班级接口年份分组
     * @author Dai Ming
     * @DateTime 10:33 2019/10/15
     * @desc  班级接口年份分组（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetClassListNew()
    {
        $domain = new Domain_Project();
        $info = $domain->GetClassListNew($this->keywords,$this->pageno,$this->pagesize);
        return $info;
    }


    /**
     * 1-3 班级详情接口
     * @author Wang Junzhe
     * @DateTime 2019-08-05T10:04:03+0800
     * @desc 班级详情数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 班级ID
     * @return string data.title 班级名称
     * @return string data.sort 排序
     * @return string data.status 状态【1正常0已删除】
     * @return string data.publisher 发布人
     * @return string data.addtime 添加时间（时间戳）
     * @return string data.updatetime 更新时间（时间戳）
     * @return string data.updateman 更新人
     * @return string data.year 年级
     * @return string data.courseLocation 上课地址
     * 
     * @return string msg 提示信息
     */
    public function GetClassInfo() {
        $domain = new Domain_Project();
        $info = $domain->GetClassInfo($this->class_id);
        return $info;
    }

    /**
     * 1-4 编辑班级接口
     * @author Wang Junzhe
     * @DateTime 2019-08-05T10:20:09+0800
     * @desc 编辑班级数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostEditClass() {
        $newData = array(
            'class_id' => $this->class_id,
            'class_name' => $this->class_name,
            'sort' => $this->sort,
            'field_list' => $this->field_list
        );

        $domain = new Domain_Project();
        $info = $domain->PostEditClass($newData,$this->uid);
        return $info;
    }

    /**
     * 1-5 删除班级接口
     * @author Wang Junzhe
     * @DateTime 2019-08-05T10:55:17+0800
     * @desc 删除班级数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function DeleteClassInfo() {
        $domain = new Domain_Project();
        $info = $domain->DeleteClassInfo($this->class_id,$this->uid);
        return $info;
    }

    /**
     * 2 院校新增接口
     * @author Wang Junzhe
     * @DateTime 2019-08-06T17:00:52+0800
     * @desc 院校新增数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostAddSchool() {
        $newData = array(
            'title' => $this->title,
            'area' => $this->area,
            'address' => $this->address,
            'school_motto' => $this->school_motto,
            'content' => $this->content,
            'remark' => $this->remark,
            'file' => $this->file,
            'field_list' => $this->field_list,
            'sort' => $this->sort
        );
        $domain = new Domain_Project();
        $info = $domain->PostAddSchool($newData,$this->uid);
        return $info;
    }

    /**
     * 2-1 院校列表接口
     * @author Wang Junzhe
     * @DateTime 2019-08-07T14:32:06+0800
     * @desc院校列表数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.school_list 院校列表
     * @return int school_list.id 院校ID
     * @return string school_list.title 院校名称
     * @return string school_list.sort 排序
     * @return string school_list.status 状态【1正常0已删除】
     * @return string school_list.publisher 发布人
     * @return string school_list.addtime 添加时间（时间戳）
     * @return string school_list.updatetime 更新时间
     * @return string school_list.updateman 更新人
     * @return string school_list.project_type 项目分类（json字符串）
     * @return string school_list.school_attr 院校分类（json字符串）
     * @return string school_list.province 省
     * @return string school_list.city 市
     * @return string school_list.country 县
     * @return string school_list.address 详细地址
     * @return string school_list.school_motto 校训
     * @return string school_list.content 内容（未截取）
     * @return string school_list.remark 摘要
     * @return string school_list.file 附件
     * 
     * @return int data.num 院校总数
     * 
     * @return string msg 提示信息
     */
    public function GetSchoolList() {
        $domain = new Domain_Project();
        $info = $domain->GetSchoolList($this->uid,$this->keywords,$this->pageno,$this->pagesize);
        return $info;
    }

    /**
     * 2-2 院校编辑接口
     * @author Wang Junzhe
     * @DateTime 2019-08-08T10:16:23+0800
     * @desc 院校编辑数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostEditSchool() {
        $newData = array(
            'id' => $this->id,
            'title' => $this->title,
            'area' => $this->area,
            'address' => $this->address,
            'school_motto' => $this->school_motto,
            'content' => $this->content,
            'remark' => $this->remark,
            'file' => $this->file,
            'field_list' => $this->field_list,
            'sort' => $this->sort
        );
        $domain = new Domain_Project();
        $info = $domain->PostEditSchool($newData,$this->uid);
        return $info;
    }

    /**
     * 2-3 院校删除接口
     * @author Wang Junzhe
     * @DateTime 2019-08-08T14:29:11+0800
     * @desc 院校删除数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function DeleteSchoolInfo() {
        $domain = new Domain_Project();
        $info = $domain->DeleteSchoolInfo($this->id,$this->uid);
        return $info;
    }

    /**
     * 2-4 院校详情接口
     * @author Wang Junzhe
     * @DateTime 2019-08-13T15:06:27+0800
     * @desc 院校详情数据接口（待测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 院校ID
     * @return string data.title 院校名称
     * @return string data.sort 排序
     * @return string data.status 状态【1正常0已删除】
     * @return string data.publisher 发布人
     * @return string data.addtime 添加时间（时间戳）
     * @return string data.updatetime 更新时间
     * @return string data.updateman 更新人
     * @return string data.project_type 项目分类（json字符串）
     * @return string data.school_attr 院校分类（json字符串）
     * @return string data.province 省
     * @return string data.city 市
     * @return string data.country 县
     * @return string data.address 详细地址
     * @return string data.school_motto 校训
     * @return string data.content 内容（未截取）
     * @return string data.remark 摘要
     * @return string data.file 附件
     * 
     * @return string msg 提示信息
     */
    public function GetSchoolInfo() {
        $domain = new Domain_Project();
        $info = $domain->GetSchoolInfo($this->id);
        return $info;
    }

    /**
     * 3 专业新增接口
     * @author Wang Junzhe
     * @DateTime 2019-08-09T11:27:49+0800
     * @desc 专业新增数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostMajorAdd() {
        $newData = array(
            'title' => $this->title,
            'sort' => $this->sort
        );
        $domain = new Domain_Project();
        $info = $domain->PostMajorAdd($newData,$this->uid);
        return $info;
    }

    /**
     * 3-1 专业列表接口
     * @author Wang Junzhe
     * @DateTime 2019-08-09T11:46:40+0800
     * @desc专业列表数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.major_list 专业列表
     * @return int major_list.id 专业ID
     * @return string major_list.title 专业名称
     * @return string major_list.sort 排序
     * @return string major_list.status 状态【1正常0已删除】
     * @return string major_list.publisher 发布人
     * @return string major_list.addtime 添加时间（时间戳）
     * @return string major_list.updatetime 更新时间
     * @return string major_list.updateman 更新人
     * 
     * @return int data.num 专业总数
     * 
     * @return string msg 提示信息
     */
    public function GetMajorList() {
        $domain = new Domain_Project();
        $info = $domain->GetMajorList($this->keywords,$this->pageno,$this->pagesize,$this->uid);
        return $info;
    }

    /**
     * 3-2 专业编辑接口
     * @author Wang Junzhe
     * @DateTime 2019-08-09T11:27:49+0800
     * @desc 专业编辑数据接口（待测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostEditMajor() {
        $newData = array(
            'id' => $this->id,
            'title' => $this->title,
            'sort' => $this->sort
        );
        $domain = new Domain_Project();
        $info = $domain->PostEditMajor($newData,$this->uid);
        return $info;
    }

    /**
     * 3-3 专业删除接口
     * @author Wang Junzhe
     * @DateTime 2019-08-08T14:29:11+0800
     * @desc 专业删除数据接口（待测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function DeleteMajorInfo() {
        $domain = new Domain_Project();
        $info = $domain->DeleteMajorInfo($this->id,$this->uid);
        return $info;
    }
    
    /**
     * 4 方向(专业)新增接口
     * @author Wang Junzhe
     * @DateTime 2019-08-13T09:22:34+0800
     * @desc 方向(专业)新增数据接口（待测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostAddMajorDirection() {
        $newData = array(
            'title' => $this->title,
            'major_id' => $this->major_id,
            'content' => $this->content,
            'remark' => $this->remark,
            'file' => $this->file,
            'field_list' => $this->field_list,
            'sort' => $this->sort
        );
        $domain = new Domain_Project();
        $info = $domain->PostAddMajorDirection($newData,$this->uid);
        return $info;
    }
    

    /**
     * 4-1 方向(专业)列表接口
     * @author Wang Junzhe
     * @DateTime 2019-08-13T09:56:24+0800
     * @desc 方向(专业)列表数据接口（待测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.major_direction_list 专业方向列表
     * @return int major_direction_list.id 专业方向ID
     * @return string major_direction_list.title 专业方向名称
     * @return string major_direction_list.sort 排序
     * @return string major_direction_list.status 状态【1正常0已删除】
     * @return string major_direction_list.publisher 发布人
     * @return string major_direction_list.addtime 添加时间（时间戳）
     * @return string major_direction_list.updatetime 更新时间
     * @return string major_direction_list.updateman 更新人
     * @return string major_direction_list.project_type 项目分类（json字符串）
     * @return string major_direction_list.major_id 所属专业ID（不用）
     * @return string major_direction_list.content 内容（未截取）
     * @return string major_direction_list.remark 摘要
     * @return string major_direction_list.file 附件（需要拼接域名）
     * @return string major_direction_list.major_name 所属专业名称
     * 
     * @return int data.num 院校总数
     * 
     * @return string msg 提示信息
     */
    public function MajorDirectList() {
        $domain = new Domain_Project();
        $info = $domain->MajorDirectList($this->uid,$this->keywords,$this->pageno,$this->pagesize);
        return $info;
    }

    /**
     * 4-2 方向(专业)编辑接口
     * @author Wang Junzhe
     * @DateTime 2019-08-13T14:42:00+0800
     * @desc 方向(专业)编辑数据接口（待测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function EditMajorDirection() {
        $newData = array(
            'id' => $this->id,
            'title' => $this->title,
            'major_id' => $this->major_id,
            'content' => $this->content,
            'remark' => $this->remark,
            'file' => $this->file,
            'field_list' => $this->field_list,
            'sort' => $this->sort
        );
        $domain = new Domain_Project();
        $info = $domain->EditMajorDirection($newData,$this->uid);
        return $info;
    }

    /**
     * 4-3 方向(专业)删除接口 
     * @author Wang Junzhe
     * @DateTime 2019-08-13T14:55:00+0800
     * @desc 方向(专业)数据接口（待测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function DeleteMajorDirection() {
        $domain = new Domain_Project();
        $info = $domain->DeleteMajorDirection($this->id,$this->uid);
        return $info;
    }

    /**
     * 4-4 方向(专业)详情接口
     * @author Wang Junzhe
     * @DateTime 2019-08-13T15:24:17+0800
     * @desc 方向(专业)详情数据接口（待测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 专业方向ID
     * @return string data.title 专业方向名称
     * @return string data.sort 排序
     * @return string data.status 状态【1正常0已删除】
     * @return string data.publisher 发布人
     * @return string data.addtime 添加时间（时间戳）
     * @return string data.updatetime 更新时间
     * @return string data.updateman 更新人
     * @return string data.project_type 项目分类（json字符串）
     * @return string data.major_id 所属专业ID
     * @return string data.content 内容（未截取）
     * @return string data.remark 摘要
     * @return string data.file 附件（需要拼接域名）
     * @return string data.major_name 所属专业名称
     * 
     * @return string msg 提示信息
     */
    public function GetMajorDirectionInfo() {
        $domain = new Domain_Project();
        $info = $domain->GetMajorDirectionInfo($this->id);
        return $info;
    }

    /**
     * 5 简章--新增数据接口
     * @author Wang Junzhe
     * @DateTime 2019-08-14T11:13:05+0800
     * @desc 简章(新增)新增数据接口（开发中）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostAddGeneralRules() {
        $newData = array(
            'title' => $this->title,
            'school_id' => $this->school_id,
            'major_id' => $this->major_id,
            'redirect_id' => $this->redirect_id,
            'xuezhi' => $this->xuezhi,
            'xuefei' => $this->xuefei,
            'bm_price' => $this->bm_price,
            'zl_price' => $this->zl_price,
            'cl_price' => $this->cl_price,
            'sq_price' => $this->sq_price,
            'area' => $this->area,
            'content' => $this->content,
            'remark' => $this->remark,
            'file' => $this->file,
            'field_list' => $this->field_list,
            'sort' => $this->sort
        );
        $domain = new Domain_Project();
        $info = $domain->PostAddGeneralRules($newData,$this->uid);
        return $info;
    }

    /**
     * 5-1 简章列表接口
     * @author Wang Junzhe
     * @DateTime 2019-08-15T10:36:26+0800
     *  @desc 简章列表数据接口（开发中）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.general_list 简章列表
     * @return int general_list.id 简章ID
     * @return string general_list.title 简章名称
     * @return string general_list.sort 排序
     * @return string general_list.status 状态【1正常0已删除】
     * @return string general_list.publisher 发布人
     * @return string general_list.addtime 添加时间（时间戳）
     * @return string general_list.updatetime 更新时间
     * @return string general_list.updateman 更新人
     * @return string general_list.project_type 项目分类（json字符串）
     * @return string general_list.teaching_type 授课方式（json字符串）
     *
     * @return string general_list.recommen 推荐指数
     * @return string general_list.education 学历
     * 
     * @return int general_list.school_id 所属院校ID（不用）
     * @return int general_list.major_id 所属专业ID（不用）
     * @return int general_list.redirect_id 所属研究方向ID（不用）
     *
     * @return int general_list.xuezhi 学制
     * @return int general_list.remark 学费
     * @return int general_list.bm_price 报名费
     * @return int general_list.zl_price 资料费
     * @return int general_list.cl_price 材料费
     * @return int general_list.sq_price 申请费
     * 
     *
     * @return string general_list.province 省
     * @return string general_list.city 市
     * @return string general_list.country 县
     * 
     * @return string general_list.file 附件（需要拼接域名）
     * @return string general_list.school_name 所属院校名称
     * @return string general_list.major_name 所属专业名称
     * @return string general_list.direction_name 所属专业方向名称
     *
     * @return int data.num 简章总数
     * 
     * 
     * @return string msg 提示信息
     */
    public function GetGerenalList() {
        $domain = new Domain_Project();
        $info = $domain->GetGerenalList($this->uid,$this->keywords,$this->pageno,$this->pagesize);
        return $info;
    }

    /**
     * 5-2 简章编辑接口
     * @author Wang Junzhe
     * @DateTime 2019-08-15T11:07:24+0800
     * @desc 简章编辑数据接口（开发中）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostEditGerenal() {
        $newData = array(
            'id' => $this->id,
            'title' => $this->title,
            'school_id' => $this->school_id,
            'major_id' => $this->major_id,
            'redirect_id' => $this->redirect_id,
            'xuezhi' => $this->xuezhi,
            'xuefei' => $this->xuefei,
            'bm_price' => $this->bm_price,
            'zl_price' => $this->zl_price,
            'cl_price' => $this->cl_price,
            'sq_price' => $this->sq_price,
            'area' => $this->area,
            'content' => $this->content,
            'remark' => $this->remark,
            'file' => $this->file,
            'field_list' => $this->field_list,
            'sort' => $this->sort
        );
        $domain = new Domain_Project();
        $info = $domain->PostEditGerenal($newData,$this->uid);
        return $info;
    }
    
    /**
     * 5-3 简章删除接口
     * @author Wang Junzhe
     * @DateTime 2019-08-15T11:25:11+0800
     * @desc 简章删除数据接口（开发中）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function DeleteGeneralInfo() {
        $domain = new Domain_Project();
        $info = $domain->DeleteGeneralInfo($this->id,$this->uid);
        return $info;
    }


    /**
     * 5-4 简章详情
     * @author Wang Junzhe
     * @DateTime 2019-08-15T11:35:40+0800
     * @desc 简章详情数据接口（开发中）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * @return int data.id 简章ID
     * @return string data.title 简章名称
     * @return string data.sort 排序
     * @return string data.status 状态【1正常0已删除】
     * @return string data.publisher 发布人
     * @return string data.addtime 添加时间（时间戳）
     * @return string data.updatetime 更新时间
     * @return string data.updateman 更新人
     * @return string data.project_type 项目分类（json字符串）
     * @return string data.teaching_type 授课方式（json字符串）
     * @return string data.recommen 推荐指数
     * @return string data.education 学历
     * @return int data.school_id 所属院校ID
     * @return int data.major_id 所属专业ID
     * @return int data.redirect_id 所属研究方向ID
     * @return int data.xuezhi 学制
     * @return int data.remark 学费
     * @return int data.bm_price 报名费
     * @return int data.zl_price 资料费
     * @return int data.cl_price 材料费
     * @return int data.sq_price 申请费
     * @return string data.province 省
     * @return string data.city 市
     * @return string data.country 县
     * @return string data.file 附件（需要拼接域名）
     * @return string data.remark 简介
     * @return string data.content 内容
     * @return string data.school_name 所属院校名称
     * @return string data.major_name 所属专业名称
     * @return string data.direction_name 所属专业方向名称
     * @return string msg 提示信息
     */
    public function GetGeneralInfo() {
        $domain = new Domain_Project();
        $info = $domain->GetGeneralInfo($this->id);
        return $info;
    }
    
} 

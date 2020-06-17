<?php
namespace App\Api;
use PhalApi\Api;
use App\Domain\Site as Domain_Site;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * 公共接口服务类
 * @author: xiaozhe <1184378894@qq.com> 2019-09-10
 */
class Site extends Api {
    public function getRules() {
        return array(
            'UploadFile' => array(
                'file' => array('name' => 'file', 'type' => 'file','require' => false, 'desc' => '附件（必填）' ),
                'type' => array('name' => 'type', 'type' => 'int','require' => false, 'desc' => '类型[0默认1代理商2师资3项目方]（不必填）','default'=>0 ),
            ),
            'PostImportExcel' => array(
                'type' => array('name' => 'type', 'type' => 'string','require' => false, 'desc' => '导入类型[customer|agent|project_side|teacher|class|school|major|major_direction|general_rules]（必填）' ),
                'file' => array('name' => 'file', 'type' => 'file','require' => false, 'desc' => '附件（必填）' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）' ),
            ),
            'GetAllGeneralList' => array(
                'type' => array('name' => 'type', 'type' => 'int','require' => false, 'desc' => '类型[1代理商2项目方]（不必填）' ),
                'id' => array('name' => 'id','type' => 'int','require' => false,'desc' => '代理/项目ID（不必填）' ),
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetAllSchoolList' => array(
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetAllMajorList' => array(
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetAllAgentList'=>array(
                'type' => array('name' => 'type', 'type' => 'int','require' => false, 'desc' => '类型[1源数据2带ID]（不必填）','default' => 1 ),
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetAllUserList' => array(
                'type' => array('name' => 'type', 'type' => 'int','require' => false, 'desc' => '类型[0全部1部门2岗位3全部]（不必填）' ),
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）' ),
                'condition' => array('name' => 'condition', 'type' => 'int','require' => false, 'desc' => '是否全部[0全部]（不必填）' ),
            ),
            'GetGeneralPrice' => array(
                'id' => array('name' => 'id', 'type' => 'int', 'min' => 1, 'require' => true, 'desc' => '简章ID（必填）' ),
            ),
            'GetSharePersonList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）' ),
                'id' => array('name' => 'id', 'type' => 'int', 'require' => true, 'desc' => '信息ID' ),
                'type' => array('name' => 'type', 'type' => 'int', 'require' => true, 'desc' => '类型[1代理商2师资3一手项目方]' ),
            ),
            'GetAllCusstomer' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）' ),
                'cname' => array('name' => 'cname','type' => 'string','require' => false,'desc' => '当前用户id（必填）' )
            ),
            'PublicCancleShare' => array(
                'type' => array('name' => 'type', 'type' => 'int', 'require' => true, 'desc' => '类型[1代理商2师资3一手项目方]' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'desc' => '信息id（必填）','default' => 3 ),
            ),
            'GetAgentAllStudent' => array(
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'desc' => '代理商ID（必填）' ),
            ),
            'GetOrderNumberByCid' => array(
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'desc' => '客户ID（必填）' ),
            ),
            'PublicDeleteAgentGeneral' => array(
                'type' => array('name' => 'type', 'type' => 'int', 'require' => true, 'desc' => '类型[1代理商2项目方]' ),
                'id' => array('name' => 'id', 'type' => 'int', 'require' => true, 'desc' => '[代理/项目方]ID' ),
                'contract_id' => array('name' => 'contract_id', 'type' => 'int', 'require' => true, 'desc' => '[代理/项目方]合同ID' ),
                'general_id' => array('name' => 'general_id', 'type' => 'int', 'require' => true, 'desc' => '简章ID' ),
            ),
            'GetAllTeacherList' => array(
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）' ),
            ), 
            'PostExportModelExcel' => array(
                'tablename'=>array('name' => 'tablename','type' => 'string','require' => true,'desc' => '模板名称[customer|agent|project_side|teacher|class|school|major|major_direction|general_rules]（必填）'),
            ),
            'GetAllMajorDirection' => array(
                'major_id' => array('name' => 'major_id','type' => 'int','require' => false,'desc' => '专业id（不必填）' ),
            ),
           'PushFoloowMsg' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）' )
            ),
           'PublicAction' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）' )
            ),
           'GetUserLoginLog' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'order_by' => array('name' => 'order_by', 'type' => 'array','require' => false, 'desc' => '排序字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','require' => false, 'desc' => '条数限制（不必填）' ),
           ),
        );
    }

    /**
     * 01 上传单个文件
     * @author Wang Junzhe
     * @DateTime 2019-08-09T10:03:22+0800
     * @desc 上传单个文件数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.file_path  文件路径
     * 
     * @return string msg 提示信息
     */
    public function UploadFile() {
        $domain = new Domain_Site();
        $info = $domain->UploadFile($this->file,$this->type);
        return $info;
    }

    /**
     * 02 获取全部专业列表
     * @author Wang Junzhe
     * @DateTime 2019-08-14T11:25:58+0800
     * @desc 获取专业列表数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.key  专业ID
     * @return string data.value  专业名称
     * 
     * @return string msg 提示信息
     */
    public function GetAllMajorList() {
        $newData = array(
            'keywords' => $this->keywords,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Site();
        $info = $domain->GetAllMajorList($newData);
        return $info;
    }

    /**
     * 03 获取全部院校列表
     * @author Wang Junzhe
     * @DateTime 2019-08-14T11:41:55+0800
     * @desc 获取全部院校数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.key  院校ID
     * @return string data.value  院校名称
     * 
     * @return string msg 提示信息
     */
    public function GetAllSchoolList() {
        $newData = array(
            'keywords' => $this->keywords,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Site();
        $info = $domain->GetAllSchoolList($newData);
        return $info;
    }

    /**
     * 04 获取全部专业方向
     * @author Wang Junzhe
     * @DateTime 2019-08-14T15:39:05+0800
     * @desc 获取全部专业方向数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.key  院校ID
     * @return string data.value  院校名称
     * 
     * @return string msg 提示信息
     */
    public function GetAllMajorDirection() {
        $domain = new Domain_Site();
        $info = $domain->GetAllMajorDirection($this->major_id);
        return $info;
    }

    /**
     * 05 文件导入
     * @author Wang Junzhe
     * @DateTime 2019-08-17T09:37:03+0800
     * @desc 文件导入数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.value 错误信息
     *
     * @return string msg 提示信息
     */
    public function PostImportExcel() {
        $domain = new Domain_Site();
        $info = $domain->PostImportExcel($this->type,$this->file,$this->uid);
        return $info;
    }

    /**
     * 06 企业通讯录列表
     * @author Wang Junzhe
     * @DateTime 2019-08-27T15:05:40+0800
     * @desc 获取企业通讯录列表接口
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.key 0[A..Z]1[部门]2[岗位]
     * @return array data.value 数据列表
     * @return string data.id 用户ID
     * @return string data.realname 用户名
     * @return string data.getlimit 当前数量
     * @return string data.setlimit 私海限制数量
     * @return string data.post 岗位id
     * @return string data.post_name [type=0]部门+岗位[type=1or2or3]岗位
     * @return string data.id_structure 部门id【type=1无用】
     * @return string data.structure_id 用户部门【type=3】
     * @return string data.structure_name 部门名称【type=3】
     * 
     * @return string msg 提示信息
     */
    public function GetAllUserList() {
        $newData = array(
            'type' => $this->type,
            'keywords' => $this->keywords,
            'condition' => $this->condition
        );
        $domain = new Domain_Site();
        $info = $domain->GetAllUserList($newData);
        return $info;
    }

    /**
     * 07 获取全部简章
     * @author Wang Junzhe
     * @DateTime 2019-09-04T15:41:59+0800
     * @desc 获取全部简章数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.key  简章ID
     * @return string data.value  简章名称
     * 
     * @return string msg 提示信息
     */
    public function GetAllGeneralList() {
        $newData = array(
            'type' => $this->type,
            'id' => $this->id,
            'keywords' => $this->keywords,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Site();
        $info = $domain->GetAllGeneralList($newData);
        return $info;
    }

    /**
     * 08 获取简章价格
     * @author Wang Junzhe
     * @DateTime 2019-09-04T16:17:01+0800
     * @desc 简章价格接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id  简章ID
     * @return string data.title  简章名称
     * @return int data.xuefei  学费
     * @return int data.bm_price  报名费
     * @return int data.zl_price  资料费
     * @return int data.cl_price  材料费
     * @return int data.sq_price  申请费
     * 
     * @return string msg 提示信息
     */
    public function GetGeneralPrice() {
        $domain = new Domain_Site();
        $info = $domain->GetGeneralPrice($this->id);
        return $info;
    }

    /**
     * 09 [公共]获取信息共享人员列表
     * @author Wang Junzhe
     * @DateTime 2019-09-06T09:53:33+0800
     * @desc [公共]信息共享人员列表[id值：一般传当条信息的id，如果是客户模块，id传cid]
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.user_name 人员名
     * @return string data.uid 人员ID
     * 
     * @return string msg 提示信息
     */
    public function GetSharePersonList() {
        $newData = array(
            'uid' => $this->uid,
            'id' => $this->id,
            'type' => $this->type
        );
        $domain = new Domain_Site();
        $info = $domain->GetSharePersonList($newData);
        return $info;
    }

    /**
     * 10 我的全部客户列表
     * @author Wang Junzhe
     * @DateTime 2019-09-10T10:08:22+0800
     * @desc 我的全部客户接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.key  客户ID
     * @return string data.value  客户名称
     * 
     * @return string msg 提示信息
     */
    public function GetAllCusstomer() {
        $newData = array(
            'uid' => $this->uid,
            'cname' => $this->cname
        );
        $domain = new Domain_Site();
        $info = $domain->GetAllCusstomer($newData);
        return $info;
    }

    /**
     * 11 公共取消共享
     * @author Wang Junzhe
     * @DateTime 2019-09-10T17:22:39+0800
     * @desc 公共取消共享接口（未完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PublicCancleShare() {
        $newData = array(
            'type' => $this->type,
            'uid' => $this->uid,
            'id' => $this->id
        );
        $domain = new Domain_Site();
        $info = $domain->PublicCancleShare($newData);
        return $info;
    }

    /**
     * 12 获取全部代理商
     * @author Wang Junzhe
     * @DateTime 2019-09-18T15:43:37+0800
     * @desc 【代理商】列表接口
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.key  代理商编号
     * @return string data.value  代理商花名
     * 
     * @return string msg 提示信息
     */
    public function GetAllAgentList() {
        $newData = array(
            'type' => $this->type,
            'keywords' => $this->keywords,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Site();
        $info = $domain->GetAllAgentList($newData);
        return $info;
    }


    /**
     * 13 获取全部一手项目方
     * @author Wang Junzhe
     * @DateTime 2019-09-18T15:43:37+0800
     * @desc 【一手项目方】列表接口
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.key  一手项目方id
     * @return string data.value  一手项目方编号
     * 
     * @return string msg 提示信息
     */
    public function GetAllProdutionList() {
        $domain = new Domain_Site();
        $info = $domain->GetAllProdutionList();
        return $info;
    }

    /**
     * 14 获取代理商全部学员
     * @author Wang Junzhe
     * @DateTime 2019-09-23T10:51:15+0800
     * @desc 【代理商】全部学员
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.key  客户cid
     * @return string data.value  客户名称
     * 
     * @return string msg 提示信息
     */
    public function GetAgentAllStudent() {
        $domain = new Domain_Site();
        $info = $domain->GetAgentAllStudent($this->agent_id);
        return $info;
    }

    /**
     * 15 获取成交编号
     * @author Wang Junzhe
     * @DateTime 2019-09-23T10:51:15+0800
     * @desc 【代理商】根据客户cid获取成交编号
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.key  成交ID
     * @return string data.value  成交编号
     * 
     * @return string msg 提示信息
     */
    public function GetOrderNumberByCid() {
        $domain = new Domain_Site();
        $info = $domain->GetOrderNumberByCid($this->cid);
        return $info;
    }

    /**
     * 16 代理/项目方删除简章
     * @author Wang Junzhe
     * @DateTime 2019-10-14T16:43:51+0800
     * @desc [代理/项目方]删除简章
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PublicDeleteAgentGeneral() {
        $newData = array(
            'type' => $this->type,
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'general_id' => $this->general_id
        );
        $domain = new Domain_Site();
        $info = $domain->PublicDeleteAgentGeneral($newData);
        return $info;
    }

    /**
     * 17 导出Excel模板
     * @author Wang Junzhe
     * @DateTime 2019-10-17T09:53:17+0800
     * @desc 导出Excel模板（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostExportModelExcel() {
        $domain = new Domain_Site();
        $info = $domain->PostExportModelExcel($this->tablename);
        return $info;
    }

    /**
     * 18 推送消息接口
     * @author Wang Junzhe
     * @DateTime 2019-11-27T13:55:22+0800
     * @desc 推送消息接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function GetNoticeFollowMsg() {
        $domain = new Domain_Site();
        $info = $domain->GetNoticeFollowMsg();
        return $info;
    }
  
    /**
     * 19 登录之后推送跟进消息
     * @author Wang Junzhe
     * @DateTime 2019-12-27T16:25:04+0800
     * @desc 登录之后推送跟进消息（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     */
    public function PushFoloowMsg() {
        $domain = new Domain_Site();
        $info = $domain->PushFoloowMsg($this->uid);
        return $info;
    }

    /**
     * 20 公共处理的方法
     * @author Wang Junzhe
     * @DateTime 2020-04-13T10:17:39+0800
     */
    public function PublicAction() {
        $domain = new Domain_Site();
        $info = $domain->PublicAction($this->uid);
        return $info;
    }

    /**
     * 21 用户登录日志
     * @author Wang Junzhe
     * @DateTime 2020-04-27T15:07:54+0800
     * @desc 用户登录日志（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.num 总数
     * 
     * @return array data.logo_list 日志列表
     * @return int logo_list.id ID
     * @return string logo_list.ip IP
     * @return string logo_list.operation 内容
     * @return string logo_list.city 城市
     * @return date logo_list.creat_time 时间
     *
     * @return string msg 提示信息
     */
    public function GetUserLoginLog() {
        $newData = array(
            'uid' => $this->uid,
            'where_arr' => $this->where_arr,
            'order_by' => $this->order_by,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Site();
        $info = $domain->GetUserLoginLog($newData);
        return $info;
    }
    
}
 
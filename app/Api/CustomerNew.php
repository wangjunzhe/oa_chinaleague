<?php
namespace App\Api;
use PhalApi\Api;
use App\Domain\CustomerNew as Domain_CustomerNew;

/**
 * 新客户模块接口服务
 * @author: dogstar <1184378894@qq.com>
 */
class CustomerNew extends Api {
    public function getRules() {
        return array(
            'GetMyCustomerList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'min' => 0,'desc' => '搜索类型[0全部1我的客户2公海..12我的未共享]（不必填）','default' => '0' ),
                'gj_type' => array('name' => 'gj_type','type' => 'int','require' => false,'min' => 0,'desc' => '跟进类型[0全部1已跟进2未跟进]（不必填）','default' => '0' ),
                'day_type' => array('name' => 'day_type','type' => 'int','require' => false,'min' => 0,'desc' => '搜索时间类型[0今日1(3天内)..5(90天内)]（不必填）','default' => '0' ),
                'yx_type' => array('name' => 'yx_type','type' => 'string','require' => false,'min' => 0,'desc' => '意向类型（不必填）','default' => '' ),
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）','default' => '' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'order_by' => array('name' => 'order_by', 'type' => 'array','require' => false, 'desc' => '排序字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetMySeaCustomerLists' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'min' => 0,'desc' => '搜索类型[0全部1今日新增2今日回归]（不必填）','default' => '0' ),
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）','default' => '' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'order_by' => array('name' => 'order_by', 'type' => 'array','require' => false, 'desc' => '排序字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetReceiveCustomerList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type'  => array('name' => 'type','type' => 'int','require' => false,'desc' => '数据类型[0分配1领取2我分配的]（不必填）','default' => '0' ),
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）','default' => '' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'order_by' => array('name' => 'order_by', 'type' => 'array','require' => false, 'desc' => '排序字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostShareMyCustomer' => array(
                'type' => array('name' => 'type','type' => 'int','require' => true,'desc' => '类型0[1对多]1[多对1]（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cid_arr' => array('name' => 'cid_arr','type' => 'array','require' => true,'desc' => '共享客户id（必填）'),
                'is_leader' => array('name' => 'is_leader','type' => 'int','require' => false,'desc' => '查看下级并共享时为1（不必填）','default' => '' ),
                'beshare_uid' => array('name' => 'beshare_uid','type' => 'array','require' => true,'desc' => '被分享人用户id[数组]（必填）'),
                'is_repeat' => array('name' => 'is_repeat','type' => 'int','require' => false,'desc' => '报备类型[1撞单自动共享]')
            ),
            'PostReturnToSea' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cid_arr' => array('name' => 'cid_arr','type' => 'array','require' => true,'desc' => '客户cid[key为cid,value为bid]（必填）'),
                'cancle_uid' => array('name' => 'cancle_uid','type' => 'int','require' => false,'desc' => '回归公海uid[管理员和查看下级必填]')
            ),
        );
    }

    /**
     * 01 客户列表
     * @author Wang Junzhe
     * @DateTime 2020-04-28T16:00:47+0800
     * @desc 客户列表数据接口（待对接测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * @return array data.customer_list 客户列表
     * @return string customer_list.cid 客户ID
     * @return string customer_list.cname 客户名称
     * @return string customer_list.intentionally 客户意向
     * @return string customer_list.levels 客户级别
     * @return string customer_list.types 客户种类
     * @return string customer_list.ittnxl 意向学历
     * @return string customer_list.ittnzy 意向专业
     * @return string customer_list.ittnyx 意向院校
     * @return string customer_list.ittnxm 意向项目
     * @return string customer_list.ittngj 意向国家
     * @return string customer_list.labelpeer 标签
     * @return string customer_list.budget 预算
     * @return string customer_list.timeline 入学时间
     * @return string customer_list.graduate 毕业院校
     * @return string customer_list.graduatezy 毕业专业
     * @return string customer_list.xuel 当前学历
     * @return string customer_list.traffic 数据来源
     * @return string customer_list.channel_level 渠道级别
     * @return string customer_list.tolink 发起沟通的网址
     * @return string customer_list.attachment 附件
     * @return string customer_list.note 备注
     * @return string customer_list.creatid 创建人ID
     * @return string customer_list.is_top 是否置顶（1是）
     * @return string customer_list.sea_type 类型（0私海1公海）
     * @return string customer_list.sort 序号
     * @return string customer_list.myshare 是否共享（0否1是）
     * @return string customer_list.getshare 是否被共享（0否1是）
     * @return string customer_list.charge_person 负责人
     * @return string customer_list.publisher 创建人
     * @return string customer_list.follow_up 跟进次数
     * @return string customer_list.follow_person 跟进人
     * @return string customer_list.follw_time 最后跟进时间
     * @return string customer_list.next_follow 下次回访时间
     * @return string customer_list.share_time 共享时间[只有共享相关的有]
     * @return string customer_list.share_username 分享人[只有共享相关的有]
     * 
     * 
     * @return string customer_list.group 部门
     * @return string customer_list.share_group 分享部门
     * 
     * @return string customer_list.sex 性别
     * @return string customer_list.age 年龄
     * @return string customer_list.station 岗位
     * @return string customer_list.occupation 职业
     * @return string customer_list.industry 行业
     * @return string customer_list.experience 工作经历
     * @return string customer_list.city 城市
     * @return string customer_list.adress 当前住址
     * @return string customer_list.company 所在公司
     * @return string customer_list.scale 公司规模
     * @return string customer_list.character 客户性格
     * @return string customer_list.cphone 联系电话
     * @return string customer_list.cphonetwo 联系电话2
     * @return string customer_list.cphonethree 联系电话3
     * @return string customer_list.telephone 座机
     * @return string customer_list.formwhere 归属地
     * @return string customer_list.formwhere2 归属地2
     * @return string customer_list.formwhere3 归属地3
     * @return string customer_list.wxnum 微信号
     * @return string customer_list.cemail 邮箱
     * @return string customer_list.qq QQ
     * @return string customer_list.cimg 客户头像
     * @return string customer_list.create_time 创建时间
     * @return string customer_list.update_time 更新时间
     * @return string customer_list.invoice_company 开票公司
     * @return string customer_list.taxpayer_num 纳税人识别号
     * @return string customer_list.bank 开户行
     * @return string customer_list.open_bank 开户行名称
     * @return string customer_list.bank_num 银行账号
     * @return string customer_list.bank_adress 银行地址
     * @return string customer_list.legal_person 法人
     * @return string customer_list.business_license 营业执照
     * @return string customer_list.own 个人花名
     * @return string customer_list.agent_name 代理商花名
     * @return string customer_list.agent_num 代理商编号
     * @return string customer_list.project_name 项目方花名
     * @return string customer_list.project_num 项目方编号
     * @return string customer_list.agent_price 代理费
     * @return string customer_list.paid 已付款
     * @return string customer_list.obligations 待付款
     * @return string customer_list.zdnum 撞单次数
     * @return string customer_list.old_publisher 原始创建人
     * 
     * @return int data.customer_num 客户数量
     * 
     * @return array data.total_count 客户统计
     * @return int total_count.total 全部客户
     * @return int total_count.gh_total 公海客户
     * @return int total_count.sh_total 私有客户
     * @return int total_count.gx_total 我的共享
     * @return int total_count.gxgw_total 共享给我
     * @return int total_count.jrxz_total 今日新增
     * @return int total_count.jrgh_total 今日公海
     * @return int total_count.jrgx_total 今日共享
     * @return int total_count.jrgxgw_total 今日共享给我
     * @return int total_count.jrtxgj_total 今日提醒跟进
     * @return int total_count.jrygj_total 今日已跟进
     * @return int total_count.jrwgj_total 今日未跟进
     * @return int total_count.wgx_total 我的&&未共享
     * @return int total_count.jrgxygj_total 今日共享已跟进
     * @return int total_count.jrgxwgj_total 今日共享未跟进
     * @return int total_count.wgx_total 我的未共享
     *
     * @return string msg 提示信息
     */
    public function GetMyCustomerList() {
        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'gj_type' => $this->gj_type,
            'day_type' => $this->day_type,
            'yx_type' => $this->yx_type,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'order_by' => $this->order_by,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_CustomerNew();
        $info = $domain->GetMyCustomerList($newData);
        return $info;
    }

    /**
     * 02 回归公海
     * @author Wang Junzhe
     * @DateTime 2020-04-28T16:02:36+0800
     * 
     */
    public function PostReturnToSea() {
        $newData = array(
            'uid' => $this->uid,
            'cid_arr' => $this->cid_arr,
            'cancle_uid' => $this->cancle_uid
        );
        $domain = new Domain_CustomerNew();
        $info = $domain->PostReturnToSea($newData);
        return $info;
    }

    /**
     * 03 公海客户列表
     * @author Wang Junzhe
     * @DateTime 2020-04-28T16:03:58+0800
     * @desc 公海客户列表
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.customer_list 客户列表
     * @return string customer_list.cid 客户ID
     * @return string customer_list.cname 客户名称
     * @return string customer_list.intentionally 客户意向
     * @return string customer_list.ittnxl 意向学历
     * @return string customer_list.ittnzy 意向专业
     * @return string customer_list.ittnyx 意向院校
     * @return string customer_list.ittnxm 意向项目
     * @return string customer_list.ittngj 意向国家
     * @return string customer_list.labelpeer 标签
     * @return string customer_list.budget 预算
     * @return string customer_list.timeline 入学时间
     * @return string customer_list.graduate 毕业院校
     * @return string customer_list.graduatezy 毕业专业
     * @return string customer_list.xuel 当前学历
     * @return string customer_list.tolink 发起沟通的网址
     * @return string customer_list.attachment 附件
     * @return string customer_list.note 备注
     * @return string customer_list.creatid 创建人ID
     * @return string customer_list.is_top 是否置顶（1是）
     * @return string customer_list.sea_type 类型（0私海1公海）
     * @return string customer_list.sort 序号
     * @return string customer_list.myshare 是否共享（0否1是）
     * @return string customer_list.getshare 是否被共享（0否1是）
     * @return string customer_list.charge_person 负责人
     * @return string customer_list.publisher 创建人
     * @return string customer_list.follow_up 跟进次数
     * @return string customer_list.follow_person 跟进人
     * @return string customer_list.zdnum 撞单次数
     * @return string customer_list.follw_time 最后跟进时间
     * @return string customer_list.next_follow 下次回访时间
     * 
     * 
     * @return string customer_list.group 部门
     * @return string customer_list.sex 性别
     * @return string customer_list.age 年龄
     * @return string customer_list.station 岗位
     * @return string customer_list.occupation 职业
     * @return string customer_list.industry 行业
     * @return string customer_list.experience 工作经历
     * @return string customer_list.city 城市
     * @return string customer_list.adress 当前住址
     * @return string customer_list.company 所在公司
     * @return string customer_list.scale 公司规模
     * @return string customer_list.character 客户性格
     * @return string customer_list.cphone 联系电话
     * @return string customer_list.cphonetwo 联系电话2
     * @return string customer_list.cphonethree 联系电话3
     * @return string customer_list.telephone 座机
     * @return string customer_list.formwhere 归属地
     * @return string customer_list.formwhere2 归属地2
     * @return string customer_list.formwhere3 归属地3
     * @return string customer_list.wxnum 微信号
     * @return string customer_list.cemail 邮箱
     * @return string customer_list.qq QQ
     * @return string customer_list.create_time 创建时间
     * @return string customer_list.update_time 更新时间
     * @return string customer_list.own 个人花名
     * @return string customer_list.zdnum 撞单次数
     * 
     * 
     * @return int data.customer_gh_num 客户数量
     * 
     * @return array data.total_count 客户统计
     * @return int total_count.customer_gh_total 全部公海客户
     * @return int total_count.customer_xzgh_total 今日新增公海客户
     * @return int total_count.customer_hggh_total 今日回归公海客户
     * 
     * @return string msg 提示信息
     */
    public function GetMySeaCustomerLists() {
        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'order_by' => $this->order_by,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_CustomerNew();
        $info = $domain->GetMySeaCustomerLists($newData);
        return $info;
    }

    /**
     * 04 [领取/分配]客户列表
     * @author Wang Junzhe
     * @DateTime 2020-05-04T09:47:02+0800
     * @desc [领取/分配]客户列表（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.data_list 客户列表
     * @return string data_list.cid 客户ID
     * @return string data_list.addtime 申领[分配时间](时间戳)
     * @return string data_list.cname 客户名称
     * @return string data_list.intentionally 客户意向
     * @return string data_list.levels 客户级别
     * @return string data_list.ittnxl 意向学历
     * @return string data_list.sex 性别[0--1男2女]
     * @return string data_list.cphone 联系电话
     * 
     * @return int data.data_num 客户数量
     */
    public function GetReceiveCustomerList() {
        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'order_by' => $this->order_by,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_CustomerNew();
        $info = $domain->GetReceiveCustomerList($newData);
        return $info;
    }

    /**
     * 05 报备客户
     * @author Wang Junzhe
     * @DateTime 2020-05-04T15:19:29+0800
     * @desc 客户报备（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostShareMyCustomer() {
        $newData = array(
            'type' => $this->type,
            'uid' => $this->uid,
            'cid_arr' => $this->cid_arr,
            'is_leader' => $this->is_leader,
            'beshare_uid' => $this->beshare_uid,
            'is_repeat' => $this->is_repeat
        );
        $domain = new Domain_CustomerNew();
        $info = $domain->PostShareMyCustomer($newData);
        return $info;
    }

}
 
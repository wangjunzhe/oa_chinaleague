<?php
namespace App\Api;
use PhalApi\Api;
use App\Domain\Customer as Domain_Customer;

/**
 * 客户模块接口服务
 * @author: 智库联盟
 */
class Customer extends Api {
    public function getRules() {
        return array(
            'GetCustomerList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'min' => 0,'desc' => '搜索类型[0全部1我的客户2公海..12我的未共享]（不必填）','default' => '0' ),
                'gj_type' => array('name' => 'gj_type','type' => 'int','require' => false,'min' => 0,'desc' => '跟进类型[0全部1已跟进2未跟进]（不必填）','default' => '0' ),
                'day_type' => array('name' => 'day_type','type' => 'int','require' => false,'min' => 0,'desc' => '搜索时间类型[0今日1(3天内)..5(90天内)]（不必填）','default' => '0' ),
                'yx_type' => array('name' => 'yx_type','type' => 'string','require' => false,'min' => 0,'desc' => '意向类型（不必填）','default' => '' ),
                'level_type' => array('name' => 'level_type','type' => 'string','require' => false,'min' => 0,'desc' => '客户级别（不必填）','default' => '' ),
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）','default' => '' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'order_by' => array('name' => 'order_by', 'type' => 'array','require' => false, 'desc' => '排序字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostDeleteCustomer' => array(
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型1[自用]（不必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '客户id（必填）','default' => '' ),
            ),
            'PostReturnSea' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cid_arr' => array('name' => 'cid_arr','type' => 'array','require' => true,'desc' => '回归公海客户id数组（必填）' ),
            ),
            'GetSeaCustomerList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'min' => 0,'desc' => '搜索类型[0全部1今日新增2今日回归]（不必填）','default' => '0' ),
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）','default' => '' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'order_by' => array('name' => 'order_by', 'type' => 'array','require' => false, 'desc' => '排序字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostShareCustomer' => array(
                'type' => array('name' => 'type','type' => 'int','require' => true,'desc' => '类型0[1对多]1[多对1]（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cid_arr' => array('name' => 'cid_arr','type' => 'array','require' => true,'desc' => '共享客户id（必填）'),
                'is_leader' => array('name' => 'is_leader','type' => 'int','require' => false,'desc' => '查看下级并共享时为1（不必填）','default' => '' ),
                'share_uid' => array('name' => 'share_uid','type' => 'array','require' => true,'desc' => '用户id[数组]（必填）'),
            ),
            'GetShareCustomerPerson' => array(
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型0[1默认]1[查看下级取消共享]（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => false,'desc' => '当前用户id（必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'desc' => '共享客户id（必填）'),
            ),
            'PostCancleShare' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'desc' => '共享客户id（必填）'),
            ),
            'PostCancleShareNew' => array(
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'desc' => '共享客户id（必填）'),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cancle_uid' => array('name' => 'cancle_uid','type' => 'array','require' => true,'desc' => '取消共享uid（必填）','default' => array('36','94') ),
            ),
            'PostDistribute' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'beshare_uid' => array('name' => 'beshare_uid','type' => 'int','require' => true,'desc' => '被分配用户id（必填）','default' => '' ),
                'cid_arr' => array('name' => 'cid_arr','type' => 'array','require' => true,'desc' => '分配客户id[key为cid,value为bid]（必填）' ),
            ),
            'PostReceive' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型0[1默认]1[录入公海启用]（必填）','default' => '' ),
                'cid_arr' => array('name' => 'cid_arr','type' => 'array','require' => true,'desc' => '分配客户id[key为cid,value为bid]（必填）' ),
            ),
            'GetMySeaCustomerList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type'  => array('name' => 'type','type' => 'int','require' => false,'desc' => '数据类型[0分配1领取2我分配的]（不必填）','default' => '0' ),
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）','default' => '' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'order_by' => array('name' => 'order_by', 'type' => 'array','require' => false, 'desc' => '排序字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostAddCustomerDeal' => array(
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'min' => 1,'desc' => '客户id（必填）','default' => '' ),
                'cname' => array('name' => 'cname','type' => 'string','require' => true,'desc' => '客户名称（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'deal_id' => array('name' => 'deal_id','type' => 'int','require' => true,'min' => 1,'desc' => '成交用户id（必填）','default' => '' ),
                'structure_id' => array('name' => 'structure_id','type' => 'int','require' => true,'min' => 1,'desc' => '成交部门id（必填）','default' => '' ),
                'general_id' => array('name' => 'general_id','type' => 'int','require' => true,'min' => 1,'desc' => '简章id（必填）','default' => '' ),
                'xuefei' => array('name' => 'xuefei','type' => 'int','require' => false,'desc' => '学费（不必填）','default' => '' ),
                'bm_price' => array('name' => 'bm_price','type' => 'int','require' => false,'desc' => '报名费（不必填）','default' => '' ),
                'zl_price' => array('name' => 'zl_price','type' => 'int','require' => false,'desc' => '资料费（不必填）','default' => '' ),
                'cl_price' => array('name' => 'cl_price','type' => 'int','require' => false,'desc' => '材料费（不必填）','default' => '' ),
                'sq_price' => array('name' => 'sq_price','type' => 'int','require' => false,'desc' => '申请费（不必填）','default' => '' ),
                'yh_price' => array('name' => 'yh_price','type' => 'int','require' => false,'desc' => '优惠费（不必填）','default' => '' ),
                'total' => array('name' => 'total','type' => 'int','require' => true,'desc' => '总费（必填）','default' => '' ),
                'deal_place' => array('name' => 'deal_place','type' => 'string','require' => false,'desc' => '成交地点（不必填）','default' => '' ),
                'collect_time' => array('name' => 'collect_time','type' => 'int','require' => false,'desc' => '收款日期（时间戳）','default' => '' ),
                'deal_time' => array('name' => 'deal_time','type' => 'int','require' => false,'desc' => '成交日期（时间戳）','default' => '' ),
                'pay_cardno' => array('name' => 'pay_cardno','type' => 'string','require' => true,'desc' => '付款账号（必填）','default' => '' ),
                'pay_bank' => array('name' => 'pay_bank','type' => 'string','require' => true,'desc' => '付款银行（必填）','default' => '' ),
                'collect_bank' => array('name' => 'collect_bank','type' => 'string','require' => true,'desc' => '收款银行（必填）','default' => '' ),
                'collect_cardno' => array('name' => 'collect_cardno','type' => 'string','require' => true,'desc' => '收款账号（必填）','default' => '' ),         
                'pay_name' => array('name' => 'pay_name','type' => 'string','require' => true,'desc' => '付款名称（必填）','default' => '' ),
                'collect_name' => array('name' => 'collect_name','type' => 'string','require' => true,'desc' => '收款名称（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'invoice' => array('name' => 'invoice','type' => 'int','require' => false,'desc' => '开票类型0未开票1已开票2无需开票（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（不必填）'),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型[0默认1代理2项目]（不必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => false,'desc' => '代理/项目方ID[type12必填]（不必填）','default' => '' ),
            ),
            'PostEditCustomerDeal' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '成交id（必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'min' => 1,'desc' => '客户id（必填）','default' => '' ),
                'cname' => array('name' => 'cname','type' => 'string','require' => true,'desc' => '客户名称（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'deal_id' => array('name' => 'deal_id','type' => 'int','require' => true,'min' => 1,'desc' => '成交用户id（必填）','default' => '' ),
                'structure_id' => array('name' => 'structure_id','type' => 'int','require' => true,'min' => 1,'desc' => '成交部门id（必填）','default' => '' ),
                'general_id' => array('name' => 'general_id','type' => 'int','require' => true,'min' => 1,'desc' => '简章id（必填）','default' => '' ),
                'xuefei' => array('name' => 'xuefei','type' => 'int','require' => false,'desc' => '学费（不必填）','default' => '' ),
                'bm_price' => array('name' => 'bm_price','type' => 'int','require' => false,'desc' => '报名费（不必填）','default' => '' ),
                'zl_price' => array('name' => 'zl_price','type' => 'int','require' => false,'desc' => '资料费（不必填）','default' => '' ),
                'cl_price' => array('name' => 'cl_price','type' => 'int','require' => false,'desc' => '材料费（不必填）','default' => '' ),
                'sq_price' => array('name' => 'sq_price','type' => 'int','require' => false,'desc' => '申请费（不必填）','default' => '' ),
                'yh_price' => array('name' => 'yh_price','type' => 'int','require' => false,'desc' => '优惠费（不必填）','default' => '' ),
                'total' => array('name' => 'total','type' => 'int','require' => true,'desc' => '总费（必填）','default' => '' ),
                'deal_place' => array('name' => 'deal_place','type' => 'string','require' => false,'desc' => '成交地点（不必填）','default' => '' ),
                'collect_time' => array('name' => 'collect_time','type' => 'int','require' => false,'desc' => '收款日期（时间戳）','default' => '' ),
                'deal_time' => array('name' => 'deal_time','type' => 'int','require' => false,'desc' => '成交日期（时间戳）','default' => '' ),
                'pay_cardno' => array('name' => 'pay_cardno','type' => 'string','require' => true,'desc' => '付款账号（必填）','default' => '' ),
                'pay_bank' => array('name' => 'pay_bank','type' => 'string','require' => true,'desc' => '付款银行（必填）','default' => '' ),
                'collect_bank' => array('name' => 'collect_bank','type' => 'string','require' => true,'desc' => '收款银行（必填）','default' => '' ),
                'collect_cardno' => array('name' => 'collect_cardno','type' => 'string','require' => true,'desc' => '收款账号（必填）','default' => '' ),         
                'pay_name' => array('name' => 'pay_name','type' => 'string','require' => true,'desc' => '付款名称（必填）','default' => '' ),
                'collect_name' => array('name' => 'collect_name','type' => 'string','require' => true,'desc' => '收款名称（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'invoice' => array('name' => 'invoice','type' => 'int','require' => false,'desc' => '开票类型0未开票1已开票2无需开票（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（必填）'),
            ),
            'GetCustomerDealInfo' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '成交id（必填）','default' => '' ),
            ),
            'PostChangePerson' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'cid_arr' => array('name' => 'cid_arr','type' => 'array','require' => true,'desc' => '客户数组[key为cid,value为bid]（必填）','default' => '' ),
                'charge_uid' => array('name' => 'charge_uid','type' => 'int','require' => false,'desc' => '跟进人用户id（必填）'),
                'change_uid' => array('name' => 'change_uid','type' => 'int','require' => true,'desc' => '转移用户id（必填）'),
            ),
            'NumberOfLimitlist' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
            ),
            'PostNumberLimit' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'uid_value' => array('name' => 'uid_value','type' => 'array','require' => true,'desc' => '人员uid[key为uid,value为值]（必填）'),
            ),
        );
    }

    /**
     * 01 客户列表接口
     * @author Wang Junzhe
     * @DateTime 2019-08-21T15:59:47+0800
     * @desc 客户列表数据接口（待对接测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
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
     *
     * 
     * @return int total_count.wgx_total 我的未共享
     * 
     * @return string msg 提示信息
     */
    public function GetCustomerList() {
        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'gj_type' => $this->gj_type,
            'day_type' => $this->day_type,
            'yx_type' => $this->yx_type,
            'level_type' => $this->level_type,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'order_by' => $this->order_by,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Customer();
        $info = $domain->GetCustomerList($newData);
        return $info;
    }

    /**
     * 02 删除客户接口
     * @author Wang Junzhe
     * @DateTime 2019-08-21T14:54:01+0800
     * @desc 删除客户数据接口（未完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostDeleteCustomer() {
        $domain = new Domain_Customer();
        $info = $domain->PostDeleteCustomer($this->type,$this->uid,$this->id);
        return $info;
    }

    /**
     * 03 (批量)回归公海
     * @author Wang Junzhe
     * @DateTime 2019-08-26T18:46:46+0800
     * @desc (批量)回归公海数据接口（待对接测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostReturnSea() {
        $domain = new Domain_Customer();
        $info = $domain->PostReturnSea($this->uid,$this->cid_arr);
        return $info;
    }

    /**
     * 04 公海客户列表
     * @author Wang Junzhe
     * @DateTime 2019-08-27T14:33:50+0800
     * @desc 公海客户列表（待对接测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
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
     * 
     */
    public function GetSeaCustomerList() {
        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'order_by' => $this->order_by,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Customer();
        $info = $domain->GetSeaCustomerList($newData);
        return $info;
    }

    /**
     * 05 [批量]共享客户
     * @author Wang Junzhe
     * @DateTime 2019-08-29T17:18:35+0800
     * @desc [批量]共享客户数据接口（待对接测试）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostShareCustomer() {
        $newData = array(
            'type' => $this->type,
            'uid' => $this->uid,
            'cid_arr' => $this->cid_arr,
            'is_leader' => $this->is_leader,
            'share_uid' => $this->share_uid
        );
        $domain = new Domain_Customer();
        $info = $domain->PostShareCustomer($newData);
        return $info;
    }
    
    /**
     * 06 获取客户共享人员列表
     * @author Wang Junzhe
     * @DateTime 2019-08-30T16:22:08+0800
     * @desc 共享人员列表数据接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return array data.user_list 人员列表
     * @return string data.user_name 人员名
     * @return string data.uid 人员ID
     * @return int data.is_click 是否可点[0不可点1可点]
     * 
     * @return int data.role 角色[1创建人2负责人3查看下级0无权]
     * 
     * @return string msg 提示信息
     */
    public function GetShareCustomerPerson() {
        $newData = array(
            'type' => $this->type,
            'uid' => $this->uid,
            'cid' => $this->cid
        );
        $domain = new Domain_Customer();
        $info = $domain->GetShareCustomerPerson($newData);
        return $info;
    }

    /**
     * 07 取消共享
     * @author Wang Junzhe
     * @DateTime 2019-09-02T09:46:03+0800
     * @desc 取消共享数据接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostCancleShare() {
        $newData = array(
            'uid' => $this->uid,
            'cid' => $this->cid
        );
        $domain = new Domain_Customer();
        $info = $domain->PostCancleShare($newData);
        return $info;
    }

    /**
     * 07-1 新取消共享
     * @author Wang Junzhe
     * @DateTime 2020-03-14T10:55:54+0800
     * @desc 新取消共享数据接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostCancleShareNew() {
        $newData = array(
            'uid' => $this->uid,
            'cid' => $this->cid,
            'cancle_uid' => $this->cancle_uid
        );
        $domain = new Domain_Customer();
        $info = $domain->PostCancleShareNew($newData);
        return $info;
    }

    /**
     * 08 分配客户
     * @author Wang Junzhe
     * @DateTime 2019-09-02T11:09:18+0800
     *  @desc 分配客户数据接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostDistribute() {
        $newData = array(
            'uid' => $this->uid,
            'cid_arr' => $this->cid_arr,
            'beshare_uid' => $this->beshare_uid
        );
        $domain = new Domain_Customer();
        $info = $domain->PostDistribute($newData);
        return $info;
    }

    /**
     * 09 领取客户
     * @author Wang Junzhe
     * @DateTime 2019-09-02T11:09:18+0800
     * @desc 领取客户数据接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostReceive() {
        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'cid_arr' => $this->cid_arr
        );
        $domain = new Domain_Customer();
        $info = $domain->PostReceive($newData);
        return $info;
    }

    /**
     * 10 [分配]领取客户列表
     * @author Wang Junzhe
     * @DateTime 2019-09-03T10:38:42+0800
     * @desc 分配/领取客户列表（待对接）
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
     * @return string data_list.types 客户种类
     * @return string data_list.ittnxl 意向学历
     * @return string data_list.labelpeer 标签
     * @return string data_list.timeline 入学时间(时间戳)
     * @return string data_list.xuel 当前学历
     * @return string data_list.traffic 数据来源
     * @return string data_list.channel_level 渠道级别
     * @return string data_list.is_top 是否置顶（1是）
     * @return string data_list.sort 序号
     * @return string data_list.charge_person 负责人
     * @return string data_list.follow_up 跟进次数
     * @return string data_list.follw_time 下次回访时间
     * @return string data_list.sex 性别
     * @return string data_list.cphone 联系电话
     * @return string data_list.wxnum 微信号
     * 
     * @return int data.data_num 客户数量
     */
    public function GetMySeaCustomerList() {
        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'order_by' => $this->order_by,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Customer();
        $info = $domain->GetMySeaCustomerList($newData);
        return $info;
    }

    /**
     * 11 客户成交新增
     * @author Wang Junzhe
     * @DateTime 2019-09-05T13:51:58+0800
     * @desc 提交客户成交接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostAddCustomerDeal() {
        $newData = array(
            'cid' => $this->cid,
            'cname' => $this->cname,
            'uid' => $this->uid,
            'deal_id' => $this->deal_id,
            'structure_id' => $this->structure_id,
            'general_id' => $this->general_id,
            'xuefei' => $this->xuefei,
            'bm_price' => $this->bm_price,
            'zl_price' => $this->zl_price,
            'cl_price' => $this->cl_price,
            'sq_price' => $this->sq_price,
            'yh_price' => $this->yh_price,
            'total' => $this->total,
            'deal_place' => $this->deal_place,
            'collect_time' => $this->collect_time,
            'deal_time' => $this->deal_time,
            'pay_cardno' => $this->pay_cardno,
            'pay_bank' => $this->pay_bank,
            'collect_bank' => $this->collect_bank,
            'collect_cardno' => $this->collect_cardno,
            'pay_name' => $this->pay_name,
            'collect_name' => $this->collect_name,
            'note' => $this->note,
            'file' => $this->file,
            'invoice' => $this->invoice
        );
        $domain = new Domain_Customer();
        $info = $domain->PostAddCustomerDeal($newData,$this->field_list,$this->type,$this->agent_id);
        return $info;
    }

    /**
     * 12 客户成交编辑
     * @author Wang Junzhe
     * @DateTime 2019-09-05T16:15:53+0800
     * @desc 客户成交编辑接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEditCustomerDeal() {
        $newData = array(
            'cid' => $this->cid,
            'cname' => $this->cname,
            'uid' => $this->uid,
            'deal_id' => $this->deal_id,
            'structure_id' => $this->structure_id,
            'general_id' => $this->general_id,
            'xuefei' => $this->xuefei,
            'bm_price' => $this->bm_price,
            'zl_price' => $this->zl_price,
            'cl_price' => $this->cl_price,
            'sq_price' => $this->sq_price,
            'yh_price' => $this->yh_price,
            'total' => $this->total,
            'deal_place' => $this->deal_place,
            'collect_time' => $this->collect_time,
            'deal_time' => $this->deal_time,
            'pay_cardno' => $this->pay_cardno,
            'pay_bank' => $this->pay_bank,
            'collect_bank' => $this->collect_bank,
            'collect_cardno' => $this->collect_cardno,
            'pay_name' => $this->pay_name,
            'collect_name' => $this->collect_name,
            'note' => $this->note,
            'file' => $this->file,
            'invoice' => $this->invoice
        );
        $domain = new Domain_Customer();
        $info = $domain->PostEditCustomerDeal($this->id,$newData,$this->field_list);
        return $info;
    }

    /**
     * 13 成交详情
     * @author Wang Junzhe
     * @DateTime 2019-09-05T16:25:57+0800
     * @desc 成交详情接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 成交信息ID
     * @return string data.id 成交信息ID
     * @return string data.title 成交简章名称
     * @return string data.order_no 成交编号
     * @return string data.cid 客户id
     * @return string data.cname 客户名称
     * @return string data.uid 用户ID
     * @return string data.deal_id 成交用户ID
     * @return string data.structure_id 成交部门ID
     * @return string data.general_id 成交简章ID
     * @return string data.xuefei 学费
     * @return string data.bm_price 报名费
     * @return string data.zl_price 资料费
     * @return string data.cl_price 材料费
     * @return string data.sq_price 申请费
     * @return string data.yh_price 优惠费
     * @return string data.total 总额
     * @return string data.status 状态[2为已退款]
     * @return string data.sort 序号
     * @return string data.publisher 发布人
     * @return string data.addtime 添加时间[时间戳]
     * @return string data.collect_type 收款方式
     * @return string data.collect_nature 收款性质
     * @return string data.deal_place 成交地点
     * @return string data.collect_time 收款日期
     * @return string data.deal_time 成交日期
     * @return string data.pay_bank 付款银行
     * @return string data.collect_bank 收款银行
     * @return string data.pay_cardno 付款账号
     * @return string data.collect_cardno 收款账号
     * @return string data.pay_name 付款账户
     * @return string data.collect_name 收款账户
     * @return string data.note 备注
     * @return string data.file 附件
     * @return string data.invoice 开票状态[0未开票1已开票2无需开票]
     * 
     * @return string msg 提示信息
     */
    public function GetCustomerDealInfo() {
        $newData = array(
            'uid' => $this->uid,
            'id' => $this->id
        );
        $domain = new Domain_Customer();
        $info = $domain->GetCustomerDealInfo($newData);
        return $info;
    }

    /**
     * 14 转移客户
     * @author Wang Junzhe
     * @DateTime 2020-04-24T10:54:03+0800
     * @desc 转移客户接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     * 
     */
    public function PostChangePerson() {
        $newData = array(
            'uid' => $this->uid,
            'cid_arr' => $this->cid_arr,
            'charge_uid' => $this->charge_uid,
            'change_uid' => $this->change_uid
        );
        $domain = new Domain_Customer();
        $info = $domain->PostChangePerson($newData);
        return $info;
    }

    /**
     * 15 私海数量限制列表
     * @author Wang Junzhe
     * @DateTime 2020-04-26T09:51:37+0800
     * @desc 私海数量限制列表（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 部门id
     * @return string data.name 部门名称
     * @return int data.pid 父级部门id
     * @return int data.level 层级
     *
     * @return array member_list 人员列表
     * @return int member_list.id 人员id[管理员为id其他为json字符串]
     * @return string data.realname 人员名称
     * @return int member_list.setlimit 限制数量
     *
     * @return array children 子集部门 
     * @return int children.id 部门id
     * @return string children.name 部门名称
     * @return int children.pid 父级部门id
     * @return int children.level 层级
     * 
     * @return string msg 提示信息
     */
    public function NumberOfLimitlist() {
        $newData = array(
            'uid' => $this->uid
        );
        $domain = new Domain_Customer();
        $info = $domain->NumberOfLimitlist($newData);
        return $info;
    }

    /**
     * 16 私海数量限制设置
     * @author Wang Junzhe
     * @DateTime 2020-04-27T09:28:21+0800
     * @desc 私海数量限制设置接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostNumberLimit() {
        $newData = array(
            'uid' => $this->uid,
            'uid_value' => $this->uid_value
        );
        $domain = new Domain_Customer();
        $info = $domain->PostNumberLimit($newData);
        return $info;
    }
    
}
 
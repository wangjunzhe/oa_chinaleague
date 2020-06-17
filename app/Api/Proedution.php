<?php
namespace App\Api;
use PhalApi\Api;
use App\Domain\Proedution as Domain_Proedution;

/**
 * 一手方项目接口
 * @author: 小明 <2669750716@qq.com> 2019-09-010
 */
class Proedution extends Api {
    public function getRules() {
        return array(
            'PostProjectAdd' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'title' => array('name' => 'title','type' => 'string','require' => true,'desc' => '名称（必填）','default' => '' ),
                'structure_id' => array('name' => 'structure_id','type' => 'string','require' => true,'desc' => '部门','default' => '6,2' ),
                'flower_name' => array('name' => 'flower_name','type' => 'string','require' => true,'desc' => '花名（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => true,'desc' => '类型0公司1个人（必填）','default' => '' ),
                'company' => array('name' => 'company','type' => 'string','require' => false,'desc' => '所属公司（不必填）','default' => '' ),
                'address' => array('name' => 'address','type' => 'string','require' => false,'desc' => '经营地址（不必填）','default' => '' ),
                'web_site' => array('name' => 'web_site','type' => 'string','require' => false,'desc' => '网址（不必填）','default' => '' ),
                'full_name' => array('name' => 'full_name','type' => 'string','require' => true,'desc' => '联系人（必填）','default' => '' ),
                'mobile' => array('name' => 'mobile','type' => 'string','require' => true,'desc' => '手机号（必填）','default' => '' ),
                'weixin' => array('name' => 'weixin','type' => 'string','require' => false,'desc' => '微信（不必填）','default' => '' ),
                'qq' => array('name' => 'qq','type' => 'string','require' => false,'desc' => 'QQ（不必填）','default' => '' ),
                'email' => array('name' => 'email','type' => 'string','require' => false,'desc' => '邮箱（不必填）','default' => '' ),
                'services' => array('name' => 'services','type' => 'string','require' => false,'desc' => '负责业务（不必填）','default' => '' ),
                'birthday' => array('name' => 'birthday','type' => 'date','require' => false,'desc' => '生日[非时间戳]（不必填）','default' => '' ),
                'sex' => array('name' => 'sex','type' => 'int','require' => false,'desc' => '性别0男1女（不必填）','default' => '' ),
                'img' => array('name' => 'img','type' => 'string','require' => false,'desc' => '头像（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
                'invoice_company' => array('name' => 'invoice_company','type' => 'string','require' => false,'desc' => '开票公司（不必填）','default' => '' ),
                'identifier' => array('name' => 'identifier','type' => 'string','require' => true,'desc' => '识别号（不必填）','default' => '' ),
                'bank' => array('name' => 'bank','type' => 'string','require' => false,'desc' => '开户行（不必填）','default' => '' ),
                'bank_name' => array('name' => 'bank_name','type' => 'string','require' => false,'desc' => '开户行名称（不必填）','default' => '' ),
                'card_no' => array('name' => 'card_no','type' => 'string','require' => false,'desc' => '卡号（不必填）','default' => '' ),
                'bank_address' => array('name' => 'bank_address','type' => 'string','require' => false,'desc' => '开户行地址（不必填）','default' => '' ),
                'legal_person' => array('name' => 'legal_person','type' => 'string','require' => false,'desc' => '法人（不必填）','default' => '' ),
                'business_file' => array('name' => 'business_file','type' => 'string','require' => false,'desc' => '营业执照附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => false, 'desc' => '灵活字段（不必填）','default' => array()),
            ),
            'PostProjectEdit' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
                'title' => array('name' => 'title','type' => 'string','require' => true,'desc' => '名称（必填）','default' => '' ),
                'flower_name' => array('name' => 'flower_name','type' => 'string','require' => true,'desc' => '花名（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => true,'desc' => '类型0公司1个人（必填）','default' => '' ),
                'company' => array('name' => 'company','type' => 'string','require' => false,'desc' => '所属公司（不必填）','default' => '' ),
                'address' => array('name' => 'address','type' => 'string','require' => false,'desc' => '经营地址（不必填）','default' => '' ),
                'web_site' => array('name' => 'web_site','type' => 'string','require' => false,'desc' => '网址（不必填）','default' => '' ),
                'structure_id' => array('name' => 'structure_id','type' => 'string','require' => false,'desc' => '部门 （不必填）','default' => '' ),
                'full_name' => array('name' => 'full_name','type' => 'string','require' => true,'desc' => '联系人（必填）','default' => '' ),
                'mobile' => array('name' => 'mobile','type' => 'string','require' => true,'desc' => '手机号（必填）','default' => '' ),
                'weixin' => array('name' => 'weixin','type' => 'string','require' => false,'desc' => '微信（不必填）','default' => '' ),
                'qq' => array('name' => 'qq','type' => 'string','require' => false,'desc' => 'QQ（不必填）','default' => '' ),
                'email' => array('name' => 'email','type' => 'string','require' => false,'desc' => '邮箱（不必填）','default' => '' ),
                'services' => array('name' => 'services','type' => 'string','require' => false,'desc' => '负责业务（不必填）','default' => '' ),
                'birthday' => array('name' => 'birthday','type' => 'date','require' => true,'desc' => '生日（必填）','default' => '' ),
                'sex' => array('name' => 'sex','type' => 'int','require' => false,'desc' => '性别0男1女（不必填）','default' => '' ),
                'img' => array('name' => 'img','type' => 'string','require' => false,'desc' => '头像（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
                'invoice_company' => array('name' => 'invoice_company','type' => 'string','require' => false,'desc' => '开票公司（不必填）','default' => '' ),
                'identifier' => array('name' => 'identifier','type' => 'string','require' => true,'desc' => '识别号（不必填）','default' => '' ),
                'bank' => array('name' => 'bank','type' => 'string','require' => false,'desc' => '开户行（不必填）','default' => '' ),
                'bank_name' => array('name' => 'bank_name','type' => 'string','require' => false,'desc' => '开户行名称（不必填）','default' => '' ),
                'card_no' => array('name' => 'card_no','type' => 'string','require' => false,'desc' => '卡号（不必填）','default' => '' ),
                'bank_address' => array('name' => 'bank_address','type' => 'string','require' => false,'desc' => '开户行地址（不必填）','default' => '' ),
                'legal_person' => array('name' => 'legal_person','type' => 'string','require' => false,'desc' => '法人（不必填）','default' => '' ),
                'business_file' => array('name' => 'business_file','type' => 'string','require' => false,'desc' => '营业执照附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（不必填）','default' => array()),
            ),
            'PostProjectList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型0公司1个人2全部（不必填）','default' => '' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'advanced_search' => array('name' => 'advanced_search', 'type' => 'array','require' => false, 'desc' => '高级字段搜索（不必填）' ),
                'follow_type' => array('name' => 'follow_type','type' => 'int','require' => false,'desc' => '跟进类型0全部1应联系2新增...8-90天未跟进（不必填）','default' => '' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostProjectDele'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '当前一手方id（必填）','default' => '' ),
            ),
            'PostProjectShare'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
                'share_uid' => array('name' => 'share_uid','type' => 'array','require' => true,'desc' => '用户id[数组]（必填）','default' => array(11) ),
            ),
            'PostProjectMove'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'mid' => array('name' => 'mid','type' => 'int','require' => true,'min' => 1,'desc' => '移交人id(单个)','default' => '' ),
                'share_uid' => array('name' => 'share_uid','type' => 'array','require' => true,'desc' => '移交项目方id[数组][批量]（必填）','default' => array(11) ),
            ),
            'GetProjectInfo' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '项目方id（必填）','default' => '' ),
            ),
            'PostContractAdd' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
                'title' => array('name' => 'title','type' => 'string','require' => true,'desc' => '代理商名称（必填）','default' => '' ),
                'signing_company' => array('name' => 'signing_company','type' => 'string','require' => true,'desc' => '签约公司名称（必填）','default' => '' ),
                'siging_person' => array('name' => 'siging_person','type' => 'string','require' => true,'desc' => '一手项目方签约人（必填）','default' => '' ),
                'siging_address' => array('name' => 'siging_address','type' => 'string','require' => true,'desc' => '一手项目方地址（必填）','default' => '' ),
                'siging_phone' => array('name' => 'siging_phone','type' => 'string','require' => true,'desc' => '一手项目方手机号（必填）','default' => '' ),
                'siging_email' => array('name' => 'siging_email','type' => 'string','require' => false,'desc' => '一手项目方email（不必填）','default' => '' ),
                'person' => array('name' => 'person','type' => 'string','require' => true,'desc' => '我方签约人（必填）','default' => '' ),
                'address' => array('name' => 'address','type' => 'string','require' => true,'desc' => '我方地点（必填）','default' => '' ),
                'phone' => array('name' => 'phone','type' => 'string','require' => true,'desc' => '我方手机号（必填）','default' => '' ),
                'email' => array('name' => 'email','type' => 'string','require' => false,'desc' => '我方email（不必填）','default' => '' ),
                'contract_status' => array('name' => 'contract_status','type' => 'int','require' => true,'desc' => '状态[0中1终止2意外终止]（必填）','default' => '' ),
                'start_time' => array('name' => 'start_time','type' => 'date','require' => true,'desc' => '开始时间（必填）','default' => '' ),
                'end_time' => array('name' => 'end_time','type' => 'date','require' => true,'desc' => '结束时间（必填）','default' => '' ),
                'addtime' => array('name' => 'addtime','type' => 'string','require' => false,'desc' => '签约时间','default' => '' ),
                'money' => array('name' => 'money','type' => 'int','require' => false,'desc' => '合同金额（不必填）','default' => '' ),
                'siging_area' => array('name' => 'siging_area','type' => 'string','require' => false,'desc' => '签约地点（不必填）','default' => '' ),
                'clause' => array('name' => 'clause','type' => 'string','require' => false,'desc' => '签约条款（不必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（不必填）','default' => array()),
                'general_list' => array('name' => 'general_list', 'type' => 'array','require' => false, 'desc' => '签约项目（不必填）','default' => array()),
            ),
            'PostStudentList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => false,'desc' => '一手项目方id（详情必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => false,'desc' => '学员id（非必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型0全部1成交2今日成交3本周成交4本月成交（不必填）','default' => '' ),
                'start_time' => array('name' => 'start_time','type' => 'int','require' => false,'desc' => '开始时间','default' => '' ),
                'end_time' => array('name' => 'end_time','type' => 'int','require' => false,'desc' => '结束时间（不必填）','default' => '' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostContractEdit' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
                'contract_id' => array('name' => 'contract_id','type' => 'int','require' => true,'min' => 1,'desc' => '合同id（必填）','default' => '' ),
                'money' => array('name' => 'money','type' => 'int','require' => true,'desc' => '原金额（必填）','default' => '' ),
                'new_money' => array('name' => 'new_money','type' => 'int','require' => true,'desc' => '新金额（必填）','default' => '' ),
                'adjust_money' => array('name' => 'adjust_money','type' => 'int','require' => true,'desc' => '调整金额（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'general_list' => array('name' => 'general_list', 'type' => 'array','require' => false, 'desc' => '签约项目（不必填）','default' => array()),
            ),
            'PostContractAdgustList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
            ),
            'PostContractInfo'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '合同id（必填）','default' => '' ),
            ),
            'GetProjectContrat'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),

            ),
            'PostContractMback'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
                'contract_id' => array('name' => 'contract_id','type' => 'int','require' => true,'min' => 1,'desc' => '合同id（必填）','default' => '' ),
                'deal_uid' => array('name' => 'deal_uid','type' => 'int','require' => true,'desc' => '成交学员id（必填）','default' => '' ),
                'order_no' => array('name' => 'order_no','type' => 'string','require' => true,'desc' => '成交编号（必填）','default' => '' ),
                'invoice_state' => array('name' => 'invoice_state','type' => 'int','require' => true,'desc' => '开票状态[0未1已2无需]（必填）','default' => '' ),
                'pay_money' => array('name' => 'pay_money','type' => 'int','require' => true,'desc' => '付款金额（必填）','default' => '' ),
                'pay_bank' => array('name' => 'pay_bank','type' => 'string','require' => true,'desc' => '付款银行（必填）','default' => '' ),
                'pay_bank_no' => array('name' => 'pay_bank_no','type' => 'string','require' => true,'desc' => '付款账号（必填）','default' => '' ),
                'pay_user' => array('name' => 'pay_user','type' => 'string','require' => true,'desc' => '付款户名（必填）','default' => '' ),
                'may_pay_date' => array('name' => 'may_pay_date','type' => 'date','require' => true,'desc' => '预回款日期（必填）','default' => '' ),
                'pay_date' => array('name' => 'pay_date','type' => 'date','require' => false,'desc' => '实际回款日期（选填）','default' => '' ),
                'notice' => array('name' => 'notice','type' => 'string','require' => false,'desc' => '款项说明（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'pay_state' => array('name' => 'pay_state','type' => 'int','require' => true,'desc' => '付款状态[0未1已]（必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（不必填）','default' => array()),
            ),
            'PostContractMedit'=>array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '回款记录id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '项目方id（必填）','default' => '' ),
                'contract_id' => array('name' => 'contract_id','type' => 'int','require' => true,'min' => 1,'desc' => '合同id（必填）','default' => '' ),
                'deal_uid' => array('name' => 'deal_uid','type' => 'int','require' => true,'desc' => '成交学员id（必填）','default' => '' ),
                'order_no' => array('name' => 'order_no','type' => 'string','require' => true,'desc' => '成交编号（必填）','default' => '' ),
                'invoice_state' => array('name' => 'invoice_state','type' => 'int','require' => true,'desc' => '开票状态[0未1已2无需]（必填）','default' => '' ),
                'pay_money' => array('name' => 'pay_money','type' => 'int','require' => true,'desc' => '付款金额（必填）','default' => '' ),
                'pay_bank' => array('name' => 'pay_bank','type' => 'string','require' => true,'desc' => '付款银行（必填）','default' => '' ),
                'pay_bank_no' => array('name' => 'pay_bank_no','type' => 'string','require' => true,'desc' => '付款账号（必填）','default' => '' ),
                'pay_user' => array('name' => 'pay_user','type' => 'string','require' => true,'desc' => '付款户名（必填）','default' => '' ),
                'may_pay_date' => array('name' => 'may_pay_date','type' => 'date','require' => true,'desc' => '预回款日期（必填）','default' => '' ),
                'pay_date' => array('name' => 'pay_date','type' => 'date','require' => false,'desc' => '实际回款日期（选填）','default' => '' ),
                'notice' => array('name' => 'notice','type' => 'string','require' => false,'desc' => '款项说明（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'pay_state' => array('name' => 'pay_state','type' => 'int','require' => true,'desc' => '付款状态[0未1已]（必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（不必填）','default' => array()),
            ),
            'PostContractMdele'=>array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '回款记录id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '项目方id（必填）','default' => '' ),
            ),
            'PostContractMinfo'=>array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '回款记录id（必填）','default' => '' ),
            ),
            'GetContractProjectInfo' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '调整合同id（必填）','default' => '' ),
                'contract_id' => array('name' => 'contract_id','type' => 'int','require' => true,'min' => 1,'desc' => '合同id（必填）','default' => '' ),
            ),
            'PostContractInvoiceAdd'=>array(
                "cid"=>array('name'=>'cid','type'=>"int",'require'=>true,'desc'=>'学员名称','default'=>''),
                "uid"=>array('name'=>'uid','type'=>"int",'require'=>true,'desc'=>'当前用户id','default'=>''),
                "number"=>array('name'=>"number",'type'=>"string",'require'=>false,'desc'=>'成交编号','default'=>''),
                "project_id"=>array('name'=>"project_id",'type'=>"int",'require'=>false,'desc'=>'项目id','default'=>''),
                "contrat_id"=>array('name'=>"contrat_id",'type'=>"int",'require'=>true,'desc'=>'合同id','default'=>''),
                "status"=>array('name'=>"status",'type'=>"int",'require'=>false,'desc'=>'开票状态0未开1已开2无需开','default'=>''),
                "content"=>array('name'=>"content",'type'=>"string",'require'=>true,'desc'=>'开票内容','default'=>''),
                "refund_price"=>array('name'=>"refund_price",'type'=>"string",'require'=>true,'desc'=>'开票金额','default'=>''),
                "refund_type"=>array('name'=>"refund_type",'type'=>"string",'require'=>true,'desc'=>'开票类型','default'=>''),
                "refund_time"=>array('name'=>"refund_time",'type'=>"string",'require'=>false,'desc'=>'开票时间','default'=>''),
                "refund_company"=>array('name'=>"refund_company",'type'=>"string",'require'=>true,'desc'=>'出票公司','default'=>''),
                "refund_num"=>array('name'=>"refund_num",'type'=>"string",'require'=>false,'desc'=>'纳税人识别号','default'=>''),
                "refund_bank"=>array('name'=>"refund_bank",'type'=>"string",'require'=>false,'desc'=>'开户银行','default'=>''),
                "refund_name"=>array('name'=>"refund_name",'type'=>"string",'require'=>false,'desc'=>'开户名称','default'=>''),
                "refund_bank_num"=>array('name'=>"refund_bank_num",'type'=>"string",'require'=>false,'desc'=>'开户银行账号','default'=>''),
                "refund_bank_adress"=>array('name'=>"refund_bank_adress",'type'=>"string",'require'=>false,'desc'=>'开户银行地址','default'=>''),
                "refund_phone"=>array('name'=> "refund_phone",'type'=>"string",'require'=>false,'desc'=>'联系电话','default'=>''),
                "shou_company"=>array('name'=>"shou_company",'type'=>"string",'require'=>true,'desc'=>'收票公司','default'=>''),
                "shou_num"=>array('name'=>"shou_num",'type'=>"string",'require'=>true,'desc'=>'纳税人识别号','default'=>''),
                "shou_bank"=>array('name'=>"shou_bank",'type'=>"string",'require'=>false,'desc'=>'开户银行','default'=>''),
                "shou_name"=>array('name'=>"shou_name",'type'=>"string",'require'=>false,'desc'=>'开户名称','default'=>''),
                "shou_bank_num"=>array('name'=>"shou_bank_num",'type'=>"string",'require'=>false,'desc'=>'账号','default'=>''),
                "shou_bank_adress"=>array('name'=>"shou_bank_adress",'type'=>"string",'require'=>false,'desc'=>'开户地址','default'=>''),
                "shou_phone"=>array('name'=>"shou_phone",'type'=>"string",'require'=>false,'desc'=>'收票联系电话','default'=>''),
                "beizhu"=>array('name'=>"beizhu",'type'=>"string",'require'=>false,'desc'=>'备注','default'=>''),
                "note"=>array('name'=>"note",'type'=>"string",'require'=>false,'desc'=>'附件','default'=>''),
            ),
            'GetProjectGeneralList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '项目方id（必填）','default' => '' ),
            ),
            'PostContractInvoiceEdit'=>array(
                "id"=>array('name'=>'cid','type'=>"int",'require'=>true,'desc'=>'开票id','default'=>''),
                "uid"=>array('name'=>'uid','type'=>"int",'require'=>true,'desc'=>'当前用户id','default'=>''),
                "project_id"=>array('name'=>"project_id",'type'=>"int",'require'=>false,'desc'=>'项目id','default'=>''),
                "contrat_id"=>array('name'=>"contrat_id",'type'=>"int",'require'=>false,'desc'=>'合同id','default'=>''),
                "status"=>array('name'=>"status",'type'=>"int",'require'=>false,'desc'=>'开票状态0未开1已开2无需开','default'=>''),
                "content"=>array('name'=>"content",'type'=>"string",'require'=>true,'desc'=>'开票内容','default'=>''),
                "refund_price"=>array('name'=>"refund_price",'type'=>"string",'require'=>true,'desc'=>'开票金额','default'=>''),
                "refund_type"=>array('name'=>"refund_type",'type'=>"string",'require'=>true,'desc'=>'开票类型','default'=>''),
                "refund_time"=>array('name'=>"refund_time",'type'=>"string",'require'=>false,'desc'=>'开票时间','default'=>''),
                "refund_company"=>array('name'=>"refund_company",'type'=>"string",'require'=>true,'desc'=>'出票公司','default'=>''),
                "refund_num"=>array('name'=>"refund_num",'type'=>"string",'require'=>false,'desc'=>'纳税人识别号','default'=>''),
                "refund_bank"=>array('name'=>"refund_bank",'type'=>"string",'require'=>false,'desc'=>'开户银行','default'=>''),
                "refund_name"=>array('name'=>"refund_name",'type'=>"string",'require'=>false,'desc'=>'开户名称','default'=>''),
                "refund_bank_num"=>array('name'=>"refund_bank_num",'type'=>"string",'require'=>false,'desc'=>'开户银行账号','default'=>''),
                "refund_bank_adress"=>array('name'=>"refund_bank_adress",'type'=>"string",'require'=>false,'desc'=>'开户银行地址','default'=>''),
                "refund_phone"=>array('name'=> "refund_phone",'type'=>"string",'require'=>false,'desc'=>'联系电话','default'=>''),
                "shou_company"=>array('name'=>"shou_company",'type'=>"string",'require'=>true,'desc'=>'收票公司','default'=>''),
                "shou_num"=>array('name'=>"shou_num",'type'=>"string",'require'=>true,'desc'=>'纳税人识别号','default'=>''),
                "shou_bank"=>array('name'=>"shou_bank",'type'=>"string",'require'=>false,'desc'=>'开户银行','default'=>''),
                "shou_name"=>array('name'=>"shou_name",'type'=>"string",'require'=>false,'desc'=>'开户名称','default'=>''),
                "shou_bank_num"=>array('name'=>"shou_bank_num",'type'=>"string",'require'=>false,'desc'=>'账号','default'=>''),
                "shou_bank_adress"=>array('name'=>"shou_bank_adress",'type'=>"string",'require'=>false,'desc'=>'开户地址','default'=>''),
                "shou_phone"=>array('name'=>"shou_phone",'type'=>"string",'require'=>false,'desc'=>'收票联系电话','default'=>''),
                "beizhu"=>array('name'=>"beizhu",'type'=>"string",'require'=>false,'desc'=>'备注','default'=>''),
                "note"=>array('name'=>"note",'type'=>"string",'require'=>false,'desc'=>'附件','default'=>''),
            ),
            'PostContractRefundAdd' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
                'payment_number' => array('name' => 'payment_number','type' => 'string','require' => true,'desc' => '付款编号（必填）','default' => '' ),
                'reason' => array('name' => 'reason','type' => 'string','require' => true,'desc' => '退款原因（必填）','default' => '' ),
                'pay_user' => array('name' => 'pay_user','type' => 'string','require' => true,'desc' => '开户名（必填）','default' => '' ),
                'bank_name' => array('name' => 'bank_name','type' => 'string','require' => true,'desc' => '开户行（必填）','default' => '' ),
                'bank_no' => array('name' => 'bank_no','type' => 'string','require' => true,'desc' => '银行账号（必填）','default' => '' ),
                'refund_date' => array('name' => 'refund_date','type' => 'date','require' => true,'desc' => '退款日期（必填）','default' => '' ),
                'refund_money' => array('name' => 'refund_money','type' => 'int','require' => true,'desc' => '退款金额（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '款项说明（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（必填）','default' => array()),
            ),
            'PostContractRefundEdit' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '退款id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
                'payment_number' => array('name' => 'payment_number','type' => 'string','require' => true,'desc' => '付款编号（必填）','default' => '' ),
                'reason' => array('name' => 'reason','type' => 'string','require' => true,'desc' => '退款原因（必填）','default' => '' ),
                'pay_user' => array('name' => 'pay_user','type' => 'string','require' => true,'desc' => '开户名（必填）','default' => '' ),
                'bank_name' => array('name' => 'bank_name','type' => 'string','require' => true,'desc' => '开户行（必填）','default' => '' ),
                'bank_no' => array('name' => 'bank_no','type' => 'string','require' => true,'desc' => '银行账号（必填）','default' => '' ),
                'refund_date' => array('name' => 'refund_date','type' => 'date','require' => true,'desc' => '退款日期（必填）','default' => '' ),
                'refund_money' => array('name' => 'refund_money','type' => 'int','require' => true,'desc' => '退款金额（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '款项说明（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（必填）','default' => array()),
            ),
            'GetProjectRefundList' => array(
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
            ),
            'GetProjectInvoiceList' => array(
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
            ),
            'GetProjectRefundInfo' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '退款id（必填）','default' => '' ),
            ),
            'PostContractInvoiceInfo' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '开票id（必填）','default' => '' ),
            ),
            'GetProjectPaymentList' => array(
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '一手项目方id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '0未回款1已回款2已退款（必填）','default' => '' ),
            ),
            'PostContractList'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'project_id' => array('name' => 'project_id','type' => 'int','require' => true,'min' => 1,'desc' => '项目方id（必填）','default' => '' ),
            ),
        );
    }

    /**
     * 01 一手项目方-新增
     * @desc 一手项目方-新增接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostProjectAdd() {
        $newData = array(
            'title' => $this->title,
            'structure_id' => $this->structure_id,
            'flower_name' => $this->flower_name,
            'type' => $this->type,
            'company' => $this->company,
            'address' => $this->address,
            'web_site' => $this->web_site,
            'full_name' => $this->full_name,
            'mobile' => $this->mobile,
            'weixin' => $this->weixin,
            'qq' => $this->qq,
            'email' => $this->email,
            'service' => $this->services,
            'birthday' => $this->birthday,
            'sex' => $this->sex,
            'img' => $this->img,
            'file' => $this->file,
            'note' => $this->note,
            'invoice_company' => $this->invoice_company,
            'identifier' => $this->identifier,
            'bank' => $this->bank,
            'bank_name' => $this->bank_name,
            'card_no' => $this->card_no,
            'bank_address' => $this->bank_address,
            'legal_person' => $this->legal_person,
            'business_file' => $this->business_file,
            'legal_person' => $this->legal_person
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostProjectAdd($this->uid,$newData,$this->field_list);
        return $info;
    }

    /**
     * 02-一手项目方-编辑
     * @author Dai Ming
     * @DateTime 16:31 2019/9/10 0010
     * @desc 一手项目方编辑  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function  PostProjectEdit(){
        $newData = array(
            'title' => $this->title,
            'flower_name' => $this->flower_name,
            'type' => $this->type,
            'company' => $this->company,
            'address' => $this->address,
            'structure_id' => $this->structure_id,
            'web_site' => $this->web_site,
            'full_name' => $this->full_name,
            'mobile' => $this->mobile,
            'weixin' => $this->weixin,
            'qq' => $this->qq,
            'email' => $this->email,
            'service' => $this->services,
            'birthday' => $this->birthday,
            'sex' => $this->sex,
            'img' => $this->img,
            'file' => $this->file,
            'note' => $this->note,
            'invoice_company' => $this->invoice_company,
            'identifier' => $this->identifier,
            'bank' => $this->bank,
            'bank_name' => $this->bank_name,
            'card_no' => $this->card_no,
            'bank_address' => $this->bank_address,
            'legal_person' => $this->legal_person,
            'business_file' => $this->business_file,
            'legal_person' => $this->legal_person
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostProjectEdit($this->uid,$this->id,$newData,$this->field_list);
        return $info;
    }

    /**
     * 03-一手项目方-删除
     * @author Dai Ming
     * @DateTime 16:31 2019/9/10 0010
     * @desc 一手项目方删除  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function  PostProjectDele(){
        $domain = new Domain_Proedution();
        $info = $domain->PostProjectDele($this->uid,$this->id);
        return $info;

    }

    /**
     * 04-一手项目方-列表
     * @author Dai Ming
     * @DateTime 16:31 2019/9/10 0010
     * @desc 一手项目方列表  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * @return int data.company_num 公司个数
     * @return int data.person_num 个人个数
     *
     * @return array data.project_list 一手项目方列表
     * @return int project_list.id 一手项目方ID
     * @return string project_list.number 一手项目方编号
     * @return string project_list.title 一手项目方名称
     * @return string project_list.flower_name 一手项目方花名
     * @return string project_list.type 一手项目方类型[0公司1个人]
     * @return string project_list.group 一手项目方分组
     * @return string project_list.company 所属公司
     * @return string project_list.address 经营地址
     * @return string project_list.web_site 网址
     * @return string project_list.full_name 联系人
     * @return string project_list.mobile 手机号
     * @return string project_list.weixin 微信
     * @return string project_list.qq QQ
     * @return string project_list.email 邮箱
     * @return string project_list.service 负责业务
     * @return string project_list.birthday 生日
     * @return string project_list.sex 性别
     * @return string project_list.img 头像
     * @return string project_list.file 附件
     * @return string project_list.note 备注
     * @return string project_list.invoice_company 开票公司
     * @return string project_list.identifier 识别号
     * @return string project_list.bank 开户行
     * @return string project_list.bank_name 银行名称
     * @return string project_list.card_no 卡号
     * @return string project_list.bank_address 银行地址
     * @return string project_list.legal_person 法人
     * @return string project_list.business_file 营业执照
     * @return string project_list.publisher 发布者
     * @return string project_list.charge_person 负责人
     * @return string project_list.sort 序号
     * @return string project_list.addtime 添加时间[时间戳]
     *
     * @return int project_list.stu_total_num 总学员
     * @return int project_list.money_total 总回款费用
     * @return int project_list.yh_money_total 已回款金额
     * @return int project_list.dh_money_total 待回款金额
     *
     * @return int data.project_num 一手项目方数量
     *
     * @return string msg 提示信息
     */
    public function  PostProjectList(){
        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'keywords' => $this->keywords,
            'advanced_search' => $this->advanced_search,
            'follow_type' => $this->follow_type,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostProjectList($newData);
        return $info;
    }

    /**
     * 05-一手项目方-共享
     * @author Dai Ming
     * @DateTime 16:31 2019/9/10 0010
     * @desc 一手项目方分享   （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function  PostProjectShare(){
        $newData = array(
            'uid' => $this->uid,
            'id' => $this->id,
            'share_uid' => $this->share_uid
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostProjectShare($newData);
        return $info;
    }
    /**
     * 06-一手项目方-移交
     * @author Dai Ming
     * @DateTime 10:46 2019/9/11 0011
     * @desc 一手项目方-移交  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function  PostProjectMove(){
        $newData = array(
            'uid' => $this->uid,
            'mid' => $this->mid,
            'share_uid' => $this->share_uid
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostProjectMove($newData);
        return $info;
    }



    /**
     * 26-一手项目方详情
     * @author Dai Ming
     * @DateTime 17:07 2019/9/18 0018
     * @desc  一手项目方详情  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetProjectInfo()
    {
        $domain = new Domain_Proedution();
        $info = $domain->GetProjectInfo($this->uid,$this->id);
        return $info;

    }

    /**
     * 08-合同新建
     * @author Dai Ming
     * @DateTime 10:57 2019/9/11 0011
     * @desc  合同新建  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractAdd(){
        $newData = array(
            'project_id' => $this->project_id,
            'title' => $this->title,
            'signing_company' => $this->signing_company,
            'siging_person' => $this->siging_person,
            'siging_address' => $this->siging_address,
            'siging_phone' => $this->siging_phone,
            'siging_email' => $this->siging_email,
            'person' => $this->person,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'end_time' => $this->end_time,
            'start_time' => $this->start_time,
            'addtime' => $this->addtime,
            'money' => $this->money,
            'siging_area' => $this->siging_area,
            'clause' => $this->clause,
            'note' => $this->note,
            'file' => $this->file,
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostContractAdd($this->uid,$newData,$this->field_list,$this->general_list);
        return $info;
    }
    /**
     * 09-合同调整
     * @author Dai Ming
     * @DateTime 10:58 2019/9/11 0011
     * @desc 合同调整   （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractEdit(){
        $newData = array(
            'project_id' => $this->project_id,
            'contract_id' => $this->contract_id,
            'money' => $this->money,
            'new_money' => $this->new_money,
            'adjust_money' => $this->adjust_money,
            'note' => $this->note,
            'file' => $this->file
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostContractEdit($this->uid,$newData,$this->general_list);
        return $info;
    }

    /**
     * 26-合同调整列表
     * @author Dai Ming
     * @DateTime 10:54 2019/9/23 0023
     * @desc  合同调整列表 （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractAdgustList()
    {

        $domain = new Domain_Proedution();
        $info = $domain->PostContractAdgustList($this->uid,$this->project_id);
        return $info;
    }

    /**
     * 10-合同详情
     * @author Dai Ming
     * @DateTime 10:59 2019/9/11 0011
     * @desc  合同详情  （未完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractInfo(){
        $newData = array(
            'uid' => $this->uid,
            'id' => $this->id
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostContractInfo($newData);
        return $info;
    }

    /**
     * 11-签约项目列表
     * @author Dai Ming
     * @DateTime 11:00 2019/9/11 0011
     * @desc 签约项目列表   （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * @return int data.contract_id 合同ID
     * @return string data.contract_number 合同编号
     * @return array data.value 签约项目
     * @return int value.id 项目ID
     * @return string data.title 项目名称
     * @return int value.xuefei 学费
     * @return int value.bm_price 报名费
     * @return int value.zl_price 资料费
     * @return int value.cl_price 材料费
     * @return int value.sq_price 申请费
     * @return int value.total_moeny 总额
     * @return int value.agent_money 代理费
     * @return string value.note 备注
     *
     * @return string msg 提示信息
     */
    public function  PostContractList(){
        $newData = array(
            'uid' => $this->uid,
            'project_id' => $this->project_id
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostContractList($newData);
        return $info;
    }
    /**
     * 12-回款添加
     * @author Dai Ming
     * @DateTime 11:01 2019/9/11 0011
     * @desc 回款添加   （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractMback(){
        $newData = array(
            'project_id' => $this->project_id,
            'contract_id' => $this->contract_id,
            'deal_uid' => $this->deal_uid,
            'order_no' => $this->order_no,
            'invoice_state' => $this->invoice_state,
            'pay_money' => $this->pay_money,
            'pay_bank' => $this->pay_bank,
            'pay_bank_no' => $this->pay_bank_no,
            'pay_user' => $this->pay_user,
            'pay_date' => $this->pay_date,
            'may_pay_date' => $this->may_pay_date,
            'notice' => $this->notice,
            'file' => $this->file,
            'pay_state' => $this->pay_state
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostProjectPaymentAdd($this->uid,$newData,$this->field_list);
        return $info;
    }

    /**
     * 13-回款编辑
     * @author Dai Ming
     * @DateTime 11:03 2019/9/11 0011
     * @desc  回款编辑  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractMedit()
    {
        $newData = array(
            'project_id' => $this->project_id,
            'contract_id' => $this->contract_id,
            'deal_uid' => $this->deal_uid,
            'order_no' => $this->order_no,
            'invoice_state' => $this->invoice_state,
            'pay_money' => $this->pay_money,
            'pay_bank' => $this->pay_bank,
            'pay_bank_no' => $this->pay_bank_no,
            'pay_user' => $this->pay_user,
            'may_pay_date' => $this->may_pay_date,
            'notice' => $this->notice,
            'file' => $this->file,
            'pay_state' => $this->pay_state
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostContractMedit($this->uid,$this->id,$newData,$this->field_list);
        return $info;
    }



    /**
     * 15-回款查看
     * @author Dai Ming
     * @DateTime 11:05 2019/9/11 0011
     * @desc  回款查看  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractMinfo()
    {
        $domain = new Domain_Proedution();
        $info = $domain->PostContractMinfo($this->id);
        return $info;

    }

    /**
     * 16-开票添加
     * @author Dai Ming
     * @DateTime 11:06 2019/9/11 0011
     * @desc 开票添加   （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractInvoiceAdd()
    {
        $Ticket=new  Domain_Proedution();
        $info=$Ticket->PostContractInvoiceAdd(\PhalApi\DI()->request->getAll());
        return $info;
    }

    /**
     * 17-开票编辑
     * @author Dai Ming
     * @DateTime 11:07 2019/9/11 0011
     * @desc  开票编辑   （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractInvoiceEdit()
    {
        $Ticket=new  Domain_Proedution();
        $info=$Ticket->PostContractInvoiceEdit(\PhalApi\DI()->request->getAll());
        return $info;
    }



    /**
     * 19-开票详情
     * @author Dai Ming
     * @DateTime 11:08 2019/9/11 0011
     * @desc  开票查看  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractInvoiceInfo()
    {
        $domain = new Domain_Proedution();
        $info = $domain->PostContractInvoiceInfo($this->id);
        return $info;
    }

    /**
     * 30-开票列表
     * @author Dai Ming
     * @DateTime 11:09 2019/9/26 0026
     * @desc  开票列表  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetProjectInvoiceList()
    {
        $domain = new Domain_Proedution();
        $info = $domain->GetProjectInvoiceList($this->project_id);
        return $info;
    }

    /**
     * 20-退款添加
     * @author Dai Ming
     * @DateTime 11:06 2019/9/11 0011
     * @desc 退款添加   （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractRefundAdd()
    {
        $newData = array(
            'project_id' => $this->project_id,
            'payment_number' => $this->payment_number,
            'reason' => $this->reason,
            'pay_user' => $this->pay_user,
            'bank_name' => $this->bank_name,
            'bank_no' => $this->bank_no,
            'refund_date' => $this->refund_date,
            'refund_money' => $this->refund_money,
            'note' => $this->note,
            'file' => $this->file
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostContractRefundAdd($this->uid,$newData,$this->field_list);
        return $info;
    }

    /**
     *  签约项目列表
     * @author Dai Ming
     * @DateTime 15:11 2019/10/25
     * @desc   签约项目列表（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetProjectGeneralList()
    {
        $newData = array(
            'project_id' => $this->project_id,

            'uid' => $this->uid
        );
        $domain = new Domain_Proedution();
        $info = $domain->GetProjectGeneralList($newData);
        return $info;
    }

    /**
     * 21-退款编辑
     * @author Dai Ming
     * @DateTime 11:07 2019/9/11 0011
     * @desc  退款编辑  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractRefundEdit()
    {
        $newData = array(
            'project_id' => $this->project_id,
            'payment_number' => $this->payment_number,
            'reason' => $this->reason,
            'pay_user' => $this->pay_user,
            'bank_name' => $this->bank_name,
            'bank_no' => $this->bank_no,
            'refund_date' => $this->refund_date,
            'refund_money' => $this->refund_money,
            'note' => $this->note,
            'file' => $this->file
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostContractRefundEdit($this->uid,$this->id,$newData,$this->field_list);
        return $info;
    }


    /**
     * 23-退款查看
     * @author Dai Ming
     * @DateTime 11:08 2019/9/11 0011
     * @desc  退款查看  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *  * @return int data.id 退款ID
     * @return string data.payment_number 付款编号
     * @return string data.project 所属项目
     * @return string data.pay_company 付款公司
     * @return string data.refund_type 退款方式
     * @return string data.reason 退款原因
     * @return string data.pay_user 开会名
     * @return string data.bank_name 开户行
     * @return string data.bank_no 银行账号
     * @return int data.refund_date 退款日期
     * @return string data.refund_money 退款金额
     * @return string data.note 备注
     * @return string data.file 附件
     * @return string data.publisher 创建人
     * @return string data.updateman 更新人
     * @return string data.addtime 添加时间[时间戳]
     * @return string data.updatetime 更新时间[时间戳]
     *
     * @return string msg 提示信息
     */
    public function GetProjectRefundInfo()
    {
        $domain = new Domain_Proedution();
        $info = $domain->GetProjectRefundInfo($this->id);
        return $info;
    }
    /**
     * 29-退款列表
     * @author Dai Ming
     * @DateTime 11:08 2019/9/11 0011
     * @desc 退款列表  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetProjectRefundList()
    {
        $domain = new Domain_Proedution();
        $info = $domain->GetProjectRefundList($this->project_id);
        return $info;

    }
    /**
     * 24-学员列表
     * @author Dai Ming
     * @DateTime 11:12 2019/9/11 0011
     * @desc  学员列表 (带查询) （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostStudentList()
    {
        $newData = array(
            'uid' => $this->uid,
            'id' => $this->id,
            'cid' => $this->cid,
            'type' => $this->type,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Proedution();
        $info = $domain->PostStudentList($newData);
        return $info;

    }

    /**
     *27-合同列表
     * @author Dai Ming
     * @DateTime 16:58 2019/9/24 0024
     * @desc  合同列表  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetProjectContrat()
    {
        $newData = array(
            'uid' => $this->uid,
            'project_id' => $this->project_id
        );
        $domain = new Domain_Proedution();
        $info = $domain->GetProjectContrat($newData);
        return $info;
    }

    /**
     * 28-[调整合同]-详情
     * @author Dai Ming
     * @DateTime 09:41 2019/9/25 0025
     * @desc  [调整合同]-详情  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetContractProjectInfo()
    {
        $newData = array(
            'id' => $this->id,
            'contract_id' => $this->contract_id
        );
        $domain = new Domain_Proedution();
        $info = $domain->GetContractProjectInfo($newData);
        return $info;

    }

    /**
     * 31-回款列表
     * @author Dai Ming
     * @DateTime 09:25 2019/9/27 0027
     * @desc 回款列表（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetProjectPaymentList()
    {
        $domain = new Domain_Proedution();
        $info = $domain->GetProjectPaymentList($this->project_id,$this->type);
        return $info;
    }



}

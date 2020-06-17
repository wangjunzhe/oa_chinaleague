<?php
namespace App\Api;
use PhalApi\Api;
use App\Domain\Xiaozhe as Domain_Xiaozhe;

/**
 * 小哲接口
 * @author: dogstar <1184378894@qq.com> 2019-09-04
 */
class Xiaozhe extends Api {
    public function getRules() {
        return array(
            'AAOtherContacts' => array(
                'id' => array('name' => 'id','type' => 'int','require' => false,'desc' => '联系人id（编辑提交必填）','default' => '' ),
                'full_name' => array('name' => 'full_name','type' => 'string','require' => true,'desc' => '联系人（必填）','default' => '' ),
                'mobile' => array('name' => 'mobile','type' => 'string','require' => true,'desc' => '手机号（必填）','default' => '' ),
                'birthday' => array('name' => 'birthday','type' => 'date','require' => true,'desc' => '生日（必填）','default' => '' ),
                'sex' => array('name' => 'sex','type' => 'string','require' => false,'desc' => '性别0男1女（不必填）','default' => '' ),
                'weixin' => array('name' => 'weixin','type' => 'string','require' => false,'desc' => '微信（不必填）','default' => '' ),
                'email' => array('name' => 'email','type' => 'string','require' => false,'desc' => '邮箱（不必填）','default' => '' ),
                'qq' => array('name' => 'qq','type' => 'string','require' => false,'desc' => 'qq（不必填）','default' => '' ),
                'telphone' => array('name' => 'telphone','type' => 'string','require' => false,'desc' => '座机（不必填）','default' => '' ),
                'post' => array('name' => 'post','type' => 'string','require' => false,'desc' => '岗位（不必填）','default' => '' ),
            ),
            'PostAgentAdd' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'title' => array('name' => 'title','type' => 'string','require' => true,'desc' => '名称（必填）','default' => '' ),
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
                'birthday' => array('name' => 'birthday','type' => 'date','require' => true,'desc' => '生日（必填）','default' => '' ),
                'sex' => array('name' => 'sex','type' => 'int','require' => false,'desc' => '性别0男1女（不必填）','default' => '' ),
                'img' => array('name' => 'img','type' => 'string','require' => false,'desc' => '头像（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
                'invoice_company' => array('name' => 'invoice_company','type' => 'string','require' => false,'desc' => '开票公司（不必填）','default' => '' ),
                'identifier' => array('name' => 'identifier','type' => 'string','require' => false,'desc' => '识别号（不必填）','default' => '' ),
                'bank' => array('name' => 'bank','type' => 'string','require' => false,'desc' => '开户行（不必填）','default' => '' ),
                'bank_name' => array('name' => 'bank_name','type' => 'string','require' => false,'desc' => '开户行名称（不必填）','default' => '' ),
                'card_no' => array('name' => 'card_no','type' => 'string','require' => false,'desc' => '卡号（不必填）','default' => '' ),
                'bank_address' => array('name' => 'bank_address','type' => 'string','require' => false,'desc' => '开户行地址（不必填）','default' => '' ),
                'legal_person' => array('name' => 'legal_person','type' => 'string','require' => false,'desc' => '法人（不必填）','default' => '' ),
                'business_file' => array('name' => 'business_file','type' => 'string','require' => false,'desc' => '营业执照附件（不必填）','default' => '' ),
                'next_follow' => array('name' => 'next_follow','type' => 'int','require' => false,'desc' => '下次提醒日期[时间戳]（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（不必填）'),
                'other_contacts' => array('name' => 'other_contacts', 'type' => 'array','require' => false, 'desc' => '其他联系人（不必填）'),
            ),
            'PostDeleteOtherContacts' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'desc' => '其他联系人id（必填）','default' => '' ),
            ),
            'PostEditAgent' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
                'title' => array('name' => 'title','type' => 'string','require' => true,'desc' => '名称（必填）','default' => '' ),
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
                'birthday' => array('name' => 'birthday','type' => 'date','require' => true,'desc' => '生日（必填）','default' => '' ),
                'sex' => array('name' => 'sex','type' => 'int','require' => false,'desc' => '性别0男1女（不必填）','default' => '' ),
                'img' => array('name' => 'img','type' => 'string','require' => false,'desc' => '头像（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
                'invoice_company' => array('name' => 'invoice_company','type' => 'string','require' => false,'desc' => '开票公司（不必填）','default' => '' ),
                'identifier' => array('name' => 'identifier','type' => 'string','require' => false,'desc' => '识别号（不必填）','default' => '' ),
                'bank' => array('name' => 'bank','type' => 'string','require' => false,'desc' => '开户行（不必填）','default' => '' ),
                'bank_name' => array('name' => 'bank_name','type' => 'string','require' => false,'desc' => '开户行名称（不必填）','default' => '' ),
                'card_no' => array('name' => 'card_no','type' => 'string','require' => false,'desc' => '卡号（不必填）','default' => '' ),
                'bank_address' => array('name' => 'bank_address','type' => 'string','require' => false,'desc' => '开户行地址（不必填）','default' => '' ),
                'legal_person' => array('name' => 'legal_person','type' => 'string','require' => false,'desc' => '法人（不必填）','default' => '' ),
                'business_file' => array('name' => 'business_file','type' => 'string','require' => false,'desc' => '营业执照附件（不必填）','default' => '' ),
                'next_follow' => array('name' => 'next_follow','type' => 'int','require' => false,'desc' => '下次提醒日期[时间戳]（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（不必填）'),
                'other_contacts' => array('name' => 'other_contacts', 'type' => 'array','require' => false, 'desc' => '其他联系人（不必填）'),
            ),
            'GetAgentList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型0公司1个人2全部（不必填）','default' => '' ),
                'follow_type' => array('name' => 'follow_type','type' => 'int','require' => false,'desc' => '跟进类型0全部1应联系2新增...8-90天未跟进（不必填）','default' => '' ),
                'start_time' => array('name' => 'start_time','type' => 'int','require' => false,'desc' => '开始时间（不必填）','default' => '' ),
                'end_time' => array('name' => 'end_time','type' => 'int','require' => false,'desc' => '结束时间（不必填）','default' => '' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'order_by' => array('name' => 'order_by', 'type' => 'array','require' => false, 'desc' => '排序字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetAgentInfo' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
            ),
            'PostDeleteAgent' => array(
            	'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
            ),
            'PostShareAgentInfo' => array(
            	'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
                'share_uid' => array('name' => 'share_uid','type' => 'array','require' => true,'desc' => '用户id[数组]（必填）' ),
            ),
            'PostChangeCreater' => array(
            	'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
            	'new_uid' => array('name' => 'new_uid','type' => 'int','require' => true,'min' => 1,'desc' => '移交用户id（必填）','default' => '' ),
                'agentid_arr' => array('name' => 'agentid_arr','type' => 'array','require' => true,'desc' => '代理商id[数组]（必填）' ),
            ),
            'GetStudentList' => array(
            	'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
            	'id' => array('name' => 'id','type' => 'int','require' => false,'desc' => '代理商id（详情必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型0全部1成交2今日3...5未成交（不必填）','default' => '' ),
                'start_time' => array('name' => 'start_time','type' => 'int','require' => false,'desc' => '开始时间（不必填）','default' => '' ),
                'end_time' => array('name' => 'end_time','type' => 'int','require' => false,'desc' => '结束时间（不必填）','default' => '' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostAddAgentContract' => array(
            	'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
            	'title' => array('name' => 'title','type' => 'string','require' => true,'desc' => '代理商名称（必填）','default' => '' ),
            	'signing_company' => array('name' => 'signing_company','type' => 'string','require' => true,'desc' => '签约公司名称（必填）','default' => '' ),
            	'siging_person' => array('name' => 'siging_person','type' => 'string','require' => true,'desc' => '代理商签约人（必填）','default' => '' ),
            	'siging_address' => array('name' => 'siging_address','type' => 'string','require' => true,'desc' => '代理商地址（必填）','default' => '' ),
            	'siging_phone' => array('name' => 'siging_phone','type' => 'string','require' => true,'desc' => '代理商手机号（必填）','default' => '' ),
            	'siging_email' => array('name' => 'siging_email','type' => 'string','require' => false,'desc' => '代理商email（不必填）','default' => '' ),
            	'person' => array('name' => 'person','type' => 'string','require' => true,'desc' => '我方签约人（必填）','default' => '' ),
            	'address' => array('name' => 'address','type' => 'string','require' => true,'desc' => '我方地点（必填）','default' => '' ),
            	'phone' => array('name' => 'phone','type' => 'string','require' => true,'desc' => '我方手机号（必填）','default' => '' ),
            	'email' => array('name' => 'email','type' => 'string','require' => false,'desc' => '我方email（不必填）','default' => '' ),
            	'contract_status' => array('name' => 'contract_status','type' => 'int','require' => true,'desc' => '状态[0中1终止2意外终止]（必填）','default' => '' ),
            	'start_time' => array('name' => 'start_time','type' => 'date','require' => true,'desc' => '开始时间（必填）','default' => '' ),
            	'end_time' => array('name' => 'end_time','type' => 'date','require' => true,'desc' => '结束时间（必填）','default' => '' ),
            	'money' => array('name' => 'money','type' => 'int','require' => false,'desc' => '合同金额（不必填）','default' => '' ),
            	'siging_area' => array('name' => 'siging_area','type' => 'string','require' => false,'desc' => '签约地点（不必填）','default' => '' ),
            	'clause' => array('name' => 'clause','type' => 'string','require' => false,'desc' => '签约条款（不必填）','default' => '' ),
            	'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
            	'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（不必填）'),
            	'general_list' => array('name' => 'general_list', 'type' => 'array','require' => false, 'desc' => '签约项目（不必填）'),
            ),
            'AddContractGeneralListParams' => array(
                'general_id' => array('name' => 'general_id','type' => 'int','require' => true,'min' => 1,'desc' => '简章id（必填）','default' => '' ),
                'title' => array('name' => 'title','type' => 'string','require' => true,'desc' => '简章名称（必填）','default' => '' ),
                'xuefei' => array('name' => 'xuefei','type' => 'int','require' => true,'desc' => '学费（必填）','default' => '' ),
                'bm_price' => array('name' => 'bm_price','type' => 'int','require' => true,'desc' => '报名费（必填）','default' => '' ),
                'zl_price' => array('name' => 'zl_price','type' => 'int','require' => true,'desc' => '资料费（必填）','default' => '' ),
                'cl_price' => array('name' => 'cl_price','type' => 'int','require' => true,'desc' => '材料费（必填）','default' => '' ),
                'sq_price' => array('name' => 'sq_price','type' => 'int','require' => true,'desc' => '申请费（必填）','default' => '' ),
                'total_moeny' => array('name' => 'total_moeny','type' => 'int','require' => true,'desc' => '总额（必填）','default' => '' ),
                'agent_money' => array('name' => 'agent_money','type' => 'int','require' => true,'desc' => '代理费（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
            ),
            'GetAgentContractList' => array(
            	'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
            ),
            'GetAgentContractInfo' => array(
            	'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '合同id（必填）','default' => '' ),
            ),
            'GetAgentGeneralInfo' => array(
            	'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
            	'general_id' => array('name' => 'general_id','type' => 'int','require' => true,'min' => 1,'desc' => '简章id（必填）','default' => '' ),
            ),
            'GetAgentGeneralList' => array(
            	'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
            ),
            'PostContractAdjust' => array(
            	'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
            	'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
            	'contract_id' => array('name' => 'contract_id','type' => 'int','require' => true,'min' => 1,'desc' => '合同id（必填）','default' => '' ),
                'money' => array('name' => 'money','type' => 'int','require' => true,'desc' => '原金额（必填）','default' => '' ),
                'new_money' => array('name' => 'new_money','type' => 'int','require' => true,'desc' => '新金额（必填）','default' => '' ),
                'adjust_money' => array('name' => 'adjust_money','type' => 'int','require' => true,'desc' => '调整金额（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'general_list' => array('name' => 'general_list', 'type' => 'array','require' => false, 'desc' => '签约项目（不必填）'),
            ),
            'AdjustGeneralListParams' => array(
                'general_id' => array('name' => 'general_id','type' => 'int','require' => true,'min' => 1,'desc' => '简章id（必填）','default' => '' ),
                'title' => array('name' => 'title','type' => 'string','require' => true,'desc' => '简章名称（必填）','default' => '' ),
                'xuefei' => array('name' => 'xuefei','type' => 'int','require' => true,'desc' => '学费（必填）','default' => '' ),
                'bm_price' => array('name' => 'bm_price','type' => 'int','require' => true,'desc' => '报名费（必填）','default' => '' ),
                'zl_price' => array('name' => 'zl_price','type' => 'int','require' => true,'desc' => '资料费（必填）','default' => '' ),
                'cl_price' => array('name' => 'cl_price','type' => 'int','require' => true,'desc' => '材料费（必填）','default' => '' ),
                'sq_price' => array('name' => 'sq_price','type' => 'int','require' => true,'desc' => '申请费（必填）','default' => '' ),
                'total_moeny' => array('name' => 'total_moeny','type' => 'int','require' => true,'desc' => '总额（必填）','default' => '' ),
                'agent_money' => array('name' => 'agent_money','type' => 'int','require' => true,'desc' => '代理费（必填）','default' => '' ),
                'new_xuefei' => array('name' => 'new_xuefei','type' => 'int','require' => true,'desc' => '新学费（必填）','default' => '' ),
                'new_bm_price' => array('name' => 'new_bm_price','type' => 'int','require' => true,'desc' => '新报名费（必填）','default' => '' ),
                'new_zl_price' => array('name' => 'new_zl_price','type' => 'int','require' => true,'desc' => '新资料费（必填）','default' => '' ),
                'new_cl_price' => array('name' => 'new_cl_price','type' => 'int','require' => true,'desc' => '新材料费（必填）','default' => '' ),
                'new_sq_price' => array('name' => 'new_sq_price','type' => 'int','require' => true,'desc' => '新申请费（必填）','default' => '' ),
                'new_total_moeny' => array('name' => 'new_total_moeny','type' => 'int','require' => true,'desc' => '新总额（必填）','default' => '' ),
                'new_agent_money' => array('name' => 'new_agent_money','type' => 'int','require' => true,'desc' => '新代理费（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注（不必填）','default' => '' ),
            ),
            'GetContractAdjustList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
            ),
            'GetContractAdjustInfo' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '调整合同id（必填）','default' => '' ),
                'contract_id' => array('name' => 'contract_id','type' => 'int','require' => true,'min' => 1,'desc' => '合同id（必填）','default' => '' ),
            ),
            'PostAgentPaymentAdd' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
                'contract_id' => array('name' => 'contract_id','type' => 'int','require' => true,'min' => 1,'desc' => '合同id（必填）','default' => '' ),
                'deal_uid' => array('name' => 'deal_uid','type' => 'int','require' => true,'desc' => '成交学员id（必填）','default' => '' ),
                'order_no' => array('name' => 'order_no','type' => 'string','require' => true,'desc' => '成交编号（必填）','default' => '' ),
                'invoice_state' => array('name' => 'invoice_state','type' => 'int','require' => true,'desc' => '开票状态[0未1已2无需]（必填）','default' => '' ),
                'pay_money' => array('name' => 'pay_money','type' => 'int','require' => true,'desc' => '付款金额（必填）','default' => '' ),
                'pay_bank' => array('name' => 'pay_bank','type' => 'string','require' => true,'desc' => '付款银行（必填）','default' => '' ),
                'pay_bank_no' => array('name' => 'pay_bank_no','type' => 'string','require' => true,'desc' => '付款账号（必填）','default' => '' ),
                'pay_user' => array('name' => 'pay_user','type' => 'string','require' => true,'desc' => '付款户名（必填）','default' => '' ),
                'may_pay_date' => array('name' => 'may_pay_date','type' => 'date','require' => true,'desc' => '预付款日期（必填）','default' => '' ),
                'notice' => array('name' => 'notice','type' => 'string','require' => false,'desc' => '款项说明（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'pay_state' => array('name' => 'pay_state','type' => 'int','require' => true,'desc' => '付款状态[0未1已]（必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（不必填）'),
            ),
            'PostAgentPaymentEdit' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '付款记录id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
                'contract_id' => array('name' => 'contract_id','type' => 'int','require' => true,'min' => 1,'desc' => '合同id（必填）','default' => '' ),
                'deal_uid' => array('name' => 'deal_uid','type' => 'int','require' => true,'desc' => '成交学员id（必填）','default' => '' ),
                'order_no' => array('name' => 'order_no','type' => 'string','require' => true,'desc' => '成交编号（必填）','default' => '' ),
                'invoice_state' => array('name' => 'invoice_state','type' => 'int','require' => true,'desc' => '开票状态[0未1已2无需]（必填）','default' => '' ),
                'pay_money' => array('name' => 'pay_money','type' => 'int','require' => true,'desc' => '付款金额（必填）','default' => '' ),
                'pay_bank' => array('name' => 'pay_bank','type' => 'string','require' => true,'desc' => '付款银行（必填）','default' => '' ),
                'pay_bank_no' => array('name' => 'pay_bank_no','type' => 'string','require' => true,'desc' => '付款账号（必填）','default' => '' ),
                'pay_user' => array('name' => 'pay_user','type' => 'string','require' => true,'desc' => '付款户名（必填）','default' => '' ),
                'may_pay_date' => array('name' => 'may_pay_date','type' => 'date','require' => true,'desc' => '预付款日期（必填）','default' => '' ),
                'notice' => array('name' => 'notice','type' => 'string','require' => false,'desc' => '款项说明（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'pay_state' => array('name' => 'pay_state','type' => 'int','require' => true,'desc' => '付款状态[0未1已]（必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（不必填）'),
            ),
            'GetAgentPaymentList' => array(
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => true,'desc' => '类型0未付款1已付款2全部（必填）','default' => '' ),
            ),
            'GetAgentPaymentInfo' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '付款记录id（必填）','default' => '' ),
            ),
            'PostAddAgentInvoice' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
                'payment_number' => array('name' => 'payment_number','type' => 'string','require' => true,'desc' => '付款编号（必填）','default' => '' ),
                'content' => array('name' => 'content','type' => 'string','require' => false,'desc' => '收票内容（不必填）','default' => '' ),
                'money' => array('name' => 'money','type' => 'int','require' => true,'desc' => '开票金额（必填）','default' => '' ),
                'company' => array('name' => 'company','type' => 'string','require' => true,'desc' => '开票公司（必填）','default' => '' ),
                'collect_time' => array('name' => 'collect_time','type' => 'date','require' => true,'desc' => '开票日期（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '款项说明（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（必填）'),
            ),
            'PostEditAgentInvoice' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '开票id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
                'payment_number' => array('name' => 'payment_number','type' => 'string','require' => true,'desc' => '付款编号（必填）','default' => '' ),
                'content' => array('name' => 'content','type' => 'string','require' => false,'desc' => '收票内容（不必填）','default' => '' ),
                'money' => array('name' => 'money','type' => 'int','require' => true,'desc' => '开票金额（必填）','default' => '' ),
                'company' => array('name' => 'company','type' => 'string','require' => true,'desc' => '开票公司（必填）','default' => '' ),
                'collect_time' => array('name' => 'collect_time','type' => 'date','require' => true,'desc' => '开票日期（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '款项说明（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（必填）'),
            ),
            'GetAgentInvoiceList' => array(
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
            ),
            'GetAgentInvoiceInfo' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '收票id（必填）','default' => '' ),
            ),
            'PostAgentRefundAdd' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
                'payment_number' => array('name' => 'payment_number','type' => 'string','require' => true,'desc' => '付款编号（必填）','default' => '' ),
                'reason' => array('name' => 'reason','type' => 'string','require' => true,'desc' => '退款原因（必填）','default' => '' ),
                'pay_user' => array('name' => 'pay_user','type' => 'string','require' => true,'desc' => '开户名（必填）','default' => '' ),
                'bank_name' => array('name' => 'bank_name','type' => 'string','require' => true,'desc' => '开户行（必填）','default' => '' ),
                'bank_no' => array('name' => 'bank_no','type' => 'string','require' => true,'desc' => '银行账号（必填）','default' => '' ),
                'refund_date' => array('name' => 'refund_date','type' => 'date','require' => true,'desc' => '退款日期（必填）','default' => '' ),
                'refund_money' => array('name' => 'refund_money','type' => 'int','require' => true,'desc' => '退款金额（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '款项说明（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（必填）'),
            ),
            'PostAgentRefundEdit' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '退款id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
                'payment_number' => array('name' => 'payment_number','type' => 'string','require' => true,'desc' => '付款编号（必填）','default' => '' ),
                'reason' => array('name' => 'reason','type' => 'string','require' => true,'desc' => '退款原因（必填）','default' => '' ),
                'pay_user' => array('name' => 'pay_user','type' => 'string','require' => true,'desc' => '开户名（必填）','default' => '' ),
                'bank_name' => array('name' => 'bank_name','type' => 'string','require' => true,'desc' => '开户行（必填）','default' => '' ),
                'bank_no' => array('name' => 'bank_no','type' => 'string','require' => true,'desc' => '银行账号（必填）','default' => '' ),
                'refund_date' => array('name' => 'refund_date','type' => 'date','require' => true,'desc' => '退款日期（必填）','default' => '' ),
                'refund_money' => array('name' => 'refund_money','type' => 'int','require' => true,'desc' => '退款金额（必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '款项说明（不必填）','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（必填）'),
            ),
            'GetAgentRefundList' => array(
                'agent_id' => array('name' => 'agent_id','type' => 'int','require' => true,'min' => 1,'desc' => '代理商id（必填）','default' => '' ),
            ),
            'GetAgentRefundInfo' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '退款id（必填）','default' => '' ),
            ),
            'PostAddTeacher' => array(
                'uid' => array('name' => 'uid', 'type' => 'int','require' => true, 'desc' => '当前用户ID（必填）','default' => '' ),
                'name' => array('name' => 'name', 'type' => 'string','require' => true, 'desc' => '教师名称（必填）','default' => '' ),
                'structure_id' => array('name' => 'structure_id', 'type' => 'int','require' => true, 'desc' => '所属部门（必填）','default' => '' ),
                'post' => array('name' => 'post', 'type' => 'string','require' => true, 'desc' => '职务（必填）','default' => '' ),
                'remark' => array('name' => 'remark', 'type' => 'string','require' => true, 'desc' => '简介（必填）','default' => '' ),
                'field' => array('name' => 'field', 'type' => 'string','require' => true, 'desc' => '研究领域（必填）','default' => '' ),
                'sex' => array('name' => 'sex', 'type' => 'int','require' => true, 'desc' => '性别0男1女（必填）','default' => '' ),
                'birthday' => array('name' => 'birthday', 'type' => 'date','require' => true, 'desc' => '生日（必填）','default' => '' ),
                'phone' => array('name' => 'phone', 'type' => 'string','require' => true, 'desc' => '手机号码（必填）','default' => '' ),
                'weixin' => array('name' => 'weixin', 'type' => 'string','require' => false, 'desc' => '微信号（不必填）','default' => '' ),
                'email' => array('name' => 'email', 'type' => 'string','require' => false, 'desc' => '邮箱（不必填）','default' => '' ),
                'img' => array('name' => 'img', 'type' => 'string','require' => false, 'desc' => '头像（不必填）' ),
                'file' => array('name' => 'file', 'type' => 'string','require' => false, 'desc' => '附件（不必填）' ),
                'note' => array('name' => 'note', 'type' => 'string','require' => false, 'desc' => '备注（不必填）','default' => '' ),
                'course' => array('name' => 'course', 'type' => 'string','require' => false, 'desc' => '主要课程（不必填）','default' => '' ),
                'outline' => array('name' => 'outline', 'type' => 'string','require' => false, 'desc' => '课程大纲（不必填）','default' => '' ),
                'bright_spot' => array('name' => 'bright_spot', 'type' => 'string','require' => false, 'desc' => '课程亮点（不必填）','default' => '' ),
                'schedule' => array('name' => 'schedule', 'type' => 'string','require' => false, 'desc' => '排班表（不必填）','default' => '' ),
                'attainment' => array('name' => 'attainment', 'type' => 'string','require' => false, 'desc' => '职业素养（不必填）','default' => '' ),
                'pleased' => array('name' => 'pleased', 'type' => 'string','require' => false, 'desc' => '满意度（不必填）','default' => '' ),
                'home_area' => array('name' => 'home_area', 'type' => 'string','require' => false, 'desc' => '常住地（不必填）','default' => '' ),
                'food' => array('name' => 'food', 'type' => 'string','require' => false, 'desc' => '饮食（不必填）','default' => '' ),
                'stay' => array('name' => 'stay', 'type' => 'string','require' => false, 'desc' => '住宿（不必填）','default' => '' ),
                'noon_break' => array('name' => 'noon_break', 'type' => 'string','require' => false, 'desc' => '午休（不必填）','default' => '' ),
                'bank' => array('name' => 'bank', 'type' => 'string','require' => false, 'desc' => '开户行（不必填）','default' => '' ),
                'card_number' => array('name' => 'card_number', 'type' => 'string','require' => false, 'desc' => '银行卡号（不必填）','default' => '' ),
                'money' => array('name' => 'money', 'type' => 'string','require' => false, 'desc' => '课酬（不必填）','default' => '' ),
                'collect_type' => array('name' => 'collect_type', 'type' => 'string','require' => false, 'desc' => '收款方式（不必填）','default' => '' ),
                'collect_time' => array('name' => 'collect_time', 'type' => 'string','require' => false, 'desc' => '收款时间（不必填）','default' => '' ),
                'status' => array('name' => 'status', 'type' => 'int','require' => true, 'desc' => '状态1进行中2停止（必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（必填）'),
            ),
            'GetTeacherList' => array(
                'uid' => array('name' => 'uid', 'type' => 'int','require' => true, 'desc' => '当前用户ID（必填）','default' => '' ),
                'type' => array('name' => 'type', 'type' => 'int','require' => true, 'desc' => '类型0全部1进行中2无效（必填）','default' => '' ),
                'follow_type' => array('name' => 'follow_type','type' => 'int','require' => false,'desc' => '跟进类型0全部1应联系2新增...8-90天未跟进（不必填）','default' => '' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostEditTeacher' => array(
                'uid' => array('name' => 'uid', 'type' => 'int','require' => true, 'desc' => '当前用户ID（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'int','require' => true, 'desc' => '师资ID（必填）','default' => '' ),
                'name' => array('name' => 'name', 'type' => 'string','require' => true, 'desc' => '教师名称（必填）','default' => '' ),
                'structure_id' => array('name' => 'structure_id', 'type' => 'int','require' => true, 'desc' => '所属部门（必填）','default' => '' ),
                'post' => array('name' => 'post', 'type' => 'string','require' => true, 'desc' => '职务（必填）','default' => '' ),
                'remark' => array('name' => 'remark', 'type' => 'string','require' => true, 'desc' => '简介（必填）','default' => '' ),
                'field' => array('name' => 'field', 'type' => 'string','require' => true, 'desc' => '研究领域（必填）','default' => '' ),
                'sex' => array('name' => 'sex', 'type' => 'int','require' => true, 'desc' => '性别0男1女（必填）','default' => '' ),
                'birthday' => array('name' => 'birthday', 'type' => 'date','require' => true, 'desc' => '生日（必填）','default' => '' ),
                'phone' => array('name' => 'phone', 'type' => 'string','require' => true, 'desc' => '手机号码（必填）','default' => '' ),
                'weixin' => array('name' => 'weixin', 'type' => 'string','require' => false, 'desc' => '微信号（不必填）','default' => '' ),
                'email' => array('name' => 'email', 'type' => 'string','require' => false, 'desc' => '邮箱（不必填）','default' => '' ),
                'img' => array('name' => 'img', 'type' => 'string','require' => false, 'desc' => '头像（不必填）' ),
                'file' => array('name' => 'file', 'type' => 'string','require' => false, 'desc' => '附件（不必填）' ),
                'note' => array('name' => 'note', 'type' => 'string','require' => false, 'desc' => '备注（不必填）','default' => '' ),
                'course' => array('name' => 'course', 'type' => 'string','require' => false, 'desc' => '主要课程（不必填）','default' => '' ),
                'outline' => array('name' => 'outline', 'type' => 'string','require' => false, 'desc' => '课程大纲（不必填）','default' => '' ),
                'bright_spot' => array('name' => 'bright_spot', 'type' => 'string','require' => false, 'desc' => '课程亮点（不必填）','default' => '' ),
                'schedule' => array('name' => 'schedule', 'type' => 'string','require' => false, 'desc' => '排班表（不必填）','default' => '' ),
                'attainment' => array('name' => 'attainment', 'type' => 'string','require' => false, 'desc' => '职业素养（不必填）','default' => '' ),
                'pleased' => array('name' => 'pleased', 'type' => 'string','require' => false, 'desc' => '满意度（不必填）','default' => '' ),
                'home_area' => array('name' => 'home_area', 'type' => 'string','require' => false, 'desc' => '常住地（不必填）','default' => '' ),
                'food' => array('name' => 'food', 'type' => 'string','require' => false, 'desc' => '饮食（不必填）','default' => '' ),
                'stay' => array('name' => 'stay', 'type' => 'string','require' => false, 'desc' => '住宿（不必填）','default' => '' ),
                'noon_break' => array('name' => 'noon_break', 'type' => 'string','require' => false, 'desc' => '午休（不必填）','default' => '' ),
                'bank' => array('name' => 'bank', 'type' => 'string','require' => false, 'desc' => '开户行（不必填）','default' => '' ),
                'card_number' => array('name' => 'card_number', 'type' => 'string','require' => false, 'desc' => '银行卡号（不必填）','default' => '' ),
                'money' => array('name' => 'money', 'type' => 'string','require' => false, 'desc' => '课酬（不必填）','default' => '' ),
                'collect_type' => array('name' => 'collect_type', 'type' => 'string','require' => false, 'desc' => '收款方式（不必填）','default' => '' ),
                'collect_time' => array('name' => 'collect_time', 'type' => 'string','require' => false, 'desc' => '收款时间（不必填）','default' => '' ),
                'status' => array('name' => 'status', 'type' => 'int','require' => true, 'desc' => '状态1进行中2停止（必填）','default' => '' ),
                'field_list' => array('name' => 'field_list', 'type' => 'array','require' => true, 'desc' => '灵活字段（必填）'),
            ),
            'PostDeleteTeacher' => array(
                'uid' => array('name' => 'uid', 'type' => 'int','require' => true, 'desc' => '当前用户ID（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'array','require' => true, 'desc' => '师资ID（必填）','default' => array() ),
            ),
            'GetTeacherInfo' => array(
                'uid' => array('name' => 'uid', 'type' => 'int','require' => true, 'desc' => '当前用户ID（必填）','default' => '' ),
                'id' => array('name' => 'id', 'type' => 'int','require' => true, 'desc' => '师资ID（必填）','default' => '' ),
            ),
            'PostShareTeacher' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '师资id（必填）','default' => '' ),
                'share_uid' => array('name' => 'share_uid','type' => 'array','require' => true,'desc' => '用户id[数组]（必填）' ),
            ),
            'PostChangeTeacher' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'new_uid' => array('name' => 'new_uid','type' => 'int','require' => true,'min' => 1,'desc' => '移交用户id（必填）','default' => '' ),
                'id_arr' => array('name' => 'id_arr','type' => 'array','require' => true,'desc' => '师资id[数组]（必填）' ),
            ),
            'GetMsgList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetMsgInfo' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '信息id（必填）','default' => '' ),
            ),
            'PostDeleteMsg' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id_arr' => array('name' => 'id_arr','type' => 'array','require' => true,'desc' => '信息id数组（必填）' ),
            ),
            'PostReadMsg' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'id_arr' => array('name' => 'id_arr','type' => 'array','require' => true,'desc' => '信息id数组（必填）' ),
            ),
            'GetStatisticsList' => array(
                'type' => array('name' => 'type', 'type' => 'string','require' => true, 'desc' => '分类[0全部130天2半年3本年]（必填）','default' => ''),
                'start_time' => array('name' => 'start_time', 'type' => 'date','require' => false, 'desc' => '开始时间（不必填）','default' => ''),
                'end_time' => array('name' => 'end_time', 'type' => 'date','require' => false, 'desc' => '结束时间（不必填）','default' => ''),
            ),
            'PostAddPublicFollow' => array(
                'type' => array('name' => 'type', 'type' => 'string','require' => true, 'desc' => '类型[0代理1项目2师资]（必填）','default' => ''),
                'info_id' => array('name' => 'info_id','type' => 'int','require' => true,'desc' => '[代理/项目/师资]信息id（必填）','default' => '' ),
                'subject' => array('name' => 'subject', 'type' => 'string','require' => true, 'desc' => '主题（必填）' ),
                'types' => array('name' => 'types', 'type' => 'string','require' => true, 'desc' => '类型（必填）' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'next_time' => array('name' => 'next_time', 'type' => 'int','require' => false, 'desc' => '下次提醒时间（不必填）','default' => ''),
                'file' => array('name' => 'file', 'type' => 'string','require' => false, 'desc' => '附件（不必填）' ),
            ),
            'GetPublicFollowList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type', 'type' => 'string','require' => true, 'desc' => '类型[0代理1项目2师资]（必填）','default' => ''),
                'info_id' => array('name' => 'info_id','type' => 'int','require' => true,'desc' => '[代理/项目/师资]信息id（必填）','default' => '' ),
            ),
            'GetPublicFollowInfo' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '跟进记录id（必填）','default' => '' ),
            ),
        );
    }

    /**
     * 001 代理商新增其他联系人参数列表
     * @author Wang Junzhe
     * @DateTime 2019-12-17T10:06:56+0800
     * @desc 其他联系人参数列表（已完成）
     */
    public function AAOtherContacts() {

    }

    /**
     * 01 代理商-新增
     * @author Wang Junzhe
     * @DateTime 2019-09-09T10:06:56+0800
     * @desc 新增-代理商接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostAgentAdd() {
    	$newData = array(
            'title' => $this->title,
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
            'services' => $this->services,
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
            'legal_person' => $this->legal_person,
            'next_follow' => $this->next_follow
    	);
    	$domain = new Domain_Xiaozhe();
        $info = $domain->PostAgentAdd($this->uid,$newData,$this->field_list,$this->other_contacts);
        return $info;
    }

    /**
     * 01-1 代理商-编辑-其他联系人删除
     * @author Wang Junzhe
     * @DateTime 2019-12-17T16:57:39+0800
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostDeleteOtherContacts() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostDeleteOtherContacts($this->uid,$this->id);
        return $info;
    }

    /**
     * 02 代理商-编辑
     * @author Wang Junzhe
     * @DateTime 2019-09-09T16:27:59+0800
     * @desc 编辑-代理商接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostEditAgent() {
    	$newData = array(
            'title' => $this->title,
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
            'services' => $this->services,
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
            'legal_person' => $this->legal_person,
            'next_follow' => $this->next_follow
    	);
    	$domain = new Domain_Xiaozhe();
        $info = $domain->PostEditAgent($this->uid,$this->id,$newData,$this->field_list,$this->other_contacts);
        return $info;
    }

    /**
     * 03 代理商-列表
     * @author Wang Junzhe
     * @DateTime 2019-09-09T16:48:04+0800
     * @desc 列表-代理商接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.agent_total 总数
     * @return int data.company_num 公司个数
     * @return int data.person_num 个人个数
     *
     * @return array data.agent_list 代理商列表
     * @return int agent_list.id 代理商ID
     * @return string agent_list.number 代理商编号
     * @return string agent_list.title 代理商名称
     * @return string agent_list.flower_name 代理商花名
     * @return string agent_list.type 代理商类型[0公司1个人]
     * @return string agent_list.group 代理商分组
     * @return string agent_list.scale 公司规模
     * @return string agent_list.post 岗位
     * @return string agent_list.company 所属公司
     * @return string agent_list.address 经营地址
     * @return string agent_list.web_site 网址
     * @return string agent_list.full_name 联系人
     * @return string agent_list.mobile 手机号
     * @return string agent_list.weixin 微信
     * @return string agent_list.qq QQ
     * @return string agent_list.email 邮箱
     * @return string agent_list.services 负责业务
     * @return string agent_list.birthday 生日
     * @return string agent_list.sex 性别
     * @return string agent_list.img 头像
     * @return string agent_list.file 附件
     * @return string agent_list.note 备注
     * @return string agent_list.invoice_company 开票公司
     * @return string agent_list.identifier 识别号
     * @return string agent_list.bank 开户行
     * @return string agent_list.bank_name 银行名称
     * @return string agent_list.card_no 卡号
     * @return string agent_list.bank_address 银行地址
     * @return string agent_list.legal_person 法人
     * @return string agent_list.business_file 营业执照
     * @return string agent_list.publisher 发布者
     * @return string agent_list.charge_person 负责人
     * @return string agent_list.sort 序号
     * @return string agent_list.addtime 添加时间[时间戳]
     * @return string agent_list.follow_time 最后跟进时间[时间戳]
     * @return string agent_list.next_follow 下次提醒时间[时间戳]
     * @return string agent_list.follow_up 跟进次数
     * @return string agent_list.old_publisher 原始创建人
     *
     * @return int agent_list.stu_deal_num 成交学员数量
     * @return int agent_list.stu_total_num 总学员
     * @return int agent_list.money_total 总付款金额
     * @return int agent_list.yf_money_total 已付款金额
     * @return int agent_list.df_money_total 代付款金额
     * 
     * @return array agent_list.return_date 预计回款日期
     *
     * @return array agent_list.other_contacts 其他联系人
     * @return int other_contacts.id 其他联系人id
     * @return string other_contacts.full_name 联系人名称
     * @return string other_contacts.mobile 手机号
     * @return date other_contacts.birthday 生日
     * @return int other_contacts.sex 性别0男1女
     * @return string other_contacts.weixin 微信
     * @return string other_contacts.email 邮箱
     * @return string other_contacts.qq QQ
     * @return string other_contacts.telphone 座机
     * @return string other_contacts.post 岗位
     * 
     * @return int data.agent_num 代理商数量
     * @return int data.money_total 总金额
     * @return int data.total_paid 已付款
     * @return int data.total_obligations 待付款
     * 
     * @return string msg 提示信息
     */
    public function GetAgentList() {
    	$newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'follow_type' => $this->follow_type,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'order_by' => $this->order_by,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
    	);
    	$domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentList($newData);
        return $info;
    }

    /**
     * 04 代理商-详情
     * @author Wang Junzhe
     * @DateTime 2019-09-09T17:06:56+0800
     * @desc 详情-代理商接口（未完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.next_agent_id 下一个
     * @return int data.prev_agent_id 上一个
     * 
     * @return array data.agent_info 代理商基本信息
     * @return int agent_info.id 代理商ID
     * @return string agent_info.number 代理商编号
     * @return string agent_info.title 代理商名称
     * @return string agent_info.flower_name 代理商花名
     * @return string agent_info.type 代理商类型[0公司1个人]
     * @return string agent_info.group 代理商分组
     * @return string agent_info.scale 公司规模
     * @return string agent_info.post 岗位
     * @return string agent_info.company 所属公司
     * @return string agent_info.address 经营地址
     * @return string agent_info.web_site 网址
     * @return string agent_info.full_name 联系人
     * @return string agent_info.mobile 手机号
     * @return string agent_info.weixin 微信
     * @return string agent_info.qq QQ
     * @return string agent_info.email 邮箱
     * @return string agent_info.services 负责业务
     * @return string agent_info.birthday 生日
     * @return string agent_info.sex 性别
     * @return string agent_info.img 头像
     * @return string agent_info.file 附件
     * @return string agent_info.note 备注
     * @return string agent_info.invoice_company 开票公司
     * @return string agent_info.identifier 识别号
     * @return string agent_info.bank 开户行
     * @return string agent_info.bank_name 银行名称
     * @return string agent_info.card_no 卡号
     * @return string agent_info.bank_address 银行地址
     * @return string agent_info.legal_person 法人
     * @return string agent_info.business_file 营业执照
     * @return string agent_info.publisher 发布者
     * @return string agent_info.charge_person 负责人
     * @return string agent_info.sort 序号
     * @return string agent_info.addtime 添加时间[时间戳]
     * @return string agent_info.total_price 总额
     * @return string agent_info.total_paid 已付款
     * @return string agent_info.total_obligations 未付款
     * @return string agent_info.old_publisher 原始创建人
     * 
     * @return array data.agent_log 操作日志
     * @return string agent_log.addtime 添加时间[时间戳]
     * @return string agent_log.note 日志内容
     *
     * @return array data.other_contacts 其他联系人
     * @return int other_contacts.id 其他联系人id
     * @return string other_contacts.full_name 联系人名称
     * @return string other_contacts.mobile 手机号
     * @return date other_contacts.birthday 生日
     * @return int other_contacts.sex 性别0男1女
     * @return string other_contacts.weixin 微信
     * @return string other_contacts.email 邮箱
     * @return string other_contacts.qq QQ
     * @return string other_contacts.telphone 座机
     * @return string other_contacts.post 岗位
     * 
     * @return string msg 提示信息
     */
    public function GetAgentInfo() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentInfo($this->uid,$this->id);
        return $info;
    }

    /**
     * 05 代理商删除
     * @author Wang Junzhe
     * @DateTime 2019-09-10T14:11:15+0800
     * @desc 删除-代理商接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostDeleteAgent() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostDeleteAgent($this->uid,$this->id);
        return $info;
    }

    /**
     * 06 代理商-共享
     * @author Wang Junzhe
     * @DateTime 2019-09-10T15:45:30+0800
     * @desc 共享-代理商接口（完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostShareAgentInfo() {
        $newData = array(
            'uid' => $this->uid,
            'id' => $this->id,
            'share_uid' => $this->share_uid
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostShareAgentInfo($newData);
        return $info;
    }

    /**
     * 07 代理商-移交
     * @author Wang Junzhe
     * @DateTime 2019-09-16T09:49:17+0800
     * @desc 移交-代理商接口（未完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostChangeCreater() {
    	$newData = array(
            'uid' => $this->uid,
            'new_uid' => $this->new_uid,
            'agentid_arr' => $this->agentid_arr
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostChangeCreater($newData);
        return $info;
    }

    /**
     * 08 代理商-学员列表
     * @author Wang Junzhe
     * @DateTime 2019-09-16T11:08:05+0800
     * @desc 代理商-学员列表[管理]
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.total_count 数量【详情无】
     * @return int total_count.total 总数
     * @return int total_count.cj_total 成交总数
     * @return int total_count.jrcj_total 今日成交
     * @return int total_count.bzcj_total 本周成交
     * @return int total_count.bycj_total 本月成交
     * @return int total_count.zfk_total 总付款
     * @return int total_count.dfk_total 待付款
     * @return int total_count.yfk_total 已付款
     *
     * @return array data.agent_stu_list 代理商学员列表
     * @return int agent_stu_list.cid 学员id【详情无】
     * @return string agent_stu_list.cname 学员名称
     * @return string agent_stu_list.cphone 学员手机号
     * @return string agent_stu_list.intentionally 学员意向
     * @return string agent_stu_list.charge_person 负责人
     * @return string agent_stu_list.create_time 创建时间[时间戳]
     * @return string agent_stu_list.agent_num 代理商编号
     * @return string agent_stu_list.agent_name 代理商名称
     * @return int agent_stu_list.agent_price 代理费
     * @return int agent_stu_list.paid 已付款
     * @return int agent_stu_list.obligations 待付款
     * @return int agent_stu_list.agent_id 代理商id【详情无】
     * @return int agent_stu_list.deal_time 成交时间[时间戳][null为无]
     * 
     * @return array agent_list.return_date 预计回款日期
     * 
     * @return int data.agent_stu_num 学员数量
     * 
     * @return string msg 提示信息
     */
    public function GetStudentList() {
    	$newData = array(
            'uid' => $this->uid,
            'id' => $this->id,
            'type' => $this->type,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetStudentList($newData);
        return $info;
    }

    /**
     * 09 合同[代理商]-新建
     * @author Wang Junzhe
     * @DateTime 2019-09-17T14:26:38+0800
     * @desc 新增-合同[代理商]接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostAddAgentContract() {
    	$newData = array(
            'agent_id' => $this->agent_id,
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
            'money' => $this->money,
            'siging_area' => $this->siging_area,
            'clause' => $this->clause,
            'note' => $this->note,
            'file' => $this->file,
    	);
    	$domain = new Domain_Xiaozhe();
        $info = $domain->PostAddAgentContract($this->uid,$newData,$this->field_list,$this->general_list);
        return $info;
    }

    /**
     * 09-1 项目参数【新建】
     * @author Wang Junzhe
     * @DateTime 2019-09-18T16:05:29+0800
     * @desc 合同-新建-签约项目
     */
    public function AddContractGeneralListParams() {

    }

    /**
     * 10 合同[代理商]-列表
     * @author Wang Junzhe
     * @DateTime 2019-09-17T15:27:20+0800
     * @desc 列表-合同[代理商]接口（开发中）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.contract_list 合同列表
     * @return int contract_list.id 合同ID
     * @return string contract_list.number 合同编号
     * @return string contract_list.person 我方签约人
     * @return string contract_list.company 我方签约公司
     * @return int contract_list.contract_status 状态[0进行中1终止2意外终止]
     * @return string contract_list.start_time 开始时间
     * @return string contract_list.end_time 结束时间
     * @return string contract_list.addtime 签约时间[时间戳]
     * 
     * @return int data.num 合同数量
     * 
     * @return string msg 提示信息
     */
    public function GetAgentContractList() {
    	$newData = array(
            'uid' => $this->uid,
            'agent_id' => $this->agent_id
    	);
    	$domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentContractList($newData);
        return $info;
    }

    /**
     * 11 合同[代理商]-详情
     * @author Wang Junzhe
     * @DateTime 2019-09-17T15:44:47+0800
     * @desc 详情-合同[代理商]接口（开发中）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 合同ID
     * @return string data.title 代理商名称
     * @return int data.agent_id 代理商ID
     * @return string data.number 合同编号
     * @return string data.classify 分类
     * @return string data.signing_company 签约公司
     * @return string data.siging_person 签约人
     * @return string data.siging_address 签约地址
     * @return string data.siging_phone 签约手机号
     * @return string data.siging_email 签约email
     * @return string data.company 我方签约公司
     * @return string data.person 我方签约人
     * @return string data.address 我方地址
     * @return string data.phone 我方手机号
     * @return string data.email 我方email
     * @return string data.contract_status 状态[0进行中1终止2意外终止]
     * @return string data.pay_type 付款方式
     * @return string data.start_time 开始时间
     * @return string data.end_time 结束时间
     * @return int data.money 合同金额
     * @return string data.siging_area 签约地点
     * @return string data.clause 签约条款
     * @return int data.note 备注
     * @return string data.file 附件
     * @return string data.sort 序号
     * @return string data.status 状态[2为已退款]
     * @return string data.addtime 签约时间[时间戳]
     * @return string data.updatetime 调整时间[时间戳]
     * @return string data.publisher 发布人
     * @return string data.updateman 更新人
     * 
     * @return array data.general_list 签约简章
     * @return int general_list.id 签约项目ID
     * @return int general_list.general_id 简章ID
     * @return string general_list.title 简章名称
     * @return int general_list.xuefei 学费
     * @return int general_list.bm_price 报名费
     * @return int general_list.zl_price 资料费
     * @return int general_list.cl_price 材料费
     * @return int general_list.sq_price 申请费
     * @return int general_list.total_moeny 总额
     * @return int general_list.agent_money 代理费
     * @return string general_list.note 备注
     * 
     * @return string msg 提示信息
     */
    public function GetAgentContractInfo() {
    	$newData = array(
            'uid' => $this->uid,
            'id' => $this->id
    	);
    	$domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentContractInfo($newData);
        return $info;
    }

    /**
     * 12 签约项目详情[代理商]
     * @author Wang Junzhe
     * @DateTime 2019-09-17T16:29:44+0800
     * @desc 签约项目详情[代理商]接口（开发中）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return int data.id 签约项目ID
     * @return int data.general_id 简章ID
     * @return string data.title 简章名称
     * @return int data.xuefei 学费
     * @return int data.bm_price 报名费
     * @return int data.zl_price 资料费
     * @return int data.cl_price 材料费
     * @return int data.sq_price 申请费
     * @return int data.total_moeny 总额
     * @return int data.agent_money 代理费
     * @return string data.note 备注
     * @return string data.publisher 发布人
     * @return string data.addtime 新增时间
     * @return string data.updateman 更新人
     * @return string data.updatetime 更新时间
     * @return string data.number 合同编号
     * 
     * @return string msg 提示信息
     */
    public function GetAgentGeneralInfo() {
    	$newData = array(
            'uid' => $this->uid,
            'agent_id' => $this->agent_id,
            'general_id' => $this->general_id
    	);
    	$domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentGeneralInfo($newData);
        return $info;
    }

    /**
     * 13 签约项目列表[代理商]
     * @author Wang Junzhe
     * @DateTime 2019-09-18T10:25:21+0800
     * @desc 签约项目列表接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
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
    public function GetAgentGeneralList() {
    	$newData = array(
            'uid' => $this->uid,
            'agent_id' => $this->agent_id
    	);
    	$domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentGeneralList($newData);
        return $info;
    }

    /**
     * 14 合同调整[代理商]
     * @author Wang Junzhe
     * @DateTime 2019-09-18T16:11:53+0800
     * @desc 合同调整[代理商]接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostContractAdjust() {
        $newData = array(
            'agent_id' => $this->agent_id,
            'contract_id' => $this->contract_id,
            'money' => $this->money,
            'new_money' => $this->new_money,
            'adjust_money' => $this->adjust_money,
            'note' => $this->note,
            'file' => $this->file
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostContractAdjust($this->uid,$newData,$this->general_list);
        return $info;
    }

    /**
     * 14-1 项目参数[合同]
     * @author Wang Junzhe
     * @DateTime 2019-09-18T16:31:32+0800
     * @desc 调整--合同-项目参数
     */
    public function AdjustGeneralListParams() {

    }

    /**
     * 15 [调整]合同列表[代理商]
     * @author Wang Junzhe
     * @DateTime 2019-09-19T14:37:44+0800
     * @desc 调整-合同-列表[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 调整合同id
     * @return int data.contract_id 合同id
     * @return int data.money 原金额
     * @return int data.new_money 新金额
     * @return int data.adjust_money 调整金额
     * @return string data.updatetime 调整时间[时间戳]
     * @return string data.number 合同编号
     * 
     * @return string msg 提示信息
     */
    public function GetContractAdjustList() {
        $newData = array(
            'agent_id' => $this->agent_id
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetContractAdjustList($this->uid,$newData);
        return $info;
    }

    /**
     * 16 [调整]合同详情
     * @author Wang Junzhe
     * @DateTime 2019-09-19T14:53:06+0800
     * @desc 调整-合同-详情[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.contract_name 代理商名称
     * @return string data.number 合同编号
     * @return string data.publisher 发布人
     * @return string data.updateman 修改人
     * @return string data.addtime 添加时间[时间戳]
     * @return string data.updatetime 修改时间[时间戳]
     * @return int data.money 原金额
     * @return int data.new_money 新金额
     * @return int data.adjust_money 调整金额
     * @return string data.note 备注
     * @return string data.file 附件
     * 
     * @return array data.general_list 签约项目列表
     * @return int general_list.general_id 简章ID
     * @return string general_list.title 简章标题
     * @return int general_list.xuefei 学费
     * @return int general_list.bm_price 报名费
     * @return int general_list.zl_price 资料费
     * @return int general_list.cl_price 材料费
     * @return int general_list.sq_price 申请费
     * @return int general_list.total_moeny 总额
     * @return int general_list.agent_money 代理费
     * @return int general_list.new_xuefei 新学费
     * @return int general_list.new_bm_price 新报名费
     * @return int general_list.new_zl_price 新资料费
     * @return int general_list.new_cl_price 新材料费
     * @return int general_list.new_sq_price 新申请费
     * @return int general_list.new_total_moeny 新总额
     * @return int general_list.new_agent_money 新代理费
     * @return string general_list.note 备注
     * 
     * @return string msg 提示信息
     */
    public function GetContractAdjustInfo() {
       $newData = array(
            'id' => $this->id,
            'contract_id' => $this->contract_id
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetContractAdjustInfo($newData);
        return $info; 
    }

    /**
     * 17 付款记录-新建
     * @author Wang Junzhe
     * @DateTime 2019-09-23T10:20:52+0800
     * @desc 付款记录-新建[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostAgentPaymentAdd() {
        $newData = array(
            'agent_id' => $this->agent_id,
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
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostAgentPaymentAdd($this->uid,$newData,$this->field_list);
        return $info;
    }

    /**
     * 18 付款记录-编辑
     * @author Wang Junzhe
     * @DateTime 2019-09-23T11:42:29+0800
     * @desc 付款记录-编辑[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostAgentPaymentEdit() {
        $newData = array(
            'agent_id' => $this->agent_id,
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
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostAgentPaymentEdit($this->uid,$this->id,$newData,$this->field_list);
        return $info;
    }

    /**
     * 19 付款记录-列表
     * @author Wang Junzhe
     * @DateTime 2019-09-23T15:02:09+0800
     * @desc 付款记录-列表[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.payment_list 付款记录列表
     * @return int payment_list.id 付款记录ID
     * @return string payment_list.number 付款编号
     * @return string payment_list.contract_number 合同编号
     * @return string payment_list.cname 成交学员
     * @return string payment_list.project 所属项目
     * @return string payment_list.period 期次
     * @return string payment_list.pay_nature 付款性质
     * @return string payment_list.may_pay_date 预计付款日期
     * @return string payment_list.pay_date 实际付款日期[时间戳]
     * @return string payment_list.invoice_state 开票状态[0未开1已开2无需开票]
     * @return string payment_list.pay_money 付款金额
     * @return string payment_list.note 备注
     * @return string payment_list.pay_state 付款状态[0未1已]
     * 
     * @return int data.payment_num 付款记录数量
     * 
     * @return string msg 提示信息
     */
    public function GetAgentPaymentList() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentPaymentList($this->agent_id,$this->type);
        return $info;
    }

    /**
     * 20 付款记录-详情
     * @author Wang Junzhe
     * @DateTime 2019-09-23T16:16:55+0800
     * @desc 付款记录-详情[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 付款记录ID
     * @return string data.number 付款编号
     * @return int data.contract_id 合同ID
     * @return string data.contract_number 合同编号
     * @return int data.deal_uid 成交学员ID
     * @return string data.cname 成交学员
     * @return string data.project 所属项目
     * @return string data.pay_company 付款公司
     * @return string data.period 期次
     * @return string data.pay_nature 付款性质
     * @return string data.pay_type 付款方式
     * @return string data.notice 款项说明
     * @return string data.invoice_state 开票状态[0未开1已开2无需开票]
     * @return int data.pay_money 付款金额
     * @return int data.pay_bank_no 付款账号
     * @return string data.pay_bank 付款银行
     * @return string data.pay_user 付款户名
     * @return string data.may_pay_date 预计付款日期
     * @return string data.pay_state 付款状态[0未1已]
     * @return string data.pay_date 实际付款日期[时间戳]
     * @return string data.publisher 创建人
     * @return string data.updateman 更新人
     * @return string data.addtime 添加时间
     * @return string data.updatetime 更新时间
     * @return int data.status 状态[2为已退款]
     * 
     * 
     * @return string data.note 备注
     * 
     * @return string msg 提示信息
     */
    public function GetAgentPaymentInfo() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentPaymentInfo($this->id);
        return $info;
    }
    
    /**
     * 21 收票-新增
     * @author Wang Junzhe
     * @DateTime 2019-09-24T14:13:48+0800
     * @desc 收票-新增[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostAddAgentInvoice() {
        $newData = array(
            'agent_id' => $this->agent_id,
            'payment_number' => $this->payment_number,
            'content' => $this->content,
            'money' => $this->money,
            'company' => $this->company,
            'collect_time' => $this->collect_time,
            'note' => $this->note,
            'file' => $this->file
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostAddAgentInvoice($this->uid,$newData,$this->field_list);
        return $info;
    }

    /**
     * 22 开票-编辑
     * @author Wang Junzhe
     * @DateTime 2019-09-24T14:32:06+0800
     * @desc 收票-编辑[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEditAgentInvoice() {
        $newData = array(
            'agent_id' => $this->agent_id,
            'payment_number' => $this->payment_number,
            'content' => $this->content,
            'money' => $this->money,
            'company' => $this->company,
            'collect_time' => $this->collect_time,
            'note' => $this->note,
            'file' => $this->file
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostEditAgentInvoice($this->uid,$this->id,$newData,$this->field_list);
        return $info;
    }

    /**
     * 23 开票-列表
     * @author Wang Junzhe
     * @DateTime 2019-09-24T14:39:25+0800
     * @desc 收票-列表[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.invoice_list 收票列表
     * @return int invoice_list.id 收票ID
     * @return string invoice_list.number 收票编号
     * @return string invoice_list.payment_number 付款编号
     * @return int invoice_list.money 开票金额
     * @return string invoice_list.type 开票类型
     * @return string invoice_list.collect_company 收票公司
     * @return date invoice_list.collect_time 收票日期
     * @return string invoice_list.company 开票公司
     * @return string invoice_list.note 备注
     * @return string invoice_list.publisher 创建人
     * 
     * @return int data.invoice_num 收票数量
     * 
     * @return string msg 提示信息
     */
    public function GetAgentInvoiceList() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentInvoiceList($this->agent_id);
        return $info;
    }

    /**
     * 24 开票-详情
     * @author Wang Junzhe
     * @DateTime 2019-09-24T14:53:46+0800
     * @desc 收票-详情[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 收票ID
     * @return string data.number 收票编号
     * @return string data.payment_number 付款编号
     * @return string data.content 开票内容
     * @return int data.money 开票金额
     * @return string data.type 开票类型
     * @return string data.collect_company 收票公司
     * @return date data.collect_time 收票日期
     * @return string data.company 开票公司
     * @return string data.note 备注
     * @return string data.file 附件
     * @return string data.publisher 创建人
     * @return string data.updateman 更新人
     * @return string data.addtime 添加时间[时间戳]
     * @return string data.updatetime 更新时间[时间戳]
     * 
     * @return string msg 提示信息
     */
    public function GetAgentInvoiceInfo() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentInvoiceInfo($this->id);
        return $info;
    }

    /**
     * 25 退款-新增
     * @author Wang Junzhe
     * @DateTime 2019-09-25T15:15:17+0800
     * @desc 退款-新增[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostAgentRefundAdd() {
        $newData = array(
            'agent_id' => $this->agent_id,
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
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostAgentRefundAdd($this->uid,$newData,$this->field_list);
        return $info;
    }

    /**
     * 26 退款-编辑
     * @author Wang Junzhe
     * @DateTime 2019-09-25T15:31:01+0800
     * @desc 退款-编辑[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostAgentRefundEdit() {
        $newData = array(
            'agent_id' => $this->agent_id,
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
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostAgentRefundEdit($this->uid,$this->id,$newData,$this->field_list);
        return $info;
    }

    /**
     * 27 退款-列表
     * @author Wang Junzhe
     * @DateTime 2019-09-25T15:45:30+0800
     * @desc 退款-列表[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.refund_list 退款列表
     * @return int refund_list.id 退款ID
     * @return string refund_list.payment_number 付款编号
     * @return int refund_list.reason 退款原因
     * @return string refund_list.refund_date 退款日期
     * @return string refund_list.refund_money 退款金额
     * @return date refund_list.refund_type 退款方式
     * @return string refund_list.note 备注
     * @return string refund_list.publisher 创建人
     * 
     * @return int data.refund_num 收票数量
     * 
     * @return string msg 提示信息
     */
    public function GetAgentRefundList() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentRefundList($this->agent_id);
        return $info;
    }

    /**
     * 28 退款-详情
     * @author Wang Junzhe
     * @DateTime 2019-09-25T15:46:27+0800
     * @desc 退款-详情[代理商]（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 退款ID
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
    public function GetAgentRefundInfo() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetAgentRefundInfo($this->id);
        return $info;
    }

    /**
     * 29 师资新增
     * @author Wang Junzhe
     * @DateTime 2019-09-04T10:09:08+0800
     * @desc 师资新增接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostAddTeacher() {
        $newData = array(
            'name' => $this->name,
            'structure_id' => $this->structure_id,
            'post' => $this->post,
            'remark' => $this->remark,
            'field' => $this->field,
            'sex' => $this->sex,
            'birthday' => $this->birthday,
            'phone' => $this->phone,
            'weixin' => $this->weixin,
            'img' => $this->img,
            'email' => $this->email,
            'file' => $this->file,
            'note' => $this->note,
            'course' => $this->course,
            'outline' => $this->outline,
            'bright_spot' => $this->bright_spot,
            'schedule' => $this->schedule,
            'attainment' => $this->attainment,
            'pleased' => $this->pleased,
            'home_area' => $this->home_area,
            'food' => $this->food,
            'stay' => $this->stay,
            'noon_break' => $this->noon_break,
            'bank' => $this->bank,
            'card_number' => $this->card_number,
            'money' => $this->money,
            'collect_type' => $this->collect_type,
            'collect_time' => $this->collect_time,
            'status' => $this->status
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostAddTeacher($this->uid,$newData,$this->field_list);
        return $info;
    }

    /**
     * 30 师资列表
     * @author Wang Junzhe
     * @DateTime 2019-09-04T11:23:52+0800
     * @desc 师资列表接口（开发中）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.count 统计数量
     * @return int count.total_num 师资总数
     * @return int count.having_num 进行中师资
     * @return int count.ending_num 无效师资
     * 
     * @return array data.teacher_list 师资列表
     * @return int teacher_list.id 师资ID
     * @return string teacher_list.name 师资名称
     * @return int teacher_list.structure_id 所属部门id
     * @return string teacher_list.post 所属部门
     * @return string teacher_list.remark 简介
     * @return string teacher_list.field 研究领域
     * @return string teacher_list.sex 性别[0男1女]
     * @return string teacher_list.birthday 生日
     * @return string teacher_list.phone 手机号
     * @return string teacher_list.weixin 微信
     * @return string teacher_list.email 邮箱
     * @return string teacher_list.img 头像
     * @return string teacher_list.file 附件
     * @return string teacher_list.note 备注
     * @return string teacher_list.course 主要课程
     * @return string teacher_list.outline 课程大纲
     * @return string teacher_list.bright_spot 课程亮点
     * @return string teacher_list.schedule 排班表
     * @return string teacher_list.type 师资类型
     * @return string teacher_list.attainment 职业素养
     * @return string teacher_list.pleased 满意度
     * @return string teacher_list.home_area 常住地
     * @return string teacher_list.food 饮食
     * @return string teacher_list.stay 住宿
     * @return string teacher_list.noon_break 午休
     * @return string teacher_list.bank 开户行
     * @return string teacher_list.card_number 卡号
     * @return string teacher_list.money 课酬
     * @return string teacher_list.collect_type 收款方式
     * @return string teacher_list.collect_time 收款时间
     * @return string teacher_list.sort 序号
     * @return string teacher_list.status 状态[1正常2终止]
     * @return string teacher_list.publisher 创建人
     * @return string teacher_list.addtime 添加时间[时间戳]
     * @return string teacher_list.charge_person 负责人
     * @return string teacher_list.follow_time 最后跟进时间[时间戳]
     * @return string teacher_list.next_follow 下次提醒时间[时间戳]
     * @return string teacher_list.follow_up 跟进次数
     * 
     * @return string msg 提示信息
     */
    public function GetTeacherList() {
        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'follow_type' => $this->follow_type,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );

        $domain = new Domain_Xiaozhe();
        $info = $domain->GetTeacherList($newData);
        return $info;
    }

    /**
     * 31 师资编辑
     * @author Wang Junzhe
     * @DateTime 2019-09-04T14:00:45+0800
     * @desc 师资编辑接口（开发中）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEditTeacher() {
        $newData = array(
            'name' => $this->name,
            'structure_id' => $this->structure_id,
            'post' => $this->post,
            'remark' => $this->remark,
            'field' => $this->field,
            'sex' => $this->sex,
            'birthday' => $this->birthday,
            'phone' => $this->phone,
            'weixin' => $this->weixin,
            'email' => $this->email,
            'img' => $this->img,
            'file' => $this->file,
            'note' => $this->note,
            'course' => $this->course,
            'outline' => $this->outline,
            'bright_spot' => $this->bright_spot,
            'schedule' => $this->schedule,
            'attainment' => $this->attainment,
            'pleased' => $this->pleased,
            'home_area' => $this->home_area,
            'food' => $this->food,
            'stay' => $this->stay,
            'noon_break' => $this->noon_break,
            'bank' => $this->bank,
            'card_number' => $this->card_number,
            'money' => $this->money,
            'collect_type' => $this->collect_type,
            'collect_time' => $this->collect_time,
            'status' => $this->status
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostEditTeacher($this->uid,$this->id,$newData,$this->field_list);
        return $info;
    }

    /**
     * 32 [批量]删除师资
     * @author Wang Junzhe
     * @DateTime 2019-09-04T14:24:20+0800
     * @desc [批量]删除师资接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostDeleteTeacher() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostDeleteTeacher($this->uid,$this->id);
        return $info;
    }

    /**
     * 33 师资详情
     * @author Wang Junzhe
     * @DateTime 2019-09-04T14:37:47+0800
     * @desc 师资详情接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.next_agent_id 下一个
     * @return int data.prev_agent_id 上一个
     *
     * @return array data.teacher_info 师资列表
     * @return int teacher_info.id 师资ID
     * @return string teacher_info.name 师资名称
     * @return int teacher_info.structure_id 所属部门id
     * @return string teacher_info.post 所属部门
     * @return string teacher_info.remark 简介
     * @return string teacher_info.field 研究领域
     * @return string teacher_info.sex 性别[0男1女]
     * @return string teacher_info.birthday 生日
     * @return string teacher_info.phone 手机号
     * @return string teacher_info.weixin 微信
     * @return string teacher_info.email 邮箱
     * @return string teacher_info.img 头像
     * @return string teacher_info.file 附件
     * @return string teacher_info.note 备注
     * @return string teacher_info.course 主要课程
     * @return string teacher_info.outline 课程大纲
     * @return string teacher_info.bright_spot 课程亮点
     * @return string teacher_info.schedule 排班表
     * @return string teacher_info.type 师资类型
     * @return string teacher_info.attainment 职业素养
     * @return string teacher_info.pleased 满意度
     * @return string teacher_info.home_area 常住地
     * @return string teacher_info.food 饮食
     * @return string teacher_info.stay 住宿
     * @return string teacher_info.noon_break 午休
     * @return string teacher_info.bank 开户行
     * @return string teacher_info.card_number 卡号
     * @return string teacher_info.money 课酬
     * @return string teacher_info.collect_type 收款方式
     * @return string teacher_info.collect_time 收款时间
     * @return string teacher_info.sort 序号
     * @return string teacher_info.status 状态[1正常2终止]
     * @return string teacher_info.publisher 创建人
     * @return string teacher_info.addtime 添加时间[时间戳]
     * @return string teacher_info.charge_person 负责人
     *
     * @return array data.educlass_list 上课记录
     * @return int educlass_list.id 上课记录ID
     * @return int educlass_list.class_num 上课编号
     * @return string educlass_list.xm 所属项目
     * @return string educlass_list.zhuti 课程主题
     * @return string educlass_list.class_name 班级名称
     * @return int educlass_list.class_date 上课时间[时间戳]
     * @return string educlass_list.class_adress 上课地点
     * @return string educlass_list.fankui 反馈
     * @return string educlass_list.note 备注
     * @return string educlass_list.status 是否付款[0未1已]
     * 
     * @return array data.edusalary_list 付款记录
     * @return int edusalary_list.id 付款记录ID
     * @return string edusalary_list.class_num 上课编号
     * @return int edusalary_list.class_date 上课时间[时间戳]
     * @return int edusalary_list.pay_time 付款时间[时间戳]
     * @return int edusalary_list.pay_money 付款金额
     * @return string edusalary_list.pay_type 付款方式
     * @return string edusalary_list.askforman_name 申请人
     *
     * @return array data.teacher_log 操作日志
     * @return string data.addtime 添加时间[时间戳]
     * @return string data.note 日志内容
     * 
     * @return string msg 提示信息
     */
    public function GetTeacherInfo() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetTeacherInfo($this->uid,$this->id);
        return $info;
    }

    /**
     * 34 师资共享
     * @author Wang Junzhe
     * @DateTime 2019-10-08T09:47:24+0800
     * @desc 共享师资接口（未完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostShareTeacher() {
        $newData = array(
            'uid' => $this->uid,
            'id' => $this->id,
            'share_uid' => $this->share_uid
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostShareTeacher($newData);
        return $info;
    }

    /**
     * 35 移交师资
     * @author Wang Junzhe
     * @DateTime 2019-09-16T09:49:17+0800
     * @desc 移交师资接口（未完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostChangeTeacher() {
        $newData = array(
            'uid' => $this->uid,
            'new_uid' => $this->new_uid,
            'id_arr' => $this->id_arr
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostChangeTeacher($newData);
        return $info;
    }

    /**
     * 36 消息列表
     * @author Wang Junzhe
     * @DateTime 2019-10-12T14:05:14+0800
     * @desc 消息列表接口
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.msg_list 消息列表
     * @return int msg_list.id 消息id
     * @return int msg_list.type 消息类型【无需】
     * @return string msg_list.type_name 消息类型
     * @return string msg_list.title 消息标题
     * @return string msg_list.content 消息内容
     * @return int msg_list.creatid 发布人
     * @return int msg_list.to_uid 接收人
     * @return int msg_list.addtime 添加时间[时间戳]
     * @return int msg_list.end_time 结束时间[时间戳]为0不显示
     * @return int msg_list.notice_time 提醒时间[时间戳]为0不显示
     * @return int msg_list.notice_num 提醒次数
     * @return int msg_list.status 状态[0未读1已读]
     * @return string msg_list.action 操作[share共享|change移交|deal成交|send分配]
     * @return string msg_list.table_name 数据表[customer客户|agent代理|project_side项目|teacher师资]
     * @return int msg_list.info_id 信息id
     * 
     * @return int data.msg_num 消息数量
     * @return int data.wd_msg_num 未读消息数量
     * 
     * @return string msg 提示信息
     */
    public function GetMsgList() {
        $newData = array(
            'uid' => $this->uid,
            'keywords' => $this->keywords,
            'where_arr' => $this->where_arr,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );

        $domain = new Domain_Xiaozhe();
        $info = $domain->GetMsgList($newData);
        return $info;
    }

    /**
     * 37 消息详情
     * @author Wang Junzhe
     * @DateTime 2019-10-12T14:07:20+0800
     * @desc 消息详情接口
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 消息id
     * @return int data.type 消息类型【无需】
     * @return string data.type_name 消息类型
     * @return string data.title 消息标题
     * @return string data.content 消息内容
     * @return int data.creatid 发布人
     * @return int data.to_uid 接收人
     * @return int data.addtime 添加时间[时间戳]
     * @return int data.end_time 结束时间[时间戳]为0不显示
     * @return int data.notice_time 提醒时间[时间戳]为0不显示
     * @return int data.notice_num 提醒次数
     * @return int data.status 状态[0未读1已读]
     * @return string data.action 操作[share共享|change移交|deal成交|send分配]
     * @return string data.table_name 数据表[customer客户|agent代理|project_side项目|teacher师资]
     * @return int data.info_id 信息id
     * 
     * @return string msg 提示信息
     */
    public function GetMsgInfo() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetMsgInfo($this->id);
        return $info;
    }

    /**
     * 38 消息[批量]删除
     * @author Wang Junzhe
     * @DateTime 2019-10-12T14:07:20+0800
     * @desc 消息删除接口
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostDeleteMsg() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostDeleteMsg($this->uid,$this->id_arr);
        return $info;
    }

    /**
     * 39 消息[批量]已读
     * @author Wang Junzhe
     * @DateTime 2019-10-12T14:07:20+0800
     * @desc 消息设置已读接口
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostReadMsg() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostReadMsg($this->uid,$this->id_arr);
        return $info;
    }

    /**
     * 40 成交属性分析
     * @author Wang Junzhe
     * @DateTime 2019-10-15T09:40:18+0800
     * @desc 成交属性分析接口
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return array data.sex_list 性别统计
     * @return string sex_list.name  性别
     * @return string sex_list.value  个数
     *
     * @return array data.age_list 年龄统计
     * @return string age_list.name  年龄
     * @return string age_list.value  个数
     *
     * @return array data.education_list 学历统计
     * @return string education_list.name  学历
     * @return string education_list.value  个数
     *
     * @return array data.work_list 职业统计
     * @return string work_list.name  职业
     * @return string work_list.value  个数
     *
     * @return array data.money_list 费用统计
     * @return string money_list.name  费用
     * @return string money_list.value  个数
     *
     * @return array data.month_list 月份统计
     * @return string month_list.name  月份
     * @return string month_list.value  个数
     *
     * @return array data.general_list 简章统计
     * @return string general_list.name  简章
     * @return string general_list.value  个数
     *
     * @return array data.yuanxi_list 院校统计
     * @return string yuanxi_list.name  院校
     * @return string yuanxi_list.value  个数
     *
     * @return array data.direction_list 方向统计
     * @return string direction_list.name  方向
     * @return string direction_list.value  个数
     *
     * @return array data.area_list 地区统计
     * @return string area_list.name  地区
     * @return string area_list.value  个数
     * 
     * @return int data.total 总数
     * 
     * @return string msg 提示信息
     */
    public function GetStatisticsList() {
        $newData = array(
            'type' => $this->type,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetStatisticsList($newData);
        return $info;
    }

    /**
     * 41 公共新增跟进记录
     * @author Wang Junzhe
     * @DateTime 2019-11-15T15:19:13+0800
     * @desc 公共新增跟进记录
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostAddPublicFollow() {
        $newData = array(
            'type' => $this->type,
            'info_id' => $this->info_id,
            'subject' => $this->subject,
            'types' => $this->types,
            'uid' => $this->uid,
            'next_time' => $this->next_time,
            'file' => $this->file,
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->PostAddPublicFollow($newData);
        return $info;
    }

    /**
     * 42 公共跟进记录列表
     * @author Wang Junzhe
     * @DateTime 2019-11-18T15:17:37+0800
     * @desc 公共跟进记录列表
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 跟进记录ID
     * @return int data.type 跟进类型[0代理商1项目方2师资]
     * @return string data.subject 主题
     * @return string data.types 类型
     * @return int data.addtime 添加时间[时间戳]
     * @return int data.next_time 下次提醒时间[时间戳]
     * @return string data.file 附件
     * @return string data.publisher 添加人
     * 
     * @return string msg 提示信息
     */
    public function GetPublicFollowList() {
        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'info_id' => $this->info_id
        );
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetPublicFollowList($newData);
        return $info;
    }

    /**
     * 43 公共跟进记录详情
     * @author Wang Junzhe
     * @DateTime 2019-11-20T09:38:50+0800
     * @desc 公共跟进记录详情
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 跟进记录ID
     * @return int data.type 跟进类型[0代理商1项目方2师资]
     * @return int data.info_id [代理/项目/师资]ID
     * @return string data.subject 主题
     * @return string data.types 类型
     * @return int data.addtime 添加时间[时间戳]
     * @return int data.next_time 下次提醒时间[时间戳]
     * @return string data.file 附件
     * @return string data.publisher 添加人
     * 
     * @return string msg 提示信息
     */
    public function GetPublicFollowInfo() {
        $domain = new Domain_Xiaozhe();
        $info = $domain->GetPublicFollowInfo($this->id);
        return $info;
    }
    

}
 
<?php
namespace App\Api;

use PhalApi\Api;
use App\Domain\Structure;
use PhalApi\Redis\Lite;
/**
 * 用户及客户模块接口
 */
class User extends Api {

    public function getRules() {
        return array(
            'login' => array(
                'username' => array('name' => 'username', 'require' => true, 'min' => 1, 'max' => 50, 'desc' => '用户名'),
                'password' => array('name' => 'password', 'require' => true, 'min' => 6, 'max' => 20, 'desc' => '密码'),
            ),
            'loginChangeStatus'=> array(
                'uid' => array('name' => 'uid', 'require' => true, 'min' => 1, 'max' => 50, 'desc' => '用户名'),
                'noticestatus' => array('name' => 'noticestatus', 'require' => true,  'desc' => '1为已读'),
            ),
            'loginSign' => array(
                'id' => array('name' => 'id', 'require' => true, 'type'=>'int','desc' => '用户名ID'),
                'token' => array('name' => 'token', 'require' => false, 'min' => 32, 'max' => 32, 'desc' => 'token'),
            ),
            'AddCustomer' => array(
                'uid' =>array('name'=>'uid','require'=>true,'desc'=>'用户id'),
                'cname' =>array('name'=>'cname','require'=>true,'desc'=>'客户名称'),
                'agent_name'=> array('name' => 'agent_name', 'type' => 'string','require' => false, 'desc' => '学员来源(花名)'),
                'agent_num'=> array('name' => 'agent_num', 'type' => 'string','require' => false, 'desc' => '学员来源(花名)编号'),
                'own'=> array('name' => 'own', 'type' => 'int','require' => false, 'desc' => '学员来源(个人花名)ID','default'=>'0'),
                'cphone' => array('name' => 'cphone', 'type' => 'string','require' => false, 'desc' => '联系电话'),
                'formwhere' => array('name' => 'formwhere', 'type' => 'string','require' => false, 'desc' => '归属地'),
                'attachment' =>array('name'=>'attachment','require'=>false,'desc'=>'附件'),
                'city' =>array('name'=>'city','require'=>false,'type'=>'string','desc'=>'格式:省,市,县'),
                'adress' =>array('name'=>'adress','require'=>false,'type'=>'string','desc'=>'详细住址'),
                'note' =>array('name'=>'note','require'=>false,'desc'=>'备注'),
                //联系方式
                'cphonetwo' => array('name' => 'cphonetwo', 'type' => 'string','require' => false, 'desc' => '联系电话2'),
                'formwhere2' => array('name' => 'formwhere2', 'type' => 'string','require' => false, 'desc' => '归属地2'),
                'cphonethree' => array('name' => 'cphonethree', 'type' => 'string','require' => false, 'desc' => '联系电话3'),
                'formwhere3' => array('name' => 'formwhere3', 'type' => 'string','require' => false, 'desc' => '归属地3'),
                'telephone' => array('name' => 'telephone', 'type' => 'string','require' => false, 'desc' => '座机'),
                'wxnum' => array('name' => 'wxnum', 'type' => 'string','require' => false, 'desc' => '微信号'),
                'cemail' => array('name' => 'cemail', 'type' => 'string','require' => false, 'desc' => '邮箱'),
                'qq' => array('name' => 'qq', 'type' => 'string','require' => false, 'desc' => 'qq'),
                //意向
                'ittnzy' =>array('name'=>'ittnzy','type' => 'array','require'=>false,'desc'=>'意向专业'),
                'ittnxm' =>array('name'=>'ittnxm','type' => 'array','require'=>false,'desc'=>'意向项目(多选)'),
                'ittnyx' =>array('name'=>'ittnyx','type' => 'array','require'=>false,'desc'=>'意向院校'),
                'ittngj' =>array('name'=>'ittngj','require'=>false,'desc'=>'意向地区'),
                'budget' =>array('name'=>'budget','require'=>false,'desc'=>'预算'),
                //背景
                'timeline' =>array('name'=>'timeline','require'=>false,'desc'=>'预计入学时间'),
                'graduate' =>array('name'=>'graduate','require'=>false,'desc'=>'毕业院校'),
                'graduatezy' =>array('name'=>'graduatezy','require'=>false,'desc'=>'毕业专业'),
                //工作
                'sex' => array('name' => 'sex', 'type' => 'string','require' => false, 'desc' => '性别'),
                'age' => array('name' => 'age', 'type' => 'string','require' => false, 'desc' => '年龄'),
                'station' => array('name' => 'station', 'type' => 'string','require' => false, 'desc' => '岗位'),
                'occupation' => array('name' => 'occupation', 'type' => 'string','require' => false, 'desc' => '职业'),
                'industry' => array('name' => 'industry', 'type' => 'string','require' => false, 'desc' => '行业'),
                'character' => array('name' => 'character', 'type' => 'string','require' => false, 'desc' => '客户性格'),
                'company' => array('name' => 'company', 'type' => 'string','require' => false, 'desc' => '单位'),
                //其他
                'next_follow'=>array('name'=>'next_follow','require'=>false,'desc'=>'下次跟进时间'),
                'tolink' =>array('name'=>'tolink','require'=>false,'desc'=>'发起沟通的网址'),
                'creatid' =>array('name'=>'creatid','require'=>true,'desc'=>'创建人ID'),
                'field_list' => array('name' => 'field_list', 'type' => 'array','min' => 1,'require' => false, 'desc' => '灵活字段 (必填字段:学员级别)（不必填）','default' => []),
                'group' => array('name' => 'group', 'type' => 'string','require' => false, 'desc' => '部门'),
                'sea_type'=> array('name' => 'sea_type', 'type' => 'string','require' => false, 'desc' => '公海是1 私海是0 默认0'),
                //开票
                'invoice_company'=> array('name' => 'invoice_company', 'type' => 'string','require' => false, 'desc' => '开票公司'),
                'taxpayer_num'=> array('name' => 'taxpayer_num', 'type' => 'string','require' => false, 'desc' => '	纳税人识别号	'),
                'bank'=> array('name' => 'bank', 'type' => 'string','require' => false, 'desc' => '开户行'),
                'open_bank'=> array('name' => 'open_bank', 'type' => 'string','require' => false, 'desc' => '开户名称'),
                'bank_num'=> array('name' => 'bank', 'type' => 'string','require' => false, 'desc' => '开户账号'),
                'bank_adress'=> array('name' => 'bank_adress', 'type' => 'string','require' => false, 'desc' => '开户地址'),
                'legal_person'=> array('name' => 'legal_person', 'type' => 'string','require' => false, 'desc' => '法人'),
                'business_license'=> array('name' => 'business_license', 'type' => 'string','require' => false, 'desc' => '营业执照'),
                //一手项目方
                'project_name'=> array('name' => 'project_name', 'type' => 'string','require' => false, 'desc' => '一手项目方花名'),
                'project_num'=> array('name' => 'project_num', 'type' => 'string','require' => false, 'desc' => '一手项目方编号'),
                'token'=> array('name' => 'token', 'type' => 'string','require' => true, 'desc' => '表单令牌'),
            ),
            'loginOut' => array(
                'uid' => array('name' => 'uid', 'require' => true, 'min' => 1, 'max' => 50, 'desc' => '用户id'),
            ),
            'EditCustomer' => array(
                'id' => array('name'=>"id",'require'=>true,'desc'=>'客户ID'),
                'uid' => array('name'=>"uid",'require'=>true,'desc'=>'操作员id'),
                'bid' => array('name'=>"bid",'require'=>false,'desc'=>'bid'),
                'cname' =>array('name'=>'cname','require'=>true,'desc'=>'客户名称'),
                'attachment' =>array('name'=>'attachment','require'=>false,'desc'=>'附件'),
                'city' =>array('name'=>'city','require'=>false,'type'=>'string','desc'=>'格式:省,市,县'),
                'adress' =>array('name'=>'adress','require'=>false,'type'=>'string','desc'=>'详细住址'),
                'note' =>array('name'=>'note','require'=>false,'desc'=>'备注'),
                //联系方式
                'cphone' => array('name' => 'cphone', 'type' => 'string','require' => false, 'desc' => '联系电话'),
                'cphonetwo' => array('name' => 'cphonetwo', 'type' => 'string','require' => false, 'desc' => '联系电话2'),
                'formwhere2' => array('name' => 'formwhere2', 'type' => 'string','require' => false, 'desc' => '归属地2'),
                'cphonethree' => array('name' => 'cphonethree', 'type' => 'string','require' => false, 'desc' => '联系电话3'),
                'formwhere3' => array('name' => 'formwhere3', 'type' => 'string','require' => false, 'desc' => '归属地3'),
                'telephone' => array('name' => 'telephone', 'type' => 'string','require' => false, 'desc' => '座机'),
                'wxnum' => array('name' => 'wxnum', 'type' => 'string','require' => false, 'desc' => '微信号'),
                'cemail' => array('name' => 'cemail', 'type' => 'string','require' => false, 'desc' => '邮箱'),
                'qq' => array('name' => 'qq', 'type' => 'string','require' => false, 'desc' => 'qq'),
                //意向
                'ittnzy' =>array('name'=>'ittnzy','type' => 'array','require'=>false,'desc'=>'意向专业'),
                'ittnxm' =>array('name'=>'ittnxm','type' => 'array','require'=>false,'desc'=>'意向项目(多选)'),
                'ittnyx' =>array('name'=>'ittnyx','type' => 'array','require'=>false,'desc'=>'意向院校'),
                'ittngj' =>array('name'=>'ittngj','require'=>false,'desc'=>'意向地区'),
                'budget' =>array('name'=>'budget','require'=>false,'desc'=>'预算'),
                //背景
                'timeline' =>array('name'=>'timeline','require'=>false,'desc'=>'预计入学时间'),
                'graduate' =>array('name'=>'graduate','require'=>false,'desc'=>'毕业院校'),
                'graduatezy' =>array('name'=>'graduatezy','require'=>false,'desc'=>'毕业专业'),
                //工作
                'sex' => array('name' => 'sex', 'type' => 'string','require' => false, 'desc' => '性别'),
                'age' => array('name' => 'age', 'type' => 'string','require' => false, 'desc' => '年龄'),
                'station' => array('name' => 'station', 'type' => 'string','require' => false, 'desc' => '岗位'),
                'occupation' => array('name' => 'occupation', 'type' => 'string','require' => false, 'desc' => '职业'),
                'industry' => array('name' => 'industry', 'type' => 'string','require' => false, 'desc' => '行业'),
                'character' => array('name' => 'character', 'type' => 'string','require' => false, 'desc' => '客户性格'),
                'company' => array('name' => 'company', 'type' => 'string','require' => false, 'desc' => '单位'),
                //其他
                'next_follow'=>array('name'=>'next_follow','require'=>false,'desc'=>'下次跟进时间'),
                'tolink' =>array('name'=>'tolink','require'=>false,'desc'=>'发起沟通的网址'),
                'creatid' =>array('name'=>'creatid','require'=>true,'desc'=>'创建人ID'),
                'field_list' => array('name' => 'field_list', 'type' => 'array','min' => 1,'require' => false, 'desc' => '灵活字段 (必填字段:学员级别)（不必填）','default' => []),
                'group' => array('name' => 'group', 'type' => 'string','require' => false, 'desc' => '部门'),
                'sea_type'=> array('name' => 'sea_type', 'type' => 'string','require' => false, 'desc' => '公海是1 私海是0 默认0'),
                //开票
                'invoice_company'=> array('name' => 'invoice_company', 'type' => 'string','require' => false, 'desc' => '开票公司'),
                'taxpayer_num'=> array('name' => 'taxpayer_num', 'type' => 'string','require' => false, 'desc' => '	纳税人识别号	'),
                'bank'=> array('name' => 'bank', 'type' => 'string','require' => false, 'desc' => '开户行'),
                'open_bank'=> array('name' => 'open_bank', 'type' => 'string','require' => false, 'desc' => '开户名称'),
                'bank_num'=> array('name' => 'bank', 'type' => 'string','require' => false, 'desc' => '开户账号'),
                'bank_adress'=> array('name' => 'bank_adress', 'type' => 'string','require' => false, 'desc' => '开户地址'),
                'legal_person'=> array('name' => 'legal_person', 'type' => 'string','require' => false, 'desc' => '法人'),
                'business_license'=> array('name' => 'business_license', 'type' => 'string','require' => false, 'desc' => '营业执照'),
                //一手项目方
                'project_name'=> array('name' => 'project_name', 'type' => 'string','require' => false, 'desc' => '一手项目方花名'),
                'project_num'=> array('name' => 'project_num', 'type' => 'string','require' => false, 'desc' => '一手项目方编号'),

            ),
            'dofp'=>array(
                'id' => array('name'=>"id",'require'=>true,'desc'=>'客户ID'),
                'type' => array('name'=>"type",'require'=>true,'desc'=>'1是新增 2是编辑','default'=>'1'),
                'lid' => array('name'=>"lid",'require'=>false,'desc'=>'跟进列表的id'),
                'uid' => array('name'=>"uid",'require'=>true,'desc'=>'操作员id'),
                'bid' => array('name'=>"bid",'require'=>false,'desc'=>'分享表id'),
                'cname' => array('name'=>"cname",'require'=>true,'desc'=>'客户姓名'),
                'zhuti' => array('name'=>"zhuti",'require'=>true,'desc'=>'主题'),
                'types' => array('name'=>"types",'require'=>true,'desc'=>'类型'),
                'executor' => array('name'=>"executor",'require'=>false,'desc'=>'执行人:默认用户id'),
                'description' => array('name'=>"description",'require'=>false,'desc'=>'行动描述'),
                'next_time' => array('name'=>"next_time",'require'=>false,'desc'=>'下次回访时间','default'=>'0'),
                'enclosure' => array('name'=>"enclosure",'require'=>false,'desc'=>'附件路径'),
                'now_time' => array('name'=>"now_time",'require'=>true,'desc'=>'创建时间'),

            ),
            'Dotop'=>array(
                'id' => array('name'=>"id",'require'=>true,'desc'=>'客户列表ID'),
                'uid' => array('name'=>"uid",'require'=>true,'desc'=>'操作员id'),
                'is_top'=>array('name'=>"is_top",'require'=>true,'desc'=>'是否置顶1为置顶0不置顶','default'=>'0')
            ),
            'Customerinfo'=>array(
                'id' => array('name'=>"id",'require'=>true,'desc'=>'客户列表ID'),
                'uid' => array('name'=>"uid",'require'=>true,'desc'=>'操作员id'),
                'bid' => array('name'=>"bid",'require'=>false,'type' => 'string','desc'=>'表id'),
            ),
            'ModeDelete'=>array(
                'id' => array('name'=>"id",'require'=>true,'desc'=>'跟进列表ID'),
                'uid' => array('name'=>"uid",'require'=>true,'desc'=>'操作员id'),
            ),
            'DofpList'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'min' => 0,'desc' => '搜索类型（不必填）','default' => '0' ),
                'keywords' => array('name' => 'keywords','type' => 'string','require' => false,'desc' => '关键词（不必填）','default' => '' ),
                'where_arr' => array('name' => 'where_arr', 'type' => 'array','require' => false, 'desc' => '高级搜索字段（不必填）'),
                'order_by' => array('name' => 'order_by', 'type' => 'array','require' => false, 'desc' => '排序字段（不必填）'),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostChangeGroup' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'groupid' => array('name' => 'groupid','type' => 'int','require' => true,'desc' => '所有更改的部门id（必填）(最后一级的部门id)','default' => '' ),
                'cid_arr' => array('name' => 'cid_arr','type' => 'array','require' => true,'desc' => '客户id数组（必填）','default' => array('3') ),
            ),
            'PostTransferAll' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'Transid' => array('name' => 'Transid','type' => 'int','require' => true,'min' => 1,'desc' => '移交人id','default' => '' ),
                'cid_arr' => array('name' => 'cid_arr','type' => 'array','require' => true,'desc' => '客户id数组（必填）','default' => '' ),
            ),

            'AddTicket' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'Transid' => array('name' => 'Transid','type' => 'int','require' => true,'min' => 1,'desc' => '移交人id','default' => '' ),
                'cid_arr' => array('name' => 'cid_arr','type' => 'array','require' => true,'desc' => '客户id数组（必填）','default' => array('25') ),
            ),
            'AddTicket' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'min' => 1,'desc' => '客户id（必填）','default' => '' ),
                'order_no' => array('name' => 'order_no','type' => 'string','require' => true,'desc' => '订单号（必填）','default' => '' ),
                'project_name' => array('name' => 'project_name','type' => 'string','require' => false,'desc' => '一手项目方花名','default' => '' ),
                'agent_name' => array('name' => 'agent_name','type' => 'string','require' => false,'desc' => '代理商花名','default' => '' ),
                'project_contract' => array('name' => 'project_contract','type' => 'string','require' => false,'desc' => '项目方合同','default' => '' ),
                'title' => array('name' => 'title','type' => 'string','require' => false,'desc' => '开票内容','default' => '' ),
                'total' => array('name' => 'total','type' => 'string','require' => false,'desc' => '开票金额','default' => '' ),
                'invoice_company' => array('name' => 'invoice_company','type' => 'string','require' => true,'desc' => '开票公司','default' => '' ),
                'open_types' => array('name' => 'open_types','type' => 'string','require' => true,'desc' => '开票类型','default' => '' ),
                'taxpayer_num' => array('name' => 'taxpayer_num','type' => 'string','require' => true,'desc' => '纳税人识别号','default' => '' ),
                'bank' => array('name' => 'bank','type' => 'string','require' => true,'desc' => '开户银行','default' => '' ),
                'open_bank' => array('name' => 'open_bank','type' => 'string','require' => true,'desc' => '开户名称','default' => '' ),
                'bank_num' => array('name' => 'bank_num','type' => 'string','require' => false,'desc' => '银行账号','default' => '' ),
                'bank_adress' => array('name' => 'bank_adress','type' => 'string','require' => true,'desc' => '开户地址','default' => '' ),
                'bank_phone' => array('name' => 'bank_phone','type' => 'string','require' => false,'desc' => '联系电话','default' => '' ),
                'tickets_company' => array('name' => 'tickets_company','type' => 'string','require' => true,'desc' => '收票公司名称','default' => '' ),
                'tickets_open_bank' => array('name' => 'tickets_open_bank','type' => 'string','require' => true,'desc' => '收票开户名称project_name','default' => '' ),
                'tickets_num' => array('name' => 'tickets_num','type' => 'string','require' => true,'desc' => '收票纳税人识别号','default' => '' ),
                'total' => array('name' => 'total','type' => 'string','require' => false,'desc' => '开票金额','default' => '' ),
                'tickets_bank' => array('name' => 'tickets_bank','type' => 'string','require' => false,'desc' => '开票公司开户银行','default' => '' ),
                'tickets_bank_num' => array('name' => 'tickets_bank_num','type' => 'string','require' => false,'desc' => '收票公司银行账号','default' => '' ),
                'tickets_bank_adress' => array('name' => 'tickets_bank_adress','type' => 'string','require' => false,'desc' => '开户地址','default' => '' ),
                'tickets_phone' => array('name' => 'tickets_phone','type' => 'string','require' => false,'desc' => '收票公司联系方式','default' => '' ),
                'notes' => array('name' => 'notes','type' => 'string','require' => false,'desc' => '备注','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件','default' => '' ),
                'tickets_time' => array('name' => 'tickets_time','type' => 'string','require' => true,'desc' => '开票时间','default' => '' ),
            ),
            'GetPhoneRepeat'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'phone' => array('name' => 'phone','type' => 'int','require' => true,'min' => 1,'desc' => '需要验证的手机号（必填）','default' => '' ),
            ),
            'EditTicket' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '开票id（必填）','default' => '' ),
                'open_types' => array('name' => 'open_types','type' => 'string','require' => true,'desc' => '开票类型','default' => '' ),
                'project_name' => array('name' => 'project_name','type' => 'string','require' => false,'desc' => '一手项目方花名','default' => '' ),
                'agent_name' => array('name' => 'project_name','type' => 'string','require' => false,'desc' => '代理商花名','default' => '' ),
                'project_contract' => array('name' => 'project_contract','type' => 'string','require' => false,'desc' => '项目方合同','default' => '' ),
                'title' => array('name' => 'title','type' => 'string','require' => true,'desc' => '开票内容','default' => '' ),
                'total' => array('name' => 'total','type' => 'string','require' => true,'desc' => '开票金额','default' => '' ),
                'invoice_company' => array('name' => 'invoice_company','type' => 'string','require' => false,'desc' => '开票公司','default' => '' ),
                'taxpayer_num' => array('name' => 'taxpayer_num','type' => 'string','require' => false,'desc' => '纳税人识别号','default' => '' ),
                'bank' => array('name' => 'bank','type' => 'string','require' => false,'desc' => '开户银行','default' => '' ),
                'open_bank' => array('name' => 'open_bank','type' => 'string','require' => false,'desc' => '开户名称','default' => '' ),
                'bank_num' => array('name' => 'bank_num','type' => 'string','require' => false,'desc' => '银行账号','default' => '' ),
                'bank_adress' => array('name' => 'bank_adress','type' => 'string','require' => false,'desc' => '开户地址','default' => '' ),
                'bank_phone' => array('name' => 'bank_phone','type' => 'string','require' => false,'desc' => '联系电话','default' => '' ),
                'tickets_company' => array('name' => 'tickets_company','type' => 'string','require' => false,'desc' => '收票公司名称','default' => '' ),
                'tickets_num' => array('name' => 'tickets_num','type' => 'string','require' => false,'desc' => '收票纳税人识别号','default' => '' ),
                'total' => array('name' => 'total','type' => 'string','require' => false,'desc' => '开票金额','default' => '' ),
                'tickets_bank' => array('name' => 'tickets_bank','type' => 'string','require' => false,'desc' => '开票公司开户银行','default' => '' ),
                'tickets_bank_num' => array('name' => 'tickets_bank_num','type' => 'string','require' => false,'desc' => '收票公司银行账号','default' => '' ),
                'tickets_bank_adress' => array('name' => 'tickets_bank_adress','type' => 'string','require' => false,'desc' => '开户地址','default' => '' ),
                'tickets_phone' => array('name' => 'tickets_phone','type' => 'string','require' => false,'desc' => '收票公司联系方式','default' => '' ),
                'notes' => array('name' => 'notes','type' => 'string','require' => false,'desc' => '备注','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件','default' => '' ),
                'tickets_time' => array('name' => 'tickets_time','type' => 'string','require' => false,'desc' => '开票时间','default' => '' ),
            ),
            'DeleTicket'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '开票id（必填）','default' => '' ),
            ),
            'AddRefund'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'min' => 1,'desc' => '客户id（必填）','default' => '' ),
                'order_no' => array('name' => 'order_no','type' => 'string','require' => true,'desc' => '订单号（必填）','default' => '' ),
                'agent_name' => array('name' => 'agent_name','type' => 'string','require' => false,'desc' => '代理商','default' => '' ),
                'project_name' => array('name' => 'project_name','type' => 'string','require' => false,'desc' => '项目方','default' => '' ),
                'agent_contract' => array('name' => 'agent_contract','type' => 'string','require' => false,'desc' => '代理商合同','default' => '' ),
                'project_contract' => array('name' => 'project_contract','type' => 'string','require' => false,'desc' => '项目方合同','default' => '' ),
                'project' => array('name' => 'project','type' => 'string','require' => true,'desc' => '所属项目','default' => '' ),
                'invoice_company' => array('name' => 'invoice_company','type' => 'string','require' => true,'desc' => '付款公司名称','default' => '' ),
                'refund_note' => array('name' => 'refund_note','type' => 'string','require' => true,'desc' => '退款原因','default' => '' ),
                'opens_account' => array('name' => 'opens_account','type' => 'string','require' => true,'desc' => '对方开户名称','default' => '' ),
                'opens_bank' => array('name' => 'opens_bank','type' => 'string','require' => false,'desc' => '对方开户银行','default' => '' ),
                'opens_num' => array('name' => 'opens_num','type' => 'string','require' => true,'desc' => '对方开户账号','default' => '' ),
                'refund_type' => array('name' => 'refund_type','type' => 'string','require' => false,'desc' => '退款方式','default' => '' ),
                'refund_time' => array('name' => 'refund_time','type' => 'string','require' => true,'desc' => '退款时间','default' => '' ),
                'refund_total' => array('name' => 'refund_total','type' => 'string','require' => true,'desc' => '退款金额','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件','default' => '' ),
            ),
            'EditRefund'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'desc' => '退款列表id（必填）','default' => '' ),
                'order_no' => array('name' => 'order_no','type' => 'string','require' => true,'desc' => '订单号（必填）','default' => '' ),
                'agent_name' => array('name' => 'agent_name','type' => 'string','require' => true,'desc' => '代理商','default' => '' ),
                'project_name' => array('name' => 'project_name','type' => 'string','require' => true,'desc' => '项目方','default' => '' ),
                'agent_contract' => array('name' => 'agent_contract','type' => 'string','require' => true,'desc' => '代理商合同','default' => '' ),
                'project_contract' => array('name' => 'project_contract','type' => 'string','require' => true,'desc' => '项目方合同','default' => '' ),
                'project' => array('name' => 'project','type' => 'string','require' => true,'desc' => '所属项目','default' => '' ),
                'invoice_company' => array('name' => 'invoice_company','type' => 'string','require' => true,'desc' => '付款公司名称','default' => '' ),
                'refund_note' => array('name' => 'refund_note','type' => 'string','require' => true,'desc' => '退款原因','default' => '' ),
                'opens_account' => array('name' => 'opens_account','type' => 'string','require' => true,'desc' => '对方开户名称','default' => '' ),
                'opens_bank' => array('name' => 'opens_bank','type' => 'string','require' => false,'desc' => '对方开户银行','default' => '' ),
                'opens_num' => array('name' => 'opens_num','type' => 'string','require' => true,'desc' => '对方开户账号','default' => '' ),
                'refund_type' => array('name' => 'refund_type','type' => 'string','require' => false,'desc' => '退款方式','default' => '' ),
                'refund_time' => array('name' => 'refund_time','type' => 'string','require' => true,'desc' => '退款时间','default' => '' ),
                'refund_total' => array('name' => 'refund_total','type' => 'string','require' => true,'desc' => '退款金额','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '备注','default' => '' ),
                'file' => array('name' => 'file','type' => 'string','require' => false,'desc' => '附件','default' => '' ),
            ),
            'DeleRefund'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '退款列表id（必填）','default' => '' ),
            ),
            'GetInfoList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'min' => 1,'desc' => '当前客户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => true,'min' => 0,'desc' => '搜索类型[1成交2开票3退款]（必填）','default' => '1' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'GetCusLog'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'min' => 1,'desc' => '当前客户id（必填）','default' => '' ),
            ),
            'getOrder'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'min' => 1,'desc' => '当前客户id（必填）','default' => '' ),
            ),
            'PostOrderInfo'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '当前列表id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => true,'min' => 0,'desc' => '搜索类型[1成交2开票3退款]（必填）','default' => '1' ),
            ),
            'GetUserOrder'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => true,'min' => 0,'desc' => '搜索类型[1成交2开票3退款]（必填）','default' => '1' ),
            ),
            'GetUserInfo'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
            ),
            'ChangePassword'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'oldpassword'=>array('name' => 'oldpassword','type' => 'string','require' => true,'min' => 6,'desc' => '旧密码（必填）','default' => '' ),
                'newpassword'=>array('name' => 'newpassword','type' => 'string','require' => true,'min' => 6,'desc' => '新密码（必填）','default' => '' ),
            ),
            'GetLoveFiled'=>array(
                'uid'=>array('name' => 'uid','type' => 'string','require' => true,'desc' => '用户id（必填）','default' => '' ),
                'filed_title'=>array('name' => 'filed_title','type' => 'string','require' => true,'min' => 6,'desc' => '字段名称（必填）','default' => '' ),
                'type'=>array('name' => 'type','type' => 'int','require' => true,'desc' => '2是客户列表字段与model同字段（必填）','default' => '' ),

            ),
            'GetAllFolwer'=>array(
                'uid'=>array('name' => 'uid','type' => 'string','require' => true,'desc' => '用户id（必填）','default' => '' ),
            ),

        );
    }

    /**
     * 设置公告已读状态
     * @author Dai Ming
     * @DateTime 14:06 2020/6/9 0009
     * @desc    设置公告已读状态（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function loginChangeStatus()
    {
        \PhalApi\DI()->notorm->user->where(array("id"=>$this->uid))->update(['noticestatus'=>$this->noticestatus]);
        $re=array('code'=>1,'msg'=>'设置已读成功!','data'=>[],'info'=>[]);
        return $re;
    }

    /**
     * 36-退出登录
     * @author Dai Ming
     * @DateTime 16:01 2019/11/5 0005
     * @desc   退出登录（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function  loginOut(){
        \App\LoginOut($this->uid);
        $re=array('code'=>1,'msg'=>'退出成功!','data'=>[],'info'=>[]);
        return $re;
//        var_dump($uid);
    }
    /**
     * 获取所有花名(包括代理商)
     * @author Dai Ming
     * @DateTime 09:46 2019/11/19 0019
     * @desc 获取所有花名(包括代理商)（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetAllFolwer()
    {
        $list=new Structure();
        $data=$list->GetAllFolwer($this->uid);
        return $data;
    }

    /**
     * 获取用户喜好字段排序
     * @author Dai Ming
     * @DateTime 14:41 2019/11/12 0012
     * @desc    获取用户喜好字段排序（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetLoveFiled()
    {
        $list=new Structure();
        $data=$list->GetLoveFiled($this->uid,$this->filed_title,$this->type);
        return $data;
    }

    /**
     * 22- 获取用户详情
     * @author Dai Ming
     * @DateTime 16:59 2019/11/1 0001
     * @desc 获取用户详情 （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function getUserInfo()
    {
        $user=new Structure();
        $data=$user->getUserInfo($this->uid);
        return $data;
    }

    /**
     * 04- 修改密码
     * @author Dai Ming
     * @DateTime 16:23 2019/11/1 0001
     * @desc  修改密码（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function ChangePassword()
    {
        $user=new Structure();
        $data=$user->ChangePassword($this->uid,$this->oldpassword,$this->newpassword);
        return $data;
    }


    /**
     *08- 登录接口
     * @desc 根据账号和密码进行登录操作 已完成
     * @return int ret 200代表请求成功
     * @return int code 1代表登录成功其他为失败
     * @return string ID 成功返回ID
     * @return string username 成功返回用户名称
     * @return string token 成功返回验证字段
     * @return string structure_id 部门信息
     * @return array level_num_list 客户意向数量
     *
     *
     *
     */
    public function login() {
        $user=new Structure();
        $ip = \PhalApi\Tool::getClientIp();
        $data=$user->yz($this->username,$this->password,$ip);
        return $data;
    }
    /**
     *08- 登录验签
     * @desc 根据账号和密码进行登录操作 已完成
     * @return int ret 200代表请求成功
     * @return int code 1代表登录成功其他为失败
     * @return string ID 成功返回ID
     * @return string username 成功返回用户名称
     * @return string token 成功返回验证字段
     * @return string structure_id 部门信息
     *
     *
     */
    public function loginSign() {
        $ip = \PhalApi\Tool::getClientIp();
        $token=$this->token;
        $id=$this->id;
        $this->redis = \PhalApi\DI()->redis;

        $data= \PhalApi\DI()->session->list;
        $data2=$this->redis->get_forever($id,'userinfo');
        $data=empty($data2)?$data:$data2;
//        $data=empty($data)?$data2:$data;
        if($data){
            $rs = array('code' => 1, 'msg' => '用户登录状态-正常!', 'data' => $data, 'info' => $data);
        }else{
            $rs = array('code'=>0,'msg'=>'账号过期,请重新登陆!','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    /**
     * 用户成交列表
     * @author Dai Ming
     * @DateTime 16:47 2019/10/18
     * @desc  用户成交列表（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetUserOrder()
    {
        $user=new Structure();

        $data=$user->GetUserOrder($this->uid,$this->type);
        return $data;
    }

    /**
     * 09-客户手机号码录入验证
     * @author Dai Ming
     * @DateTime 11:36 2019/11/13 0013
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetPhoneRepeat()
    {
        $user=new Structure();
        $data=$user->GetPhoneRepeat($this->uid,$this->phone);
        return $data;
    }


    /**
     *01- 客户新增
     * @desc 客户信息的录入 (已完成)
     *
     */
    public function AddCustomer(){
        $newData = array(
            'cname' => $this->cname,
            'ittnzy' => $this->ittnzy,
            'ittnyx' => $this->ittnyx,
            'ittnxm' => $this->ittnxm,
            'ittngj' => $this->ittngj,
            'budget' => $this->budget,
            'timeline' => $this->timeline,
            'graduate' => $this->graduate,
            'graduatezy' => $this->graduatezy,
            'tolink' => $this->tolink,
            'attachment' => $this->attachment,
            'note' => $this->note,
            'creatid' => $this->creatid,
            'field_list' => $this->field_list,
            'sea_type' => $this->sea_type,
            'next_follow' => $this->next_follow,
        );
        $cus_data=array(
            'group' => $this->group,
            'sex' => $this->sex,
            'age' => $this->age,
            'station' => $this->station,
            'occupation' => $this->occupation,
            'industry' =>$this->industry,
            'company' => $this->company,
            'city' => $this->city,
            'adress' => $this->adress,
            'character' => $this->character,
            'cphone' => $this->cphone,
            'cphonetwo' => $this->cphonetwo,
            'cphonethree' => $this->cphonethree,
            'formwhere' => $this->formwhere,
            'formwhere2' => $this->formwhere2,
            'formwhere3' => $this->formwhere3,
            'telephone' => $this->telephone,
            'wxnum' => $this->wxnum,
            'cemail' => $this->cemail,
            'qq' => $this->qq,
            'invoice_company'=>$this->invoice_company,
            'taxpayer_num'=> $this->taxpayer_num,
            'bank'=> $this->bank,
            'open_bank'=> $this->open_bank,
            'bank_num'=>$this->bank_num,
            'bank_adress'=>$this->bank_adress,
            'legal_person'=>$this->legal_person,
            'business_license'=>$this->business_license,
            'agent_name'=> $this->agent_name,
            'agent_num'=>$this->agent_num,
            'own'=>$this->own,
            'project_name'=>$this->project_name,
            'project_num'=> $this->project_num,
        );
        $domain =new Structure();
        $info = $domain->PostAddcus($this->uid,$newData,$cus_data,$this->token);
        return $info;

    }


    /**
     * 06-编辑客户
     * @desc 客户信息的编辑(包含共享和创建以及分配所得用户)已完成
     */

    public function EditCustomer(){
        $id=$this->id;
        $uid=$this->uid;
        $newData = array(
            'cname' => $this->cname,
            'ittnzy' => $this->ittnzy,
            'ittnyx' => $this->ittnyx,
            'ittnxm' => $this->ittnxm,
            'ittngj' => $this->ittngj,
            'budget' => $this->budget,
            'timeline' => $this->timeline,
            'graduate' => $this->graduate,
            'graduatezy' => $this->graduatezy,
            'next_follow' => $this->next_follow,
            'tolink' => $this->tolink,
            'attachment' => $this->attachment,
            'note' => $this->note,
            'creatid' => $this->creatid,
            'field_list' => $this->field_list,
            'sea_type' => $this->sea_type,
        );
        $cus_data=array(
            'group' => $this->group,
            'sex' => $this->sex,
            'age' => $this->age,
            'city' => $this->city,
            'adress' => $this->adress,
            'station' => $this->station,
            'occupation' => $this->occupation,
            'industry' =>$this->industry,
            'company' => $this->company,
            'character' => $this->character,
            'cphonetwo' => $this->cphonetwo,
            'cphone' => $this->cphone,
            'cphonethree' => $this->cphonethree,
            'formwhere2' => $this->formwhere2,
            'formwhere3' => $this->formwhere3,
            'telephone' => $this->telephone,
            'wxnum' => $this->wxnum,
            'cemail' => $this->cemail,
            'qq' => $this->qq,
            'invoice_company'=>$this->invoice_company,
            'taxpayer_num'=> $this->taxpayer_num,
            'bank'=> $this->bank,
            'open_bank'=> $this->open_bank,
            'bank_num'=>$this->bank_num,
            'bank_adress'=>$this->bank_adress,
            'legal_person'=>$this->legal_person,
            'business_license'=>$this->business_license,
            'project_name'=>$this->project_name,
            'project_num'=> $this->project_num,
        );
        $domain =new Structure();
        $info = $domain->EditCustomer($id,$uid,$this->bid,$newData,$cus_data);
        return $info;

    }


    /**
     *03-客户跟进/编辑
     * @desc 客户信息的跟进和编辑(已完成)
     *
     */
    public function Dofp(){
        $list=new Structure();
        $bewdata=array(

            'cname' =>$this->cname,
            'zhuti' => $this->zhuti,
            'types' => $this->types,
            'executor' => $this->executor,
            'description' => $this->description,
            'bid' => $this->bid,
            'next_time' => $this->next_time,
            'enclosure' => $this->enclosure,
            'now_time' => $this->now_time,

        );
        $data=$list->Dofp($this->id,$this->uid,$this->type,$this->lid,$bewdata);

        return $data;
    }
    /**
     * 04-跟进列表
     * @desc  客户的跟进列表带查询分页  已完成
     * @return array data.customer_gh_list 成功返回的跟进列表
     * @return string id 成功返回用户id
     * @return string cid 成功返回客户唯一字段id
     * @return string cname 客户名称
     * @return string zhuti 跟进主题
     * @return string types 跟进类型
     * @return string numcom 跟进次数
     * @return string executor 执行人id(默认等于用户名称)
     * @return string description 行动描述
     * @return string next_time 下次回访时间戳
     * @return string enclosure 附件路径
     * @return string creat_time 设定时间
     * @return string now_time 实际创建时间
     * @return string data.customer_gh_num 总数量
     * @return string data.total_count 上面分类数量
     * @return string data.total_count.customer_jr_total 今日跟进
     * @return string data.total_count.customer_dhgh_total 电话跟进
     * @return string data.total_count.[] 以此类推,顺序和蓝湖一致
     * @desc   type 提交值从左到右依次是1-9
     */


    public function DofpList(){

        $newData = array(
            'uid' => $this->uid,
            'type' => $this->type,
            'keywords' => $this->keywords,
            'pageno' => $this->pageno,
            'where_arr' => $this->where_arr,
            'order_by' => $this->order_by,
            'pagesize' => $this->pagesize
        );
        $list=new Structure();
        $info = $list->GetDoFpList($newData);
        return $info;

    }
    /**
     * 07-跟进删除
     * @desc 删除跟进(已完成)
     */

    public  function ModeDelete(){
        $list=new Structure();
        $info = $list->DeleFp($this->id,$this->uid);
        return $info;
    }
    /**
     * 05-置顶
     * @desc  置顶客户  已完成
     */

    public function Dotop(){
        $list=new Structure();
        $msg=$list->dotop($this->id,$this->uid,$this->is_top);
        return $msg;
    }
    /**
     *02- 客户详情
     * @desc  客户的基础信息 已完成
     *
     */
    public function Customerinfo(){
        $list=new Structure();
        $msg=$list->customerinfo($this->id,$this->uid,$this->bid);
        return $msg;
    }
    /**
     * 09-[批量]更改部门   (已完成)
     * @desc   更改客户所属部门
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function  PostChangeGroup(){
        $domain = new Structure();
        $info = $domain->ChangeGroup($this->uid,$this->groupid,$this->cid_arr);
        return $info;
    }

    /**
     * 10-移交客户
     * @desc  移交用户所有的客户
     *
     */
    public  function PostTransferAll(){
        $domain = new Structure();
        $info = $domain->PostTransferAll($this->uid,$this->Transid,$this->cid_arr,0);
        return $info;
    }

    /**
     * 11-开票添加
     * @desc 开票添加  已完成
     */
    public function AddTicket(){
        $Ticket=new  Structure();

        $info=$Ticket->AddTicket(\PhalApi\DI()->request->getAll());
        return $info;
    }

    /**
     * 12-开票编辑
     * 开票编辑   已完成
     */
    public function EditTicket(){
        $Ticket=new  Structure();

        $info=$Ticket->EditTicket(\PhalApi\DI()->request->getAll());
        return $info;
    }
    /**
     * 13-开票删除
     * 开票删除  已完成
     */
    public function DeleTicket(){
        $Ticket=new  Structure();

        $info=$Ticket->DeleTicket($this->uid,$this->id);
        return $info;
    }
    /**
     * 14-退款添加
     */
    public function AddRefund(){
        $Ticket=new  Structure();

        $info=$Ticket->AddRefund(\PhalApi\DI()->request->getAll());
        return $info;
    }

    /**
     * 15-退款编辑
     * @desc 退款编辑
     */
    public function EditRefund(){
        $Ticket=new  Structure();
        $info=$Ticket->EditRefund(\PhalApi\DI()->request->getAll());
        return $info;
    }
    /**
     * 16-退款删除
     * @desc 退款删除
     */

    public function DeleRefund(){
        $Ticket=new  Structure();
        $info=$Ticket->DeleRefund($this->uid,$this->id);
        return $info;
    }
    /**
     * 17-成交/开票/退款列表
     * @desc  成交列表 开票列表 退款列表
     */
    public function GetInfoList(){
        $newData = array(
            'uid' => $this->uid,
            'cid' => $this->cid,
            'type' => $this->type,
            'pageno' => $this->pageno,
            'pagesize' => $this->pagesize
        );
        $domain = new Structure();
        $info = $domain->GetInfoList($newData);
        return $info;
    }

    /**
     *18-操作记录
     * @author Dai Ming
     * @DateTime 17:13 2019/9/11 0011
     * @desc  操作记录  （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetCusLog()
    {
        $domain = new Structure();
        $info = $domain->GetCusLog($this->uid,$this->cid);
        return $info;
    }

    /**
     * 19-获取成交编号
     * @author Dai Ming
     * @DateTime 15:06 2019/9/16 0016
     * @desc 获取成交编号 （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
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
    public function getOrder()
    {
        $domain = new Structure();
        $info = $domain->getOrder($this->uid,$this->cid);
        return $info;

    }

    /**
     * 20-成交退款开票详情
     * @author Dai Ming
     * @DateTime 16:23 2019/9/16 0016
     * @desc  成交退款开票详情（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostOrderInfo()
    {

        $domain = new Structure();
        $info = $domain->PostOrderInfo($this->uid,$this->id,$this->type);
        return $info;

    }

    /**
     *更新手机号
     * @author Dai Ming
     * @DateTime 16:01 2019/11/5 0005
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function setphone()
    {
        $list= \PhalApi\DI()->notorm->customer->where('creatid',32)->fetchAll();
        foreach ($list as $K=>$v){
            $phone=\App\randMobile();
            \PhalApi\DI()->notorm->customer_data->where('id',$v['id'])->update(['cphonetwo'=>$phone]);
        }
    }




}

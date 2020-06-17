<?php
namespace App\Domain;

use App\Model\Xiaozhe as Model_Xiaozhe;

class Xiaozhe {

	// 01 代理商-新增
    public function PostAgentAdd($uid,$newData,$field_list,$other_contacts) {
        $model = new Model_Xiaozhe();
        $info = $model->PostAgentAdd($uid,$newData,$field_list,$other_contacts);
        return $info;
    }

    // 01-1 代理商-编辑-删除其他联系人
    public function PostDeleteOtherContacts($uid,$id) {
        $model = new Model_Xiaozhe();
        $info = $model->PostDeleteOtherContacts($uid,$id);
        return $info;
    }

    // 02 代理商-编辑
    public function PostEditAgent($uid,$id,$newData,$field_list,$other_contacts) {
        $model = new Model_Xiaozhe();
        $info = $model->PostEditAgent($uid,$id,$newData,$field_list,$other_contacts);
        return $info;
    }

    // 03 代理商-列表
    public function GetAgentList($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentList($newData);
        return $info;
    }
    
    // 04 代理商-详情
    public function GetAgentInfo($uid,$id) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentInfo($uid,$id);
        return $info;
    }

    // 05 代理商-删除
    public function PostDeleteAgent($uid,$id) {
        $model = new Model_Xiaozhe();
        $info = $model->PostDeleteAgent($uid,$id);
        return $info;
    }

    // 06 代理商-共享
    public function PostShareAgentInfo($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->PostShareAgentInfo($newData);
        return $info;
    }

    // 07 代理商-移交
    public function PostChangeCreater($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->PostChangeCreater($newData);
        return $info;
    }

    // 08 代理商-学员列表
    public function GetStudentList($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetStudentList($newData);
        return $info;
    }

    // 09 合同[代理商]-新增
    public function PostAddAgentContract($uid,$newData,$field_list,$general_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostAddAgentContract($uid,$newData,$field_list,$general_list);
        return $info;
    }

    // 10 合同[代理商]-列表
    public function GetAgentContractList($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentContractList($newData);
        return $info;
    }

    // 11 合同[代理商]-详情
    public function GetAgentContractInfo($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentContractInfo($newData);
        return $info;
    }

    // 12 签约项目查看[代理商]
    public function GetAgentGeneralInfo($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentGeneralInfo($newData);
        return $info;
    }

    // 13 签约项目列表[代理商]
    public function GetAgentGeneralList($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentGeneralList($newData);
        return $info;
    }

    // 14 合同调整[代理商]
    public function PostContractAdjust($uid,$newData,$general_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostContractAdjust($uid,$newData,$general_list);
        return $info;
    }

    // 15 合同调整列表[代理商]
    public function GetContractAdjustList($uid,$newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetContractAdjustList($uid,$newData);
        return $info;
    }

    // 16 合同调整详情[代理商]
    public function GetContractAdjustInfo($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetContractAdjustInfo($newData);
        return $info;
    }

    // 17 付款记录-新建[代理商]
    public function PostAgentPaymentAdd($uid,$newData,$field_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostAgentPaymentAdd($uid,$newData,$field_list);
        return $info;
    }

    // 18 付款记录-编辑[代理商]
    public function PostAgentPaymentEdit($uid,$id,$newData,$field_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostAgentPaymentEdit($uid,$id,$newData,$field_list);
        return $info;
    }

    // 19 付款记录-列表[代理商]
    public function GetAgentPaymentList($agent_id,$type) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentPaymentList($agent_id,$type);
        return $info;
    }

    // 20 付款记录-详情[代理商]
    public function GetAgentPaymentInfo($id) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentPaymentInfo($id);
        return $info;
    }

    // 21 收票-新增[代理商]
    public function PostAddAgentInvoice($uid,$newData,$field_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostAddAgentInvoice($uid,$newData,$field_list);
        return $info;
    }

    // 22 收票-编辑[代理商]
    public function PostEditAgentInvoice($uid,$id,$newData,$field_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostEditAgentInvoice($uid,$id,$newData,$field_list);
        return $info;
    }

    // 23 开票-列表[代理商]
    public function GetAgentInvoiceList($agent_id) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentInvoiceList($agent_id);
        return $info;
    }

    // 24 开票-详情[代理商]
    public function GetAgentInvoiceInfo($id) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentInvoiceInfo($id);
        return $info;
    }

    // 25 退款-新增[代理商]
    public function PostAgentRefundAdd($uid,$newData,$field_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostAgentRefundAdd($uid,$newData,$field_list);
        return $info;
    }

    // 26 退款-编辑[代理商]
    public function PostAgentRefundEdit($uid,$id,$newData,$field_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostAgentRefundEdit($uid,$id,$newData,$field_list);
        return $info;
    }

    // 27 退款-列表[代理商]
    public function GetAgentRefundList($agent_id) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentRefundList($agent_id);
        return $info;
    }

    // 28 退款-详情[代理商]
    public function GetAgentRefundInfo($id) {
        $model = new Model_Xiaozhe();
        $info = $model->GetAgentRefundInfo($id);
        return $info;
    }

    // 29 师资新增
    public function PostAddTeacher($uid,$newData,$field_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostAddTeacher($uid,$newData,$field_list);
        return $info;
    }

    // 30 师资列表
    public function GetTeacherList($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetTeacherList($newData);
        return $info;
    }

    // 31 师资编辑
    public function PostEditTeacher($uid,$id,$newData,$field_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostEditTeacher($uid,$id,$newData,$field_list);
        return $info;
    }

    // 32 [批量]删除师资
    public function PostDeleteTeacher($uid,$id) {
        $model = new Model_Xiaozhe();
        $info = $model->PostDeleteTeacher($uid,$id);
        return $info;
    }

    // 33 师资详情
    public function GetTeacherInfo($uid,$id) {
        $model = new Model_Xiaozhe();
        $info = $model->GetTeacherInfo($uid,$id);
        return $info;
    }

    // 34 师资共享
    public function PostShareTeacher($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->PostShareTeacher($newData);
        return $info;
    }

    // 35 师资移交
    public function PostChangeTeacher($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->PostChangeTeacher($newData);
        return $info;
    }

    // 36 消息列表
    public function GetMsgList($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetMsgList($newData);
        return $info;
    }

    // 37 消息详情
    public function GetMsgInfo($id) {
        $model = new Model_Xiaozhe();
        $info = $model->GetMsgInfo($id);
        return $info;
    }

    // 38 消息批量删除
    public function PostDeleteMsg($uid,$id_arr) {
        $model = new Model_Xiaozhe();
        $info = $model->PostDeleteMsg($uid,$id_arr);
        return $info;
    }

    // 39 消息批量已读
    public function PostReadMsg($uid,$id_arr) {
        $model = new Model_Xiaozhe();
        $info = $model->PostReadMsg($uid,$id_arr);
        return $info;
    }

    // 40 成交属性分析
    public function GetStatisticsList($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetStatisticsList($newData);
        return $info;
    }

    // 41 公共新增跟进记录
    public function PostAddPublicFollow($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->PostAddPublicFollow($newData);
        return $info;
    }

    // 42 公共跟进记录列表
    public function GetPublicFollowList($newData) {
        $model = new Model_Xiaozhe();
        $info = $model->GetPublicFollowList($newData);
        return $info;
    }

    // 43 公共跟进记录详情
    public function GetPublicFollowInfo($id) {
        $model = new Model_Xiaozhe();
        $info = $model->GetPublicFollowInfo($id);
        return $info;
    }
    
}

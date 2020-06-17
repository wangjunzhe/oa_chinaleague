<?php
namespace App\Domain;

use App\Model\Proedution as Model_Proedution;

class Proedution {

	// 01 一手项目方-新增
    public function PostProjectAdd($uid,$newData,$field_list) {
        $model = new Model_Proedution();
        $info = $model->PostProjectAdd($uid,$newData,$field_list);
        return $info;
    }
    //02-一手项目方-编辑
    public  function PostProjectEdit($uid,$id,$newData,$field_list){
        $model = new Model_Proedution();
        $info = $model->PostProjectEdit($uid,$id,$newData,$field_list);
        return $info;
    }
    //删除项目方
    public function  PostProjectDele($uid,$id){
        $model = new Model_Proedution();
        $info = $model->PostProjectDele($uid,$id);
        return $info;
    }
    //项目方列表
    public  function PostProjectList($newData){
        $model = new Model_Proedution();
        $info = $model->PostProjectList($newData);
        return $info;
    }
    //分享项目方
    public function PostProjectShare($newData){
        $model = new Model_Proedution();
        $info = $model->PostProjectShare($newData);
        return $info;
    }
    //移交一手方
    public function PostProjectMove($newdata){
        $model = new Model_Proedution();
        $info = $model->PostProjectMove($newdata);
        return $info;
    }
    //项目方详情
    public function GetProjectInfo($uid,$id){
        $model = new Model_Proedution();
        $info = $model->GetProjectInfo($uid,$id);
        return $info;
    }
    //合同新建
    public function PostContractAdd($uid,$newData,$field_list,$general_list){
        $model = new Model_Proedution();
        $info = $model->PostContractAdd($uid,$newData,$field_list,$general_list);
        return $info;
    }
    //学员列表
    public function PostStudentList($newData) {
        $model = new Model_Proedution();
        $info = $model->PostStudentList($newData);
        return $info;
    }
    //合同调整
    public function PostContractEdit($uid,$newData,$general_list){
        $model = new Model_Proedution();
        $info = $model->PostContractEdit($uid,$newData,$general_list);
        return $info;
    }
    //合同调整列表
    public function PostContractAdgustList($uid,$projectid){
        $model = new Model_Proedution();
        $info = $model->PostContractAdgustList($uid,$projectid);
        return $info;
    }
    //合同详情
    public function  PostContractInfo($newData){
        $model = new Model_Proedution();
        $info = $model->PostContractInfo($newData);
        return $info;
    }
    //合同列表
    public function GetProjectContrat($newData){
        $model = new Model_Proedution();
        $info = $model->GetProjectContrat($newData);
        return $info;
    }
    //回款添加
    public function PostProjectPaymentAdd($uid,$newData,$field_list){
        $model = new Model_Proedution();
        $info = $model->PostProjectPaymentAdd($uid,$newData,$field_list);
        return $info;
    }
    //回款编辑
    public function PostContractMedit($uid,$id,$newData,$field_list){
        $model = new Model_Proedution();
        $info = $model->PostContractMedit($uid,$id,$newData,$field_list);
        return $info;
    }
    //回款详情查看
    public function PostContractMinfo($id){
        $model = new Model_Proedution();
        $info = $model->PostContractMinfo($id);
        return $info;
    }
    //合同调整详情
    public function GetContractProjectInfo($newData){
        $model = new Model_Proedution();
        $info = $model->GetContractProjectInfo($newData);
        return $info;
    }
    //开票新增
    public function PostContractInvoiceAdd($data){
        $model = new Model_Proedution();
        $info = $model->PostContractInvoiceAdd($data);
        return $info;
    }
    //开票编辑
    public function  PostContractInvoiceEdit($data){
        $model = new Model_Proedution();
        $info = $model->PostContractInvoiceEdit($data);
        return $info;
    }
    //开票列表
    public function GetProjectInvoiceList($project_id){
        $model = new Model_Proedution();
        $info = $model->GetProjectInvoiceList($project_id);
        return $info;
    }
    //开票详情
    public function PostContractInvoiceInfo($id){
        $model = new Model_Proedution();
        $info = $model->PostContractInvoiceInfo($id);
        return $info;
    }
    //退款-新增
    public function PostContractRefundAdd($uid,$newData,$field_list) {
        $model = new Model_Proedution();
        $info = $model->PostContractRefundAdd($uid,$newData,$field_list);
        return $info;
    }
    public function GetProjectGeneralList($newData) {
        $model = new Model_Proedution();
        $info = $model->GetProjectGeneralList($newData);
        return $info;
    }

    // 退款-编辑
    public function PostContractRefundEdit($uid,$id,$newData,$field_list) {
        $model = new Model_Xiaozhe();
        $info = $model->PostContractRefundEdit($uid,$id,$newData,$field_list);
        return $info;
    }

    //退款-列表
    public function GetProjectRefundList($project_id) {
        $model = new Model_Proedution();
        $info = $model->GetProjectRefundList($project_id);
        return $info;
    }

    //退款-详情
    public function GetProjectRefundInfo($id) {
        $model = new Model_Proedution();
        $info = $model->GetProjectRefundInfo($id);
        return $info;
    }
    //回款列表
    public function GetProjectPaymentList($project_id,$type){
        $model = new Model_Proedution();
        $info = $model->GetProjectPaymentList($project_id,$type);
        return $info;
    }
    //签约项目列表
    public function PostContractList($newData){
        $model = new Model_Proedution();
        $info = $model->PostContractList($newData);
        return $info;
    }

}

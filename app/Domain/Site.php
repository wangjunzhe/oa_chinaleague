<?php
namespace App\Domain;

use App\Model\Site as Model_Site;

class Site {

    // 01 上传单个文件
    public function UploadFile($file,$type) {
        $model = new Model_Site();
        $info = $model->UploadFile($file,$type);
        return $info;
    }

    // 02 全部专业列表
    public function GetAllMajorList($newData) {
        $model = new Model_Site();
        $info = $model->GetAllMajorList($newData);
        return $info;
    }

    // 03 全部院校列表
    public function GetAllSchoolList($newData) {
        $model = new Model_Site();
        $info = $model->GetAllSchoolList($newData);
        return $info;
    }

    // 04 全部专业方向列表
    public function GetAllMajorDirection($major_id) {
        $model = new Model_Site();
        $info = $model->GetAllMajorDirection($major_id);
        return $info;
    }

    // 05 文件导入
    public function PostImportExcel($type,$file,$uid) {
        $model = new Model_Site();
        $info = $model->PostImportExcel($type,$file,$uid);
        return $info;
    }

    // 06 企业通讯录列表
    public function GetAllUserList($newData) {
        $model = new Model_Site();
        $info = $model->GetAllUserList($newData);
        return $info;
    }

    // 07 获取全部简章
    public function GetAllGeneralList($newData) {
        $model = new Model_Site();
        $info = $model->GetAllGeneralList($newData);
        return $info;
    }

    // 08 简章价格
    public function GetGeneralPrice($id) {
        $model = new Model_Site();
        $info = $model->GetGeneralPrice($id);
        return $info;
    }

    // 09 [公共]获取信息共享人员列表
    public function GetSharePersonList($newData) {
        $model = new Model_Site();
        $info = $model->GetSharePersonList($newData);
        return $info;
    }

    // 10 我的全部客户
    public function GetAllCusstomer($newData) {
        $model = new Model_Site();
        $info = $model->GetAllCusstomer($newData);
        return $info;
    }

    // 11 公共取消共享
    public function PublicCancleShare($newData) {
        $model = new Model_Site();
        $info = $model->PublicCancleShare($newData);
        return $info;
    }

    // 12 全部代理商
    public function GetAllAgentList($newData) {
        $model = new Model_Site();
        $info = $model->GetAllAgentList($newData);
        return $info;
    }


    // 13 全部一手项目方
    public function GetAllProdutionList() {
        $model = new Model_Site();
        $info = $model->GetAllProdutionList();
        return $info;
    }

    // 14 获取代理商全部学员
    public function GetAgentAllStudent($agent_id) {
        $model = new Model_Site();
        $info = $model->GetAgentAllStudent($agent_id);
        return $info;
    }

    // 15 获取成交编号
    public function GetOrderNumberByCid($cid) {
        $model = new Model_Site();
        $info = $model->GetOrderNumberByCid($cid);
        return $info;
    }

    // 16 [代理/项目方]删除简章
    public function PublicDeleteAgentGeneral($newData) {
        $model = new Model_Site();
        $info = $model->PublicDeleteAgentGeneral($newData);
        return $info;
    }

    // 17 导出Excel模板
    public function PostExportModelExcel($tablename) {
        $model = new Model_Site();
        $info = $model->PostExportModelExcel($tablename);
        return $info;
    }

    // 18 推送消息
    public function GetNoticeFollowMsg() {
        $model = new Model_Site();
        $info = $model->GetNoticeFollowMsg();
        return $info;
    }
  
  	// 19 登录之后推送跟进消息
    public function PushFoloowMsg($uid) {
        $model = new Model_Site();
        $info = $model->PushFoloowMsg($uid);
        return $info;
    }

    // 20 公共处理方法
    public function PublicAction($uid) {
        $model = new Model_Site();
        $info = $model->PublicAction($uid);
        return $info;
    }
    
    // 21 用户登录日志
    public function GetUserLoginLog($newData) {
        $model = new Model_Site();
        $info = $model->GetUserLoginLog($newData);
        return $info;
    }
    
}

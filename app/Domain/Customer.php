<?php
namespace App\Domain;

use App\Model\Customer as Model_Customer;
use App\Model\CustomerNew as Model_CustomerNew;

class Customer {

    // 01 客户列表接口
    public function GetCustomerList($newData) {
        $model = new Model_Customer();
        $info = $model->GetCustomerList($newData);
        return $info;
    }

	// 02 删除客户接口
    public function PostDeleteCustomer($type,$uid,$id) {
        $model = new Model_Customer();
        $info = $model->PostDeleteCustomer($type,$uid,$id);
        return $info;
    }

    // 03 回归公海
    public function PostReturnSea($uid,$cid_arr) {
        $model = new Model_Customer();
        $info = $model->PostReturnSea($uid,$cid_arr);
        return $info;
    }

    // 04 公海列表
    public function GetSeaCustomerList($newData) {
        $model = new Model_Customer();
        $info = $model->GetSeaCustomerList($newData);
        return $info;
    }

    // 05 共享客户
    public function PostShareCustomer($newData) {
        $model = new Model_Customer();
        $info = $model->PostShareCustomer($newData);
        return $info;
    }

    // 06 共享客户人员列表
    public function GetShareCustomerPerson($newData) {
        $model = new Model_Customer();
        $info = $model->GetShareCustomerPerson($newData);
        return $info;
    }

    // 07 取消共享
    public function PostCancleShare($newData) {
        $model = new Model_Customer();
        $info = $model->PostCancleShare($newData);
        return $info;
    }

    // 07-1 新取消共享
    public function PostCancleShareNew($newData) {
        $model = new Model_Customer();
        $info = $model->PostCancleShareNew($newData);
        return $info;
    }

    // 08 分配客户
    public function PostDistribute($newData) {
        $model = new Model_Customer();
        $info = $model->PostDistribute($newData);
        return $info;
    }
    
    // 09 领取客户
    public function PostReceive($newData) {
        $model = new Model_Customer();
        $info = $model->PostReceive($newData);
        return $info;
    }

    // 10 分配/领取客户列表
    public function GetMySeaCustomerList($newData) {
        $model = new Model_Customer();
        $info = $model->GetMySeaCustomerList($newData);
        return $info;
    }

    // 11 客户成交新增
    public function PostAddCustomerDeal($newData,$field_list,$type,$agent_id) {
        $model = new Model_Customer();
        $info = $model->PostAddCustomerDeal($newData,$field_list,$type,$agent_id);
        return $info;
    }

    // 12 客户成交编辑
    public function PostEditCustomerDeal($id,$newData,$field_list) {
        $model = new Model_Customer();
        $info = $model->PostEditCustomerDeal($id,$newData,$field_list);
        return $info;
    }

    // 13 成交详情
    public function GetCustomerDealInfo($newData) {
        $model = new Model_Customer();
        $info = $model->GetCustomerDealInfo($newData);
        return $info;
    }

    // 14 转移跟进人
    public function PostChangePerson($newData) {
        $model = new Model_Customer();
        $info = $model->PostChangePerson($newData);
        return $info;
    }

    // 15 私海数量限制列表
    public function NumberOfLimitlist($newData) {
        $model = new Model_Customer();
        $info = $model->NumberOfLimitlist($newData);
        return $info;
    }

    // 16 私海数量限制设置
    public function PostNumberLimit($newData) {
        $model = new Model_Customer();
        $info = $model->PostNumberLimit($newData);
        return $info;
    }
}

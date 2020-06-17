<?php
namespace App\Domain;

use App\Model\CustomerNew as Model_CustomerNew;

class CustomerNew {

    // 01 客户列表接口
    public function GetMyCustomerList($newData) {
        $model = new Model_CustomerNew();
        $info = $model->GetMyCustomerList($newData);
        return $info;
    }

    // 02 回归公海
    public function PostReturnToSea($newData) {
        $model = new Model_CustomerNew();
        $info = $model->PostReturnToSea($newData);
        return $info;
    }

    // 03 公海客户列表
    public function GetMySeaCustomerLists($newData) {
    	$model = new Model_CustomerNew();
        $info = $model->GetMySeaCustomerLists($newData);
        return $info;
    }

    // 04 [领取/分配]客户列表
    public function GetReceiveCustomerList($newData) {
    	$model = new Model_CustomerNew();
        $info = $model->GetReceiveCustomerList($newData);
        return $info;
    }

    // 05 报备客户
    public function PostShareMyCustomer($newData) {
    	$model = new Model_CustomerNew();
        $info = $model->PostShareMyCustomer($newData);
        return $info;
    }
	
}

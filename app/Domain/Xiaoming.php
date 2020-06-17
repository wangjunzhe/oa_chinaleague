<?php
namespace App\Domain;

use App\Model\Xiaoming as Model_Xiaoming;

class Xiaoming {

	// 01 代理商-新增
    public function GetAddUserStatus($uid) {
        $model = new Model_Xiaoming();
        $info = $model->GetAddUserStatus($uid);
        return $info;
    }

    
}

<?php
namespace App\Domain;

use App\Model\Admin as Model_Admin;

class Admin {

    // 01 添加模型接口
    public function PostModelAdd($newData) {
        $model = new Model_Admin();
        $info = $model->PostModelAdd($newData);
        return $info;
    }

    // 02 模型列表接口
    public function GetModelList() {
        $model = new Model_Admin();
        $info = $model->GetModelList();
        return $info;
    }

    // 03 删除模型接口
    public function PostDeleteModel($modelid) {
        $model = new Model_Admin();
        $info = $model->PostDeleteModel($modelid);
        return $info;
    }

    // 04 模型字段列表接口
    public function GetModelFieldList($modelid) {
        $model = new Model_Admin();
        $info = $model->GetModelFieldList($modelid);
        return $info;
    }

    // 05 添加字段接口
    public function PostFieldAdd($newData) {
        $model = new Model_Admin();
        $info = $model->PostFieldAdd($newData);
        return $info;
    }

    // 06 字段详情接口
    public function GetFieldInfo($fieldid) {
        $model = new Model_Admin();
        $info = $model->GetFieldInfo($fieldid);
        return $info;
    }

    // 07 字段修改接口
    public function PostEditField($newData) {
        $model = new Model_Admin();
        $info = $model->PostEditField($newData);
        return $info;
    }

    // 08 字段删除接口
    public function PostDeleteField($fieldid) {
        $model = new Model_Admin();
        $info = $model->PostDeleteField($fieldid);
        return $info;
    }
   
}

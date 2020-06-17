<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/31 0031
 * Time: 10:09
 */

namespace App\Domain;

use App\Model\Group as crm_group;

class Group
{
    public function GetGroupList($page, $perpage,$id){
        $model = new crm_group();
        return $model->getListItems($page, $perpage,$id);
    }
    public function get($id) {
        $model = new crm_group();
        return $model->get($id);
    }

    public function insertdb($data){
        $model=new crm_group();
        $data=$model->insertdb($data);
        return $data;

    }
    public function editdb($id,$data){
        $model=new crm_group();
        $data=$model->editdb($id,$data);
        return $data;
    }
    public function deldb($id){
        $model=new crm_group();
        $data=$model->deldb($id);
        return $data;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/1 0001
 * Time: 09:16
 */

namespace App\Domain;
use App\Model\Rule as Domain_rule;
use App\Common\Category;
use App\Common\Tree;
class Rule
{
    public function getDataList($type='')
    {
        $model=new Domain_rule();
        $data=$model->GetRuleList();
        return $data;

    }
    public  function GetColumnList(){
        $model=new Domain_rule();
        $data=$model->GetColumnList();
         return $data;
    }

    public  function AddColumnList($data,$id){
        $model=new Domain_rule();
        $data=$model->AddColumnList($data,$id);
         return $data;
    }
    public function delColumn($pid,$id){
        $model=new Domain_rule();
        $data=$model->delColumn($pid,$id);
        return $data;
    }


}
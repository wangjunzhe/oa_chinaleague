<?php
namespace App\Domain;

use App\Model\Educational as Model_Educational;

class Educational {

	// 01 教务新增-新增
    public function PostEduAdd($newData,$file_list) {
        $model = new Model_Educational();
        $info = $model->PostEduAdd($newData,$file_list);
        return $info;
    }
    //02-教务编辑
    public  function PostEduEdit($id,$newData,$field_list){
        $model = new Model_Educational();
        $info = $model->PostEduEdit($id,$newData,$field_list);
        return $info;
    }
    //教务删除
    public function PostEduDele($uid,$id){
        $model = new Model_Educational();
        $info = $model->PostEduDele($uid,$id);
        return $info;
    }
    //教务列表带查询
    public function GetEduList($newData){
        $model = new Model_Educational();
        $info = $model->GetEduList($newData);
        return $info;
    }
    //教务详情
    public function GetEduInfo($uid,$id,$type){
        $model=new Model_Educational();
        $info=$model->GetEduInfo($uid,$id,$type);
        return $info;
    }

    //新增上课信心
    public function PostEduClassAdd($data){
        $model=new Model_Educational();
        $info=$model->PostEduClassAdd($data);
        return $info;
    }
    //编辑上课信息
    public function PostEduClassEdit($data){
        $model=new Model_Educational();
        $info=$model->PostEduClassEdit($data);
        return $info;
    }
    //删除上课信息
    public function PostEduClassDele($uid,$id){
        $model=new Model_Educational();
        $info=$model->PostEduClassDele($uid,$id);
        return $info;
    }
    //上课信息列表
    public function PostEduClassList($data){
        $model=new Model_Educational();
        $info=$model->PostEduClassList($data);
        return $info;
    }
    //新建上课学员资料
    public function PostEduStudent($data){
        $model=new Model_Educational();
        $info=$model->PostEduStudent($data);
        return $info;
    }
    //编辑上课学员资料
    public function PostEduStudentEdit($data){
        $model=new Model_Educational();
        $info=$model->PostEduStudentEdit($data);
        return $info;
    }
    //学员资料列表
    public function PostEduStudentList($data){
        $model=new Model_Educational();
        $info=$model->PostEduStudentList($data);
        return $info;
    }
    //教师薪资记录新增
    public function PostEduTeacherSalaryAdd($data){
        $model=new Model_Educational();
        $info=$model->PostEduTeacherSalaryAdd($data);
        return $info;
    }
    //教师薪资记录编辑
    public function PostEduTeacherSalaryEdit($data){
        $model=new Model_Educational();
        $info=$model->PostEduTeacherSalaryEdit($data);
        return $info;
    }
    //教师薪资记录列表
    public function PostEduTeacherSalaryList($data){
        $model=new Model_Educational();
        $info=$model->PostEduTeacherSalaryList($data);
        return $info;
    }
    //首页数据分析
    public function GetAllStatisticsList($uid,$type,$date_sl,$date_yj){
        $model=new Model_Educational();
        $info=$model->GetAllStatisticsList($uid,$type,$date_sl,$date_yj);
        return $info;
    }
    //获取上课编号
    public function GetClassNum($uid,$id){
        $model=new Model_Educational();
        $info=$model->GetClassNum($uid,$id);
        return $info;
    }
    //删除学员资料
    public function PostEduStudentDele($uid,$id){
        $model=new Model_Educational();
        $info=$model->PostEduStudentDele($uid,$id);
        return $info;
    }
    //删除工资资料
    public function PostEduTeacherSalaryDel($uid,$id){
        $model=new Model_Educational();
        $info=$model->PostEduTeacherSalaryDel($uid,$id);
        return $info;
    }
    //
    public function GetCusFollowCensus($uid,$type,$users,$date_len,$date_section){
        $model=new Model_Educational();
        $info=$model->GetCusFollowCensus($uid,$type,$users,$date_len,$date_section);
        return $info;
    }



}

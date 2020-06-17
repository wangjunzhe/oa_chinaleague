<?php
namespace App\Domain;

use App\Model\Project as Model_Project;

class Project {
    // 项目方登录

    public function  ProjectLogin($user,$pass,$sign){
        $model = new Model_Project();
        $info = $model->PostAddClass($user,$pass,$sign);
        return $info;
    }
	// 1 添加班级接口
    public function PostAddClass($newData,$uid) {
        $model = new Model_Project();
        $info = $model->PostAddClass($newData,$uid);
        return $info;
    }

    // 1-2 班级列表接口
    public function GetClassList($uid,$keywords,$pageno,$pagesize) {
    	$model = new Model_Project();
        $info = $model->GetClassList($uid,$keywords,$pageno,$pagesize);
        return $info;
    }
    // 1-2 班级列表接口xin
    public function GetClassListNew($keywords,$pageno,$pagesize) {
    	$model = new Model_Project();
        $info = $model->GetClassListNew($keywords,$pageno,$pagesize);
        return $info;
    }

    // 1-3 班级详情接口
    public function GetClassInfo($class_id) {
    	$model = new Model_Project();
        $info = $model->GetClassInfo($class_id);
        return $info;
    }

    // 1-4 编辑班级接口
    public function PostEditClass($newData,$uid) {
        $model = new Model_Project();
        $info = $model->PostEditClass($newData,$uid);
        return $info;
    }

    // 1-5 删除班级接口
    public function DeleteClassInfo($class_id,$uid) {
    	$model = new Model_Project();
        $info = $model->DeleteClassInfo($class_id,$uid);
        return $info;
    }

    // 2 院校新增接口
    public function PostAddSchool($newData,$uid) {
        $model = new Model_Project();
        $info = $model->PostAddSchool($newData,$uid);
        return $info;
    }

    // 2-1 院校列表接口
    public function GetSchoolList($uid,$keywords,$pageno,$pagesize) {
        $model = new Model_Project();
        $info = $model->GetSchoolList($uid,$keywords,$pageno,$pagesize);
        return $info;
    }

    // 2-2 院校编辑接口
    public function PostEditSchool($newData,$uid) {
        $model = new Model_Project();
        $info = $model->PostEditSchool($newData,$uid);
        return $info;
    }

    // 2-3 院校删除接口
    public function DeleteSchoolInfo($id,$uid) {
        $model = new Model_Project();
        $info = $model->DeleteSchoolInfo($id,$uid);
        return $info;
    }

    // 2-4 院校详情接口
    public function GetSchoolInfo($id) {
        $model = new Model_Project();
        $info = $model->GetSchoolInfo($id);
        return $info;
    }

    // 3 专业新增接口
    public function PostMajorAdd($newData,$uid) {
        $model = new Model_Project();
        $info = $model->PostMajorAdd($newData,$uid);
        return $info;
    }

    // 3-1 专业列表接口
    public function GetMajorList($keywords,$pageno,$pagesize,$uid) {
        $model = new Model_Project();
        $info = $model->GetMajorList($keywords,$pageno,$pagesize,$uid);
        return $info;
    }

    // 3-2 专业编辑接口
    public function PostEditMajor($newData,$uid) {
        $model = new Model_Project();
        $info = $model->PostEditMajor($newData,$uid);
        return $info;
    }

    // 3-3 专业删除口接口
    public function DeleteMajorInfo($id,$uid) {
        $model = new Model_Project();
        $info = $model->DeleteMajorInfo($id,$uid);
        return $info;
    }

    // 4 专业方向新增接口
    public function PostAddMajorDirection($newData,$uid) {
        $model = new Model_Project();
        $info = $model->PostAddMajorDirection($newData,$uid);
        return $info;
    }

    // 4-1 专业方向列表
    public function MajorDirectList($uid,$keywords,$pageno,$pagesize) {
        $model = new Model_Project();
        $info = $model->MajorDirectList($uid,$keywords,$pageno,$pagesize);
        return $info;
    }

    // 4-2 专业方向编辑
    public function EditMajorDirection($newData,$uid) {
        $model = new Model_Project();
        $info = $model->EditMajorDirection($newData,$uid);
        return $info;
    }

    // 4-3 专业方向删除
    public function DeleteMajorDirection($id,$uid) {
        $model = new Model_Project();
        $info = $model->DeleteMajorDirection($id,$uid);
        return $info;
    }

    // 4-4 专业方向详情接口
    public function GetMajorDirectionInfo($id) {
        $model = new Model_Project();
        $info = $model->GetMajorDirectionInfo($id);
        return $info;
    }

    // 5 简章新增接口
    public function PostAddGeneralRules($newData,$uid) {
        $model = new Model_Project();
        $info = $model->PostAddGeneralRules($newData,$uid);
        return $info;
    }

    // 5-1 简章列表
    public function GetGerenalList($uid,$keywords,$pageno,$pagesize) {
        $model = new Model_Project();
        $info = $model->GetGerenalList($uid,$keywords,$pageno,$pagesize);
        return $info;
    }

    // 5-2 简章编辑接口
    public function PostEditGerenal($newData,$uid) {
        $model = new Model_Project();
        $info = $model->PostEditGerenal($newData,$uid);
        return $info;
    }

    // 5-3 简章删除接口
    public function DeleteGeneralInfo($id,$uid) {
        $model = new Model_Project();
        $info = $model->DeleteGeneralInfo($id,$uid);
        return $info;
    }

    // 5-4 简章详情接口
    public function GetGeneralInfo($id) {
        $model = new Model_Project();
        $info = $model->GetGeneralInfo($id);
        return $info;
    }

}

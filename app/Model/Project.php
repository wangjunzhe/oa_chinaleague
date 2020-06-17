<?php
namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Common\Admin as AdminCommon;
use App\Common\Category;
class Project extends NotORM
{
    protected $di;
    protected $iv;
    protected $prefix;
    protected $redis;
    protected $config;
    protected $pagesize;
    public function __construct()
    {
        $this->di = \PhalApi\DI()->notorm;
        $this->config = \PhalApi\DI()->config;
        $this->redis = \PhalApi\DI()->redis;
        $this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
        // 加密向量
        $this->iv = \PhalApi\DI()->config->get('common.IV');
        // 页码设置
        $this->pagesize = \PhalApi\DI()->config->get('common.PAGESIZE');
    }
    // 项目负登录
    public function ProjectLogin($user,$pass,$sign){
        //验证签名

    }
    // 1 新增班级接口
    public function PostAddClass($newData,$uid) {
        $class_name = isset($newData['class_name']) && !empty($newData['class_name']) ? $newData['class_name'] : "";
        $sort = isset($newData['sort']) && !empty($newData['sort']) ? $newData['sort'] : 0;
        $field_list = isset($newData['field_list']) && !empty($newData['field_list']) ? $newData['field_list'] : array();
        
        if (empty($class_name)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname,structure_id")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }
        $structure_id = json_decode($user_info['structure_id']);
        $user_structure_id = array_pop($structure_id);
        // 添加人 需要后续增加上
        $class_arr = array(
            "title" => $class_name,
            "sort" => $sort,
            "status" => 1,
            "addtime" => time(),
            "updatetime" => time(),
            "creatid" => $uid,
            "structure_id" => $user_structure_id,
            "publisher" => $user_info['realname'],
            "updateman" => $user_info['realname']
        );
        if (is_array($field_list) && !empty($field_list)) {
            $class_data = array_merge($class_arr,$field_list);
        }

        $class_id = $this->di->class->insert($class_data);
        if ($class_id) {
            // 清除班级缓存
            $rs = array('code'=>1,'msg'=>'000014','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000015','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 1-2 班级列表接口
    public function GetClassList($uid,$keywords,$pageno,$pagesize) {
        $keywords = isset($keywords) ? $keywords : "";
        $pageno = isset($pageno) ? intval($pageno) : 1;
        $pagesize = isset($pagesize) ? intval($pagesize) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        
        $uid = isset($uid) ? $uid : 1;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("structure_id,is_leader,parent_id")->where("id",$uid)->fetchOne();
        $structure_id = json_decode($user_info['structure_id']);

        if ($user_info['is_leader'] != 0) {
            # 主管/经理
            $admin_common = new AdminCommon;
            $user_structure_arr = $admin_common->GetUserChargeStruct($structure_id,$user_info['is_leader'],$user_info['parent_id']);
        }

        $class_where = "status = 1";

        if ($uid != 1) {
            # 非系统管理员
            if (!empty($user_structure_arr)) {
                $user_structure_str = implode(',', $user_structure_arr);
                $user_structure_str = trim($user_structure_str,",");
                $class_where .= " AND FIND_IN_SET(structure_id, '{$user_structure_str}') ";
            } else {
                $class_where .= " AND structure_id = {$structure_id} ";
            }

        }

        if ($keywords != "") {
            $class_where .= " AND title LIKE '%{$keywords}%'";
        }

        // var_dump($class_where);
        $class_data = array();
        $class_list = $this->di->class->select("*")->where($class_where)->order("sort DESC,addtime DESC")->limit($pagenum,$pagesize)->fetchRows();
        $class_data['class_list'] = $class_list ? $class_list : array();
        $class_data['num'] = $this->di->class->where($class_where)->count("id");
        if ($class_data['num'] > 0) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$class_data,'info'=>$class_data);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }// 1-2 班级列表接口
    // 班级列表接口分组
    public function GetClassListNew($keywords,$pageno,$pagesize) {
        
        $class_where = "status = 1";
        
        $class_data = array();
        $class_list = $this->di->class->select("id as value,year,title as label")->where($class_where)->order("year DESC")->fetchRows();
        $class_data = array();
        foreach ($class_list as $key => $value) {
            $class_data[$value['year']]['label'] = $value['year'];
            $class_data[$value['year']]['children'][] = $value;
        }
        $class_data = array_values($class_data);
        // if(count($class_list)>0){
        //     foreach ($class_list as $k=>$v){
        //         $new= $this->di->class->select("*")->where('year',$v['year'])->order("year DESC")->fetchRows();
        //         foreach ($new as $n=>$m){
        //               $newlist[$m['year']][]=array(
        //                       'id'=>$m['id'],
        //                       'title'=>$m['title']
        //               );

        //         }

        //     }
        // }
        
        $data = $class_data ? $class_data : array();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        return $rs;
    }

    // 1-3 班级详情接口
    public function GetClassInfo($class_id) {
        $class_id = isset($class_id) ? intval($class_id) : 0;
        if (empty($class_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $class_info = $this->di->class->select("*")->where(array("id"=>$class_id,"status"=>1))->fetchOne();
        if (!empty($class_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$class_info,'info'=>$class_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 1-4 编辑班级接口
    public function PostEditClass($newData,$uid) {
        $class_id = isset($newData['class_id']) ? $newData['class_id'] : 0;
        $class_name = isset($newData['class_name']) ? $newData['class_name'] : "";
        $sort = isset($newData['sort']) ? $newData['sort'] : 0;
        $field_list = isset($newData['field_list']) && !empty($newData['field_list']) ? $newData['field_list'] : array();

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($class_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $class_info = $this->di->class->where(array("id"=>$class_id,"status"=>1))->fetchOne("title");
        if (empty($class_info)) {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
            return $rs;
        }

        // $class_infos = $this->di->class->where(array("title"=>$class_name,"year"=>$year,"id <> ?"=>$class_id))->fetchOne("title");
        // if (!empty($class_infos)) {
        //     $rs = array('code'=>0,'msg'=>'000013','data'=>array(),'info'=>array());
        //     return $rs;
        // }

        $update_arr = array(
            "title"=>$class_name,
            "sort"=>$sort,
            "updatetime"=>time(),
            "updateman"=>$user_info['realname']
        );
        if (is_array($field_list) && !empty($field_list)) {
            $update_arr = array_merge($update_arr,$field_list);
        }
        $edit_info = $this->di->class->where("id",$class_id)->update($update_arr);
        if ($edit_info) {
            $rs = array('code'=>1,'msg'=>'000016','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000017','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 1-5 删除班级接口
    public function DeleteClassInfo($class_id,$uid) {
        $class_id = isset($class_id) ? intval($class_id) : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($class_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $class_info = $this->di->class->where(array("id"=>$class_id,"status"=>1))->fetchOne("title");
        if (empty($class_info)) {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
            return $rs;
        }
        $recycle = $this->config->get('common.IS_DELETE');
        $note = '删除班级ID：'.$class_id;
        if ($recycle) {
            $edit_info = $this->di->class->where("id",$class_id)->delete();
            \App\setlog($uid,3,$note,'成功',$note,'删除班级');
        } else {
            $edit_info = $this->di->class->where("id",$class_id)->update(array("status"=>0));
            \App\setlog($uid,3,$note,'成功',$note,'物理删除班级');
        }
        if ($edit_info) {
            $rs = array('code'=>1,'msg'=>'000018','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000019','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 2 院校新增接口
    public function PostAddSchool($newData,$uid) {
        $title = isset($newData['title']) && !empty($newData['title']) ? $newData['title'] : "";
        $area = isset($newData['area']) && !empty($newData['area']) ? $newData['area'] : array();
        $address = isset($newData['address']) && !empty($newData['address']) ? $newData['address'] : "";
        $school_motto = isset($newData['school_motto']) && !empty($newData['school_motto']) ? $newData['school_motto'] : "";
        $content = isset($newData['content']) && !empty($newData['content']) ? $newData['content'] : "";
        $remark = isset($newData['remark']) && !empty($newData['remark']) ? $newData['remark'] : "";
        $file = isset($newData['file']) && !empty($newData['file']) ? $newData['file'] : "";
        $field_list = isset($newData['field_list']) && !empty($newData['field_list']) ? $newData['field_list'] : array();
        $sort = isset($newData['sort']) ? $newData['sort'] : 0;

        if (empty($title)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname,structure_id")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        $structure_id = json_decode($user_info['structure_id']);
        $user_structure_id = array_pop($structure_id);

        $school_info = $this->di->school->where(array("title"=>$title,"status"=>1))->fetchOne("id");
        if (!empty($school_info)) {
            $rs = array('code'=>0,'msg'=>'000022','data'=>array(),'info'=>array());
            return $rs;
        }
        
        // 新增院校
        $province = $area[0] ? $area[0] : "";
        $city = $area[1] ? $area[1] : "";
        $country = $area[2] ? $area[2] : "";
        $school_arr = array(
            "title" => $title,
            "status" => 1,
            "sort" => $sort,
            "addtime" => time(),
            "updatetime" => time(),
            "creatid" => $uid,
            "structure_id" => $user_structure_id,
            "publisher" => $user_info['realname'],
            "updateman" => $user_info['realname'],
            "address" => $address,
            "province" => $province,
            "city" => $city,
            "country" => $country,
            "school_motto" => $school_motto,
            "remark" => $remark,
            "content" => $content,
            "file" => $file
        );

        if (is_array($field_list) && !empty($field_list)) {
            foreach ($field_list as $key => $value) {
                if (is_array($value)) {
                    $field_list[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
                }
            }
            $school_data = array_merge($school_arr,$field_list);
        }

        $school_id = $this->di->school->insert($school_data);
        if ($school_id) {
            // 清除院校缓存
            $this->redis->set_time('school_list',array(),3000,'project');
            $rs = array('code'=>1,'msg'=>'000020','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000021','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 2-1 院校列表接口
    public function GetSchoolList($uid,$keywords,$pageno,$pagesize) {
        $keywords = isset($keywords) && !empty($keywords) ? $keywords : "";
        $pageno = isset($pageno) ? intval($pageno) : 1;
        $pagesize = isset($pagesize) ? intval($pagesize) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        $uid = isset($uid) ? $uid : 1;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("structure_id,is_leader,parent_id")->where("id",$uid)->fetchOne();
        $structure_id = json_decode($user_info['structure_id']);
        
        if ($user_info['is_leader'] != 0) {
            # 主管/经理
            $admin_common = new AdminCommon;
            $user_structure_arr = $admin_common->GetUserChargeStruct($structure_id,$user_info['is_leader'],$user_info['parent_id']);
        }

        $school_where = " status = 1 ";
        if ($uid != 1) {
            # 非系统管理员
            if (!empty($user_structure_arr)) {
                $user_structure_str = implode(',', $user_structure_arr);
                $user_structure_str = trim($user_structure_str,",");
                $school_where .= " AND FIND_IN_SET(structure_id, '{$user_structure_str}') ";
            } else {
                $school_where .= " AND structure_id = {$structure_id} ";
            }

        }
        
        // 关键词搜索
        if (!empty($keywords)) {
            $school_where .= " AND title LIKE '{$keywords}%'";
        }

        $school_data = array();
        $school_list = $this->di->school->select("*")->where($school_where)->order("sort DESC,addtime DESC")->limit($pagenum,$pagesize)->fetchRows();
        $school_data['school_list'] = $school_list ? $school_list : array();
        $school_data['num'] = $this->di->school->where($school_where)->count("id");
        if ($school_data['num'] > 0) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$school_data,'info'=>$school_data);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 2-2 院校编辑接口
    public function PostEditSchool($newData,$uid) {
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $title = isset($newData['title']) && !empty($newData['title']) ? $newData['title'] : "";
        $area = isset($newData['area']) && !empty($newData['area']) ? $newData['area'] : array();
        $address = isset($newData['address']) && !empty($newData['address']) ? $newData['address'] : "";
        $school_motto = isset($newData['school_motto']) && !empty($newData['school_motto']) ? $newData['school_motto'] : "";
        $content = isset($newData['content']) && !empty($newData['content']) ? $newData['content'] : "";
        $remark = isset($newData['remark']) && !empty($newData['remark']) ? $newData['remark'] : "";
        $file = isset($newData['file']) && !empty($newData['file']) ? $newData['file'] : "";
        $field_list = isset($newData['field_list']) && !empty($newData['field_list']) ? $newData['field_list'] : array();
        $sort = isset($newData['sort']) ? $newData['sort'] : 0;

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        $school_info = $this->di->school->where(array("id"=>$id,"status"=>1))->fetchOne("title");
        if (empty($school_info)) {
            $rs = array('code'=>0,'msg'=>'000024','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断院校名是否重复
        $school_infos = $this->di->school->where(array("title"=>$title,"id <> ?"=>$id))->fetchOne("title");
        if (!empty($school_infos)) {
            $rs = array('code'=>0,'msg'=>'000023','data'=>array(),'info'=>array());
            return $rs;
        }

        $province = $area[0] ? $area[0] : "";
        $city = $area[1] ? $area[1] : "";
        $country = $area[2] ? $area[2] : "";
        $update_arr = array(
            "title"=>$title,
            "address"=>$address,
            "province"=>$province,
            "city"=>$city,
            "country"=>$country,
            "school_motto"=>$school_motto,
            "content"=>$content,
            "remark"=>$remark,
            "file"=>$file,
            "updatetime" => time(),
            "updateman" => $user_info['realname'],
            "sort"=>$sort
        );
        if (is_array($field_list) && !empty($field_list)) {
            foreach ($field_list as $key => $value) {
                if (is_array($value)) {
                    $field_list[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
                }
            }
            $update_arr = array_merge($update_arr,$field_list);
        }
        $edit_info = $this->di->school->where("id",$id)->update($update_arr);
        if ($edit_info) {
            // 清除院校缓存
            $this->redis->set_time('school_list',array(),3000,'project');
            $rs = array('code'=>1,'msg'=>'000027','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000028','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 2-3 院校删除接口
    public function DeleteSchoolInfo($id,$uid) {
        $id = isset($id) ? intval($id) : 0;
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $school_info = $this->di->school->where(array("id"=>$id,"status"=>1))->fetchOne("title");
        if (empty($school_info)) {
            $rs = array('code'=>0,'msg'=>'000024','data'=>array(),'info'=>array());
            return $rs;
        }
        $note = '删除院校ID：'.$id;
        $recycle = $this->config->get('common.IS_DELETE');
        if ($recycle) {
            $edit_info = $this->di->school->where("id",$id)->delete();
            \App\setlog($uid,3,$note,'成功',$note,'删除院校');
        } else {
            $edit_info = $this->di->school->where("id",$id)->update(array("status"=>0));
            \App\setlog($uid,3,$note,'成功',$note,'物理删除院校');
        }
        if ($edit_info) {
            // 清除院校缓存
            $this->redis->set_time('school_list',array(),3000,'project');
            $rs = array('code'=>1,'msg'=>'000025','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000026','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 2-4 院校详情接口
    public function GetSchoolInfo($id) {
        $id = isset($id) ? intval($id) : 0;
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $school_info = $this->di->school->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
        if (!empty($school_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$school_info,'info'=>$school_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 3 专业新增接口
    public function PostMajorAdd($newData,$uid) {
        $title = isset($newData['title']) && !empty($newData['title']) ? $newData['title'] : "";
        $sort = isset($newData['sort']) ? $newData['sort'] : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname,structure_id")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }
        $structure_id = json_decode($user_info['structure_id']);
        $user_structure_id = array_pop($structure_id);
        if (empty($title)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $major_info = $this->di->major->where(array("title"=>$title,"status"=>1))->fetchOne("id");
        if (!empty($major_info)) {
            $rs = array('code'=>0,'msg'=>'000029','data'=>array(),'info'=>array());
            return $rs;
        }
        $major_data = array(
            "title" => $title,
            "sort" => $sort,
            "addtime" => time(),
            "updatetime" => time(),
            "creatid" => $uid,
            "structure_id" => $user_structure_id,
            "publisher" => $user_info['realname'],
            "updateman" => $user_info['realname']
        );

        $major_id = $this->di->major->insert($major_data);
        if ($major_id) {
            // 清除专业缓存
            $this->redis->set_time('major_list',array(),3000,'project');
            $rs = array('code'=>1,'msg'=>'000030','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000031','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 3-1 专业列表接口
    public function GetMajorList($keywords,$pageno,$pagesize,$uid) {
        $keywords = isset($keywords) && !empty($keywords) ? $keywords : "";
        $pageno = isset($pageno) ? intval($pageno) : 1;
        $pagesize = isset($pagesize) ? intval($pagesize) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        $uid = isset($uid) ? $uid : 1;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("structure_id,is_leader,parent_id")->where("id",$uid)->fetchOne();
        $structure_id = json_decode($user_info['structure_id']);
        
        if ($user_info['is_leader'] != 0) {
            # 主管/经理
            $admin_common = new AdminCommon;
            $user_structure_arr = $admin_common->GetUserChargeStruct($structure_id,$user_info['is_leader'],$user_info['parent_id']);
        }
        $major_where = "status = 1";
        
        if ($uid != 1) {
            # 非系统管理员
            if (!empty($user_structure_arr)) {
                $user_structure_str = implode(',', $user_structure_arr);
                $user_structure_str = trim($user_structure_str,",");
                $major_where .= " AND FIND_IN_SET(structure_id, '{$user_structure_str}') ";
            } else {
                $major_where .= " AND structure_id = {$structure_id} ";
            }

        }
        if (!empty($keywords)) {
            $major_where .= " AND title LIKE '%{$keywords}%'";
        }
        $major_data = array();
        $major_list = $this->di->major->select("*")->where($major_where)->order("sort DESC,addtime DESC")->limit($pagenum,$pagesize)->fetchRows();
        $major_data['major_list'] = $major_list ? $major_list : array();
        $major_data['num'] = $this->di->major->where($major_where)->count("id");
        if ($major_data['num'] > 0) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$major_data,'info'=>$major_data);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 3-2 专业编辑接口
    public function PostEditMajor($newData,$uid) {
        $id = isset($newData['id']) && !empty($newData['id']) ? $newData['id'] : 0;
        $title = isset($newData['title']) && !empty($newData['title']) ? $newData['title'] : "";
        $sort = isset($newData['sort']) ? $newData['sort'] : 0;

        if (empty($title) || empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        $major_info = $this->di->major->where(array("id"=>$id,"status"=>1))->fetchOne("title");
        if (empty($major_info)) {
            $rs = array('code'=>0,'msg'=>'000032','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断专业名是否重复
        $major_infos = $this->di->major->where(array("title"=>$title,"id <> ?"=>$id))->fetchOne("title");
        if (!empty($major_infos)) {
            $rs = array('code'=>0,'msg'=>'000029','data'=>array(),'info'=>array());
            return $rs;
        }

        $major_data = array(
            "title" => $title,
            "sort" => $sort,
            "updateman" => $user_info['realname'],
            "updatetime" => time()
        );
        $major_update = $this->di->major->where("id",$id)->update($major_data);
        if ($major_update) {
            // 清除专业缓存
            $this->redis->set_time('major_list',array(),3000,'project');
            $rs = array('code'=>1,'msg'=>'000033','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000034','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 3-3 专业删除接口
    public function DeleteMajorInfo($id,$uid) {
        $id = isset($id) ? intval($id) : 0;
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $major_info = $this->di->major->where(array("id"=>$id,"status"=>1))->fetchOne("title");
        if (empty($major_info)) {
            $rs = array('code'=>0,'msg'=>'000032','data'=>array(),'info'=>array());
            return $rs;
        }

        $recycle = $this->config->get('common.IS_DELETE');
        $note = '删除专业ID：'.$id;
        if ($recycle) {
            $edit_info = $this->di->major->where("id",$id)->delete();
            \App\setlog($uid,3,$note,'成功',$note,'删除专业');

        } else {
            $edit_info = $this->di->major->where("id",$id)->update(array("status"=>0));
            \App\setlog($uid,3,$note,'成功',$note,'物理删除专业');
        }
        if ($edit_info) {
            // 清除专业缓存
            $this->redis->set_time('major_list',array(),3000,'project');
            $rs = array('code'=>1,'msg'=>'000005','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000035','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 4 专业方向新增接口
    public function PostAddMajorDirection($newData,$uid) {
        $title = isset($newData['title']) && !empty($newData['title']) ? $newData['title'] : "";
        $major_id = isset($newData['major_id']) && !empty($newData['major_id']) ? $newData['major_id'] : 0;
        $content = isset($newData['content']) && !empty($newData['content']) ? $newData['content'] : "";
        $remark = isset($newData['remark']) && !empty($newData['remark']) ? $newData['remark'] : "";
        $file = isset($newData['file']) && !empty($newData['file']) ? $newData['file'] : "";
        $field_list = isset($newData['field_list']) && !empty($newData['field_list']) ? $newData['field_list'] : array();
        $sort = isset($newData['sort']) ? $newData['sort'] : 0;

        if (empty($title) || empty($major_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname,structure_id")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }
        $structure_id = json_decode($user_info['structure_id']);
        $user_structure_id = array_pop($structure_id);

        $major_direction_info = $this->di->major_direction->where(array("title"=>$title,"status"=>1))->fetchOne("id");
        if (!empty($major_direction_info)) {
            $rs = array('code'=>0,'msg'=>'000036','data'=>array(),'info'=>array());
            return $rs;
        }
        
        // 新增专业方向
        $major_direction_arr = array(
            "title" => $title,
            "major_id" => $major_id,
            "status" => 1,
            "sort" => $sort,
            "addtime" => time(),
            "updatetime" => time(),
            "creatid" => $uid,
            "structure_id" => $user_structure_id,
            "publisher" => $user_info['realname'],
            "updateman" => $user_info['realname'],
            "remark" => $remark,
            "content" => $content,
            "file" => $file
        );

        if (is_array($field_list) && !empty($field_list)) {
            foreach ($field_list as $key => $value) {
                if (is_array($value)) {
                    $field_list[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
                }
            }
            $major_direction_data = array_merge($major_direction_arr,$field_list);
        }

        $major_direction_id = $this->di->major_direction->insert($major_direction_data);
        if ($major_direction_id) {
            // 清除专业方向缓存
            $this->redis->set_time('direction_list',array(),3000,'project');
            $rs = array('code'=>1,'msg'=>'999995','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000037','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 4-1 专业方向列表接口
    public function MajorDirectList($uid,$keywords,$pageno,$pagesize) {
        $keywords = isset($keywords) && !empty($keywords) ? $keywords : "";
        $pageno = isset($pageno) ? intval($pageno) : 1;
        $pagesize = isset($pagesize) ? intval($pagesize) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        $uid = isset($uid) ? $uid : 1;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("structure_id,is_leader,parent_id")->where("id",$uid)->fetchOne();
        $structure_id = json_decode($user_info['structure_id']);

        if ($user_info['is_leader'] != 0) {
            # 主管/经理
            $admin_common = new AdminCommon;
            $user_structure_arr = $admin_common->GetUserChargeStruct($structure_id,$user_info['is_leader'],$user_info['parent_id']);
        }

        $major_direction_data = array();

        $direction_arr[':status'] = 1;
        $direction_where = "md.status = :status"; 
        $num_where = "status = 1";

        if ($uid != 1) {
            # 非系统管理员
            if (!empty($user_structure_arr)) {
                $user_structure_str = implode(',', $user_structure_arr);
                $user_structure_str = trim($user_structure_str,",");
                $direction_where .= " AND FIND_IN_SET(md.structure_id, '{$user_structure_str}') ";
                $num_where .= " AND FIND_IN_SET(structure_id, '{$user_structure_str}') ";

            } else {
                $direction_where .= " AND md.structure_id = {$structure_id} ";
                $num_where .= " AND FIND_IN_SET(structure_id, '{$user_structure_str}') ";

            }

        }

        if (!empty($keywords)) {
            $direction_where .= " AND md.title LIKE '%{$keywords}%'"; 
            $num_where .= " AND title LIKE '%{$keywords}%'";

        }
        $major_direction_sql = "SELECT md.*,m.title as major_name FROM ".$this->prefix."major_direction md LEFT JOIN ".$this->prefix."major m ON md.major_id = m.id WHERE ".$direction_where." ORDER BY sort DESC,addtime DESC LIMIT ".$pagenum.",".$pagesize;
        $major_direction_list = $this->di->major_direction->queryAll($major_direction_sql, $direction_arr);

        $major_direction_data['major_direction_list'] = $major_direction_list ? $major_direction_list : array();
        $major_direction_data['num'] = $this->di->major_direction->where($num_where)->count("id");

        if ($major_direction_data['num'] > 0) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$major_direction_data,'info'=>$major_direction_data);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 4-2 专业方向编辑接口
    public function EditMajorDirection($newData,$uid) {
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $title = isset($newData['title']) && !empty($newData['title']) ? $newData['title'] : "";
        $major_id = isset($newData['major_id']) && !empty($newData['major_id']) ? $newData['major_id'] : 0;
        $content = isset($newData['content']) && !empty($newData['content']) ? $newData['content'] : "";
        $remark = isset($newData['remark']) && !empty($newData['remark']) ? $newData['remark'] : "";
        $file = isset($newData['file']) && !empty($newData['file']) ? $newData['file'] : "";
        $field_list = isset($newData['field_list']) && !empty($newData['field_list']) ? $newData['field_list'] : array();
        $sort = isset($newData['sort']) ? $newData['sort'] : 0;

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }
        $direction_info = $this->di->major_direction->where(array("id"=>$id,"status"=>1))->fetchOne("title");
        if (empty($direction_info)) {
            $rs = array('code'=>0,'msg'=>'000038','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断专业方向名是否重复
        $direction_infos = $this->di->major_direction->where(array("title"=>$title,"id <> ?"=>$id))->fetchOne("title");
        if (!empty($direction_infos)) {
            $rs = array('code'=>0,'msg'=>'000036','data'=>array(),'info'=>array());
            return $rs;
        }

        $update_arr = array(
            "title"=>$title,
            "major_id"=>$major_id,
            "content"=>$content,
            "remark"=>$remark,
            "file"=>$file,
            "updatetime" => time(),
            "updateman" => $user_info['realname'],
            "sort"=>$sort
        );
        if (is_array($field_list) && !empty($field_list)) {
            foreach ($field_list as $key => $value) {
                if (is_array($value)) {
                    $field_list[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
                }
            }
            $update_arr = array_merge($update_arr,$field_list);
        }
        $edit_info = $this->di->major_direction->where("id",$id)->update($update_arr);
        if ($edit_info) {
            // 清除专业方向缓存
            $this->redis->set_time('direction_list',array(),3000,'project');
            $rs = array('code'=>1,'msg'=>'000039','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000040','data'=>array(),'info'=>array());
        }

        return $rs;
    }

    // 4-3 专业方向删除接口
    public function DeleteMajorDirection($id,$uid) {
        $id = isset($id) ? intval($id) : 0;
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $direction_info = $this->di->major_direction->where(array("id"=>$id,"status"=>1))->fetchOne("title");
        if (empty($direction_info)) {
            $rs = array('code'=>0,'msg'=>'000038','data'=>array(),'info'=>array());
            return $rs;
        }
        $note = '删除专业方向ID：'.$id;
        $recycle = $this->config->get('common.IS_DELETE');
        if ($recycle) {
            $edit_info = $this->di->major_direction->where("id",$id)->delete();
            \App\setlog($uid,3,$note,'成功',$note,'删除专业方向');
        } else {
            $edit_info = $this->di->major_direction->where("id",$id)->update(array("status"=>0));
            \App\setlog($uid,3,$note,'成功',$note,'物理删除专业方向');
        }
        if ($edit_info) {
            // 清除专业方向缓存
            $this->redis->set_time('direction_list',array(),3000,'project');
            $rs = array('code'=>1,'msg'=>'000005','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000035','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    
    // 4-4 专业方向详情接口
    public function GetMajorDirectionInfo($id) {
        $id = isset($id) ? intval($id) : 0;
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $direction_info = $this->di->major_direction->where(array("id"=>$id,"status"=>1))->fetchOne("title");
        if (empty($direction_info)) {
            $rs = array('code'=>0,'msg'=>'000038','data'=>array(),'info'=>array());
            return $rs;
        }

        $direction_arr[':status'] = 1;
        $direction_where = "md.status = :status";
        $direction_arr[':id'] = $id;
        $direction_where .= " AND md.id = :id";

        $major_direction_sql = "SELECT md.*,m.title as major_name FROM ".$this->prefix."major_direction md LEFT JOIN ".$this->prefix."major m ON md.major_id = m.id WHERE ".$direction_where." LIMIT 1";
        $major_direction_info = $this->di->major_direction->queryAll($major_direction_sql, $direction_arr);
        if (!empty($major_direction_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$major_direction_info[0],'info'=>$major_direction_info[0]);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }

        return $rs;
    }

    // 5 简章新增接口
    public function PostAddGeneralRules($newData,$uid) {
        $title = isset($newData['title']) && !empty($newData['title']) ? $newData['title'] : "";
        $school_id = isset($newData['school_id']) && !empty($newData['school_id']) ? $newData['school_id'] : 0;
        $major_id = isset($newData['major_id']) && !empty($newData['major_id']) ? $newData['major_id'] : 0;
        $redirect_id = isset($newData['redirect_id']) && !empty($newData['redirect_id']) ? $newData['redirect_id'] : 0;
        $xuezhi = isset($newData['xuezhi']) && !empty($newData['xuezhi']) ? $newData['xuezhi'] : 0;
        $xuefei = isset($newData['xuefei']) && !empty($newData['xuefei']) ? $newData['xuefei'] : 0;
        $bm_price = isset($newData['bm_price']) && !empty($newData['bm_price']) ? $newData['bm_price'] : 0;
        $zl_price = isset($newData['zl_price']) && !empty($newData['zl_price']) ? $newData['zl_price'] : 0;
        $cl_price = isset($newData['cl_price']) && !empty($newData['cl_price']) ? $newData['cl_price'] : 0;
        $sq_price = isset($newData['sq_price']) && !empty($newData['sq_price']) ? $newData['sq_price'] : 0;
        $area = isset($newData['area']) && !empty($newData['area']) ? $newData['area'] : array();
        $content = isset($newData['content']) && !empty($newData['content']) ? $newData['content'] : "";
        $remark = isset($newData['remark']) && !empty($newData['remark']) ? $newData['remark'] : "";
        $file = isset($newData['file']) && !empty($newData['file']) ? $newData['file'] : "";
        $field_list = isset($newData['field_list']) && !empty($newData['field_list']) ? $newData['field_list'] : array();
        $sort = isset($newData['sort']) ? $newData['sort'] : 0;

        if (empty($title)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname,structure_id")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }
        $structure_id = json_decode($user_info['structure_id']);
        $user_structure_id = array_pop($structure_id);

        $general_info = $this->di->general_rules->where(array("title"=>$title,"status"=>1))->fetchOne("id");
        if (!empty($general_info)) {
            $rs = array('code'=>0,'msg'=>'000041','data'=>array(),'info'=>array());
            return $rs;
        }

        // 新增院校
        $province = $area[0] ? $area[0] : "";
        $city = $area[1] ? $area[1] : "";
        $country = $area[2] ? $area[2] : "";
        $general_arr = array(
            "title" => $title,
            "status" => 1,
            "sort" => $sort,
            "addtime" => time(),
            "updatetime" => time(),
            "creatid" => $uid,
            "structure_id" => $user_structure_id,
            "publisher" => $user_info['realname'],
            "updateman" => $user_info['realname'],
            "school_id" => $school_id,
            "major_id" => $major_id,
            "redirect_id" => $redirect_id,
            "xuezhi" => $xuezhi,
            "xuefei" => $xuefei,
            "bm_price" => $bm_price,
            "zl_price" => $zl_price,
            "cl_price" => $cl_price,
            "sq_price" => $sq_price,
            "province" => $province,
            "city" => $city,
            "country" => $country,
            "file" => $file
        );

        $general_data_arr = array(
            "remark" => $remark,
            "content" => $content,
        );

        if (is_array($field_list) && !empty($field_list)) {
            foreach ($field_list as $key => $value) {
                if (is_array($value)) {
                    $field_list[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
                }
            }
        }
        $general_data = array_merge($general_arr,$field_list);
        // Step 1: 开启事务
        $this->di->beginTransaction('db_master');

        $general_id = $this->di->general_rules->insert($general_data);
        $general_data_id = $this->di->general_rules_data->insert($general_data_arr);
        if ($general_id && $general_data_id) {
            // 提交事务
            $this->di->commit('db_master');

            $rs = array('code'=>1,'msg'=>'000042','data'=>array(),'info'=>array());
        } else {
             // 回滚事务
            $this->di->rollback('db_master');

            $rs = array('code'=>0,'msg'=>'000043','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 5-1 简章列表
    public function GetGerenalList($uid,$keywords,$pageno,$pagesize) {
        $keywords = isset($keywords) && !empty($keywords) ? $keywords : "";
        $pageno = isset($pageno) ? intval($pageno) : 1;
        $pagesize = isset($pagesize) ? intval($pagesize) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        $uid = isset($uid) ? $uid : 1;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("structure_id,is_leader,parent_id")->where("id",$uid)->fetchOne();
        $structure_id = json_decode($user_info['structure_id']);
        
        if ($user_info['is_leader'] != 0) {
            # 主管/经理
            $admin_common = new AdminCommon;
            $user_structure_arr = $admin_common->GetUserChargeStruct($structure_id,$user_info['is_leader'],$user_info['parent_id']);
        }

        $general_data = array();
        $general_arr[':status'] = 1;
        $general_where = "g.status = :status";
        $num_where = "status = 1";

        if ($uid != 1) {
            # 非系统管理员
            if (!empty($user_structure_arr)) {
                $user_structure_str = implode(',', $user_structure_arr);
                $user_structure_str = trim($user_structure_str,",");
                $general_where .= " AND FIND_IN_SET(g.structure_id, '{$user_structure_str}') ";
                $num_where .= " AND FIND_IN_SET(structure_id, '{$user_structure_str}') ";
            } else {
                $general_where .= " AND g.structure_id = {$structure_id} ";
                $num_where .= " AND structure_id = {$structure_id} ";
            }

        }

        if (!empty($keywords)) {
            $general_where .= " AND g.title LIKE '%{$keywords}%'"; 
            $num_where .= " AND title LIKE '%{$keywords}%'";
        }

        $general_sql = "SELECT g.*,s.title as school_name,m.title as major_name,md.title as direction_name FROM ".$this->prefix."general_rules g LEFT JOIN ".$this->prefix."school s ON g.school_id = s.id LEFT JOIN ".$this->prefix."major m ON g.major_id = m.id LEFT JOIN ".$this->prefix."major_direction md ON g.redirect_id = md.id WHERE ".$general_where." ORDER BY g.sort DESC,g.addtime DESC LIMIT ".$pagenum.",".$pagesize;
        $general_list = $this->di->general_rules->queryAll($general_sql, $general_arr);

        $general_data['general_list'] = $general_list ? $general_list : array();
        $general_data['num'] = $this->di->general_rules->where($num_where)->count("id");
        
        if ($general_data['num'] > 0) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$general_data,'info'=>$general_data);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 5-2 简章编辑接口
    public function PostEditGerenal($newData,$uid) {
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $title = isset($newData['title']) && !empty($newData['title']) ? $newData['title'] : "";
        $school_id = isset($newData['school_id']) && !empty($newData['school_id']) ? $newData['school_id'] : 0;
        $major_id = isset($newData['major_id']) && !empty($newData['major_id']) ? $newData['major_id'] : 0;
        $redirect_id = isset($newData['redirect_id']) && !empty($newData['redirect_id']) ? $newData['redirect_id'] : 0;
        $xuezhi = isset($newData['xuezhi']) && !empty($newData['xuezhi']) ? $newData['xuezhi'] : 0;
        $xuefei = isset($newData['xuefei']) && !empty($newData['xuefei']) ? $newData['xuefei'] : 0;
        $bm_price = isset($newData['bm_price']) && !empty($newData['bm_price']) ? $newData['bm_price'] : 0;
        $zl_price = isset($newData['zl_price']) && !empty($newData['zl_price']) ? $newData['zl_price'] : 0;
        $cl_price = isset($newData['cl_price']) && !empty($newData['cl_price']) ? $newData['cl_price'] : 0;
        $sq_price = isset($newData['sq_price']) && !empty($newData['sq_price']) ? $newData['sq_price'] : 0;
        $area = isset($newData['area']) && !empty($newData['area']) ? $newData['area'] : array();
        $content = isset($newData['content']) && !empty($newData['content']) ? $newData['content'] : "";
        $remark = isset($newData['remark']) && !empty($newData['remark']) ? $newData['remark'] : "";
        $file = isset($newData['file']) && !empty($newData['file']) ? $newData['file'] : "";
        $field_list = isset($newData['field_list']) && !empty($newData['field_list']) ? $newData['field_list'] : array();
        $sort = isset($newData['sort']) ? $newData['sort'] : 0;

        if (empty($id) || empty($title)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        $general_info = $this->di->general_rules->where(array("id"=>$id,"status"=>1))->fetchOne("id");
        if (empty($general_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // 标题查重
        $general_infos = $this->di->general_rules->where(array("title"=>$title,"id <> ?"=>$id))->fetchOne("title");
        if (!empty($general_infos)) {
            $rs = array('code'=>0,'msg'=>'000041','data'=>array(),'info'=>array());
            return $rs;
        }

        $province = $area[0] ? $area[0] : "";
        $city = $area[1] ? $area[1] : "";
        $country = $area[2] ? $area[2] : "";

        $update_arr = array(
            "title" => $title,
            "sort" => $sort,
            "updateman" => $user_info['realname'],
            "updatetime" => time(),
            "school_id" => $school_id,
            "major_id" => $major_id,
            "redirect_id" => $redirect_id,
            "xuezhi" => $xuezhi,
            "xuefei" => $xuefei,
            "bm_price" => $bm_price,
            "zl_price" => $zl_price,
            "cl_price" => $cl_price,
            "sq_price" => $sq_price,
            "province" => $province,
            "city" => $city,
            "country" => $country,
            "file" => $file
        );
        $update_data_arr = array(
            "remark" => $remark,
            "content" => $content,
        );
        if (is_array($field_list) && !empty($field_list)) {
            foreach ($field_list as $key => $value) {
                if (is_array($value)) {
                    $field_list[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
                }
            }
            $update_arr = array_merge($update_arr,$field_list);
        }
        $edit_info = $this->di->general_rules->where("id",$id)->update($update_arr);
        $edit_data_info = $this->di->general_rules_data->where("id",$id)->update($update_data_arr);
        if ($edit_info || $edit_data_info) {
            $rs = array('code'=>1,'msg'=>'000039','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000040','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 5-3 简章删除接口
    public function DeleteGeneralInfo($id,$uid) {
        $id = isset($id) ? intval($id) : 0;
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        $general_info = $this->di->general_rules->where(array("id"=>$id,"status"=>1))->fetchOne("id");
        if (empty($general_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        $recycle = $this->config->get('common.IS_DELETE');
        $note = '删除简章ID：'.$id;
        if ($recycle) {
            $edit_info = $this->di->general_rules->where("id",$id)->delete();
            $this->di->general_rules_data->where("id",$id)->delete();
            \App\setlog($uid,3,$note,'成功',$note,'删除简章');
        } else {
            $edit_info = $this->di->general_rules->where("id",$id)->update(array("status"=>0));
            \App\setlog($uid,3,$note,'成功',$note,'物理删除简章');
        }
        if ($edit_info) {
            $rs = array('code'=>1,'msg'=>'000005','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000035','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 5-4 简章详情接口
    public function GetGeneralInfo($id) {
        $id = isset($id) ? intval($id) : 0;
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $general_info = $this->di->general_rules->where(array("id"=>$id,"status"=>1))->fetchOne("id");
        if (empty($general_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        $general_arr[':status'] = 1;
        $general_where = "g.status = :status";
        $general_arr[':id'] = $id;
        $general_where .= " AND g.id = :id";

        $general_sql = "SELECT g.*,gd.remark,gd.content,s.title as school_name,m.title as major_name,md.title as direction_name FROM ".$this->prefix."general_rules g LEFT JOIN ".$this->prefix."general_rules_data gd ON g.id = gd.id LEFT JOIN ".$this->prefix."school s ON g.school_id = s.id LEFT JOIN ".$this->prefix."major m ON g.major_id = m.id LEFT JOIN ".$this->prefix."major_direction md ON g.redirect_id = md.id WHERE ".$general_where." LIMIT 1";
        $general_infos = $this->di->general_rules->queryAll($general_sql, $general_arr);
        if (!empty($general_infos)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$general_infos[0],'info'=>$general_infos[0]);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

}
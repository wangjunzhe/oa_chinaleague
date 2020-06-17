<?php
namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Common\Pinyin;
use App\Common\Admin as AdminCommon;
use App\Common\Category as Project;
class Educational extends NotORM
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
		$this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
        $this->redis = \PhalApi\DI()->redis;
		// 加密向量
		$this->iv = \PhalApi\DI()->config->get('common.IV');
		// 页码设置
        $this->pagesize = \PhalApi\DI()->config->get('common.PAGESIZE');

	}

    // 01 教务新增-新增
    public function PostEduAdd($newData,$file_list) {
    	$uid = $newData['uid'] ? $newData['uid'] : "";
    	$group_id = $newData['group_id'] ? $newData['group_id'] : "";
    	$uname = $newData['uname'] ? $newData['uname'] : "";
    	$phone = $newData['phone'] ? $newData['phone'] : "";
    	if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($group_id) || empty($uname) || empty($phone) ) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断用户角色
        $user_info = $this->di->user->select("username,is_leader,status,group_id")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断教务是否存在
        $edu_info = $this->di->educational->where(array("uname"=>$uname))->or(['phone'=>$phone])->fetchOne();
        if (!empty($edu_info)) {
            $rs = array('code'=>0,'msg'=>'000070','data'=>array(),'info'=>array());
            return $rs;
        }
        $cha=$this->di->educational->where('uname',$newData['uname'])->and('is_delete',0)->count();
        if($cha!=0){
            $rs = array('code'=>0,'msg'=>'教务已存在,请重新选择!','data'=>array(),'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $num = \App\CreateOrderNo();
        $newData['group_id'] = $group_id;
        $newData['uname'] = $uname;
        $newData['creatid'] = $uid;
        $newData['create_time'] = time();
        unset($newData['uid']);
        $project_data = $admin_common->GetFieldListArr($newData,$file_list);
        $project_id = $this->di->educational->insert($project_data);
        $project_id = $this->di->educational->insert_id();
        if ($project_id) {
            \App\AddShareLog($project_id,$uid,$uid,4,'add','新增教务');
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;

    }
    public function  PostEduEdit($id,$newData,$field_list){


        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $project_info = $this->di->educational->where('id',$id)->and('is_delete',0)->fetchOne();
        if (count($project_info)==0) {
            $rs = array('code'=>0,'msg'=>'教务信息不存在!','data'=>array(),'info'=>array());
            return $rs;
        }

        // 只允许创建人修改
        if ($project_info['creatid'] != $newData['uid']) {
            $rs = array('code'=>0,'msg'=>'000061','data'=>array(),'info'=>array());
            return $rs;
        }
        //查重复
        $cha=$this->di->educational->where('uname',$newData['uname'])->and('is_delete',0)->count();
        if($cha<=1){
            $admin_common = new AdminCommon();
            $newData['update_time'] = time();
            $newData['updateman']=$newData['uid'];
            \App\AddShareLog($id,$newData['uid'],$newData['uid'],4,'change','编辑教务');
            unset($newData['uid']);
            $project_data = $admin_common->GetFieldListArr($newData,$field_list);
            // 写入缓存
            $update_info = $this->di->educational->where(array("id"=>$id))->update($project_data);
        }else{
            $rs = array('code'=>0,'msg'=>'教务已存在,请重新选择!','data'=>array(),'info'=>array());
            return $rs;
        }

        if ($update_info) {
            $this->redis->set_time('edu_info_'.$id,$project_data,6000,'edu');

            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //删除工资
    public function PostEduTeacherSalaryDel($uid,$id){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $id = isset($id) && !empty($id) ? intval($id) : 0;
        if (empty($uid) || empty($id)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $status=$this->di->edusalary->where('id = ?',$id)->where('status = ?',0)->fetchOne();
        if($status){
            //如果存在的话判断权限
            if($status['creatid']== $uid){
                //是创建人
                $st= $this->di->edusalary->where('id',$id)->update(['status'=>1]);
                if($st>=1){
                    \App\AddShareLog($id,$uid,$uid,4,'del','删除教务工资');
                    $rs = array('code'=>1,'msg'=>'删除成功','data'=>array(),'info'=>array());
                }else{
                    $rs = array('code'=>0,'msg'=>'删除失败','data'=>array(),'info'=>array());
                }
            }else{
                $rs = array('code'=>0,'msg'=>'您没有权限删除该教务信息','data'=>array(),'info'=>array());
            }
        }else{
            $rs = array('code'=>0,'msg'=>'教务信息不存在','data'=>array(),'info'=>array());
            return $rs;
        }

        return $rs;
    }
    //删除教务
    public function PostEduDele($uid,$id){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $id = isset($id) && !empty($id) ? intval($id) : 0;
        if (empty($uid) || empty($id)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $status=$this->getORM()->where('id = ?',$id)->where('is_delete = ?',0)->fetchOne();
        if($status){
            //如果存在的话判断权限
            if($status['creatid']== $uid){
                //是创建人
              $st= $this->di->educational->where('id',$id)->update(['is_delete'=>1]);
              if($st>=1){
                  \App\AddShareLog($id,$uid,$uid,4,'del','删除教务');
                  $rs = array('code'=>1,'msg'=>'删除成功','data'=>array(),'info'=>array());
              }else{
                  $rs = array('code'=>0,'msg'=>'删除失败','data'=>array(),'info'=>array());
              }
            }else{
                $rs = array('code'=>0,'msg'=>'您没有权限删除该教务信息','data'=>array(),'info'=>array());
            }
        }else{
            $rs = array('code'=>0,'msg'=>'教务信息不存在','data'=>array(),'info'=>array());
            return $rs;
        }

        return $rs;

    }
    //教务列表
    public function GetEduList($newData){
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? $newData['type'] : 0;
        $keywords = isset($newData['keywords']) && !empty($newData['keywords']) ? $newData['keywords'] : "";
        $pageno = isset($pageno) ? intval($pageno) : 1;
        $pagesize = isset($pagesize) ? intval($pagesize) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("is_leader,status,structure_id")->where("id",$uid)->fetchOne();
        $structure_id = json_decode($user_info['structure_id']);
        $user_info['structure_id'] = array_pop($structure_id);
        $project_where = "is_delete = 0";//0未删除
        if ($uid == 1) {
            $project_where .= "";
        }
//        } else {
//
//           $project_where .= " AND FIND_IN_SET('{$uid}', creatid) ";
//
//        }
        if (!empty($keywords)) {
            $project_where .= " AND uname LIKE '%{$keywords}%' ";
        }
        $company_num = $this->di->educational->where('status',1)->and('is_delete',0)->count("id");// 正常个数
        $person_num = $this->di->educational->where('status',2)->and('is_delete',0)->count("id");// 无效个数
        $data['valid_num'] = $company_num ? $company_num : 0;
        $data['invalid_num'] = $person_num ? $person_num : 0;
        if ($type == 1 || $type == 2) {
            $project_where .= " AND status = ".$type;
        }
        $project_list = $this->di->educational->select("*")->where($project_where)->order("create_time DESC")->limit($pagenum,$pagesize)->fetchRows();
        $total_num = $this->di->educational->where('is_delete',0)->count("id");// 全部教务
        $data['all_total'] = $total_num;
        if (!empty($project_list)) {
            foreach ($project_list as $k=>$v){
                $username=$this->di->user->where('id',$v['creatid'])->fetchOne('realname');
                $class_name='';
                if($v['class_id']!=''){
                    $class_lilst=explode(',',$v['class_id']);
                    foreach ($class_lilst as $n=>$m){
                        $class_nu=$this->di->class->where('id',$m)->fetchOne('title');
                       $class_name.=$class_nu.',';
                    }
                }
                $username=$this->di->user->where('id',$v['creatid'])->fetchOne('realname');
                $project_list[$k]['cname']=$username;
                $project_list[$k]['teacher']=$this->di->user->where('id',explode(',',$v['uname'])[1])->fetchOne('realname');
                $project_list[$k]['class_name']=$class_name;
                $project_list[$k]['updateman']=\App\GetFiledInfo('user','realname',$v['updateman']);
                $group_id=$this->di->structure->where('id',$v['group_id'])->fetchOne('pid');
                $project_list[$k]['group_id']=$group_id.','.$v['group_id'];
            }
            $data['project_list'] = $project_list ? $project_list : array();

            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>$data,'info'=>$data);
        }
        return $rs;
    }
    public function PostEduClassDele($uid,$id){

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("is_leader,status,structure_id")->where("id",$uid)->fetchOne();
        $project_info = $this->di->educlass->where("id",$id)->fetchOne();
        if($user_info['is_leader']==1 && !empty($project_info)){
            //如果用户是主管  可以删除
            $this->di->educlass->where('id',$id)->update(['is_delete'=>1]);
            $rs=array('code'=>1,'msg'=>'999993','data'=>array(),'info'=>array());
            //记录操作日志
        }else{
                if($project_info['creatid']==$uid){
                    $this->di->educlass->where('id',$id)->update(['status'=>0]);
                    //记录操作日志
                    \App\AddShareLog($id,$uid,$uid,4,'del','删除上课信息');
                    $rs=array('code'=>1,'msg'=>'999993','data'=>array(),'info'=>array());
                }else{

                    $rs=array('code'=>0,'msg'=>'999980','data'=>array(),'info'=>array());
                }
        }

        return $rs;

    }
    //上课信息列表
    public function PostEduClassList($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $tid = isset($data['tid']) && !empty($data['tid']) ? intval($data['tid']) : 0;
        $keywords = isset($data['keywords']) && !empty($data['keywords']) ? $data['keywords'] : '';
        $type = isset($data['type']) && !empty($data['type']) ? $data['type'] : '1';
        $pageno = isset($data['pageno']) ? intval($data['pageno']) : 1;
        $pagesize = isset($data['pagesize']) ? intval($data['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if($tid!=0){
            $tid=$this->di->educational->where('id',$tid)->and('is_delete',0)->fetchOne('uname');
            if($tid==false){
                $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
                return $rs;
            }
            $tid=explode(',',$tid)[1];
            $twhere=" AND creatid =".$tid;

        }else{
            $twhere=" AND creatid =".$uid;
        }
        $params=[];
        $sea_where1 =" is_delete = 0";
        $count_sql1 = "SELECT count(id) as num FROM ".$this->prefix."educlass WHERE ".$sea_where1.$twhere;
        $all_total= $this->di->educlass->queryAll($count_sql1,$params);
        $total_arr['all_total']=$all_total[0]['num'];
//        今日
        $params=[];
        $sea_where ="to_days(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = to_days(now()) AND is_delete = 0";
        $count_sql = "SELECT count(id) as num FROM ".$this->prefix."educlass WHERE ".$sea_where.$twhere;
        $today_total= $this->di->educlass->queryAll($count_sql,$params);
        $total_arr['today_total']=$today_total[0]['num'];
       //本周
        $date_array = \App\get_weeks();
        foreach ($date_array as $k=>$v){
            if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                $date_list[]=$v;
            }
        }
        $start_time=strtotime(reset($date_array));
        $end_time=strtotime(end($date_array));
        $wherew = " UNIX_TIMESTAMP(FROM_UNIXTIME(create_time,'%Y-%m-%d')) BETWEEN ".$start_time." and ".$end_time." AND is_delete = 0 ";
        $count_sql2 = "SELECT count(id) as num FROM ".$this->prefix."educlass WHERE ".$wherew.$twhere;
        $week_total=$this->di->educlass->queryAll($count_sql2,$params);
        $total_arr['week_total']= $week_total[0]['num'];
        //本月
        $month=\App\getMonth();
        foreach ($month as $k=>$v){
            if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                $month_list[]=$v;
            }
        }
        $start_time=strtotime(reset($month_list));
        $end_time=strtotime(end($month_list));
        $wheremonth = " UNIX_TIMESTAMP(FROM_UNIXTIME(create_time,'%Y-%m-%d')) BETWEEN ".$start_time." and ".$end_time.' AND is_delete = 0 ';
        $count_sql3 = "SELECT count(id) as num FROM ".$this->prefix."educlass WHERE ".$wheremonth.$twhere;
        $month_total=$this->di->educlass->queryAll($count_sql3,$params);
        $total_arr['month_total']= $month_total[0]['num'];

        if ($keywords!='') {
            $keywords_where = " ( class_name LIKE '%{$keywords}%' OR edu_uname LIKE '%{$keywords}%' OR create_time = '{$keywords}' ) AND is_delete = 0 ";
            //根基按照班级、教务老师、上课老师、创建时间进行关键词筛选
        }else{
            $keywords_where="is_delete = 0 ";
        }

        switch ($type) {
            case 1:
                # 本日
                $keywords_where.=' AND ';
                $sql = "SELECT * FROM ".$this->prefix."educlass WHERE ".$keywords_where.$sea_where.$twhere." ORDER BY create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(id) as num FROM ".$this->prefix."educlass WHERE ".$keywords_where.$sea_where.$twhere;
                break;
            case 2:
                # 本周
                $keywords_where.=' AND ';
                $sql = "SELECT * FROM ".$this->prefix."educlass WHERE ".$keywords_where.$wherew.$twhere." ORDER BY create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(id) as num FROM ".$this->prefix."educlass WHERE ".$keywords_where.$wherew.$twhere;
                break;
            case 3:
                //本月
                $keywords_where.=' AND ';
                $sql = "SELECT * FROM ".$this->prefix."educlass WHERE ".$keywords_where.$wheremonth.$twhere." ORDER BY create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(id) as num FROM ".$this->prefix."educlass WHERE ".$keywords_where.$wheremonth.$twhere;
                break;
            case 4:
               if($keywords_where!='' && $tid!=0){
                    $keywords_where.='';
                }
                $sql = "SELECT * FROM ".$this->prefix."educlass WHERE ".$keywords_where.$twhere." ORDER BY create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(id) as num FROM ".$this->prefix."educlass WHERE ".$keywords_where.$twhere;
                break;
            default:
                # 全部
              break;

        }
        $params=[];

        // 上课记录列表数据

        $customer_gh_list = $this->di->educlass->queryAll($sql, $params);
        $customer_gh_num = $this->di->educlass->queryAll($count_sql,$params);
        $customer_gh_data['customer_gh_list'] = !empty($customer_gh_list) ? $customer_gh_list : array();
        $customer_gh_data['customer_gh_num'] = !empty($customer_gh_num[0]['num']) ? $customer_gh_num[0]['num'] : 0;
        $customer_gh_data['total_count'] = !empty($total_arr) ? $total_arr : array();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_gh_data,'info'=>$customer_gh_data);
        return $rs;

    }


    //教务详情

    public function GetEduInfo($uid,$id,$type){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $id = isset($id) && !empty($id) ? intval($id) : 0;
        $type = isset( $type) && !empty($type) ? intval($type) : 1;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $keywords_where="id = '{$id}'";
        $params=[];
        switch ($type){
            case 1:
                //教务详情
                $sql = "SELECT * FROM ".$this->prefix."educational WHERE ".$keywords_where;
                $data = $this->di->educational->queryAll($sql, $params);
                if(count($data)>0){
                    $data[0]['cname']=$this->di->user->where('id',$data[0]['creatid'])->fetchOne('realname');
                    $data[0]['updateman']=\App\GetFiledInfo('user','realname',$data['updateman']);
                    $class_lilst=explode(',',$data[0]['class_id']);
                    foreach ($class_lilst as $n=>$m){
                        $class_nu=$this->di->class->where('id',$m)->fetchOne('title');
                        $class_name.=$class_nu.',';
                    }
                    $data[0]['teacher']=preg_replace("/\\,/",'',preg_replace("/\\d+/",'', $data[0]['uname']));
                    $data[0]['class_name']=$class_name;//上课记录
                    $tid=explode(',',$data[0]['uname'])[1];
                    $data[0]['class_num']=$this->di->educlass->where('creatid',$tid)->and('is_delete',0)->count();
                    //付款记录
                    $data[0]['salary_num']=$this->di->edusalary->where('askforman_id',$tid)->and('status',0)->count();
                    //付款总额
                    $data[0]['salary_total_money']=$this->di->edusalary->where('askforman_id',$tid)->and('status',0)->sum('pay_money');
//                    $data[0]['salary_total_money']=='null'?$data[0]['salary_total_money']:$data[0]['salary_total_money']=0;
                    $data[0]['log']= $this->di->share_log->select("note,addtime")->where(array("type"=>4,"info_id"=>$tid,"share_uid"=>$uid))->fetchRows();
                    $prev_agent_id = $this->di->educational->where("is_delete <> 1 AND FIND_IN_SET('{$uid}', creatid) AND `id`<'$id' ")->order("id DESC")->fetchOne("id");
                    $next_agent_id = $this->di->educational->where("is_delete <> 1 AND FIND_IN_SET('{$uid}', creatid) AND `id`>'$id' ")->order("id ASC")->fetchOne("id");
                    $data[0]['next_edu_id'] = $next_agent_id;
                    $data[0]['prev_edu_id'] = $prev_agent_id;
                }

                break;
            case 2:
                //上课记录详情
                $sql = "SELECT * FROM ".$this->prefix."educlass WHERE ".$keywords_where;
                $data = $this->di->educlass->queryAll($sql, $params);
                break;
            case 3:
                //学员资料详情
                $sql = "SELECT * FROM ".$this->prefix."edustudent WHERE ".$keywords_where;
                $data = $this->di->edustudent->queryAll($sql, $params);
                break;
            default:
                //老师课酬详情
                $sql = "SELECT * FROM ".$this->prefix."edusalary WHERE ".$keywords_where;
                $data = $this->di->edusalary->queryAll($sql, $params);
                break;


        }

        if($data){
            $rs = array('code'=>1,'msg'=>'加载成功!','data'=>$data,'info'=>array());
        }else{
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //添加上课信息
    public function PostEduClassAdd($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $xm_id = isset($data['xm_id']) && !empty($data['xm_id']) ? $data['xm_id'] :'';
        $creat_name = isset($data['create_name']) && !empty($data['create_name']) ? $data['create_name'] :'';
        $edu_uid = isset($data['edu_uid']) && !empty($data['edu_uid']) ? $data['edu_uid'] :'';
        $edu_uname = isset($data['edu_uname']) && !empty($data['edu_uname']) ? $data['edu_uname'] :'';
        $class_id = isset($data['class_id']) && !empty($data['class_id']) ? $data['class_id'] :'';
        $class_name = isset($data['class_name']) && !empty($data['class_name']) ? $data['class_name'] :'';
        if( $uid==0 || $xm_id=='' || $creat_name=='' || $edu_uid=='' || $edu_uname==''||$class_id==''|| $class_name=='' ){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }else{
            unset($data['service']);
            $num = \App\CreateOrderNo();
            $data['class_num']= 'SK'.$num;
            $data['create_time']=time();
            $data['creatid']=$uid;
            unset($data['uid']);

            $status=$this->di->educlass->insert($data);
            $id=$this->di->educlass->insert_id();
            if($id){
                \App\AddShareLog($id,$uid,$uid,4,'add','添加上课信息');
                $rs = array('code'=>1,'msg'=>'插入成功','data'=>array(),'info'=>array());
            }else{
                $rs = array('code'=>0,'msg'=>'插入失败','data'=>array(),'info'=>array());
            }
            return $rs;
        }
    }

    //编辑上课信息
    public function PostEduClassEdit($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $id = isset($data['id']) && !empty($data['id']) ? intval($data['id']) : 0;
        $xm_id = isset($data['xm_id']) && !empty($data['xm_id']) ? $data['mid'] :'';
        $edu_uid = isset($data['edu_uid']) && !empty($data['edu_uid']) ? $data['edu_uid'] :'';
        $edu_uname = isset($data['edu_uname']) && !empty($data['edu_uname']) ? $data['edu_uname'] :'';
        $class_id = isset($data['class_id']) && !empty($data['class_id']) ? $data['class_id'] :'';
        $class_name = isset($data['class_name']) && !empty($data['class_name']) ? $data['class_name'] :'';

            unset($data['service']);
            $data['updatetime']=time();
            unset($data['uid']);
            unset($data['id']);
            $status=$this->di->educlass->where('id',$id)->update($data);
            if( $status){
                \App\AddShareLog($id,$uid,$uid,4,'change','编辑上课信息');
                $rs = array('code'=>1,'msg'=>'更新成功','data'=>array(),'info'=>array());
            }else{
                $rs = array('code'=>0,'msg'=>'更新失败','data'=>array(),'info'=>array());
            }
            return $rs;
        }

    //新建上课学员资料
    public function PostEduStudent($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $cname = isset($data['cname']) && !empty($data['cname']) ? $data['cname'] :'';
        $cid = isset($data['cid']) && !empty($data['cid']) ? $data['cid'] :'';
        $order_num = isset($data['order_num']) && !empty($data['order_num']) ? $data['order_num'] :'';
        $cphone = isset($data['cphone']) && !empty($data['cphone']) ? $data['cphone'] :'';
        $class_name = isset($data['class_name']) && !empty($data['class_name']) ? $data['class_name'] :'';
        $class_id = isset($data['class_id']) && !empty($data['class_id']) ? $data['class_id'] :'';
        $class_teacher = isset($data['class_teacher']) && !empty($data['class_teacher']) ? $data['class_teacher'] :'';
        if( $uid==0 || $cname=='' || $cid=='' || $order_num=='' || $cphone==''||$class_id==''|| $class_name=='' || $class_teacher==''){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }else{
            unset($data['service']);
            $data['creatid']=$uid;
            $data['creatid']=$uid;
            $data['create_time']=time();
            unset($data['uid']);
            $status=$this->di->edustudent->insert($data);
            $id=$this->di->edustudent->insert_id();
            if($id){
                \App\AddShareLog($id,$uid,$uid,4,'add','新建学员资料');
                $rs = array('code'=>1,'msg'=>'插入成功','data'=>array(),'info'=>array());
            }else{
                $rs = array('code'=>0,'msg'=>'插入失败','data'=>array(),'info'=>array());
            }
            return $rs;
        }
    }
    //删除学员资料
    public function PostEduStudentDele($uid,$id){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $id = isset($id) && !empty($id) ? intval($id) : 0;
        if (empty($uid) || empty($id)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $status=$this->di->edustudent->where('id = ?',$id)->where('status = ?',0)->fetchOne();
        if($status){
            //如果存在的话判断权限
            if($status['creatid']== $uid){
                //是创建人
                $st= $this->di->edustudent->where('id',$id)->update(['status'=>1]);
                if($st>=1){
                    \App\AddShareLog($id,$uid,$uid,4,'del','删除学员资料');
                    $rs = array('code'=>1,'msg'=>'删除成功','data'=>array(),'info'=>array());
                }else{
                    $rs = array('code'=>0,'msg'=>'删除失败','data'=>array(),'info'=>array());
                }
            }else{
                $rs = array('code'=>0,'msg'=>'您没有权限删除该教务信息','data'=>array(),'info'=>array());
            }
        }else{
            $rs = array('code'=>0,'msg'=>'教务信息不存在','data'=>array(),'info'=>array());
            return $rs;
        }

        return $rs;
    }
    //编辑上课学员资料
    public function PostEduStudentEdit($data){
        $id = isset($data['id']) && !empty($data['id']) ? intval($data['id']) : 0;
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $cname = isset($data['cname']) && !empty($data['cname']) ? $data['cname'] :'';
        $cid = isset($data['cid']) && !empty($data['cid']) ? $data['cid'] :'';
        $order_num = isset($data['cid']) && !empty($data['cid']) ? $data['cid'] :'';
        $cphone = isset($data['cphone']) && !empty($data['cphone']) ? $data['cphone'] :'';
        $class_name = isset($data['class_name']) && !empty($data['class_name']) ? $data['class_name'] :'';
        $class_id = isset($data['class_id']) && !empty($data['class_id']) ? $data['class_id'] :'';
        $class_teacher = isset($data['class_teacher']) && !empty($data['class_teacher']) ? $data['class_teacher'] :'';
        if($id==0 || $uid==0 || $cname=='' || $cid=='' || $order_num=='' || $cphone==''||$class_id==''|| $class_name=='' || $class_teacher==''){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }else{
            unset($data['service']);
            $data['update_time']=time();
            unset($data['uid']);
            unset($data['id']);

            $status=$this->di->edustudent->where('id',$id)->update($data);
            if($status){
                \App\AddShareLog($id,$uid,$uid,4,'change','编辑学员资料');
                $rs = array('code'=>1,'msg'=>'更新成功','data'=>array(),'info'=>array());
            }else{
                $rs = array('code'=>0,'msg'=>'更新失败','data'=>array(),'info'=>array());
            }
            return $rs;
        }
    }
    //学员资料列表
    public function PostEduStudentList($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $keywords = isset($data['keywords']) && !empty($data['keywords']) ? $data['keywords'] : '';
        $type = isset($data['type']) && !empty($data['type']) ? $data['type'] : '1';
        $pageno = isset($data['pageno']) ? intval($data['pageno']) : 1;
        $pagesize = isset($data['pagesize']) ? intval($data['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if ($keywords!='') {
            $keywords_where = " cname LIKE '%{$keywords}%' OR class_name LIKE '%{$keywords}%' OR jiaowu_teachername LIKE '%{$keywords}%' OR 	class_teachername LIKE '%{$keywords}%' AND status = 0 AND creatid=".$uid;
            //根基按照班级、教务老师、上课老师、创建时间进行关键词筛选
        }else{
            $keywords_where=" status = 0 AND creatid=".$uid;
        }
        //计算数量
        $params=[];
        //全部
        $all_sql = "SELECT count(id) as num FROM ".$this->prefix."edustudent WHERE status = 0 AND creatid =".$uid;
        $all_num = $this->di->edustudent->queryAll($all_sql,$params);
        //今日
        $today_where =" AND to_days(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = to_days(now()) ";
        $today_sql = "SELECT count(id) as num FROM ".$this->prefix."edustudent WHERE status = 0 AND creatid =".$uid.$today_where;

        $today_num = $this->di->edustudent->queryAll($today_sql,$params);
        //本周
        $now=time();
        $time = '1' == date('w') ? strtotime('Monday', $now) : strtotime('last Monday', $now);
        $Wstarttime =  strtotime(date('Y-m-d 00:00:00', $time));
        $Wendtime = strtotime(date('Y-m-d 23:59:59', strtotime('Sunday', $now)));
        $where = " AND FROM_UNIXTIME( 'create_time',  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$Wstarttime' AND '$Wendtime' ";
        $week_sql = "SELECT count(id) as num FROM ".$this->prefix."edustudent WHERE status = 0 AND creatid =".$uid.$where;
        $week_num = $this->di->edustudent->queryAll($week_sql,$params);
        //本月
        $month=\App\getMonth();
        foreach ($month as $k=>$v){
            if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                $month_list[]=$v;
            }
        }
        $start_time=strtotime(reset($month_list));
        $end_time=strtotime(end($month_list));
        $wheremonth = " AND UNIX_TIMESTAMP(FROM_UNIXTIME(create_time,'%Y-%m-%d')) BETWEEN ".$start_time." and ".$end_time;
        $month_sql = "SELECT count(id) as num FROM ".$this->prefix."edustudent WHERE  status = 0 AND creatid =".$uid.$wheremonth;
        $month_num = $this->di->edustudent->queryAll($month_sql,$params);
        $total_arr['all_num']=$all_num[0]['num'];
        $total_arr['today_num']=$today_num[0]['num'];
        $total_arr['week_num']=$week_num[0]['num'];
        $total_arr['month_num']=$month_num[0]['num'];
        /**
         * c
         */
        switch ($type) {
            case 1:
                # 全部上课记录
                $sql = "SELECT * FROM ".$this->prefix."edustudent WHERE ".$keywords_where." ORDER BY create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(id) as num FROM ".$this->prefix."edustudent WHERE ".$keywords_where;
                break;
            case 2:
                # 今日上课记录
                $sea_where =" AND to_days(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = to_days(now()) ";
                $sql = "SELECT * FROM ".$this->prefix."edustudent WHERE ".$keywords_where.$sea_where." ORDER BY create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(id) as num FROM ".$this->prefix."edustudent WHERE ".$keywords_where.$sea_where;
                break;
           case 3:
                # 本周上课记录
                $start              = strtotime("-7 day");
                $start_date         = date('Y-m-d 00:00:00',$start);
                $end_date           = date('Y-m-d 23:59:59',time());
                $where = " AND FROM_UNIXTIME( 'create_time',  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";
//               $where = " AND YEARWEEK(date_format(create_time,'%Y-%m-%d')) = YEARWEEK(now())";
                $sql = "SELECT * FROM ".$this->prefix."edustudent WHERE ".$keywords_where.$where." ORDER BY create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(id) as num FROM ".$this->prefix."edustudent WHERE ".$keywords_where.$where;
                break;
            default:
                # 本月上课记录
//                $where = " AND DATE_FORMAT('create_time', '%Y%m' ) = DATE_FORMAT( CURDATE( ) , '%Y%m' )";
                $sql = "SELECT * FROM ".$this->prefix."edustudent WHERE ".$keywords_where.$wheremonth." ORDER BY create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(id) as num FROM ".$this->prefix."edustudent WHERE ".$keywords_where.$where;
                break;
        }
        $params=[];
        // 上课记录列表数据
        $customer_gh_list = $this->di->edustudent->queryAll($sql, $params);
        $customer_gh_num = $this->di->edustudent->queryAll($count_sql,$params);
        $customer_gh_data['customer_gh_list'] = !empty($customer_gh_list) ? $customer_gh_list : array();
        $customer_gh_data['customer_gh_num'] = !empty($customer_gh_num[0]['num']) ? $customer_gh_num[0]['num'] : 0;
        $customer_gh_data['total_count'] = !empty($total_arr) ? $total_arr : array();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_gh_data,'info'=>$customer_gh_data);
        return $rs;
    }

    //新增教师薪资
    public function PostEduTeacherSalaryAdd($data){

        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $class_num = isset($data['class_num']) && !empty($data['class_num']) ? $data['class_num'] :'';
        $edu_uid = isset($data['edu_uid']) && !empty($data['edu_uid']) ? $data['edu_uid'] :'';
        $create_name = isset($data['create_name']) && !empty($data['create_name']) ? $data['create_name'] :'';
        if($uid==0 || $class_num=='' || $edu_uid=='' || $create_name==''){

            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }else{
            unset($data['service']);
            $data['create_time']=time();
            $data['update_time']=time();
            $data['creatid']=$uid;
            unset($data['uid']);
            $status=$this->di->edusalary->insert($data);
            $id=$this->di->edusalary->insert_id();
            if($id){
                //修改上课信息的付款状态
                $this->di->educlass->where('class_num',$data['class_num'])->update(['status'=>1]);
                \App\AddShareLog($id,$uid,$uid,4,'add','新增教务薪资');
                $rs = array('code'=>1,'msg'=>'插入成功','data'=>array(),'info'=>array());
            }else{
                $rs = array('code'=>0,'msg'=>'插入失败','data'=>array(),'info'=>array());
            }
            return $rs;
        }

    }
    //获取上课编号
    public function GetClassNum($uid,$id){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $id = isset($id ) && !empty($id ) ? intval($id ) : 0;

        if($uid==0 || $id==''){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }else{
            $data=$this->di->educlass->select('class_num')->where('edu_uid',$id)->and('is_delete',0)->and('creatid',$uid)->fetchAll();
            $rs = array('code'=>1,'msg'=>'获取成功','data'=>$data,'info'=>$data);
            return $rs;
        }
    }

    //教师薪酬编辑
    public function PostEduTeacherSalaryEdit($data){
        $id = isset($data['id']) && !empty($data['id']) ? intval($data['id']) : 0;
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $class_num = isset($data['class_num']) && !empty($data['class_num']) ? $data['class_num'] :'';
        $creat_name = isset($data['create_name']) && !empty($data['create_name']) ? $data['create_name'] :'';
        if($id==0 || $uid==0 || $class_num=='' || $creat_name==''){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }else{
            //判断是否是创建人
            $sta=$this->di->edusalary->where('id',$id)->and('status',0)->fetchOne();
            if($sta==''){
                $rs = array('code'=>0,'msg'=>'信息不存在','data'=>array(),'info'=>array());
                return $rs;
            }
            // Step 1: 开启事务
            $this->di->beginTransaction('db_master');
            unset($data['service']);
            $data['update_time']=time();
            $data['update_name']=$creat_name;
            $data['update_id']=$uid;
            unset($data['uid']);
            unset($data['id']);
            $status=$this->di->edusalary->where('id',$id)->update($data);
            $zr=$this->di->educlass->where('class_num',$data['class_num'])->fetchOne();
            if($zr=='' || $zr['status']==1 ){
                // 回滚事务
                $this->di->rollback('db_master');
                $rs = array('code'=>0,'msg'=>'更新失败','data'=>array(),'info'=>array());
            } else {
                    $status=$this->di->educlass->where('class_num',$data['class_num'])->update(['status'=>1]);
                    // 提交事务
                    $this->di->commit('db_master');
                \App\AddShareLog($id,$uid,$uid,4,'change','教师师薪酬编辑');
            }

            if($status){
                $rs = array('code'=>1,'msg'=>'更新成功','data'=>array(),'info'=>array());
            }else{
                $rs = array('code'=>0,'msg'=>'更新失败','data'=>array(),'info'=>array());
            }
            return $rs;
        }
    }

    //教师薪酬列表

    public function  PostEduTeacherSalaryList($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $tid = isset($data['tid']) && !empty($data['tid']) ? intval($data['tid']) : 0;
        $keywords = isset($data['keywords']) && !empty($data['keywords']) ? $data['keywords'] : '';
        $type = isset($data['type']) && !empty($data['type']) ? $data['type'] : '1';
        $pageno = isset($data['pageno']) ? intval($data['pageno']) : 1;
        $pagesize = isset($data['pagesize']) ? intval($data['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if ($keywords!='') {
            $keywords_where = "concat(c.class_num,c.askforman_name,c.create_name) like '%{$keywords}%' AND c.status = 0";
            //根基按照班级、教务老师、上课老师、创建时间进行关键词筛选
        }else{
            if($tid==0){
                $keywords_where=" c.status = 0 AND c.askforman_id =".$uid.' OR c.creatid ='.$uid;
            }else{
                $tid=$this->di->educational->where('id',$tid)->and('is_delete',0)->fetchOne('uname');
                $tid=explode(',',$tid)[1];
                $keywords_where=" c.status = 0 AND c.askforman_id =".$tid;
            }

        }
//        1总课酬2本日3本周4本月
        $salary_list=$this->di->edusalary->where('status',0)->and('creatid',$uid)->fetchAll();
        $now = time();
        $today_audit_num = 0;
        $today_use_num = 0;
        $Tstarttime =  strtotime(date('Y-m-d 00:00:00', $now));
        $Tendtime = strtotime(date('Y-m-d 23:59:59', $now));
        //本周
        $week_audit_num = 0;
        $week_use_num = 0;
        $time = '1' == date('w') ? strtotime('Monday', $now) : strtotime('last Monday', $now);
        $Wstarttime =  strtotime(date('Y-m-d 00:00:00', $time));
        $Wendtime = strtotime(date('Y-m-d 23:59:59', strtotime('Sunday', $now)));
        //本月
        $month_audit_num = 0;
        $month_use_num = 0;
        $Mstarttime = strtotime(date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m', $now), '1', date('Y', $now))));
        $Mendtime =  strtotime(date('Y-m-d 23:39:59', mktime(0, 0, 0, date('m', $now), date('t', $now), date('Y', $now))));
        $all_money=$this->di->edusalary->where('status',0)->and('creatid',$uid)->sum('pay_money'); ;
        $today_money=$this->di->edusalary->where('status',0)->and('creatid',$uid)->and('create_time BETWEEN ? AND ?',$Tstarttime, $Tendtime)->sum('pay_money');
        $week_money=$this->di->edusalary->where('status',0)->and('creatid',$uid)->and('create_time BETWEEN ? AND ?', $Wstarttime, $Wendtime)->sum('pay_money');
        $month_money=$this->di->edusalary->where('status',0)->and('creatid',$uid)->and('create_time BETWEEN ? AND ?',  $Mstarttime,  $Mendtime)->sum('pay_money');
        switch ($type) {
            case 1:
                # 全部上课记录
                $sql = "SELECT c.*,cd.name,cs.class_date FROM ".$this->prefix."edusalary c LEFT JOIN crm_teacher cd ON cd.id=c.edu_uid LEFT JOIN crm_educlass cs ON cs.class_num=c.class_num WHERE ".$keywords_where."  ORDER BY  c.create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."edusalary c WHERE ".$keywords_where;
                break;
            case 2:
                # 今日上课记录
                $sea_where =" AND to_days(FROM_UNIXTIME(c.create_time,'%Y-%m-%d')) = to_days(now())";
                $sql = "SELECT c.*,cd.name,cs.class_date FROM ".$this->prefix."edusalary c LEFT JOIN  crm_teacher cd ON cd.id=c.edu_uid LEFT JOIN crm_educlass cs ON cs.class_num=c.class_num WHERE ".$keywords_where.$sea_where." ORDER BY c.create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."edusalary c WHERE ".$keywords_where.$sea_where;
                break;
            case 3:
                # 本周上课记录
                $date_array = \App\get_weeks();
                foreach ($date_array as $k=>$v){
                    if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                        $date_list[]=$v;
                    }
                }
                $start_time=strtotime(reset($date_array));
                $end_time=strtotime(end($date_array));

                $wherew = " AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.create_time,'%Y-%m-%d')) BETWEEN ".$start_time." and ".$end_time;
                $sql = "SELECT c.*,cd.name,cs.class_date  FROM ".$this->prefix."edusalary c LEFT JOIN  crm_teacher cd ON cd.id=c.edu_uid LEFT JOIN crm_educlass cs ON cs.class_num=c.class_num WHERE ".$keywords_where.$wherew." ORDER BY  c.create_time DESC LIMIT ".$pagenum.",".$pagesize;

                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."edusalary c WHERE ".$keywords_where.$wherew;
                break;
            default:
                # 本月上课记录
                $month=\App\getMonth();
                foreach ($month as $k=>$v){
                    if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                        $month_list[]=$v;
                    }
                }
                $start_time=strtotime(reset($month_list));
                $end_time=strtotime(end($month_list));
                $wheremonth = " AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.create_time,'%Y-%m-%d')) BETWEEN ".$start_time." and ".$end_time;
                $sql = "SELECT c.*,cd.name,cs.class_date FROM ".$this->prefix."edusalary c LEFT JOIN crm_teacher cd ON cd.id=c.edu_uid LEFT JOIN crm_educlass cs ON cs.class_num=c.class_num WHERE ".$keywords_where.$wheremonth." ORDER BY  c.create_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."edusalary c WHERE ".$keywords_where.$where;
                break;
        }
        $params=[];
        // 上课记录列表数据
        $customer_gh_list = $this->di->edusalary->queryAll($sql, $params);
        $customer_gh_num = $this->di->edusalary->queryAll($count_sql,$params);
        $customer_gh_data['all_money'] = $all_money;
        $customer_gh_data['today_money'] = $today_money;
        $customer_gh_data['week_money'] = $week_money;
        $customer_gh_data['month_money'] = $month_money;
        $customer_gh_data['customer_gh_list'] = !empty($customer_gh_list) ? $customer_gh_list : array();
        $customer_gh_data['customer_gh_num'] = !empty($customer_gh_num[0]['num']) ? $customer_gh_num[0]['num'] : 0;
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_gh_data,'info'=>$customer_gh_data);
        return $rs;
    }
    //首页分析
    public function GetAllStatisticsList($uid,$type,$date_sl,$date_yj)
    {
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $date_sl = isset($date_sl) && !empty($date_sl) ? $date_sl : '';
        $date_yj = isset($date_yj) && !empty($date_yj) ? $date_yj : '';
        $type = isset($type) && !empty($type) ? $type : '1';
        $params = [];
        $date_array = \App\get_weeks();
        $month = \App\getMonth();
        $getyear = \App\Getyear();
//        $all_list=$this->redis->get_time('all_list_'.$uid.$type.$date_sl.$date_yj,'project');
//        $all_list=0;
//        if(!empty($all_list)){
//            return $rs = array('code'=>1,'msg'=>'000000','data'=>$all_list,'info'=>$all_list);
//
//        }
        if ($uid == 0) {
            $rs = array('code' => 0, 'msg' => '000044', 'data' => array(), 'info' => array());
            return $rs;
        }
        //判断用户身份
        $user_info = $this->di->user->select("is_leader,status,structure_id,username,group_id,type,type_id")->where("id", $uid)->and('status', 1)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code' => 0, 'msg' => '000004', 'data' => array(), 'info' => array());
            return $rs;
        }
        $structure_id = json_decode($user_info['structure_id']);
        $user_structure_id = array_pop($structure_id);
        $data = [];


        if( $user_info['group_id'] =='20'){
            //超管
            //1 查询该主管部门下所有人
            //客户
//        $customer=new Customer();
            $customer_list=$this->di->customer->where('status',1)->count();
            $data['customer_total']=  $customer_list;
            //跟进
            $data['follow_total']=$this->di->follw->group('cid')->count();
            //成交
            $data['order_total']=$this->di->customer_order->where('status','<>','0')->sum('total');
            //回款
            $data['project_total']=$this->di->customer_data->where('project_name','<>',' ')->sum('paid');
            //付款
//        $agent_total_sql="SELECT SUM(paid) as num FROM ".$this->prefix."customer_data WHERE agent_name <> '' ";
//        $data['agent_total']=$this->di->customer_data->where('agent_name','<>','0')->sum('paid');
//        $customer_list = $this->di->customer_data->queryAll($agent_total_sql, $params);
            $data['agent_total']=$this->di->customer_order->where('agent_id','<>',0)->and('status','<>',0)->sum('total');
            //退款
            $data['refund_total']=$this->di->customer_refund->where('status',1)->sum('refund_total');
        }else if($user_info['is_leader']=='1' && $user_info['group_id']!='20'){
            //部门主管
            //1 查询该主管部门下所有人
            //客户
            $customer=new Customer();
            $customer_list=$customer->GetCustomerList(['uid'=>$uid,'type'=>15]);
            $data['customer_total']= $customer_list['data']['customer_num'];
            //跟进
            $user_structure_list=$this->di->user->select('id')->where('structure_id',$user_info['structure_id'])->and('status',1)->fetchAll();
            foreach($user_structure_list as $k=>$v){
                $count=$this->di->follw->where('uid',$v['id'])->group('cid')->fetchAll();
                $order_count=$this->di->customer_order->where('deal_id',$v['id'])->and('status','<>',0)->sum('total');
                $refund_count=$this->di->customer_refund->where('uid',$v['id'])->and('status',1)->sum('refund_total');
                $all_count+=count($count);
                $order_count_all+=$order_count;
                $refund_total+=$refund_count;

            }
            $data['follow_total']=$all_count;
            //成交
            if($order_count_all==''){
                $data['order_total']=0;
            }else{
                $data['order_total']=$order_count_all;
            }

            //回款  换
            $data['project_total']=$this->di->customer_data->where('groupid',$user_structure_id)->and('project_name','<>',' ')->sum('paid');
            //付款 换
            $data['agent_total']=$this->di->customer_data->where('groupid',$user_structure_id)->and('agent_name','<>',' ')->sum('paid');
            //退款
            $data['refund_total']=$refund_total;

        }else if($user_info['is_leader']!='1' && $user_info['username']!='20' && $user_info['type']==0){
            //普通员工
            //客户
            $total_arr = $this->redis->get_time('customer_count_arr','customer');
            if(empty($total_arr)){
                $data['total']=$total_arr['total'];
            }else{
                $customer=new Customer();
                $customer->GetCustomerList(['uid'=>$uid]);
                $total_arr = $this->redis->get_time('customer_count_arr','customer');
                $data['customer_total']=$total_arr['total'];
            }
            //跟进
            $follow_total_arr = $this->redis->get_time('customer_follw_count','customer');
            if(empty($follow_total_arr) && $follow_total_arr!=null){
                $data['follow_total']=$total_arr['all_total'];
            }else{
                $customer=new Structure();
                $customer->GetDoFpList(['uid'=>$uid]);
                $follow_total_arr = $this->redis->get_time('customer_follw_count','customer');
                $data['follow_total']=$follow_total_arr['all_total'];
            }
            $data['order_total']=$this->di->customer_order->where('deal_id',$uid)->and('status','<>',0)->sum('total');
            //回款
            $project_total_arr = $this->redis->get_time('project_stu_num','project');
            if(empty($project_total_arr) && $project_total_arr!=null){
                $data['project_total']= $project_total_arr['yfk_total'];
            }else{
                $customer=new Proedution();
                $customer->PostStudentList(['uid'=>$uid]);
                $agent_total_arr = $this->redis->get_time('project_stu_num','project');
                $data['project_total']=$agent_total_arr['yfk_total'];
            }
            //付款
            $agent_total_arr = $this->redis->get_time('agent_stu_num','agent');
            if(empty($agent_total_arr) && $agent_total_arr!=null){
                $data['agent_total']= $agent_total_arr['yfk_total'];
            }else{
                $customer=new Xiaozhe();
                $customer->GetStudentList(['uid'=>$uid]);
                $agent_total_arr = $this->redis->get_time('agent_stu_num','agent');
                $data['agent_total']=$agent_total_arr['yfk_total'];
            }
            //退款
            $data['refund_total']=$this->di->customer_refund->where('uid',$uid)->and('status',1)->sum('refund_total');
            //数量
            switch ($date_sl){
                case 1:
                    switch($type){
                        case 1:
                            //客户
                            foreach($date_array as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    $student_where=" FIND_IN_SET(".$uid.",c.charge_person)  ";
                                    $student_where.=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(cd.create_time,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$student_where;
                                    $customer_list = $this->di->customer_data->queryAll($sql, $params);
                                    $data['customer'][$v]=$customer_list;
                                }
                            }
                            break;
                        case 2:
                            //代理商
                            foreach($date_array as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    $student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                    $student_where.=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(id) as num FROM ".$this->prefix."agent WHERE ".$student_where;
                                    $agent_list = $this->di->agent->queryAll($sql, $params);
                                    $data['agent'][$v]= $agent_list;
                                }
                            }
                            break;
                        case 3:
                            //项目方
                            foreach($date_array as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    $student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                    $student_where.=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(id) as num FROM ".$this->prefix."project_side WHERE ".$student_where;
                                    $project_list = $this->di->project_side->queryAll($sql, $params);
                                    $data['project'][$v]= $project_list;
                                }
                            }
                            break;
                        default:
                            break;
                    }

                    break;
                case 2:
                    //本月
                    switch($type){
                        case 1:
                            //客户
                            foreach($month as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    $student_where=" FIND_IN_SET(".$uid.",c.charge_person)  ";
                                    $student_where.=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(cd.create_time,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$student_where;
                                    $customer_list = $this->di->customer_data->queryAll($sql, $params);
                                    $data['customer'][$v]=$customer_list;
                                }
                            }
                            break;
                        case 2:
                            //代理商
                            foreach($month as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    $student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                    $student_where.=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(id) as num FROM ".$this->prefix."agent WHERE ".$student_where;
                                    $agent_list = $this->di->agent->queryAll($sql, $params);
                                    $data['agent'][$v]= $agent_list;
                                }
                            }
                            break;
                        case 3:
                            //项目方
                            foreach($month as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    $student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                    $student_where.=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(id) as num FROM ".$this->prefix."project_side WHERE ".$student_where;
                                    $project_list = $this->di->project_side->queryAll($sql, $params);
                                    $data['project'][$v]= $project_list;
                                }
                            }
                            break;
                        default:
                            break;
                    }

                    break;
                case 3:

                    switch($type){
                        case 1:
                            //客户
                            foreach($getyear as $k=>$v){
                                $student_where=" FIND_IN_SET(".$uid.",c.charge_person)  ";
                                $student_where.=" AND  ( cd.create_time BETWEEN  ".$v['firstday']." AND ".$v['lastday']." )";
                                $sql = "SELECT count(cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$student_where;
                                $customer_list = $this->di->customer_data->queryAll($sql, $params);
                                $data['customer'][date("Y-m",$v['firstday'])]=$customer_list;
                            }
                            break;
                        case 2:
                            //代理商
                            foreach($getyear as $k=>$v){
                                $student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                $student_where.= " AND ( addtime  BETWEEN  ".$v['firstday']." AND ".$v['lastday']." )";
                                $sql = "SELECT count(id) as num FROM ".$this->prefix."agent WHERE ".$student_where;
                                $agent_list = $this->di->agent->queryAll($sql, $params);
                                $data['agent'][date("Y-m",$v['firstday'])]= $agent_list;

                            }
                            break;
                        case 3:
                            //项目方
                            foreach($getyear as $k=>$v){
                                $student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                $student_where.= " AND ( addtime  BETWEEN  ".$v['firstday']." AND ".$v['lastday']." )";
                                $sql = "SELECT count(id) as num FROM ".$this->prefix."project_side WHERE ".$student_where;
                                $project_list = $this->di->project_side->queryAll($sql, $params);
                                $data['project'][date("Y-m",$v['firstday'])]= $project_list;
                            }
                            break;
                        default:
                            break;
                    }
                    break;
                default:
                    break;
            }

        }

        if($user_info['type']==1 && $user_info['type_id']!=''){
            $creatid=$this->di->project_side->where('id',$user_info['type_id'])->fetchOne('creatid');
            $project=new Proedution();
            $all_total=$project->GetProjectInfo($creatid,$user_info['type_id']);
            $data['project_num']=$this->di->project_side->where('status',1)->count();
//            var_dump($all_total['data']['project_info']);die;
            if(isset($all_total['data']['project_info'])){
                $data['project_obligations_price']=$all_total['data']['project_info']['total_obligations'];
                $data['project_paid_price']=$all_total['data']['project_info']['total_paid'];
                $data['project_total_agent']=$all_total['data']['project_info']['total_agent'];
            }
            $type=3;
            unset($data['order_total']);
            unset($data['customer_total']);
            unset($data['follow_total']);
            unset($data['project_total']);
            unset($data['agent_total']);
            unset($data['refund_total']);
            $user_info['group_id'] ='20';

        }else if($user_info['type']==2 && $user_info['type_id']!=''){
            $data['agent_num']=$this->di->agent->where('status',1)->count();
            $creatid=$this->di->agent->where('id',$user_info['type_id'])->fetchOne('creatid');
            $agent=new Xiaozhe();
            $agent=$agent->GetAgentInfo($creatid,$user_info['type_id']);
            if(isset($agent['data']['agent_info'])){
                $data['agent_obligations_price']=$agent['data']['project_info']['total_obligations'];
                $data['agent_paid_price']=$agent['data']['project_info']['total_paid'];
                $data['agent_total_agent']=$agent['data']['project_info']['total_price'];
            }
            unset($data['customer_total']);
            unset($data['order_total']);
            unset($data['follow_total']);
            unset($data['project_total']);
            unset($data['agent_total']);
            unset($data['refund_total']);
            $user_info['group_id'] ='20';
            $type=2;
        }
        //柱状图 类型1本周2本月3本年
        //数量
        if(($user_info['is_leader']=='1' && $user_info['group_id']!='20') || $user_info['group_id'] =='20'){
            switch ($date_sl){
                case 1:
                    switch($type){
                        case 1:
                            //客户
                            //超管
                            //部门主管
                            foreach($date_array as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    if($user_info['group_id']=='20'){
                                        $student_where='  cd.id <> 0 ';
                                    }else if($user_info['is_leader']=='1' && $user_info['group_id']!='20'){
                                        $student_where="  cd.groupid = ".$user_structure_id;//部门内的客户
                                    }
                                    $student_where.=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(cd.create_time,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$student_where;
                                    $customer_list = $this->di->customer_data->queryAll($sql, $params);
                                    $data['customer'][$v]=$customer_list;
                                }
                            }
                            break;
                        case 2:
                            //代理商
                            foreach($date_array as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    // $student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                    $student_where =" UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(id) as num FROM ".$this->prefix."agent WHERE ".$student_where;
                                    $agent_list = $this->di->agent->queryAll($sql, $params);
                                    $data['agent'][$v]= $agent_list;
                                }
                            }
                            break;
                        case 3:
                            //项目方
                            foreach($date_array as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    //$student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                    $student_where =" UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(id) as num FROM ".$this->prefix."project_side WHERE ".$student_where;
                                    $project_list = $this->di->project_side->queryAll($sql, $params);
                                    $data['project'][$v]= $project_list;
                                }
                            }
                            break;
                        default:
                            break;
                    }

                    break;
                case 2:
                    //本月
                    switch($type){
                        case 1:
                            //客户
                            //超管
                            //主管
                            foreach($month as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
//                                $student_where=" FIND_IN_SET(".$uid.",c.charge_person)  ";
                                    //$student_where="  cd.groupid = ".$user_structure_id;//部门内的客户
                                    if($user_info['group_id']=='20'){
                                        $student_where='  cd.id <> 0 ';
                                    }else if($user_info['is_leader']=='1' && $user_info['group_id']!='20'){
                                        $student_where="  cd.groupid = ".$user_structure_id;//部门内的客户
                                    }
                                    $student_where.=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(cd.create_time,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$student_where;
                                    $customer_list = $this->di->customer_data->queryAll($sql, $params);
                                    $data['customer'][$v]=$customer_list;

                                }
                            }
                            break;
                        case 2:
                            //代理商
                            foreach($month as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    // $student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                    $student_where=" UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(id) as num FROM ".$this->prefix."agent WHERE ".$student_where;
                                    $agent_list = $this->di->agent->queryAll($sql, $params);
                                    $data['agent'][$v]= $agent_list;
                                }
                            }
                            break;
                        case 3:
                            //项目方
                            foreach($month as $k=>$v){
                                if(strtotime($v) <= strtotime(date("Y-m-d"),time())){
                                    //$student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                    $student_where ="  UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = ".strtotime($v);
                                    $sql = "SELECT count(id) as num FROM ".$this->prefix."project_side WHERE ".$student_where;
                                    $project_list = $this->di->project_side->queryAll($sql, $params);
                                    $data['project'][$v]= $project_list;
                                }
                            }
                            break;
                        default:
                            break;
                    }
                    break;
                case 3:

                    switch($type){
                        case 1:
                            //客户
                            //超管

                            //主管
                            foreach($getyear as $k=>$v){
//                            $student_where=" FIND_IN_SET(".$uid.",c.charge_person)  ";
                                //$student_where="  cd.groupid = ".$user_structure_id;//部门内的客户
                                if($user_info['group_id']=='20'){
                                    $student_where='  cd.id <> 0 ';
                                }else if($user_info['is_leader']=='1' && $user_info['group_id']!='20'){
                                    $student_where="  cd.groupid = ".$user_structure_id;//部门内的客户
                                }
                                $student_where.=" AND  ( cd.create_time BETWEEN  ".$v['firstday']." AND ".$v['lastday']." )";
                                $sql = "SELECT count(cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$student_where;
                                $customer_list = $this->di->customer_data->queryAll($sql, $params);

                                $data['customer'][date("Y-m",$v['firstday'])]=$customer_list;
                            }
                            break;
                        case 2:
                            //代理商
                            foreach($getyear as $k=>$v){
//                            $student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                $student_where = " ( addtime  BETWEEN  ".$v['firstday']." AND ".$v['lastday']." )";
                                $sql = "SELECT count(id) as num FROM ".$this->prefix."agent WHERE ".$student_where;

                                $agent_list = $this->di->agent->queryAll($sql, $params);
                                $data['agent'][date("Y-m",$v['firstday'])]= $agent_list;

                            }
                            break;
                        case 3:
                            //项目方
                            foreach($getyear as $k=>$v){
//                            $student_where=" FIND_IN_SET(".$uid.",charge_person)  ";
                                $student_where = " ( addtime  BETWEEN  ".$v['firstday']." AND ".$v['lastday']." )";
                                $sql = "SELECT count(id) as num FROM ".$this->prefix."project_side WHERE ".$student_where;
                                $project_list = $this->di->project_side->queryAll($sql, $params);
                                $data['project'][date("Y-m",$v['firstday'])]= $project_list;
                            }
                            break;
                        default:
                            break;
                    }
                    break;
                default:
                    break;
            }
        }


        /*
         *
         * ********
         * **********
         * ************
         * 分割线
         */
        //  业绩全部 类型0今天1本周2本月3本年4全部

        switch ($date_yj){
            case 1:
                //今天天
                $student_where=" AND  UNIX_TIMESTAMP(FROM_UNIXTIME(cd.deal_time,'%Y-%m-%d')) =".strtotime(date("Y-m-d",time()));
                break;
            case 2:
                //本周
                $firsttime=strtotime(date('Y-m-d', strtotime("this week Monday", time())));
                $endtime=strtotime(date('Y-m-d', strtotime("this week Sunday", time()))) + 24 * 3600 - 1;
                $student_where=" AND   cd.deal_time BETWEEN ". $firsttime." AND ".$endtime;

                break;
            case 3:
                //本月
                $firsttime= mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endtime= mktime(23, 59, 59, date('m'), date('t'), date('Y'));
                $student_where=" AND   cd.deal_time BETWEEN ". $firsttime." AND ".$endtime;
                break;
            case 4:
                //本年

                $firsttime=  mktime(0, 0, 0, 1, 1, date('Y'));
                $endtime=mktime(23, 59, 59, 12, 31, date('Y'));
                $student_where=" AND   cd.deal_time BETWEEN ". $firsttime." AND ".$endtime;
                break;
            default:
                $student_where="";
                break;
        }

        $sql = "SELECT sum(cd.total) as num,cs.realname,cd.deal_id FROM ".$this->prefix."customer_data c LEFT JOIN ".$this->prefix."customer_order cd ON cd.cid=c.cid LEFT JOIN ".$this->prefix."user cs ON cs.id=cd.deal_id WHERE  cd.status = 1 ".$student_where." GROUP BY cd.deal_id ORDER BY sum(cd.total) desc";
        $data['yj_list']= $this->di->customer_data->queryAll($sql, $params);
//        $uid,$type,$date_sl,$date_yj
//        $this->redis->set_time('all_list_'.$uid.$type.$date_sl.$date_yj,$data,3600,'project');
        return $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);

    }
    //客户级别分析
    public function GetCusFollowCensus($uid,$type,$users,$date_len,$date_section)
    {
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $users = is_array($users) && !empty($users)?$users:['0'=>$uid];
        if(count($users)==1 && $users[0]==''){
            $users[0]=$uid;
        }
        $first_time=isset($date_section['first'])?$date_section['first']:0;
        $last_time=isset($date_section['last'])?$date_section['last']:0;
        $params = [];
        $creatid='';
        foreach ($users as $k){
            $creatid.=$k.',';
        }
        $users_data="FIND_IN_SET(s.creatid,'".$creatid."')";
        $date_array = \App\get_weeks();
        $first_week=reset($date_array);
        $last_week=end($date_array);
        $month = \App\getMonth();
        $first_month=reset($month);
        $last_month=end($month);
        $getyear = \App\Getyear();
        $first_year=reset(reset($getyear));
        $last_year=end(end($getyear));
        //判断用户身份
        $user_info = $this->di->user->select("is_leader,status,structure_id,username,group_id,type,type_id")->where("id", $uid)->and('status', 1)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code' => 0, 'msg' => '000004', 'data' => array(), 'info' => array());
            return $rs;
        }
//        $structure_id = json_decode($user_info['structure_id']);
//        $user_structure_id = array_pop($structure_id);
        $data = [];
        if ($user_info['is_leader'] != '0' || $user_info['group_id'] == '20') {
            //超管 可以查看所有人的统计
            //部门经理或主管,只能查看本部门的统计数据
            if($user_info['is_leader'] != '0' && count($users)>1){
                foreach ($users as $k){
                    $status=$this->di->user->where('id',$k)->and('structure_id',$user_info['structure_id'])->fetchOne();
                    if($status==''){
                        $list[]=['userid'=>$k];
                    }
                }
                if(count($list)>=1){
                    $rs = array('code' => 0, 'msg' => '您没有权限查看其他部门人员的统计数据', 'data' => $list, 'info' => array());
                    return $rs;
                }

            }

        }else{
            if(count($users)>1 || (reset($users)!=$uid && reset($users)!='')){
                $rs = array('code' => 0, 'msg' => '您没有权限查看其他人的统计数据', 'data' => array(), 'info' => array());
                return $rs;
            }
            //普通员工 只能查看自己的统计

        }
        //柱状图 类型1本周2本月3本年
        //数量
        switch ($type){
            case 1:
                //客户数量
                switch($date_len){
                    case 1:
                        //本周
                        $users_data='(';
                        $joins_data='(';
                        $lqr_joins_data='(';
                        foreach ($users as $k){
                            $users_data.=' c.creatid = '.$k.' OR ';
                            $joins_data.=' beshare_uid = '.$k.' OR ';
                            $lqr_joins_data.=' s.beshare_uid = '.$k.' OR ';
                        }
                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
//                            $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
                        $lqr_joins_data = substr($lqr_joins_data,0,strlen($lqr_joins_data)-4).')';
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(cd.create_time,'%Y-%m-%d')) BETWEEN  ".strtotime($first_week).' AND '.strtotime($last_week);
//                            $student_where2=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.create_time,'%Y-%m-%d')) BETWEEN  ".strtotime($first_week).' AND '.strtotime($last_week);
//                            $join_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) BETWEEN  ".strtotime($first_week).' AND '.strtotime($last_week);
                        $lqr_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(s.addtime,'%Y-%m-%d')) BETWEEN  ".strtotime($first_week).' AND '.strtotime($last_week);
//                            $sql = "SELECT count(*) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$users_data.$student_where.' UNION ALL '."SELECT count(*) as num FROM ".$this->prefix."share_join  WHERE ".$joins_data.' AND  from_type = 2 '.$join_where;
//                            $num = $this->di->customer->queryAll($sql, $params);
//                            $res = array_sum(array_column($num,'num'));
                        $sql="SELECT a.intentionally,COUNT(a.intentionally) as num FROM (SELECT c.intentionally FROM ".$this->prefix."customer c left JOIN ".$this->prefix."customer_data cd ON c.id=cd.cid WHERE c.sea_type = 0 AND ".$users_data.$student_where." UNION ALL SELECT sc.intentionally FROM ".$this->prefix."share_customer sc INNER JOIN ".$this->prefix."share_join s ON s.bid = sc.id WHERE ".$lqr_joins_data.$lqr_where." AND s.from_type = 2 ) a GROUP BY a.intentionally";
                        $data_list = $this->di->customer_data->queryAll($sql, $params);
                        break;
                    case 2:
                        //本月
                        $users_data='(';
                        $joins_data='(';
                        $lqr_joins_data='(';
                        foreach ($users as $k){
                            $users_data.=' c.creatid = '.$k.' OR ';
                            $joins_data.=' beshare_uid = '.$k.' OR ';
                            $lqr_joins_data.=' s.beshare_uid = '.$k.' OR ';
                        }
                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
//                            $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
                        $lqr_joins_data = substr($lqr_joins_data,0,strlen($lqr_joins_data)-4).')';
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(cd.create_time,'%Y-%m-%d')) BETWEEN  ".strtotime($first_month).' AND '.strtotime($last_month);
//                            $student_where2=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.create_time,'%Y-%m-%d')) BETWEEN  ".strtotime($first_month).' AND '.strtotime($last_month);
//                            $join_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) BETWEEN  ".strtotime($first_month).' AND '.strtotime($last_month);
                        $lqr_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(s.addtime,'%Y-%m-%d')) BETWEEN  ".strtotime($first_month).' AND '.strtotime($last_month);
//                            $sql = "SELECT count(*) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$users_data.$student_where.' UNION ALL '."SELECT count(*) as num FROM ".$this->prefix."share_join  WHERE ".$joins_data.' AND  from_type = 2 '.$join_where;
//                            $num = $this->di->customer->queryAll($sql, $params);
//                            $res = array_sum(array_column($num,'num'));
                        $sql="SELECT a.intentionally,COUNT(a.intentionally) as num FROM (SELECT c.intentionally FROM ".$this->prefix."customer c left JOIN ".$this->prefix."customer_data cd ON c.id=cd.cid WHERE c.sea_type = 0 AND ".$users_data.$student_where." UNION ALL SELECT sc.intentionally FROM ".$this->prefix."share_customer sc INNER JOIN ".$this->prefix."share_join s ON s.bid = sc.id WHERE ".$lqr_joins_data.$lqr_where." AND s.from_type = 2 ) a GROUP BY a.intentionally";
                        $data_list = $this->di->customer_data->queryAll($sql, $params);
                        break;
                    case 3:
                        //本年
                        $users_data='(';
                        $joins_data='(';
                        $lqr_joins_data='(';
                        foreach ($users as $k){
                            $users_data.=' c.creatid = '.$k.' OR ';
                            $joins_data.=' beshare_uid = '.$k.' OR ';
                            $lqr_joins_data.=' s.beshare_uid = '.$k.' OR ';
                        }
                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
//                            $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
                        $lqr_joins_data = substr($lqr_joins_data,0,strlen($lqr_joins_data)-4).')';
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(cd.create_time,'%Y-%m-%d')) BETWEEN  ".$first_year.' AND '.$last_year;
//                            $join_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) BETWEEN  ".$first_year.' AND '.$last_year;
                        $lqr_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(s.addtime,'%Y-%m-%d')) BETWEEN  ".$first_year.' AND '.$last_year;
//                            $sql = "SELECT count(*) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$users_data.$student_where.' UNION ALL '."SELECT count(*) as num FROM ".$this->prefix."share_join  WHERE ".$joins_data.' AND  from_type = 2 '.$join_where;
//                            $num = $this->di->customer->queryAll($sql, $params);
//                            $res = array_sum(array_column($num,'num'));
                        $sql="SELECT a.intentionally,COUNT(a.intentionally) as num FROM (SELECT c.intentionally FROM ".$this->prefix."customer c left JOIN ".$this->prefix."customer_data cd ON c.id=cd.cid WHERE c.sea_type = 0 AND ".$users_data.$student_where." UNION ALL SELECT sc.intentionally FROM ".$this->prefix."share_customer sc INNER JOIN ".$this->prefix."share_join s ON s.bid = sc.id WHERE ".$lqr_joins_data.$lqr_where." AND s.from_type = 2 ) a GROUP BY a.intentionally";
                        $data_list = $this->di->customer_data->queryAll($sql, $params);
                        break;
                    default:
                        $users_data='(';
                        $joins_data='(';
                        $lqr_joins_data='(';
                        foreach ($users as $k){
                            $users_data.=' c.creatid = '.$k.' OR ';
                            $joins_data.=' beshare_uid = '.$k.' OR ';
                            $lqr_joins_data.=' s.beshare_uid = '.$k.' OR ';
                        }
                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
//                            $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
                        $lqr_joins_data = substr($lqr_joins_data,0,strlen($lqr_joins_data)-4).')';
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(cd.create_time,'%Y-%m-%d')) BETWEEN  ".$first_time.' AND '.$last_time;
//                            $join_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(addtime,'%Y-%m-%d')) BETWEEN  ".$first_time.' AND '.$last_time;
                        $lqr_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(s.addtime,'%Y-%m-%d')) BETWEEN  ".$first_time.' AND '.$last_time;
//                            $sql = "SELECT count(*) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$users_data.$student_where.' UNION ALL '."SELECT count(*) as num FROM ".$this->prefix."share_join  WHERE ".$joins_data.' AND  from_type = 2 '.$join_where;
//                            $num = $this->di->customer->queryAll($sql, $params);
//                            $res = array_sum(array_column($num,'num'));
                        $sql="SELECT a.intentionally,COUNT(a.intentionally) as num FROM (SELECT c.intentionally FROM ".$this->prefix."customer c left JOIN ".$this->prefix."customer_data cd ON c.id=cd.cid WHERE c.sea_type = 0 AND ".$users_data.$student_where." UNION ALL SELECT sc.intentionally FROM ".$this->prefix."share_customer sc INNER JOIN ".$this->prefix."share_join s ON s.bid = sc.id WHERE ".$lqr_joins_data.$lqr_where." AND s.from_type = 2 ) a GROUP BY a.intentionally";
                        $data_list = $this->di->customer_data->queryAll($sql, $params);
                        break;
                }

                break;
            case 2:
                //跟进数量
                switch($date_len){
                    case 1:
                        //本周
                        $users_data='(';
                        $joins_data='(';
                        foreach ($users as $k){
                            $users_data.='FIND_IN_SET('.$k.', cd.charge_person) OR ';
                            $joins_data.=' c.uid = '.$k.' OR ';
                        }
                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
                        $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.now_time,'%Y-%m-%d')) BETWEEN  ".strtotime($first_week).' AND '.strtotime($last_week);
                        $sql = "SELECT intentionally,num  FROM (SELECT * FROM(SELECT cd.intentionally,count(c.id) AS num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer cd ON c.cid = cd.id WHERE ".$users_data." AND ".$joins_data.$student_where." GROUP BY cd.intentionally) t1 ) t";

                        $data_list = $this->di->follw->queryAll($sql, $params);
                        break;
                    case 2:
                        //本月
                        $users_data='(';
                        $joins_data='(';
                        foreach ($users as $k){
                            $users_data.='FIND_IN_SET('.$k.', cd.charge_person) OR ';
                            $joins_data.=' c.uid = '.$k.' OR ';
                        }
                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
                        $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.now_time,'%Y-%m-%d')) BETWEEN  ".strtotime($first_month).' AND '.strtotime($last_month);
                        $sql = "SELECT intentionally,num  FROM (SELECT * FROM(SELECT cd.intentionally,count(c.id) AS num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer cd ON c.cid = cd.id WHERE ".$users_data." AND ".$joins_data.$student_where." GROUP BY cd.intentionally) t1 ) t";
                        $data_list = $this->di->follw->queryAll($sql, $params);
                        break;
                    case 3:
                        //本年
                        $users_data='(';
                        $joins_data='(';
                        foreach ($users as $k){
                            $users_data.='FIND_IN_SET('.$k.', cd.charge_person) OR ';
                            $joins_data.=' c.uid = '.$k.' OR ';
                        }
                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
                        $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.now_time,'%Y-%m-%d')) BETWEEN  ".$first_year.' AND '.$last_year;
                        $sql = "SELECT intentionally,num  FROM (SELECT * FROM(SELECT cd.intentionally,count(c.id) AS num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer cd ON c.cid = cd.id WHERE ".$users_data." AND ".$joins_data.$student_where." GROUP BY cd.intentionally) t1 ) t";
                        
                        $data_list = $this->di->follw->queryAll($sql, $params);
                        break;
                    default:
                        $users_data='(';
                        $joins_data='(';
                        foreach ($users as $k){
                            $users_data.='FIND_IN_SET('.$k.', cd.charge_person) OR ';
                            $joins_data.=' c.uid = '.$k.' OR ';
                        }
                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
                        $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.now_time,'%Y-%m-%d')) BETWEEN  ".$first_time.' AND '.$last_time;
                        $sql = "SELECT intentionally,num  FROM (SELECT * FROM(SELECT cd.intentionally,count(c.id) AS num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer cd ON c.cid = cd.id WHERE ".$users_data." AND ".$joins_data.$student_where." GROUP BY cd.intentionally) t1 ) t";

                        $data_list = $this->di->follw->queryAll($sql, $params);
                        break;
                }
                break;
//            case 3:
//                //跟进数量
//                switch($date_len){
//                    case 1:
//                        //本周
//                        $users_data='(';
//                        $joins_data='(';
//                        foreach ($users as $k){
//                            $users_data.='FIND_IN_SET('.$k.', cd.charge_person) OR ';
//                            $joins_data.=' c.uid = '.$k.' OR ';
//                        }
//                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
//                        $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
//                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.now_time,'%Y-%m-%d')) BETWEEN  ".strtotime($first_week).' AND '.strtotime($last_week);
////                            $sql = "SELECT intentionally,num FROM (SELECT intentionally,count(cid) as num FROM(SELECT cd.intentionally,c.cid FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer cd ON c.cid = cd.id WHERE ".$users_data." AND ".$joins_data.$student_where.") v GROUP BY intentionally ";
//                        $sql="SELECT intentionally,COUNT(cid) as num FROM  ( SELECT cd.intentionally,c.cid FROM crm_follw c LEFT JOIN crm_customer cd ON c.cid = cd.id  WHERE".$users_data." AND ".$joins_data.$student_where." GROUP BY c.cid ) v GROUP BY intentionally";
//                        $data_list = $this->di->follw->queryAll($sql, $params);
//                        break;
//                    case 2:
//                        //本月
//                        $users_data='(';
//                        $joins_data='(';
//                        foreach ($users as $k){
//                            $users_data.='FIND_IN_SET('.$k.', cd.charge_person) OR ';
//                            $joins_data.=' c.uid = '.$k.' OR ';
//                        }
//                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
//                        $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
//                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.now_time,'%Y-%m-%d')) BETWEEN  ".strtotime($first_month).' AND '.strtotime($last_month);
//                        $sql="SELECT intentionally,COUNT(cid) as num FROM  ( SELECT cd.intentionally,c.cid FROM crm_follw c LEFT JOIN crm_customer cd ON c.cid = cd.id  WHERE".$users_data." AND ".$joins_data.$student_where." GROUP BY c.cid ) v GROUP BY intentionally";
//                        $data_list = $this->di->follw->queryAll($sql, $params);
//                        break;
//                    case 3:
//                        //本年
//                        $users_data='(';
//                        $joins_data='(';
//                        foreach ($users as $k){
//                            $users_data.='FIND_IN_SET('.$k.', cd.charge_person) OR ';
//                            $joins_data.=' c.uid = '.$k.' OR ';
//                        }
//                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
//                        $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
//                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.now_time,'%Y-%m-%d')) BETWEEN  ".$first_year.' AND '.$last_year;
//                        $sql="SELECT intentionally,COUNT(cid) as num FROM  ( SELECT cd.intentionally,c.cid FROM crm_follw c LEFT JOIN crm_customer cd ON c.cid = cd.id  WHERE".$users_data." AND ".$joins_data.$student_where." GROUP BY c.cid ) v GROUP BY intentionally";
//                        $data_list = $this->di->follw->queryAll($sql, $params);
//                        break;
//                    default:
//                        $users_data='(';
//                        $joins_data='(';
//                        foreach ($users as $k){
//                            $users_data.='FIND_IN_SET('.$k.', cd.charge_person) OR ';
//                            $joins_data.=' c.uid = '.$k.' OR ';
//                        }
//                        $users_data = substr($users_data,0,strlen($users_data)-4).')';
//                        $joins_data = substr($joins_data,0,strlen($joins_data)-4).')';
//                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.now_time,'%Y-%m-%d')) BETWEEN  ".$first_time.' AND '.$last_time;
//                        $sql="SELECT intentionally,COUNT(cid) as num FROM  ( SELECT cd.intentionally,c.cid FROM crm_follw c LEFT JOIN crm_customer cd ON c.cid = cd.id  WHERE".$users_data." AND ".$joins_data.$student_where." GROUP BY c.cid ) v GROUP BY intentionally";
//                        $data_list = $this->di->follw->queryAll($sql, $params);
//                        break;
//                }
//                break;
            case 3:
                //录入数量
                switch($date_len){
                    case 1:
                        //本周
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.create_time,'%Y-%m-%d')) BETWEEN  ".strtotime($first_week).' AND '.strtotime($last_week);
                        $sql="select s.intentionally,count(s.id) as num from crm_customer s LEFT JOIN crm_customer_data c ON s.id=c.cid WHERE ".$users_data.$student_where." GROUP BY s.intentionally";
                        $data_list = $this->di->customer->queryAll($sql, $params);
                        break;
                    case 2:
                        //本月
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.create_time,'%Y-%m-%d')) BETWEEN  ".strtotime($first_month).' AND '.strtotime($last_month);
                        $sql="select s.intentionally,count(s.id) as num from crm_customer s LEFT JOIN crm_customer_data c ON s.id=c.cid WHERE ".$users_data.$student_where." GROUP BY s.intentionally";
                        $data_list = $this->di->customer->queryAll($sql, $params);
                        break;
                    case 3:
                        //本年
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.create_time,'%Y-%m-%d')) BETWEEN  ".$first_year.' AND '.$last_year;
                        $sql="select s.intentionally,count(s.id) as num from crm_customer s LEFT JOIN crm_customer_data c ON s.id=c.cid WHERE ".$users_data.$student_where." GROUP BY s.intentionally";
                        $data_list = $this->di->customer->queryAll($sql, $params);
                        break;
                    default:
                        $student_where=" AND UNIX_TIMESTAMP(FROM_UNIXTIME(c.create_time,'%Y-%m-%d')) BETWEEN  ".$first_time.' AND '.$last_time;
                        $sql="select s.intentionally,count(s.id) as num from crm_customer s LEFT JOIN crm_customer_data c ON s.id=c.cid WHERE ".$users_data.$student_where." GROUP BY s.intentionally";
                        $data_list = $this->di->customer->queryAll($sql, $params);
                        break;
                }
                break;
            default:
                break;
        }
        return $rs = array('code'=>1,'msg'=>'000000','data'=>$data_list,'info'=>$data_list);

    }


}
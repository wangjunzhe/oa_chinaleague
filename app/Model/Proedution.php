<?php

namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Common\Admin as AdminCommon;
use App\Common\Customer as CustomerCommon;
use App\Common\Yesapiphpsdk;
class Proedution extends NotORM
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
        $this->tracer= new \App\Common\Tracer();
		// 加密向量
		$this->iv = \PhalApi\DI()->config->get('common.IV');

		// 页码设置
        $this->pagesize = \PhalApi\DI()->config->get('common.PAGESIZE');

	}
    protected function getTableName($id) {
        return 'project_side';  // 手动设置表名为 my_user
    }
    // 01 一手项目方-新增
    public function PostProjectAdd($uid,$newData,$field_list) {
        $project_name = $newData['title'] ? $newData['title'] : "";
        $full_name = $newData['full_name'] ? $newData['full_name'] : "";
        $mobile = $newData['mobile'] ? $newData['mobile'] : "";
        $customer_common = new CustomerCommon();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($project_name) || empty($full_name) || empty($mobile)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("username,is_leader,status,group_id")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断代理商是否存在
        $project_info = $this->di->project_side->where("identifier",$newData['identifier'])->fetchOne();
        if (!empty($project_info)) {
            $data['title']=$project_name;
            $data['company']=$project_info['company'];
            $data['lxr']=$project_info['full_name'];
            $data['phone']=substr_replace($project_info['mobile'], '****', 5, 4);
            $data['creatname']=\App\GetFiledInfo('user','realname',$project_info['creatid']);
            $data['creat_time']=$project_info['addtime'];
            $rs = array('code'=>0,'msg'=>'999978','data'=>$data,'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $number = \App\CreateOrderNo();
        $newData['number'] = 'YSF'.$number;
        $newData['publisher'] = $user_info['username'];
        $newData['creatid'] = $uid;
        $newData['charge_person'] = $uid.',';
        $newData['sort'] = 0;
        $newData['status'] = 1;
        $newData['addtime'] = time();
        $newData['updatetime'] = time();
        $newData['charge_person']=$uid.',';
        $newData['updateman']=$user_info['username'];
        $newData['follow_time']=time();

        $project_data = $admin_common->GetFieldListArr($newData,$field_list);

        $project_id = $this->di->project_side->insert($project_data);
        if ($project_id) {
            $customer_common->PublicShareLog($project_id,$uid,$uid,3,'add','新增项目方');
            $this->redis->flushDB('project');
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;

    }
    public function  PostProjectEdit($uid,$id,$newData,$field_list){
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $project_info = $this->di->project_side->select("creatid,publisher")->where(array("id"=>$id,"status"=>1))->fetchOne();
        if (empty($project_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        // 只允许创建人修改
        if ($project_info['creatid'] != $uid) {
            $rs = array('code'=>0,'msg'=>'000061','data'=>array(),'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $newData['updatetime'] = time();
        $newData['updateman']=\App\GetFiledInfo('user','realname',$uid);
        $project_data = $admin_common->GetFieldListArr($newData,$field_list);
        // 写入缓存
        $this->redis->set_time('project_info_'.$id,$project_data,6000,'project');
        $update_info = $this->di->project_side->where(array("id"=>$id))->update($project_data);
        if ($update_info) {
            $customer_common = new CustomerCommon();
            $customer_common->PublicShareLog($id,$uid,$uid,3,'change','编辑项目方');
            $this->redis->flushDB();
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    
    public function PostProjectList($newData){
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type'])? $newData['type'] : 2;
        $follow_type = isset($newData['follow_type']) ? $newData['follow_type'] : 0;
        $keywords = isset($newData['keywords']) && !empty($newData['keywords']) ? $newData['keywords'] : "";
        $advanced_search = isset($newData['advanced_search']) && !empty($newData['advanced_search']) ? $newData['advanced_search'] : "";
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("is_leader,status,group_id,structure_id,type,type_id")->where("id",$uid)->fetchOne();
       $payment_info = $this->redis->get_time('project_list_'.$uid.'_'.$pageno.'_'.$type.'_'.$keywords,'project');
        $payment_info = 0;
        if (!empty($payment_info) && empty($keywords) ){
            $rs = array('code'=>1,'msg'=>'000000','data'=>$payment_info,'info'=>$payment_info);
            return $rs;
        }
//      $advanced_search=['id'=>1,'title'=>'2'];
        if($user_info['group_id']!=20){
            $structure_id = json_decode($user_info['structure_id']);
            $user_info['structure_id'] = array_pop($structure_id);
            $project_where = " status = 1 ";
            $project_where .= " AND  FIND_IN_SET('{$uid}', charge_person) ";
            $project_where_arr=" a.status = 1  AND  FIND_IN_SET('{$uid}', a.charge_person) ";

        }else{
            $project_where=' status = 1 ';
            $project_where_arr='a.status = 1';
        }
//                var_dump($project_where.$search_list);die;
//        $company_num = $this->di->project_side->where($project_where." AND type = 0")->count("id");// 公司个数
        $company_num = $this->di->project_side->where($project_where)->and('type = 0')->count("id");// 公司个数
        $person_num = $this->di->project_side->where($project_where)->and('type = 1')->count("id");// 个人个数

        $data['company_num'] = $company_num ? $company_num : 0;
        $data['person_num'] = $person_num ? $person_num : 0;
        $data['project_num'] =$person_num+$company_num;
        if (!empty($keywords) && $advanced_search=='') {
            $keywords=trim($keywords);
            $project_where_arr .= " AND concat(a.title,a.mobile,a.flower_name) like '%{$keywords}%'";
        }else if($advanced_search!='' && $keywords==''){

                $senior_where = "";
                $having_where = "";
                $num_arr = array("chengjiao","student_num","agent_all_price","all_paid","all_obligations");
                foreach ($advanced_search as $key => $value) {
                    if (!empty($value) || $value == 0) {
                        if (!in_array($key,$num_arr)) {
//                            if($key=='may_pay_date')$key = 'a.'.$key;
                            $key = $key == 'may_pay_date' ? 'cw.'.$key : 'a.'.$key;
                            # 其他
                            if (is_array($value) && is_numeric($value[0])) {

                                $project_where_arr .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                            } elseif (is_array($value)) {
                                $project_where_arr .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[0]}' ) ";
                            } elseif (is_numeric($value)) {
                                $project_where_arr .= " AND {$key} = {$value} ";
                            } else {
                                $project_where_arr .= " AND {$key} LIKE '%{$value}%' ";
                            }
                        } else {
                            if (is_array($value)) {
                                    $having_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                            } else {
                                $having_where .= " AND ( {$key} = {$value} ) ";
                            }

                        }
                    }
                }
                if (!empty($having_where)) {
                    $having_where = substr($having_where,4);
                    $having_where = " HAVING ".$having_where;
                } else {
                    $having_where = "";
                }

        }
//        var_dump($having_where);die;


        if ($type == 0 || $type == 1) {
//            $project_where .= " AND type = ".$type;
            $project_where_arr.=" AND a.type = ".$type;
        }
//        var_dump();die;

        if ($follow_type != 0) {
            switch ($follow_type) {
                case 1:
                    # 今日应联系
                    $project_where_arr .= " AND to_days(FROM_UNIXTIME(a.next_follow,'%Y-%m-%d')) = to_days(now()) ";
                    break;
                case 2:
                    # 今日新增
                    $project_where_arr .= " AND to_days(FROM_UNIXTIME(a.addtime,'%Y-%m-%d')) = to_days(now()) ";
                    break;
                case 3:
                    # 今日跟进过
                    $project_where_arr .= " AND to_days(FROM_UNIXTIME(a.follow_time,'%Y-%m-%d')) = to_days(now()) ";
                    break;
                case 4:
                    # 一周跟进过
                    $mark = "<=";
                    $mark_day = 7;
                    break;
                case 5:
                    # 一周未跟进
                    $mark = ">=";
                    $mark_day = 7;
                    break;
                case 6:
                    # 30天未跟进
                    $mark = ">=";
                    $mark_day = 30;
                    break;
                case 7:
                    # 60天未跟进
                    $mark = ">=";
                    $mark_day = 60;
                    break;
                case 8:
                    # 90天未跟进
                    $mark = ">=";
                    $mark_day = 90;
                    break;
                default:
                    # 全部
                    break;
            }
            if (!empty($mark) && !empty($mark_day)) {
                $end_date         = date('Y-m-d 23:59:59',time());
                $project_where_arr .= " AND DATEDIFF('{$end_date}', FROM_UNIXTIME( a.follow_time,  '%Y-%m-%d %H:%i:%s' ) ) {$mark} {$mark_day} ";
            }
        }
        $sql = "SELECT a.*,count(co.id) as chengjiao,count(cd.id) as student_num,SUM(cd.agent_price) as agent_all_price,SUM(cd.paid) as all_paid,SUM(cd.obligations) as all_obligations FROM ".$this->prefix."project_side a LEFT JOIN ".$this->prefix."customer_data cd ON FIND_IN_SET(a.id,cd.project_name) LEFT JOIN ".$this->prefix."customer_order co ON a.id = co.project_id LEFT JOIN ".$this->prefix."project_payment cw ON a.id=cw.id  WHERE ".$project_where_arr.$senior_where." GROUP BY a.id ".$having_where." ORDER BY a.addtime DESC LIMIT ".$pagenum.",".$pagesize;
// var_dump($sql);die;
        //        $project_list = $this->di->project_side->select("*")->where($project_where)->order("sort DESC,addtime DESC")->limit($pagenum,$pagesize)->fetchRows();
        $count_sql="SELECT a.*,count(co.id) as chengjiao,count(cd.id) as student_num,SUM(cd.agent_price) as agent_all_price,SUM(cd.paid) as all_paid,SUM(cd.obligations) as all_obligations FROM ".$this->prefix."project_side a LEFT JOIN ".$this->prefix."customer_data cd ON FIND_IN_SET(a.id,cd.project_name) LEFT JOIN ".$this->prefix."customer_order co ON a.id = co.project_id LEFT JOIN ".$this->prefix."project_payment cw ON a.id=cw.id WHERE ".$project_where_arr.$senior_where." GROUP BY a.id ".$having_where;
        $project_list = $this->di->project_side->queryAll($sql, array());
        $total_num_arr = $this->di->project_side->queryAll($count_sql,array());
        $project_list_count=count($total_num_arr);
//        $project_list_count = $this->di->project_side->select("*")->where($project_where)->order("sort DESC,addtime DESC")->count();
        foreach($project_list as $k=>$v){
              $charge_person=explode(',',$project_list[$k]['charge_person']);
              array_pop($charge_person);
              foreach ($charge_person as $n=>$m){
                  $project_list[$k]['fuzeren'].=\App\GetFiledInfo('user','realname',$m).' ';
                  $project_list[$k]['publisher']=\App\GetFiledInfo('user','realname',$m);
              }
            $structure_name=explode(',',$project_list[$k]['structure_id']);
//            array_pop($structure_name);
            foreach ($structure_name as $n=>$m){
                $project_list[$k]['structure_name'].=\App\GetFiledInfo('structure','name',$m).' ';

            }
            $list=$this->di->customer_order->where('project_id',$v['id'])->and('status',1)->group('cid')->count();
            $project_list[$k]['chengjiao']=$list;
            $all_student=$this->di->customer_data->where('FIND_IN_SET('.$v['id'].',project_name)')->count();
            $project_list[$k]['student_num']= $all_student;
//            总回款金额
            $agent_price=$this->di->customer_data->where('FIND_IN_SET('.$v['id'].',project_name)')->sum('agent_price');
            $project_list[$k]['agent_all_price']= $agent_price;
//            已回款金额
            $paid=$this->di->customer_data->where('FIND_IN_SET('.$v['id'].',project_name)')->sum('paid');
            $project_list[$k]['all_paid']= empty($paid)?0:$paid;
//            待回款金额
            $obligations=$this->di->customer_data->where('FIND_IN_SET('.$v['id'].',project_name)')->sum('obligations');
            $project_list[$k]['all_obligations']= empty($obligations)?0:$obligations;
//            预计回款时间
            $may_pay_date=$this->di->project_payment->select('may_pay_date')->where('project_id',$v['id'])->and('pay_state','0')->and('status',1)->order('may_pay_date asc')->fetchRows();
            $project_list[$k]['may_pay_date']= empty($may_pay_date)?0:$may_pay_date;
        }
        $student_where='';
        if (!empty($project_list)) {
            foreach ($project_list as $k=>$v){
                $student_where .= " FIND_IN_SET("."'".$v['id']."'".",project_name) OR ";
            }
            $student_where = substr($student_where,0,strlen( $student_where)-4);
            $student_where="(".$student_where.")";
            $list =array();
            // 总收款
            $params=array();
            $sql="select sum(agent_price) as num FROM ".$this->prefix."customer_data WHERE ".$student_where." AND agent_num = '' AND project_name <> '' ";
            $project_price_total = $this->di->customer_data->queryAll( $sql,$params);
            $data['money_total'] = $project_price_total[0]['num'];
            // 已收款
            $data['yh_money_total'] = $this->di->customer_data->where($student_where)->sum("paid");
            // 代回款
            $data['dh_money_total'] = $this->di->customer_data->where($student_where)->sum("obligations");
            $data['project_list'] = $project_list;
            $data['project_list_count']=$project_list_count;
            $this->redis->set_time('project_list_'.$uid.'_'.$pageno.'_'.$type.'_'.$keywords,$data,6000,'project');
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $data['dh_money_total'] =0;
            $data['money_total'] =0;
            $data['yh_money_total'] = 0;
            $data['project_list'] = $project_list;
            $rs = array('code'=>1,'msg'=>'000002','data'=>$data,'info'=>$data);
        }
        return $rs;
    }
    public function PostProjectDele($uid,$id){

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("is_leader,status,structure_id")->where("id",$uid)->fetchOne();
        $project_info = $this->di->project_side->select("structure_id,status,creatid")->where("id",$id)->fetchOne();
        if($user_info['is_leader']==1 && !empty($project_info)){
            //如果用户是主管  可以删除
            $this->di->project_side->where('id',$id)->update(['status'=>0]);
            \App\AddShareLog($id,$uid,$uid,3,'del','删除项目方');
            $rs=array('code'=>1,'msg'=>'999993','data'=>array(),'info'=>array());
            //记录操作日志
        }else{
                if($project_info['creatid']==$uid){
                    $this->di->project_side->where('id',$id)->update(['status'=>0]);
                    //记录操作日志
                    $this->redis->set_time('prodution_list','',3000,'agent');
                    $this->redis->set_time('prodution_list','',3000,'agent');
                    $rs=array('code'=>1,'msg'=>'999993','data'=>array(),'info'=>array());
                }else{
                    $this->di->project_side->where('id',$id)->update(['status'=>0]);
                    $rs=array('code'=>1,'msg'=>'999980','data'=>array(),'info'=>array());
                }
        }

        return $rs;

    }
    //分享项目方

    public function PostProjectShare($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $id = isset($data['id']) && !empty($data['id']) ? intval($data['id']) : 0;
        $share_uid = isset($data['share_uid']) && !empty($data['share_uid']) ? $data['share_uid'] : array();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($share_uid) || empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $Project_admin =new CustomerCommon();
        $rs = $Project_admin->PublicShareInfo($uid,$id,$share_uid,"project_side");
        return $rs;
    }
    //移交一手项目方
    public function PostProjectMove($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) : 0;
        $mid = isset($data['mid']) && !empty($data['mid']) ? intval($data['mid']) : 0;
        $share_uid = isset($data['share_uid']) && !empty($data['share_uid']) ? $data['share_uid'] : array();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($mid) || empty($share_uid)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $username = $this->di->user->where(array("id"=>$uid,"status"=>1))->fetchOne("username");
        $new_user_info = $this->di->user->select("username")->where("id",$mid)->fetchOne();
        // Step 1: 开启事务
        $error='';
        $this->di->beginTransaction('db_master');
        $customer_common = new CustomerCommon();
        $admin_common = new AdminCommon();
        foreach ($share_uid as $key => $value) {
            // 代理商信息
            $project_info = $this->di->project_side->select("title,flower_name,number,creatid,charge_person")->where(array("id"=>$value,"status"=>1))->fetchOne();

            if (empty($project_info)) {
                continue;
            }
            if ($project_info['creatid'] == $uid) {
                // 更改创建人
                $charge_person_new = str_replace("{$uid},","{$mid},",$project_info['charge_person']);
                $update_data['creatid'] = $mid;
                $update_data['publisher'] = $new_user_info['username'];
                $update_data['charge_person'] = $charge_person_new;

                // 合同移交
                $this->di->project_contract->where(array("project_id"=>$value))->update($update_data);
                // 项目移交
                $this->di->project_general->where(array("project_id"=>$value))->update(array("creatid"=>$mid,"publisher"=>$new_user_info['username']));
                $this->di->project_general_adjust->where(array("project_id"=>$value))->update(array("creatid"=>$mid,"publisher"=>$new_user_info['username']));
                // 开票
                $this->di->project_invoice->where(array("project_id"=>$value))->update(array("creatid"=>$mid,"publisher"=>$new_user_info['username']));
                // 付款
                $this->di->project_payment->where(array("project_id"=>$value))->update(array("creatid"=>$mid,"publisher"=>$new_user_info['username']));
                // 退款
                $this->di->project_refund->where(array("project_id"=>$value))->update(array("creatid"=>$mid,"publisher"=>$new_user_info['username']));
                // 增加移交日志
                $success='';
                $action = 'change';
                $note = "移交项目方";
                $success .= $project_info['title'].',';
                // 增加移交消息推送
                $msg_title = $username."将项目方".$project_info['flower_name']."移交给您！";
                $msg_content = $username."将项目方".$project_info['flower_name']."移交给您,请及时处理！";
                $admin_common->SendMsgNow($uid,$mid,$msg_title,$msg_content,0,"change","project",$value);
            } else {
                // 共享给我的，取消自己的共享
                if (strpos($project_info['charge_person'], $uid)) {
                    $charge_person_new = str_replace("{$uid},","",$project_info['charge_person']);
                    // 增加取消共享日志
                    $cancel_success='';
                    $action = 'cancle';
                    $note = "取消共享";
                    $cancel_success .= $project_info['title'].',';
                } else {

                    $error .= $project_info['title'].',';
                    continue;
                }
            }

            $update_data['charge_person'] = $charge_person_new;
            $res = $this->di->project_side->where("id",$value)->update($update_data);

            if ($res) {
                // 提交事务
                $this->di->commit('db_master');
                // 增加移交
                $customer_common->PublicShareLog($value,$uid,$mid,3,$action,$note);

            } else {
                // 回滚事务
                $this->di->rollback('db_master');
            }
        }

        $msg = "";
        if ($success) {
            $msg .= "项目方：".trim($success,",")."成功移交！";
        }
        if ($cancel_success) {
            $msg .= "项目方：".trim($cancel_success,",")."成功取消共享！";
        }
        if ($error) {
            $msg .= "项目方：".trim($error,",")."移交失败！";
        }

        if (empty($msg)) {
            $rs = array('code' => 0, 'msg' => '000054', 'data' => array(), 'info' => array());
        } else {

            $rs = array('code' => 1, 'msg' => $msg, 'data' => array(), 'info' => array());
        }
        return $rs;
    }
    public function GetProjectInfo($uid,$id){
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $agent_info = $this->redis->get_time('project_side_'.$id,'project');
        if (empty($agent_info)) {
            $agent_info = $this->di->project_side->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
            if(strpos($agent_info['structure_id'],',') !== false){
               $structure=explode(',',$agent_info['structure_id']);
               $agent_info['structure_name']= \App\GetFiledInfo('structure','name',$structure[0]).' '.\App\GetFiledInfo('structure','name',$structure[1]);
            }else{
                $agent_info['structure_name']= \App\GetFiledInfo('structure','name',$agent_info['structure_id']);
            }

            $order=$this->di->customer_order->where('project_id',$id)->and('status',1)->fetchAll();
            $total_obligations=0;
            $total_paid=0;
            $total_agent=0;
            if($order){

                foreach ($order as $k=>$v){
                    $num_info = $this->di->customer_data->select("SUM(agent_price) as total_agent,SUM(paid) as total_paid,SUM(obligations) as total_obligations")->where('cid',$v['cid'])->fetchOne();
                    $total_obligations += $num_info['total_obligations'];
                    $total_paid += $num_info['total_paid'];
                    $total_agent += $num_info['total_agent'];
                }
            }
            $agent_info['total_obligations'] =$total_obligations;
            //待付款
            $agent_info['total_paid']=$total_paid;
            $agent_info['total_agent']=$total_agent;
            //已收款
        }
//        var_dump($agent_info);die;
        $prev_agent_id = $this->di->educational->where("status <> 0 AND FIND_IN_SET('{$uid}', creatid) AND `id`<'$id' ")->order("id DESC")->fetchOne("id");
        $next_agent_id = $this->di->educational->where("status <> 0 AND FIND_IN_SET('{$uid}', creatid) AND `id`>'$id' ")->order("id ASC")->fetchOne("id");
        $data['next_project_id'] = $next_agent_id;
        $data['prev_project_id'] = $prev_agent_id;

        if (!empty($agent_info)) {
            $data['project_info'] = $agent_info;
            // 日志
            $log_list = $this->di->share_log->select("share_uid,note,addtime")->where(array("type"=>3,"info_id"=>$id,"share_uid"=>$uid))->fetchRows();
            foreach ($log_list as $c=>$s){
                $log_list[$c]['note']=\App\GetFiledInfo('user','realname',$s['share_uid']).' '.$s['note'].':'.$agent_info['title'];
            }
            $data['project_log'] = $log_list;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
//        if (empty($uid)) {
//            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
//            return $rs;
//        }
//
//        if (empty($id)) {
//            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
//            return $rs;
//        }
//
//        $project_info = $this->redis->get_time('project_info_'.$id,'project');
//
//        if (empty($project_info)) {
//            $project_info = $this->di->project_side->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
//            $structure_name=explode(',', $project_info['structure_id']);
//            foreach ($structure_name as $n=>$m){
//                $project_info['structure_name'].=\App\GetFiledInfo('structure','name',$m).',';
//            }
//            $this->redis->set_time('project_info_'.$id,$project_info,6000,'project');
//        }
//
//        if (!empty($project_info)) {
//            $data['project_info'] = $project_info;
//            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
//        } else {
//            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
//        }
//        return $rs;
    }

   //合同新增
    public function PostContractAdd($uid,$newData,$field_list,$general_list) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断用户角色
        $user_info = $this->di->user->select("username")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }
        $first_general=reset($general_list);
        $admin_common = new AdminCommon();
        $number = \App\CreateOrderNo();
        $newData['number'] = 'YSFHT'.$number;
        $newData['publisher'] = $user_info['username'];
        $newData['creatid'] = $uid;
        $newData['charge_person'] = $uid.',';
        $newData['sort'] = 0;
        $newData['status'] = 1;
        $newData['updatetime'] = time();

        $project_contract_data = $admin_common->GetFieldListArr($newData,$field_list);
        // Step 1: 开启事务
        $this->di->beginTransaction('db_master');
        $contract_id = $this->di->project_contract->insert($project_contract_data);
        $contract_id = $this->di->project_contract->insert_id();
        if ($contract_id) {

            if (!empty($first_general)) {
                $general_arr = array();
                foreach ($general_list as $key => $value) {
                          $value['contract_id'] = $contract_id;
                          $value['project_id'] = $newData['project_id'];
                          $value['number'] = 'YSFHT'.$number;
                          $value['status'] = 1;
                          $value['creatid'] = $uid;
                          $value['publisher'] = $user_info['username'];
                          $value['updatetime'] = time();

                          $contract_general_id = $this->di->project_general->insert($value);
                          if ($contract_general_id) {
                              $general_arr[] = $contract_general_id;
                          }
                }
            }
            if ($contract_id ) {
                // 提交事务
                if(!empty($first_general)){
                    if((count($general_list) == count($general_arr)) ){
                        \App\AddShareLog($newData['project_id'],$uid,$uid,3,'add','新增合同');
                        $this->di->commit('db_master');
                        $rs = array('code'=>1,'msg'=>'999995','data'=>array(),'info'=>array());
                    }
                }else{
                    \App\AddShareLog($newData['project_id'],$uid,$uid,3,'add','新增合同');
                    $this->di->commit('db_master');
                    $rs = array('code'=>1,'msg'=>'999995','data'=>array(),'info'=>array());
                }
            } else {
                // 回滚事务
                $this->di->rollback('db_master');
                $rs = array('code'=>0,'msg'=>'000037','data'=>array(),'info'=>array());
            }

        } else {
            $rs = array('code'=>0,'msg'=>'000037','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //学员列表
    //type=0
    public function PostStudentList($newData){
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? $newData['type'] : 0;
        $start_time = isset($newData['start_time']) && !empty($newData['start_time']) ? $newData['start_time'] : 0;
        $end_time = isset($newData['end_time']) && !empty($newData['end_time']) ? $newData['end_time'] : 0;
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $keywords = isset($newData['keywords']) && !empty($newData['keywords']) ? $newData['keywords'] : "";
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        $user_info = $this->di->user->select("is_leader,status,type,type_id,group_id")->where("id",$uid)->fetchOne();

            $show_phone = \APP\GetPhoneType($user_info['group_id']);

        if ($id == 0 & $uid!=0) {
            # 学员管理
            $params = array();
//            $data=$this->redis->get_time('project_student_list_'.$uid,'project');
//            if(!empty($data) && $keywords==''){
//                $rs = array('code' => 1, 'msg' => '000000', 'data' => $data, 'info' => $data);
//                return $rs;
//            }
                if ($user_info['type'] == 1 && !empty($user_info['type_id'])) {
                    # 代理商账号
                    $project_list =$this->di->project_side->where('id',$user_info['type_id'])->and('status',1)->fetchRows();
                }else if($user_info['group_id']==20){
                    # 系统用户
                    $project_list=$this->di->project_side->where('status',1)->fetchRows();

                }else{
                    $project_list =$this->di->project_side->where('FIND_IN_SET('.$uid.',charge_person)')->and('status',1)->fetchRows();
                }

               if(count($project_list)==0){

                   $rs = array('code' => 0, 'msg' => '000002', 'data' => [], 'info' => []);
                   return $rs;
               }
                $student_where='';
                foreach ($project_list as $k=>$v){
                    $student_where .= " FIND_IN_SET("."'".$v['id']."'".",cd.project_name) OR ";
                }
                $student_where = substr($student_where,0,strlen( $student_where)-4);
                $student_where="(".$student_where.")";
                // 总学员
                $total_count_sql = "SELECT count(cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$student_where;

                $agent_stu_total = $this->di->customer_data->queryAll($total_count_sql,$params);
                $total_arr['total'] = $agent_stu_total[0]['num'];

                // 总成交学员
                $cj_count_sql = "SELECT count(cd.cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid WHERE ".$student_where." AND co.order_no IS NOT NULL ";
                $agent_cj_total = $this->di->customer_data->queryAll($cj_count_sql,$params);
                $total_arr['cj_total'] = $agent_cj_total[0]['num'];
                // 未成交学员
                $wcj_count_sql = "SELECT count(cd.cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid WHERE ".$student_where." AND co.order_no IS NULL ";
                $agent_wcj_total = $this->di->customer_data->queryAll($wcj_count_sql,$params);
                $total_arr['wcj_total'] = $agent_wcj_total[0]['num'];

                // 今日成交学员
                $jrcj_count_sql = "SELECT count(cd.cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid WHERE ".$student_where." AND to_days(FROM_UNIXTIME(co.deal_time,'%Y-%m-%d')) = to_days(now()) ";
                $agent_jrcj_total = $this->di->customer_data->queryAll($jrcj_count_sql,$params);
                $total_arr['jrcj_total'] = $agent_jrcj_total[0]['num'];

                // 本周成交
                $start_date         =  date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
                $end_date           =  date('Y-m-d', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600));
                $bzcj_count_sql = "SELECT count(cd.cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid WHERE ".$student_where." AND FROM_UNIXTIME( co.deal_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";
                $agent_bzcj_total = $this->di->customer_data->queryAll($bzcj_count_sql,$params);
                $total_arr['bzcj_total'] = $agent_bzcj_total[0]['num'];

                // 本月成交
                $days = array(mktime(0, 0, 0, date('m'), 1, date('Y')),mktime(23, 59, 59, date('m'), date('t'), date('Y')));
                $start_date         =  date('Y-m-d H:i:s',$days[0]);
                $end_date           =  date('Y-m-d H:i:s',$days[1]);
                $bycj_count_sql = "SELECT count(cd.cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid WHERE ".$student_where." AND FROM_UNIXTIME( co.deal_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";
                $agent_bycj_total = $this->di->customer_data->queryAll($bycj_count_sql,$params);
                $total_arr['bycj_total'] = $agent_bycj_total[0]['num'];

                // 总付款金额
                $zfk_count_sql = "SELECT SUM(agent_price) as num FROM ".$this->prefix."customer_data cd WHERE ".$student_where;
                $agent_zfk_total = $this->di->customer_data->queryAll($zfk_count_sql,$params);
                $total_arr['zfk_total'] = $agent_zfk_total[0]['num'];

                // 代付款金额
                $dfk_count_sql = "SELECT SUM(obligations) as num FROM ".$this->prefix."customer_data cd WHERE ".$student_where;
                $agent_dfk_total = $this->di->customer_data->queryAll($dfk_count_sql,$params);
                $total_arr['dfk_total'] = $agent_dfk_total[0]['num'];

                // 已付款金额
                $yfk_count_sql = "SELECT SUM(paid) as num FROM ".$this->prefix."customer_data cd WHERE ".$student_where;
                $agent_yfk_total = $this->di->customer_data->queryAll($yfk_count_sql,$params);
                $total_arr['yfk_total'] = $agent_yfk_total[0]['num'];
                $data['total_count'] = $total_arr;
            if( $type==0 && $start_time!='' && $end_time!=''){
                $student_where .=" AND  UNIX_TIMESTAMP(FROM_UNIXTIME(cf.may_pay_date,  '%Y-%m-%d' )) between ".$start_time." AND ".$end_time;
            }
            switch ($type) {
                case 1:
                    # 总成交
                    $deal_where = " AND co.order_no <> '' ";
                    break;
                case 2:
                    # 今日成交
                    $start_date         = date('Y-m-d 00:00:00',time()); //今天
                    $end_date           = date('Y-m-d 23:59:59',time());
                    $deal_where = " AND FROM_UNIXTIME( co.deal_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";

                    break;
                case 3:
                    # 本周成交
                    $start_date         =  date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
                    $end_date           =  date('Y-m-d', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600));
                    $deal_where = " AND FROM_UNIXTIME( co.deal_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";

                    break;
                case 4:
                    # 本月成交
                    $days = array(mktime(0, 0, 0, date('m'), 1, date('Y')),mktime(23, 59, 59, date('m'), date('t'), date('Y')));
                    $start_date         =  date('Y-m-d H:i:s',$days[0]);
                    $end_date           =  date('Y-m-d H:i:s',$days[1]);
                    $deal_where = " AND FROM_UNIXTIME( co.deal_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";

                    break;
                case 5:
                    # 未成交
                    $deal_where = " AND co.order_no  IS NULL ";
                    break;
                default:
                    # 全部
                    $deal_where = "";
                    break;
            }
            if (!empty($keywords)) {
                # 关键词搜索
//                $student_where .= "AND (cd.cphone LIKE '%{$keywords}%' OR c.cname LIKE '%{$keywords}%') ";
                $student_where .= "AND concat(cd.cphone,c.cname) like '%{$keywords}%'";
            }
            // 高级搜索
            if (!empty($where_arr)) {
                $customer_field_arr = array("cname","charge_person",'cphone');
                foreach ($where_arr as $key => $value) {
                    if ($key == "deal_time") $key = "co.".$key;
                    if (in_array($key,$customer_field_arr)) {
                        $key = "cd.".$key;
                    } else {
                        $key = "c.".$key;
                    }
                    if (!empty($value) || $value == 0) {
                        # 其他
                        if (is_array($value) && is_numeric($value[0])) {
                            $student_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                        } elseif (is_array($value)) {
                            $student_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[0]}' ) ";
                        } elseif (is_numeric($value)) {
                            $student_where .= " AND {$key} = {$value} ";
                        }  else {
                            $student_where .= " AND {$key} LIKE '%{$value}%' ";
                        }
                    }
                }
            }
            if (!empty($deal_where)) {
                # 成交
                $sql = "SELECT cd.cid,c.id,c.intentionally,c.ittnxl,c.ittnyx,c.ittnzy,c.ittnxm,c.cname,cd.cphone,c.charge_person,cd.cphone,cd.cphonetwo,cd.create_time,cd.project_name,cd.agent_price,cd.paid,cd.obligations,co.agent_id,co.deal_time,cf.may_pay_date,cf.pay_date FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid LEFT JOIN ".$this->prefix."project_payment cf ON cf.deal_uid = co.cid WHERE ".$student_where.$deal_where." GROUP BY cd.cid ORDER BY cd.update_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(cd.cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid LEFT JOIN ".$this->prefix."project_payment cf ON cf.deal_uid = co.cid WHERE ".$student_where.$deal_where." GROUP BY cd.cid";
//                $count_sql = "SELECT count(cd.cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid LEFT JOIN ".$this->prefix."project_payment cf ON cf.deal_uid = co.cid WHERE ".$student_where.$deal_where." GROUP BY cd.cid";

            } else {
                $sql = "SELECT cd.cid,c.id,c.intentionally,c.ittnxl,c.ittnyx,c.ittnzy,c.ittnxm,c.cname,c.charge_person,cd.cphone,cd.cphonetwo,cd.create_time,cd.project_name,cd.agent_price,cd.paid,cd.obligations,co.deal_time,cf.may_pay_date,cf.pay_date FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid LEFT JOIN ".$this->prefix."project_payment cf ON cf.deal_uid = co.cid  WHERE ".$student_where." GROUP BY cd.cid ORDER BY cd.update_time DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(cd.cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid LEFT JOIN ".$this->prefix."project_payment cf ON cf.deal_uid = co.cid  WHERE ".$student_where." GROUP BY cd.cid ";
//                $count_sql = "SELECT count(cd.cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id LEFT JOIN ".$this->prefix."project_payment cf ON cf.deal_uid = c.id WHERE ".$student_where." GROUP BY cd.cid";
            }


        } else {
            # 详情学员列表
            $project_info = $this->di->project_side->select("flower_name,number")->where(array("id"=>$id,"status"=>1))->fetchOne();
            if (empty($project_info)) {
                $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
                return $rs;
            }
            $params[':project_num'] = $project_info['number'];
            $params[':project_name'] = $project_info['flower_name'];
            $student_where = " FIND_IN_SET("."'".$id."'".",cd.project_name) ";
            $sql = "SELECT cd.cid,cd.cphone,c.*,cd.create_time,cd.project_num,cd.project_name,cd.agent_price,cd.paid,cd.obligations,co.deal_time,cf.may_pay_date FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid  LEFT JOIN ".$this->prefix."project_payment cf ON cf.deal_uid = co.cid WHERE ".$student_where."  GROUP  BY cd.cid   ORDER BY cd.update_time DESC  LIMIT ".$pagenum.",".$pagesize;
            $count_sql = "SELECT count(cd.cid) as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid  LEFT JOIN ".$this->prefix."project_payment cf ON cf.deal_uid = co.cid WHERE ".$student_where."GROUP  BY cd.cid";
        }


        $project_stu_list = $this->di->project_side->queryAll($sql, $params);

        $project_stu_num = $this->di->project_side->queryAll($count_sql,$params);
//        var_dump(count($project_stu_num));die;

//        $project_stu_num=count($project_stu_list);
        $data['project_stu_list'] = $project_stu_list ? $project_stu_list : array();
        $data['project_stu_num'] =count($project_stu_num);
//        $data['project_stu_num'] = $project_stu_num;
        if (!empty($project_stu_list)) {
            foreach ($project_stu_list as $key => $value) {
                if($value['project_name']!=''){
                    $project_side_list=explode(',',$value['project_name']);
                    array_pop($project_side_list);
                    foreach ($project_side_list as $n=>$m){
                        $project_flower_list=$this->di->project_side->select('flower_name,creatid')->where(array("id"=>$m))->fetchOne();
                        $project_stu_list[$key]['project_name_list'][$project_side_list[$n]]=['flower_name'=>$project_flower_list['flower_name'],'creatid'=>$project_flower_list['creatid']];
                    }


                    if (!$show_phone) {
                        $project_stu_list[$key]['cphone'] = substr($value['cphone'], 0, 3) . '*****' . substr($value['cphone'], 8, strlen($value['cphone']));
                    }
                }
            }

            $data['project_stu_list'] = $project_stu_list ? $project_stu_list : array();

//            if ($id == 0 & $uid!=0) {
//                $this->redis->set_time('project_student_list_'.$uidid,$data,6000,'project');
//            }

            $data['show']=$show_phone;
            $rs = array('code' => 1, 'msg' => '000000', 'data' => $data, 'info' => $data);
        } else {
            $rs = array('code' => 1, 'msg' => '000002', 'data' => $data, 'info' => $data);
        }

//        $sql = "SELECT c.id,c.intentionally,c.ittnxl,c.ittnyx,c.ittnzy,c.ittnxm,c.cname,cd.cphone,cd.cphonetwo,cd.create_time,cd.project_name,cd.agent_price,cd.paid,cd.obligations,ct.deal_time,ct.addtime,cf.pay_state,cf.may_pay_date,cf.pay_date FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer_order ct ON cd.cid = ct.cid LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id  LEFT JOIN ".$this->prefix."project_payment cf ON cf.deal_uid = c.id WHERE ".$student_where." ORDER BY cd.update_time DESC LIMIT ".$pagenum.",".$pagesize;
//        var_dump($sql);exit();
        return $rs;
    }

    //合同调整
    public function PostContractEdit($uid,$newData,$general_list){
        $project_id = isset($newData['project_id']) && !empty($newData['project_id']) ? intval($newData['project_id']) : 0;
        $contract_id = isset($newData['contract_id']) && !empty($newData['contract_id']) ? intval($newData['contract_id']) : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($project_id) || empty($contract_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $new_user_info = $this->di->user->select("username")->where("id",$uid)->fetchOne();
        $contract_info = $this->di->project_contract->select("number,creatid,publisher,addtime")->where(array("project_id"=>$project_id,"id"=>$contract_id,"status"=>1))->fetchOne();
        if (empty($contract_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        // Step 1: 开启事务
        $this->di->beginTransaction('db_master');
        $adjust_id_str='';
        foreach ($general_list as $key => $value) {
            $project_general_info = $this->di->project_general->where(array("contract_id"=>$contract_id,"project_id"=>$project_id,"general_id"=>$value['general_id']))->fetchOne("title");
            if (empty($agent_general_info)) {
                $general_update = array(
                    "contract_id" => $contract_id,
                    "project_id" => $project_id,
                    "number" => $contract_info['number'],
                    "general_id" => $value['general_id'],
                    "title" => $value['title'],
                    "xuefei" => $value['new_xuefei'],
                    "bm_price" => $value['new_bm_price'],
                    "zl_price" => $value['new_zl_price'],
                    "cl_price" => $value['new_cl_price'],
                    "sq_price" => $value['new_sq_price'],
                    "total_moeny" => $value['new_total_moeny'],
                    "agent_money" => $value['new_agent_money'],
                    "note" => $value['note'],
                    "creatid" => $uid,
                    "publisher" => $contract_info['publisher'],
                    "addtime" => time(),
                    "updatetime" => time(),
                    "updateman" => $new_user_info['username']
                );
                $res2 = $this->di->project_general->insert($general_update);
                $res2 = $this->di->project_general->insert_id();
            } else {
                $general_update = array(
                    "xuefei" => $value['new_xuefei'],
                    "bm_price" => $value['new_bm_price'],
                    "zl_price" => $value['new_zl_price'],
                    "cl_price" => $value['new_cl_price'],
                    "sq_price" => $value['new_sq_price'],
                    "total_moeny" => $value['new_total_moeny'],
                    "agent_money" => $value['new_agent_money'],
                    "note" => $value['note'],
                    "updatetime" => time(),
                    "updateman" => $new_user_info['username']
                );
                $res2 = $this->di->project_general->where(array("contract_id"=>$contract_id,"project_id"=>$project_id,"general_id"=>$value['general_id']))->update($general_update);
            }
            $value['contract_id'] = $contract_id;
            $value['project_id'] = $project_id;
            $value['number'] = $contract_info['number'];
            $value['status'] = 1;
            $value['creatid'] = $contract_info['creatid'];
            $value['publisher'] = $contract_info['publisher'];
            $value['addtime'] = $contract_info['addtime'];
            $value['updateman'] = $new_user_info['username'];
            $value['updatetime'] = time();
            unset($value['id']);
            $res = $this->di->project_general_adjust->insert($value);
            $res = $this->di->project_general_adjust->insert_id();
            if ($res && $res2) {
                $adjust_id_str .= $res.',';
            }
        }
        $newData['adjust_id'] = $adjust_id_str;
        $newData['status'] = 1;
        $newData['updatetime'] = time();
        $newData['updateman'] = $new_user_info['username'];
        $contract_adjust_res = $this->di->project_contract_adjust->insert($newData);
        $contract_update_data = array(
            "money" => $newData['new_money'],
            "updatetime" => time(),
            "updateman" => $new_user_info['username'],
            "note" => $newData['note'],
            "file" => $newData['file']
        );
        $this->di->project_contract->where(array("id"=>$contract_id,"project_id"=>$project_id))->update($contract_update_data);
        if ($contract_adjust_res) {
            // 提交事务
            $this->di->commit('db_master');
            // 更新合同缓存
            $contract_redis_info = $this->redis->get_time('contract_info_'.$contract_id,'project');
            if (!empty($contract_redis_info)) {
                $contract_redis_info['money'] = $newData['new_money'];
                $contract_redis_info['updatetime'] = time();
                $contract_redis_info['updateman'] = $new_user_info['username'];
                $contract_redis_info['note'] = $newData['note'];
                $contract_redis_info['file'] = $newData['file'];
                \App\AddShareLog($newData['project_id'],$uid,$uid,3,'change','调整合同');
                $this->redis->set_time('contract_info_'.$contract_id,$contract_redis_info,6000,'project');
            }
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            // 回滚事务
            $this->di->rollback('db_master');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    //合同调整列表
    public function PostContractAdgustList($uid,$projectid){
        $projectid = isset($projectid) && !empty($projectid) ? intval($projectid) : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($projectid)) {
            $rs = array('code'=>0,'msg'=>'参数错误!项目方ID不能为空','data'=>array(),'info'=>array());
            return $rs;
        }
        $contract_list = $this->di->project_contract_adjust->select("id,contract_id,money,new_money,adjust_money,updatetime")->where(array("project_id"=>$projectid,"status"=>1))->order("updatetime DESC")->limit(20)->fetchRows();
        if (!empty($contract_list)) {
            foreach ($contract_list as $k => $v) {
                // 合同编号
                $contract_list[$k]['number'] = $this->di->project_contract->where("id",$v['contract_id'])->fetchOne("number");
               //调整日期
                $contract_list[$k]['updatetime'] =$v["updatetime"];
               //项目名称及调整详情
                $adjust=explode(',',$v['adjust_id']);
                array_pop($adjust);
                foreach ($adjust as $n=>$m){
                    $contract_list[$k]['contract_name'][$n]=\App\GetFiledInfo('project_general_adjust','title',$m);
                    $contract_list[$k]['contract_money'][$n]=\App\GetFiledInfo('project_general_adjust','agent_money',$m);
                    $contract_list[$k]['contract_money'][$n]=\App\GetFiledInfo('project_general_adjust','new_agent_money',$m);
                }
            }
            $rs = array('code'=>1,'msg'=>'000000','data'=>$contract_list,'info'=>$contract_list);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    //合同详情
    public function PostContractInfo($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $contract_info = $this->di->project_contract->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
        $general_list = $this->di->project_general->select("id,general_id,title,xuefei,bm_price,zl_price,cl_price,sq_price,total_moeny,agent_money,note")->where(array("contract_id"=>$id,"number"=>$contract_info['number']))->order("updatetime DESC")->fetchRows();
        $contract_info['general_list'] = !empty($general_list) ? $general_list : array();
        if (!empty($contract_info)) {
            $contract_info['publisher']=$this->di->user->where('username',$contract_info['publisher'])->fetchOne('realname');
            $rs = array('code'=>1,'msg'=>'000000','data'=>$contract_info,'info'=>$contract_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    // 13 签约项目列表[代理商]
    public function GetProjectGeneralList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $project_id = isset($newData['project_id']) && !empty($newData['project_id']) ? intval($newData['project_id']) : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($project_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }


        $general_list = $this->di->project_general->select("id,title,contract_id,xuefei,bm_price,bm_price,zl_price,cl_price,sq_price,total_moeny,agent_money,note")->where(array("project_id"=>$project_id,"status"=>1))->order("updatetime DESC")->fetchRows();
        if (empty($general_list)) {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
            return $rs;
        }

        $agent_general_list = array();
        foreach ($general_list as $key => $value) {
            $agent_general_list[$value['contract_id']]['contract_id'] = $value['contract_id'];
            $contract_number = $this->di->project_contract->where(array("project_id"=>$project_id,"id"=>$value['contract_id']))->fetchOne("number");
            $agent_general_list[$value['contract_id']]['contract_number'] = $contract_number;
            $agent_general_list[$value['contract_id']]['value'][] = $value;
        }

        $rs = array('code'=>1,'msg'=>'000000','data'=>$agent_general_list,'info'=>$agent_general_list);
        return $rs;
    }
    //合同列表
    public function GetProjectContrat($newData){
        $project_id = isset($newData['project_id']) && !empty($newData['project_id']) ? intval($newData['project_id']) : 0;
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($project_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $contract_where['creatid'] = $uid;
        $contract_where['project_id'] = $project_id;
        $contract_where['status'] = 1;
        $contract_list = $this->di->project_contract->select("id,number,person,company,contract_status,start_time,end_time,addtime")->where($contract_where)->order("sort DESC,addtime DESC")->fetchRows();
        if (!empty($contract_list)) {
            $contract_num = $this->di->project_contract->where($contract_where)->count("id");
            $data['contract_list'] = $contract_list ? $contract_list : array();
            $data['num'] = $contract_num ? $contract_num : 0;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //回款添加
    public function PostProjectPaymentAdd($uid,$newData,$field_list){
        $project_id = $newData['project_id'] ? $newData['project_id'] : 0;
        $contract_id = $newData['contract_id'] ? $newData['contract_id'] : 0;
        $deal_uid = $newData['deal_uid'] ? $newData['deal_uid'] : 0;
        $order_no = $newData['order_no'] ? $newData['order_no'] : "";
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'请登录','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($project_id) || empty($contract_id) || empty($deal_uid)) {
            $rs = array('code'=>0,'msg'=>'参数有误','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断用户角色
        $user_info = $this->di->user->select("username")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'用户不存在','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断合同信息
        $customer_number = $this->di->project_contract->where("id",$contract_id)->and("status",'1')->fetchOne();
        if ($customer_number== null) {
            $rs = array('code'=>0,'msg'=>'合同信息不存在!','data'=>$contract_id,'info'=>array());
            return $rs;
        }
        // 判断是否已存在回款记录
//        $payment_info = $this->di->project_payment->where(array("project_id_id"=>$project_id,"contract_id"=>$contract_id,"deal_uid"=>$deal_uid,"order_no"=>$order_no,"status"=>1))->fetchOne("title");
//        if (!empty($payment_info)) {
//            $rs = array('code'=>0,'msg'=>'已存在回款记录','data'=>array(),'info'=>array());
//            return $rs;
//        }
        $newData['contract_number'] = $customer_number['number'];
        if ($newData['pay_state'] == 1) {
            $newData['pay_date'] = time();
        }
        $newData['publisher'] = $user_info['username'];
        $newData['creatid'] = $uid;
        $newData['sort'] = 0;
        $newData['addtime'] = time();
        $newData['updatetime'] = time();
        $number = \App\CreateOrderNo();
        $newData['number'] = 'HK'.$number;
        $admin_common = new AdminCommon();
        $payment_data = $admin_common->GetFieldListArr($newData,$field_list);
        $payment_id = $this->di->project_payment->insert($payment_data);
//        $this->di->customer_data->where(array("cid"=>$deal_uid))->update($customer_update_arr);
        if ($payment_id) {
            $obligations =$this->di->project_payment->where('deal_uid',$newData['deal_uid'])->and('project_id',$newData['project_id'])->and('pay_state',0)->and('status',1)->sum('pay_money');//未付款
            $paid=$this->di->project_payment->where('deal_uid',$newData['deal_uid'])->and('project_id',$newData['project_id'])->and('pay_state',1)->and('status',1)->sum('pay_money');//已付款
            $this->di->customer_data->where(array("cid"=>$deal_uid))->update(['paid'=>$paid,'obligations'=>$obligations]);
            $rs = array('code'=>1,'msg'=>'插入成功','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'操作失败 ','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //回款记录  编辑
    public function PostContractMedit($uid,$id,$newData,$field_list){

        $contract_id = $newData['contract_id'] ? $newData['contract_id'] : 0;
        $project_id = $newData['project_id'] ? $newData['project_id'] : 0;
        $deal_uid = $newData['deal_uid'] ? $newData['deal_uid'] : 0;
        $order_no = $newData['order_no'] ? $newData['order_no'] : "";
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($project_id) || empty($contract_id) || empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断用户角色
        $user_info = $this->di->user->select("username")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断合同信息
        $customer_number = $this->di->project_contract->where(array("id"=>$contract_id,"status"=>1))->fetchOne("number");
        if ($customer_number=='') {
            echo 1;
            $rs = array('code'=>0,'msg'=>'000066','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断是否已存在付款记录
        $payment_info = $this->di->project_payment->where('id',$id)->and('status',1)->fetchOne();
        if ($payment_info !='' && $customer_number!='') {
            $newData['contract_number'] = $customer_number;
            if ($newData['pay_state'] == 1) {
                $newData['pay_date'] = time();
            }
            $newData['sort'] = 0;
            $newData['status'] = 1;
            $newData['updatetime'] = time();
            $admin_common = new AdminCommon();
            $payment_data = $admin_common->GetFieldListArr($newData,$field_list);
            $payment_id = $this->di->project_payment->where('id',$id)->update($payment_data);
            if($payment_id){
                $obligations =$this->di->project_payment->where('deal_uid',$newData['deal_uid'])->and('project_id',$newData['project_id'])->and('pay_state',0)->and('status',1)->sum('pay_money');//未付款
                $paid=$this->di->project_payment->where('deal_uid',$newData['deal_uid'])->and('project_id',$newData['project_id'])->and('pay_state',1)->and('status',1)->sum('pay_money');//已付款
                $this->di->customer_data->where(array("cid"=>$deal_uid))->update(['paid'=>$paid,'obligations'=>$obligations]);
                $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
            }
        }else{
            $rs = array('code'=>0,'msg'=>'000066','data'=>array(),'info'=>array());
            return $rs;
        }

        return $rs;
    }
    //回款详情
    public function PostContractMinfo($id){
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'参数不能为空','data'=>array(),'info'=>array());
            return $rs;
        }
        $list=$this->di->project_payment->where('id',$id)->fetchOne();
        $rs = array('code'=>1,'msg'=>'查询成功!','data'=>$list,'info'=>array());
        return $rs;
    }
    //合同调整详情
    public function GetContractProjectInfo($newData){
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $contract_id = isset($newData['contract_id']) && !empty($newData['contract_id']) ? intval($newData['contract_id']) : 0;
        if (empty($id) || empty($contract_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 调整合同信息
        $contract_project_info = $this->di->project_contract_adjust->select("project_id,adjust_id,money,new_money,adjust_money,note,file,updatetime,updateman")->where(array("id"=>$id,"contract_id"=>$contract_id,"status"=>1))->fetchOne();
        if (empty($contract_project_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        // 合同信息
        $contract_info = $this->di->project_contract->select("title as contract_name,number,publisher,updateman,addtime,updatetime")->where(array("id"=>$contract_id))->fetchOne();
        if (empty($contract_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        $contract_info = array_merge($contract_info,$contract_project_info);

        $project_id_str = trim($contract_info['adjust_id'],",");
        $project_id_arr = explode(",",$project_id_str);
        if (!empty($project_id_arr)) {
            $general_list = array();
            foreach ($project_id_arr as $key => $value) {
                $general_info = $this->di->project_general_adjust->select("general_id,title,xuefei,bm_price,zl_price,cl_price,sq_price,total_moeny,agent_money,new_xuefei,new_bm_price,new_zl_price,new_cl_price,new_sq_price,new_total_moeny,new_agent_money,note")->where(array("id"=>$value))->fetchOne();
                $general_list[$key] = $general_info;
            }
            $contract_info['general_list'] = !empty($general_list) ? $general_list : array();

        }
        $rs = array('code'=>1,'msg'=>'000000','data'=>$contract_info,'info'=>$contract_info);
        return $rs;
    }

    //开票新增
    public function PostContractInvoiceAdd($newData){
        $cid = isset($newData['cid']) && !empty($newData['cid']) ? intval($newData['cid']) : 0;
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $refund_company= isset($newData['refund_company']) && !empty($newData['refund_company']) ?$newData['refund_company'] : 0;
        $refund_price= isset($newData['refund_price']) && !empty($newData['refund_price']) ? $newData['refund_price']: '';
        if(empty($cid) || empty($refund_company)  ||  empty($uid)|| $refund_price==''){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $newData['creat_time']=time();
        unset($newData['service']);
        $newData['status']=1;
        $newData['creatid']=$uid;
        $newData['publisher']=$uid;
        if(!isset($newData['contrat_id'])){
            $rs = array('code'=>0,'msg'=>'项目合同号不能为空!','data'=>'','info'=>'');
            return $rs;
        }else{
            $number=$this->di->project_payment->where('contract_id',$newData['contrat_id'])->fetchOne();
            if($number){
                 $newData['contract_number']=$number['contract_number'];
             }else{
                 $rs = array('code'=>0,'msg'=>'回款中不存在该合同编号!','data'=>'','info'=>'');
                 return $rs;
             }
        }
        $status=$this->di->project_payment->where('order_no',$newData['number'])->and('status',1)->fetchOne();
        if($status['invoice_state']==2){
            $rs = array('code'=>0,'msg'=>'该订单无需开票,请确认后再试!','data'=>'','info'=>'');
            return $rs;
        }else if($status['invoice_state']==1){
            $rs = array('code'=>0,'msg'=>'该订单已开过票了,请核对!','data'=>'','info'=>'');
            return $rs;
        }
        $project_refund=$this->di->project_invoice->insert($newData);
        $project_refund_id = $this->di->project_invoice->insert_id();
        if($project_refund_id){
            $rs = array('code'=>1,'msg'=>'新建开票信息成功','data'=>$project_refund_id,'info'=>$project_refund_id);

        }else{
            $rs = array('code'=>0,'msg'=>'新建开票信息失败','data'=>'','info'=>'');

        }
        return $rs;
    }
    //开票编辑
    public function PostContractInvoiceEdit($newData){
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $refund_company= isset($newData['refund_company']) && !empty($newData['refund_company']) ?$newData['refund_company'] : 0;
        $refund_price= isset($newData['refund_price']) && !empty($newData['refund_price']) ? $newData['refund_price']: '';
        if(empty($id) || empty($refund_company)  ||  empty($uid)|| $refund_price==''){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $newData['creat_time']=time();
        $project_refund=$this->di->project_invoice->update($newData);
        $project_refund_info=$this->di->project_invoice->where('id',$id)->fetchOne();
        $this->redis->set_time('project_invoice_info_'.$id,$project_refund_info,6000,'project');
        if($project_refund){
            $rs = array('code'=>1,'msg'=>'更新开票信息成功','data'=>[],'info'=>[]);
        }else{
            $rs = array('code'=>0,'msg'=>'更新开票信息失败','data'=>'','info'=>'');
        }
        return $rs;
    }
    //开票列表
    public function GetProjectInvoiceList($project_id) {
        if (empty($project_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 项目方信息
        $project_info = $this->di->project_side->where(array("id"=>$project_id,"status"=>1))->fetchOne("title");
        if ($project_info=='') {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // 开票列表
        $invoice_list = $this->di->project_invoice->where(array("project_id"=>$project_id,"status"=>1))->order("creat_time DESC")->limit($this->pagesize)->fetchRows();

        if (!empty($invoice_list)) {
            $invoice_num = $this->di->project_invoice->where(array("project_id"=>$project_id,"status"=>1))->count("id");
            $data['invoice_list'] = $invoice_list;
            $data['invoice_num'] = $invoice_num ? $invoice_num : 0;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //开票详情
    public function PostContractInvoiceInfo($id) {
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $invoice_info = $this->redis->get_time('project_invoice_info_'.$id,'project');
        if (empty($invoice_info)) {
            $invoice_info = $this->di->project_invoice->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
            $invoice_info['cname']=\App\GetFiledInfo('customer','cname',$invoice_info['cid']);
            $invoice_info['realname']=\App\GetFiledInfo('user','realname',$invoice_info['uid']);
            $this->redis->set_time('project_invoice_info_'.$id,$invoice_info,6000,'project');
        }
        if (!empty($invoice_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$invoice_info,'info'=>$invoice_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    // 退款-新增
    public function PostContractRefundAdd($uid,$newData,$field_list) {
        $project_id = $newData['project_id'] ? $newData['project_id'] : 0;
        $payment_number = $newData['payment_number'] ? $newData['payment_number'] : "";

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($project_id) || empty($payment_number)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("username")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断退款信息是否存在
        $refund_info = $this->di->project_refund->where(array("payment_number"=>$payment_number,"project_id"=>$project_id,"status"=>1))->fetchOne("number");
        if (!empty($refund_info)) {
            $rs = array('code'=>0,'msg'=>'000073','data'=>array(),'info'=>array());
            return $rs;
        }

        // 退款编号
        $admin_common = new AdminCommon();

        $newData['publisher'] = $user_info['username'];
        $newData['creatid'] = $uid;
        $newData['sort'] = 0;
        $newData['status'] = 1;
        $newData['addtime'] = time();
        $newData['updatetime'] = time();

        $refund_data = $admin_common->GetFieldListArr($newData,$field_list);
        $refund_id = $this->di->project_refund->insert($refund_data);
        $refund_id = $this->di->project_refund->insert_id();
        if ($refund_id) {
            // 将付款记录更改为已退款状态

            $this->di->project_payment->where(array("number"=>$payment_number,"status"=>1))->update(array("status"=>2,"updatetime"=>time(),"updateman"=>$user_info['username']));
            $payment_info = $this->di->agent_payment->select("deal_uid")->where(array("number"=>$payment_number,"status"=>1))->fetchOne();

            $obligations =$this->di->project_payment->where('deal_uid',$newData['deal_uid'])->and('project_id',$project_id)->and('pay_state',0)->and('status',1)->sum('pay_money');//未付款
            $paid=$this->di->project_payment->where('deal_uid',$newData['deal_uid'])->and('project_id',$project_id)->and('pay_state',1)->and('status',1)->sum('pay_money');//已付款
            $this->di->customer_data->where(array("cid"=>$payment_info['deal_uid']))->update(['paid'=>$paid,'obligations'=>$obligations]);
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 退款-编辑
    public function PostContractRefundEdit($uid,$id,$newData,$field_list) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("username")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断是否存在退款信息
        $refund_info = $this->di->project_refund->select("creatid")->where(array("id"=>$id,"status"=>1))->fetchOne();
        if (empty($refund_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // 只允许创建人修改
        if ($refund_info['creatid'] != $uid) {
            $rs = array('code'=>0,'msg'=>'000061','data'=>array(),'info'=>array());
            return $rs;
        }

        $admin_common = new AdminCommon();
        $newData['updatetime'] = time();
        $newData['updateman'] = $user_info['username'];
        $refund_data = $admin_common->GetFieldListArr($newData,$field_list);
        $update_info = $this->di->project_refund->where(array("id"=>$id))->update($refund_data);
        if ($update_info) {
            $refund_data['id'] = $id;
            // 写入缓存
            $this->redis->set_time('project_refund_info_'.$id,$refund_data,6000,'project');
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 退款-列表
    public function GetProjectRefundList($project_id) {
        if (empty($project_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 开票列表
        $refund_list = $this->di->project_refund->select("id,payment_number,reason,refund_date,refund_money,refund_type,note,publisher")->where(array("project_id"=>$project_id,"status"=>1))->order("updatetime DESC")->limit($this->pagesize)->fetchRows();
        if (!empty($refund_list)) {
            $refund_num = $this->di->project_refund->where(array("project_id"=>$project_id,"status"=>1))->count("id");
            $data['refund_list'] = $refund_list;
            $data['refund_num'] = $refund_num ? $refund_num : 0;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 退款-详情
    public function GetProjectRefundInfo($id) {
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $refund_info = $this->redis->get_time('project_refund_info_'.$id,'project');
        if (empty($refund_info)) {
            $refund_info = $this->di->project_refund->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
            $student_where = " cd.id = ".$id." AND cd.status = 1 ";
            $sql = "SELECT cd.*,c.*,cs.*,ct.* FROM ".$this->prefix."project_refund cd LEFT JOIN ".$this->prefix."project_payment c ON cd.payment_number = c.number LEFT JOIN ".$this->prefix."customer ct ON c.deal_uid = ct.id LEFT JOIN ".$this->prefix."project_side cs ON cd.project_id = cs.id WHERE ".$student_where;

            $agent_stu_list = $this->di->project_refund->queryAll($sql);
            $this->redis->set_time('project_refund_info_'.$id,$agent_stu_list,6000,'project');
        }
        if (!empty($refund_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$refund_info,'info'=>$refund_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //回款列表
    public function GetProjectPaymentList($project_id,$type){
        if (empty($project_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 一手项目方信息
        $project_info = $this->di->project_side->where(array("id"=>$project_id,"status"=>1))->fetchOne("title");
        if (empty($project_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        if(isset($type) && $type!=3){
            $where=[
                'status'=>1,
                'pay_state'=>$type,
                'project_id'=>$project_id,
            ];
        }else if($type==3){
            $where=[
                'status'=>1,
                'project_id'=>$project_id,
            ];
        }
        $payment_list = $this->di->project_payment->select("id,number,deal_uid,contract_number,project,period,pay_nature,may_pay_date,pay_date,invoice_state,pay_money,note,pay_state")->where($where)->fetchRows();
        if (!empty($payment_list)) {
            foreach ($payment_list as $key => $value) {
                // 成交客户名称
                $cname = $this->di->customer->where(array("id"=>$value['deal_uid']))->fetchOne("cname");
                $payment_list[$key]['cname'] = $cname;
            }
            $payment_num = $this->di->project_payment->where(array("project_id"=>$project_id))->count("id");
            $data['payment_list'] = $payment_list;
            $data['payment_num'] = $payment_num ? $payment_num : 0;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //签约项目
    public function PostContractList($newData){
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $project_id = isset($newData['project_id']) && !empty($newData['project_id']) ? intval($newData['project_id']) : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($project_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $general_list = $this->di->project_general->where(array("project_id"=>$project_id,"status"=>1))->order("updatetime DESC")->fetchRows();
        if (empty($general_list)) {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
            return $rs;
        }
        $project_general_list = array();
        foreach ($general_list as $key => $value) {
            $project_general_list[$value['contract_id']]['contract_id'] = $value['contract_id'];
            $contract_number = $this->di->project_contract->where(array("project_id"=>$project_id,"id"=>$value['contract_id']))->fetchOne("number");
            $project_general_list[$value['contract_id']]['contract_number'] = $contract_number;
            $project_general_list[$value['contract_id']]['value'][] = $value;
        }

        $rs = array('code'=>1,'msg'=>'000000','data'=>$project_general_list,'info'=>$project_general_list);
        return $rs;
    }
    
}
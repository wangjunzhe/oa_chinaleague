<?php
namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Common\Admin as AdminCommon;
use App\Model\Customer as CustomerModel;
use App\Common\Customer as CustomerCommon;
use App\Model\Structure as StructureCommon;
use App\Common\Tree;
use App\Common\Category;
use App\Common\Yesapiphpsdk;
require dirname(dirname(dirname(__DIR__))).'/vendor/phpexcel/PHPExcel.php';
require dirname(dirname(dirname(__DIR__))).'/vendor/phpexcel/PHPExcel/Writer/Excel5.php';
require dirname(dirname(dirname(__DIR__))).'/vendor/phpexcel/PHPExcel/Writer/Excel2007.php';
require dirname(dirname(dirname(__DIR__))).'/vendor/phpexcel/PHPExcel/IOFactory.php';
require dirname(dirname(dirname(__DIR__))).'/vendor/phpexcel/PHPExcel/Cell.php';
use PHPExcel as NOP;
use PHPExcel_IOFactory;
use PHPExcel_Writer_Excel2007;
use PHPExcel_Writer_Excel5;
use PHPExcel_Cell;
class Site extends NotORM 
{
	protected $di;
	protected $iv;
	protected $prefix;
    protected $max_num;
	protected $cache;
    protected $redis;
	protected $config;
	protected $pagesize;
	public function __construct()
	{
		$this->di = \PhalApi\DI()->notorm;
		$this->config = \PhalApi\DI()->config;
		$this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
        $this->max_num = \PhalApi\DI()->config->get('common.MAX_NUM');
		$this->cache = \PhalApi\DI()->cache;
        $this->redis = \PhalApi\DI()->redis;
		// 加密向量
		$this->iv = \PhalApi\DI()->config->get('common.IV');
		// 页码设置
        $this->pagesize = \PhalApi\DI()->config->get('common.PAGESIZE');
	}

    // 01 上传单个文件
    public function UploadFile($file,$type) {
        $file = isset($file) && !empty($file) ? $file : array();
        $type = isset($type) ? $type : 0;
        $type_arr = array('upload','agent','teacher','project_side','uploadFile');
        if (empty($file)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        
        $admin_common = new AdminCommon();
        $path = "/runtime/".$type_arr[$type].'/';

        $file_path = $admin_common->SingleFileUpload($file,$path);
        $data['file_path'] = $file_path ? $file_path : "";
        
        $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        return $rs;
    }

    // 02 全部专业列表
    public function GetAllMajorList($newData) {
        $keywords = isset($newData['keywords']) ? $newData['keywords'] : '';//搜索关键词
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        // $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : 10;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : 100;
        // 
        $pagenum = ($pageno-1)*$pagesize;
        $major_where = " status = 1 ";
        if (!empty($keywords)) {
            $major_where .= " AND title LIKE '%{$keywords}%' ";
        }
        $major_list = $this->di->major->where($major_where)->limit($pagenum,$pagesize)->fetchPairs('id', 'title');
        $rs = array('code'=>1,'msg'=>'000000','data'=>$major_list,'info'=>$major_list);
        return $rs;
    }

    // 03 全部院校列表
    public function GetAllSchoolList($newData) {
        $keywords = isset($newData['keywords']) ? $newData['keywords'] : '';//搜索关键词
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        // $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : 10;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : 100;
        // 
        $pagenum = ($pageno-1)*$pagesize;
        $school_where = " status = 1 ";
        if (!empty($keywords)) {
            $school_where .= " AND title LIKE '%{$keywords}%' ";
        }
        $school_list = $this->di->school->where($school_where)->limit($pagenum,$pagesize)->fetchPairs('id', 'title');
        $rs = array('code'=>1,'msg'=>'000000','data'=>$school_list,'info'=>$school_list);
        return $rs;
    }

    // 04 全部专业方向列表
    public function GetAllMajorDirection($major_id) {
        $direction_list = $this->redis->get_time('direction_list','project');
    	if (empty($direction_list) || !empty($major_id)) {
            if (!empty($major_id)) {
                $direction_where['major_id'] = $major_id;
            }
            $direction_where['status'] = 1;
    		$direction_list = $this->di->major_direction->where($direction_where)->limit($this->max_num)->fetchPairs('id', 'title');
            if (empty($major_id)) {
                $this->redis->set_time('direction_list',$direction_list,3000,'project');
            }
    	}
    	$rs = array('code'=>1,'msg'=>'000000','data'=>$direction_list,'info'=>$direction_list);
    	return $rs;
    }

    // 06 企业通讯录列表
    public function GetAllUserList($newData) {
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;
        $keywords = isset($newData['keywords']) && !empty($newData['keywords']) ? $newData['keywords'] : "";
        $condition = isset($newData['condition']) && !empty($newData['condition']) ? intval($newData['condition']) : 0;
        
        $params[':status'] = 1;
        $user_where = "status = 1 AND u.type = 0 ";

        if (!empty($keywords)) {
            $user_where .= " AND realname LIKE '%{$keywords}%' ";
        }
        $user_list = $this->redis->get_time('all_user_list_'.$type.'_'.$keywords,'list');
        if (empty($user_list)) {
            $user_sql = "SELECT u.id,realname,structure_id,u.getlimit,u.setlimit,post,s.title as post_name,new_groupid FROM ".$this->prefix."user u LEFT JOIN ".$this->prefix."station s ON u.post = s.id WHERE ".$user_where."LIMIT 500";
            $user_list = $this->di->user->queryAll($user_sql, $params);
            $this->redis->set_time('all_user_list_'.$type.'_'.$keywords,$user_list,6000,'list');
        }

        if (empty($user_list)) {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
            return $rs;
        }

        $user_arr = array();
        switch ($type) {
            case 1:
                # 按部门
                foreach ($user_list as $key => $value) {
                    // 部门
                    $structure_arr = json_decode($value['structure_id'],true);
                    if (!empty($structure_arr)) {
                        $structure_id = array_pop($structure_arr);
                        $structure_name = $this->di->structure->where("id",$structure_id)->fetchOne("name");
                        $user_list[$key]['id_structure'] = $structure_id;
                        $user_list[$key]['structure_name'] = $structure_name;
                    }
                }
                $user_list_arr = \App\sortArrayByfield($user_list,"id_structure");
                foreach ($user_list_arr as $key => $value) {
                    $user_arr[$value['structure_name']][] = $value;
                }
                break;
            case 2:
                # 按岗位
                foreach ($user_list as $key => $value) {
                    $user_arr[$value['post_name']][] = $value;
                }
                break;
            case 3:
                # 全部
                foreach ($user_list as $key => $value) {
                    $user_arr[$key]['id'] = $value['id'];
                    $user_arr[$key]['realname'] = $value['realname'];
                    $structure_arr = json_decode($value['structure_id'],true);
                    if (!empty($structure_arr)) {
                        $structure_id = array_pop($structure_arr);
                        $structure_name = $this->di->structure->where("id",$structure_id)->fetchOne("name");
                        $user_arr[$key]['structure_name'] = $structure_name;
                        $user_arr[$key]['structure_id'] = $structure_id;
                    }
                    $user_arr[$key]['getlimit'] = $value['getlimit'];
                    $user_arr[$key]['setlimit'] = $value['setlimit'];
                }
                break;
            case 4:
                # code...
                // 查询所有部门
                $tree = new Tree();
                $user_arr = $this->redis->get_time('all_user_list_'.$type,'list');
                if (empty($user_arr)) {
                    $structure = new Category('admin_structure', array('id', 'pid', 'name', 'title'));
                    $data = $structure->getList('structure',"", 0, "id");
                    $list = array();
                    foreach ($data as $key => $value) {
                        $member_list = array();
                        foreach ($user_list as $k => $v) {
                            if ($v['new_groupid'] == $value['id']) {
                                $member_list[$value['id']][] = $v;
                                unset($user_list[$k]);
                            }
                        }
                        $list[$key]['id'] = $value['id'];
                        $list[$key]['pid'] = $value['pid'];
                        $list[$key]['name'] = $value['name'];
                        $list[$key]['member_list'] = $member_list[$value['id']];

                    }
                    $user_arr = $tree->list_to_tree($list);
                    $this->redis->set_time('all_user_list_'.$type,$user_arr,6000,'list');
                }
                break;
            case 5:
                # 共享通讯录-只查询前台
                $tree = new Tree();
                $user_arr = $this->redis->get_time('all_user_list_'.$type,'list');
                if (empty($user_arr)) {
                    $structure = new Category('admin_structure', array('id', 'pid', 'name', 'title'));
                    $data = $structure->getList('structure',"capacity = 2", 0, "id",1);
                    $list = array();
                    foreach ($data as $key => $value) {
                        $member_list = array();
                        foreach ($user_list as $k => $v) {
                            if ($v['new_groupid'] == $value['id']) {
                                $list[$value['name']][] = $v;
                            }
                        }
                    }
                    $user_arr = $list;
                    // $user_arr = $tree->list_to_tree($list);
                    $this->redis->set_time('all_user_list_'.$type,$user_arr,6000,'list');
                }
                break;
            default:
                # 默认
                foreach ($user_list as $key => $value) {
                    // 部门
                    $structure_arr = json_decode($value['structure_id'],true);
                    if (!empty($structure_arr)) {
                        $structure_name_str = "";
                        foreach ($structure_arr as $k => $v) {
                            $structure_name = $this->di->structure->where("id",$v)->fetchOne("name");
                            $structure_name_str .= $structure_name.'/';
                        }
                        $value['post_name'] = $structure_name_str.$value['post_name'];
                    }
                    // 首字母
                    $user_name = trim($value['realname']," ");
                    $first_tit = mb_substr($user_name,0,1,'utf-8');
                    if ($first_tit != "") {
                        $initials = \App\getFirstCharter($first_tit);
                    }
                    $user_arr[$initials][] = $value;

                }
            break;
        }
        $rs = array('code'=>1,'msg'=>'000000','data'=>$user_arr,'info'=>$user_arr);
        return $rs;
    }

    // 07 全部简章
    public function GetAllGeneralList($newData) {
        $type = isset($newData['type']) ? $newData['type'] : 0;
        $id = isset($newData['id']) ? $newData['id'] : 0;
        $keywords = isset($newData['keywords']) ? $newData['keywords'] : '';//搜索关键词
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        // $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : 10;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : 100;
        // 
        $pagenum = ($pageno-1)*$pagesize;
        $general_where = " status = 1 ";
        if (!empty($keywords)) {
            $general_where .= " AND title LIKE '%{$keywords}%' ";
        }
        switch ($type) {
            case 1:
                # 代理商
                $general_where .= " AND agent_id = {$id} ";
                $general_list = $this->di->agent_general->where($general_where)->limit($pagenum,$pagesize)->fetchPairs('general_id', 'title');
                break;
            case 2:
                # 项目方
                $general_where .= " AND project_id = {$id} ";
                $general_list = $this->di->project_general->where($general_where)->limit($pagenum,$pagesize)->fetchPairs('general_id', 'title');
                break;
            default:
                # 全部
                $general_list = $this->di->general_rules->where($general_where)->limit($pagenum,$pagesize)->fetchPairs('id', 'title');
                break;
        }
        $rs = array('code'=>1,'msg'=>'000000','data'=>$general_list,'info'=>$general_list);
        return $rs;
    }

    // 08 简章价格
    public function GetGeneralPrice($id) {
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $general_info = $this->di->general_rules->select("id,title,xuefei,bm_price,zl_price,cl_price,sq_price")->where(array("id"=>$id,"status"=>1))->fetchOne();
        if (!empty($general_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$general_info,'info'=>$general_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 09 [公共]获取信息共享人员列表
    public function GetSharePersonList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $table_type_arr = array("customer","agent","teacher",'project_side');
        $curr_table = $table_type_arr[$type];

        $charge_person = $this->di->$curr_table->where(array("id"=>$id,"status"=>1))->fetchOne("charge_person");
        if (empty($charge_person)) {
             $rs = array('code'=>0,'msg'=>'000050','data'=>array(),'info'=>array());
            return $rs;
        }
        $charge_person_arr = explode(",",trim($charge_person,","));
        array_shift($charge_person_arr);
        $user_list = array();
        if (!empty($charge_person_arr)) {
            foreach ($charge_person_arr as $key => $value) {
                $user_list[$key]['user_name'] = $this->di->user->where("id",$value)->fetchOne("realname");
                $user_list[$key]['uid'] = $value;
            }
        }
        $rs = array('code'=>1,'msg'=>'000000','data'=>$user_list,'info'=>$user_list);
        return $rs;
    }

    // 10 我的全部客户
    public function GetAllCusstomer($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $cname = isset($newData['cname']) && !empty($newData['cname']) ? $newData['cname'] : '';
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($cname)) {
            $cname='';
        }
        $customer_list = $this->redis->get_time('customer_lists','customer');
        if (empty($customer_list) && $cname!='') {
            $customer_where = " status = 1 AND FIND_IN_SET('{$uid}', charge_person) AND cname like '%{$cname}%' ";
            $customer_list = $this->di->customer->where($customer_where)->limit($this->max_num)->fetchPairs('id', 'cname');
            $this->redis->set_time('customer_lists',$customer_list,6000,'customer');
        }
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_list,'info'=>$customer_list);
        return $rs;
    }

    // 11 公共取消共享
    public function PublicCancleShare($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $table_type_arr = array("customer","agent","teacher","project_side");
        $table_name_arr = array("客户","代理商","师资","一手项目方");
        $field_type_name = array("customer"=>"cname","agent"=>"flower_name","teacher"=>"name","project_side"=>"flower_name");

        $curr_table = $table_type_arr[$type];
        $field = $field_type_name[$curr_table];

        // 查看信息是否存在
        $info = $this->di->$curr_table->select("charge_person,creatid,".$field)->where(array("id"=>$id,"status"=>1))->fetchOne();
        if (empty($info)) {
             $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        $charge_person_arr = explode(",",trim($info['charge_person'],","));
        array_shift($charge_person_arr);//去掉创建人
        // 只有负责人能取消共享
        if ($uid == $info['creatid']) {
            $rs = array('code'=>0,'msg'=>'000057','data'=>array(),'info'=>array());
            return $rs;
        }

        if (!in_array($uid, $charge_person_arr)) {
            $rs = array('code'=>0,'msg'=>'000065','data'=>array(),'info'=>array());
            return $rs;
        }

        $charge_person_str = $uid.',';
        $update_data['charge_person'] = str_replace($charge_person_str,"",$info['charge_person']);
        $result = $this->di->$curr_table->where("id",$id)->update($update_data);

        if ($result) {
            // 取消共享成功 -- 增加取消共享操作记录
            $customer_common = new CustomerCommon();
            $note = "取消".$table_name_arr[$type].":".$info[$field]."的共享！";
            $result = $customer_common->PublicShareLog($id,$uid,$uid,$type,'cancle',$note);
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        
        return $rs;
    }

    // 12 全部代理商
    public function GetAllAgentList($newData) {
        $type = isset($newData['type']) ? $newData['type'] : 0;
        $keywords = isset($newData['keywords']) ? $newData['keywords'] : '';//搜索关键词
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        // $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : 10;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : 100;
        // 
        $pagenum = ($pageno-1)*$pagesize;
        $agent_where = " status = 1 ";
        if (!empty($keywords)) {
            $agent_where .= " AND flower_name LIKE '%{$keywords}%' ";
        }
        switch ($type) {
            case 1:
                # number
                $agnet_list = $this->di->agent->where($agent_where)->limit($pagenum,$pagesize)->fetchPairs('number', 'flower_name');
                break;
            default:
                # id
                $agnet_list = $this->di->agent->where($agent_where)->limit($pagenum,$pagesize)->fetchPairs('id', 'flower_name');
                break;
        }
        
        $rs = array('code'=>1,'msg'=>'000000','data'=>$agnet_list,'info'=>$agnet_list);
        return $rs;
    }

    // 13 全部一手项目方
    public function GetAllProdutionList() {
        $prodution_list = $this->redis->get_time('prodution_list','agent');
        if (empty($prodution_list)) {
            $prodution_list = $this->di->project_side->select('id,number,flower_name')->where("status",1)->limit($this->max_num)->fetchAll();
            foreach($prodution_list as $k=>$v){
                $new_prodution_list[]=array(
                    'flower_name'=>$v['flower_name'],
                    'id'=>$v['id'],
                    'number'=>$v['number']
                );
            }
            $this->redis->set_time('prodution_list',$new_prodution_list,3000,'agent');
        }
        $rs = array('code'=>1,'msg'=>'000000','data'=>$prodution_list,'info'=>$prodution_list);
        return $rs;
    }

    // 14 获取代理商全部学员
    public function GetAgentAllStudent($agent_id) {
        if (empty($agent_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $agent_info = $this->di->agent->select("flower_name,number")->where(array("id"=>$agent_id,"status"=>1))->fetchOne();
        if (empty($agent_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        $params[':agent_num'] = $agent_info['number'];
        $params[':agent_name'] = $agent_info['flower_name'];
        $student_where = " cd.agent_num = :agent_num AND cd.agent_name = :agent_name ";
        $sql = "SELECT cd.cid,c.cname FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id  WHERE ".$student_where."ORDER BY cd.update_time DESC LIMIT ".$this->max_num;
        $agent_stu_list = $this->di->agent->queryAll($sql, $params);
        if (empty($agent_stu_list)) {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
            return $rs;
        }
        $student_list = array();
        foreach ($agent_stu_list as $key => $value) {
            $student_list[$value['cid']] = $value['cname'];
        }
        if (empty($student_list)) {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$student_list,'info'=>$student_list);
        }
        return $rs;
    }

    // 15 获取成交编号
    public function GetOrderNumberByCid($cid) {
        if (empty($cid)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $customer_info = $this->di->customer->where(array("id"=>$cid))->fetchOne("cname");
        if (empty($customer_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        $order_list = $this->di->customer_order->where(array("cid"=>$cid))->fetchPairs("id","order_no");
        if (empty($order_list)) {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$order_list,'info'=>$order_list);
        }
        return $rs;
    }

    // 16 [代理/项目方]删除简章
    public function PublicDeleteAgentGeneral($newData) {
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $contract_id = isset($newData['contract_id']) && !empty($newData['contract_id']) ? intval($newData['contract_id']) : 0;
        $general_id = isset($newData['general_id']) && !empty($newData['general_id']) ? intval($newData['general_id']) : 0;
        
        $table_arr = array('','agent_general','project_general');
        $table_name = $table_arr[$type];
        $field_arr = array('','agent_id','project_id');
        $field_name = $field_arr[$type];
        

        // 查询是否存在
        $info = $this->di->$table_name->where(array("contract_id"=>$contract_id,"general_id"=>$general_id))->fetchOne();

        if (!empty($info)) {
            $res = $this->di->$table_name->where(array($field_name=>$id,"contract_id"=>$contract_id,"general_id"=>$general_id))->delete();
            if ($res) {
                $rs = array('code'=>1,'msg'=>'000005','data'=>array(),'info'=>array());
            } else {
                $rs = array('code'=>0,'msg'=>'000035','data'=>array(),'info'=>array());
            }
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 05 Excel文件导入
    public function PostImportExcel($type,$file,$uid) {
        // 查看文件格式是否正确
        if ($file['error'] == 4) {
            return array('code'=>0,'msg'=>'000080','data'=>array(),'info'=>array());
        }
        $ext = \APP\getExtension($file['name']);
        $excel_type = array('xls','csv','xlsx');
        if (!in_array($ext,$excel_type)){
            return array('code'=>0,'msg'=>'000081','data'=>array(),'info'=>array());
        }

        // 上传文件
        $upload_arr = $this->UploadFile($file,4);
        if ($upload_arr['data']['file_path'] == "") {
            return array('code'=>0,'msg'=>'000082','data'=>array(),'info'=>array());
        }
        $savePath = dirname(dirname(dirname(__DIR__))).$upload_arr['data']['file_path'];
        $admin_common = new AdminCommon();
        $customer_common = new CustomerCommon();
        $structure_common = new StructureCommon();
        // 数据处理
        $table_field = $this->config->get('common.TABLE_FIELD');
        $field_arr = $table_field[$type];
        $field_list = $field_arr['filed'];
        $model_id = $field_arr['model_id'];

        if ($type == 'customer') {
            $data_field_arr = $field_arr['data_field'];
            $field_list = array_merge($field_list,$data_field_arr);
            $data_file_key = array_keys($data_field_arr);
        }

        // 查询灵活字段以及值
        $model_field_arr = $this->di->model_field->select("field,name,type,is_require,setting")->where(array("modelid"=>$model_id,"master_field"=>1))->order("id DESC")->limit(15)->fetchRows();
        $model_field_name_arr = array();
        if (!empty($model_field_arr)) {
            foreach ($model_field_arr as $key => $value) {
                $model_field_name_arr[] = $value['name'];
                $field_list[$value['field']]['name'] = $value['name'];
                $field_list[$value['field']]['type'] = $value['type'];
                $field_list[$value['field']]['is_require'] = $value['is_require'];
                $field_list[$value['field']]['setting'] = $value['setting'];
            }
        }
        $fieldArr = [];
        $requireField = []; //必填字段
        foreach ($field_list as $key => $value) {
            $fieldArr[$value['name']]['name'] = $key;
            $fieldArr[$value['name']]['type'] = $value['type'];
            if ($value['type'] == "sql") {
                $fieldArr[$value['name']]['from_table'] = $value['from_table'];
                $fieldArr[$value['name']]['field'] = $value['field'];
            }
            if ($value['is_require'] == 1) $requireField[] = $key;
            if (isset($value['setting'])) {
                $fieldArr[$value['name']]['setting'] = $value['setting'];
            }
        }
        
        $field_num = count($fieldArr);
        
        //默认数据
        $defaultData = array(); 
        // $user_info = $this->di->user->select("realname,structure_id")->where(array("id"=>$uid))->fetchOne();
        $user_info = \App\Getkey($uid,array("realname","structure_id","setlimit","getlimit"));
        $structure_id = json_decode($user_info['structure_id']);
        $user_structure = $structure_id;
        $user_structure_id = array_pop($structure_id);
        switch ($type) {
            case 'customer':
                # 客户
                $defaultData['creatid'] = $uid;
                $defaultData['charge_person'] = $uid.",";
                $defaultData['status'] = 1;
                $defaultData['next_follow'] = 0;
                $defaultData['follw_time'] = time();
                $custome_filed_num = $field_num - count($data_field_arr) + 5;
                break;
            case 'educational':
                # 教务 
                $defaultData['creatid'] = $uid;
                $defaultData['create_time'] = time();
                $defaultData['update_time'] = time();
                $defaultData['is_delete'] = 0;
                break;
            default:
                # 其他
                if ($type == 'agent' || $type == 'project_side' || $type == 'teacher') {
                    $defaultData['creatid'] = $uid;
                    $defaultData['charge_person'] = $uid.",";
                    $defaultData['follow_time'] = time();//最后跟进日期默认为当前
                    $defaultData['follow_up'] = 0;//跟进次数默认为0
                    $defaultData['next_follow'] = 0;//下次提醒为0
                }
                $defaultData['publisher'] = $user_info['realname'];
                $defaultData['addtime'] = time();
                $defaultData['updatetime'] = time();
                $defaultData['status'] = 1;
                $defaultData['sort'] = 0;
                break;
        }

        // 验重字段
        switch ($type) {
            case 'customer':
                $uniqueField = "cname";
                $title_name = "cname";
                break;
            case 'teacher':
                $uniqueField = "name";
                $title_name = "name";
                $defaultData['structure_id'] = $user_structure_id;
                $log_type = 2;
                break;
            case 'agent':
                $uniqueField = "identifier";
                $second_uniqueField = "flower_name";
                $title_name = "title";
                $log_type = 1;
                break;
            case 'project_side':
                $uniqueField = "identifier";
                $title_name = "title";
                $structure_id_new = json_decode($user_info['structure_id']);
                $structure_id_str = implode(',',$structure_id_new);
                $defaultData['structure_id'] = trim($structure_id_str,",");
                $log_type = 3;
                break;
            default:
                $uniqueField = "title";
                $title_name = "title";
                break;
        }
        $objPHPExcel =new NOP();
        if ($ext =='xlsx') {
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            $objRender = \PHPExcel_IOFactory::createReader('Excel2007');
            // $objRender->setReadDataOnly(true);
            $ExcelObj = $objRender->load($savePath);
        } elseif ($ext =='xls') {
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            $objRender = \PHPExcel_IOFactory::createReader('Excel2007');
            // $objRender->setReadDataOnly(true);
            $ExcelObj = $objRender->load($savePath);
        } elseif ($ext=='csv') {
            $objWriter = new \PHPExcel_Reader_CSV($objPHPExcel);
            //默认输入字符集
            $objWriter->setInputEncoding('UTF-8');
            //默认的分隔符
            $objWriter->setDelimiter(',');
            //载入文件
            $ExcelObj = $objWriter->load($savePath);
        }

        $currentSheet = $ExcelObj->getSheet(0);
        //查看有几个sheet
        $sheetContent = $ExcelObj->getSheet(0)->toArray();
        //读取表头
        $excelHeader = $sheetContent[1];

        unset($sheetContent[0]);
        unset($sheetContent[1]);

        $keys = 2;
        $errorMessage = [];
        $getlimit = $user_info['getlimit'];
        foreach ($sheetContent as $kk => $val) {
            $data = '';
            $k = 0;
            $resNameIds = '';
            $keys++;
            $name = ''; //客户、线索、联系人等名称
            $data = $defaultData; //导入数据
            $resWhere = ''; //验重条件
            $resInfo = false; //Excel列是否有数据
            $customer_info = array();//默认客户数据
            $resultInfo = true;
            foreach ($excelHeader as $aa => $header) {
                if (empty($header)) break;
                if (strrpos($header,"(")) {
                    $header = substr($header,0,strrpos($header,'('));
                }
                $fieldName = trim(str_replace('*','',$header));
                $info = '';
                $info = $val[$k];
                if ($info) $resInfo = true;
                // 判断必填项是否填写
                if ( in_array($fieldArr[$fieldName]['name'],$requireField) && !$info ) {
                    $errorMessage[] = '第'.$keys.'行导入错误,失败原因：'.$fieldName.'必填';
                    $resultInfo = false;
                    break;                           
                }
                // 验重
                if ($uniqueField == $fieldArr[$fieldName]['name'] && $type!='customer') {
                    $info_id = $this->di->$type->where(array($uniqueField=>$val[$k]))->fetchOne("id");
                    if ($info_id) {
                        $errorMessage[] = '第'.$keys.'行导入错误,失败原因：'.$field_arr['model_name'].'已存在！';
                        $resultInfo = false;
                        break;
                    }
                }
                if (isset($second_uniqueField) && $second_uniqueField == $fieldArr[$fieldName]['name']) {
                    $info_id = $this->di->$type->where(array($second_uniqueField=>$val[$k]))->fetchOne("id");
                    if ($info_id) {
                        $errorMessage[] = '第'.$keys.'行导入错误,失败原因：'.$field_arr['model_name'].'花名已存在！';
                        $resultInfo = false;
                        break;
                    }
                }
                // 判断撞单
                if ($type == 'customer') {
                    // 判断是否
                    if ($user_info['setlimit'] != 0 &&  $getlimit >= $user_info['setlimit']) {
                        $errorMessage[] = "你的私海数据已满".$user_info['setlimit']."人，无法添加，添加失败！";
                        $resultInfo = false;
                        break;
                    }
                    // 判断是否
                    if ($fieldName == "联系电话" || $fieldName == "联系电话2"  || $fieldName == "联系电话3" || $fieldName == "客户座机" || $fieldName == "微信") {
                        if ($fieldName == "联系电话") $phone_filed = "cphone";
                        if ($fieldName == "联系电话2") $phone_filed = "cphonetwo";
                        if ($fieldName == "联系电话3") $phone_filed = "cphonethree";
                        if ($fieldName == "客户座机") $phone_filed = "telephone";
                        if ($fieldName == "微信") $phone_filed = "wxnum";

                        if ($resInfo && !empty($val[$k])) {
                            // 查询撞单
                            if ($phone_filed == "wxnum") {
                                $repeat_info = $structure_common->GetPhoneRepeat($uid,"",$val[$k],$user_structure);
                            } else {
                                $repeat_info = $structure_common->GetPhoneRepeat($uid,$val[$k],"",$user_structure);
                            }
                            if (!empty($repeat_info['data'])) {
                                $errorMessage[] = '第'.$keys."行导入错误,失败原因：客户{$fieldName}：".$val[$k].'撞单,已存在！';
                                $resultInfo = false;
                                break;
                            }
                            /**
                            if ($phone_filed == "telephone") {
                                $customer_info = $this->di->customer_data->select("cid,telephone")->where("`telephone` = '{$val[$k]}'")->fetchOne();
                                $mark_phone = "座机";
                            } else {
                                $customer_info = $this->di->customer_data->select("cid,cphone,cphonetwo,cphonethree")->where("FIND_IN_SET('{$val[$k]}',CONCAT_WS(',',`cphone`,`cphonetwo`,`cphonethree`))")->fetchOne();
                                $mark_phone = "手机号";
                            }
                            if (!empty($customer_info)) {
                                // 查看最后撞单时间
                                $repeat_time = $this->di->customer_log->where(array("type"=>9,"uid"=>$uid,"cid"=>$customer_info['cid']))->order("addtime DESC")->fetchOne("id");
                                $remain = ( time() - $repeat_time ) % 86400;
                                $hours = intval($remain / 3600);
                                if ($hours > 1) {
                                    // 插入撞单记录
                                    $note = "撞单手机号：".$customer_info[$phone_filed];
                                    $customer_common->CustomerActionLog($uid,$customer_info['cid'],9,'撞单',$note);
                                    // 更新撞单次数
                                    $this->di->customer_data->where('cid',$customer_info['cid'])->update(['zdnum' => new \NotORM_Literal("zdnum + 1")]);
                                }
                                $errorMessage[] = '第'.$keys."行导入错误,失败原因：客户{$mark_phone}：".$customer_info[$phone_filed].'撞单,已存在！';
                                break;
                            }
                            **/
                        }
                    }
                }
                // 整理数据
                if (empty($fieldArr[$fieldName]['name'])) continue;
                if ($aa <= $field_num) {
                    $resList = [];
                    $resList = $admin_common->sheetData($k, $fieldArr, $fieldName, $info, $model_field_name_arr, $type);
                    $resData[] = $resList['data'];
                    $k = $resList['k'];
                }
            }
            if ($resultInfo) {
                $result = $admin_common->changeArr($resData); //二维数组转一维数组
                $data = $result ? array_merge($data,$result) : [];
                if ($type == 'customer') {
                    if (empty($customer_info) ) {
                        $customer_data_arr = $data;
                        $data = array_slice($customer_data_arr,0,$custome_filed_num);
                        $customer_data = array_slice($customer_data_arr,$custome_filed_num,count($data));
                        $data_table = $type.'_data';
                        $number=$this->di->agent->where('flower_name',$customer_data['agent_name'])->fetchOne('number');
                        if(empty($number)){
                            $errorMessage[] = '第'.$keys.'行导入错误,失败原因：代理商花名错误,请核实!';
                            break;
                        }else{
                            $customer_data['agent_num']=$number;
                        }
                        $resDataId = $this->di->$type->insert($data);
                        $resDataId = $this->di->$type->insert_id();
                        $customer_data['cid'] = $resDataId;
                        if($customer_data['sex']=='0'){
                            $customer_data['sex']=1;
                        }else{
                            $customer_data['sex']=2;
                        }
                        $customer_data['create_time'] = time();
                        $customer_data['update_time'] = time();
                        $customer_data['groupid'] = $user_structure_id;
                        $customer_data['flag'] = 1;
                        $resData_Id = $this->di->$data_table->insert($customer_data);
                        // 客户+1
                        \App\SetLimit($uid,1);
                        $getlimit++;
                        // 增加客户导入日志
                        $customer_common->CustomerActionLog($uid,$resDataId,0,'导入客户',$user_info['realname'].'导入客户：'.$data['cname']);
                    }
                } elseif ($type == 'general_rules') {
                    $resDataId = $this->di->$type->insert($data);
                    $resDataId = $this->di->$type->insert_id();
                    $data_table = $type.'_data';
                    $resData_Id = $this->di->$data_table->insert(array("remark"=>"","content"=>""));
                } else {
                    if ($type == 'agent' || $type == 'project_side') {
                        $prefix = $type == 'agent' ? "DLS" : "YSF";
                        $number = \App\CreateOrderNo();
                        $data['number'] = $prefix.$number;
                    }
                    $resDataId = $this->di->$type->insert($data);
                    $resDataId = $this->di->$type->insert_id();
                    if (!empty($log_type)) {
                        // 增加导入日志
                        $note = $user_info['realname'].'导入'.$field_arr['model_name'].':'.$data[$title_name];
                        $res = $customer_common->PublicShareLog($resDataId,$uid,$uid,$log_type,'add',$note);
                    }
                }

                if (!$resDataId) {
                    $errorMessage[] = '第'.$keys.'行导入错误,失败原因：数据错误';
                    break;
                }
            }
        }
        if(count($errorMessage)==0){
            $errorMessage='导入成功!';
        }
        return $rs = array('code'=>1,'msg'=>'000000','data'=>$errorMessage,'info'=>$errorMessage);
    }

    // 17 导出Excel模板
    public function PostExportModelExcel($tablename) {
        $table_field = $this->config->get('common.TABLE_FIELD');
        $field_list = $table_field[$tablename];
        $model_id = $field_list['model_id'];

        // 查询灵活字段以及值
        $model_field_arr = $this->di->model_field->select("field,name,type,is_require,setting")->where(array("modelid"=>$model_id,"master_field"=>1))->order("id DESC")->limit(15)->fetchRows();
        if (!empty($model_field_arr)) {
            foreach ($model_field_arr as $key => $value) {
                $field_list['filed'][$value['field']]['name'] = $value['name'];
                $field_list['filed'][$value['field']]['type'] = $value['type'];
                $field_list['filed'][$value['field']]['is_require'] = $value['is_require'];
                $field_list['filed'][$value['field']]['setting'] = $value['setting'];
            }
        }
        
        $customer_field_list = $field_list['filed'];
        //客户模块
        if ($tablename == 'customer') {
            $customer_field_list = array_merge($field_list['filed'], $field_list['data_field']);
        }
         //引入phpexecl服务启动文件
        $objPHPExcel =new NOP();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $excelModel = new Excel();
        //设置属性
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator("ZKLM");
        $objProps->setLastModifiedBy("ZKLM");
        $objProps->setTitle("ZKLM");
        $objProps->setSubject("ZKLM data");
        $objProps->setDescription("ZKLM data");
        $objProps->setKeywords("ZKLM data");
        $objProps->setCategory("ZKLM");
        $objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();
        $objActSheet->setTitle('智库联盟'.$field_list['model_name'].'导入模板'.date('Y-m-d',time()));

        //填充边框
        $styleArray = [
            'borders'=>[
                'outline'=>[
                    'style'=>\PHPExcel_Style_Border::BORDER_THICK, //设置边框
                    'color' => ['argb' => '#F0F8FF'], //设置颜色
                ],
            ],
        ];

        $row = 1000;
        $k = 0;
       
        foreach ($customer_field_list as $field) {
            $objActSheet->getColumnDimension($excelModel->stringFromColumnIndex($k))->setWidth(20); //设置单元格宽度
            if ($field['type'] == 'address') {
                for ($a=0; $a<=2; $a++){
                    $address = array('所在省','所在市','所在县');//如果是所在省的话
                    $objActSheet->getStyle($excelModel->stringFromColumnIndex($k).'2')->applyFromArray($styleArray);//填充样式
                    $objActSheet->setCellValue($excelModel->stringFromColumnIndex($k).'2', $address[$a]);
                    $k++;
                }
            } else {
                if ($field['type'] == 'select' || $field['type'] == 'checkbox' || $field['type'] == 'radio') {
                    if (!empty($field['setting']) && !is_array($field['setting'])) {
                       $setting = json_decode($field['setting'],true) ? : [];
                    } else {
                        $setting = $field['setting'];
                    }
                    $select_value = implode(',',$setting);
                    if ($select_value) {
                        for ($c=3; $c<=70; $c++) {
                            //数据有效性 start
                            $objValidation = $objActSheet->getCell($excelModel->stringFromColumnIndex($k).$c)->getDataValidation(); //这一句为要设置数据有效性的单元格
                            $objValidation -> setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)
                                -> setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                                -> setAllowBlank(false)
                                -> setShowInputMessage(true)
                                -> setShowErrorMessage(true)
                                -> setShowDropDown(true)
                                -> setErrorTitle('输入的值有误')
                                -> setError('您输入的值不在下拉框列表内.')
                                -> setPromptTitle('--请选择--')
                                -> setFormula1('"'.$select_value.'"');
                            //数据有效性  end
                        }

                    }
                }

                //检查该字段若必填，加上"*"
                $field['name'] = \App\sign_required($field['is_require'], $field['name']);
                iconv("UTF-8", "GBK", $field['name']);
                $objActSheet->getStyle($excelModel->stringFromColumnIndex($k).'2')->applyFromArray($styleArray);//填充样式
                $objActSheet->setCellValue($excelModel->stringFromColumnIndex($k).'2', $field['name']);

                $k++;
            }
        }
        $max_customer_column = $excelModel->stringFromColumnIndex($k-1);
        $mark_customer = $excelModel->stringFromColumnIndex($k);

        $objActSheet->mergeCells('A1:'.$max_customer_column.'1');
        $objActSheet->getStyle('A1:'.$mark_customer.'1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //水平居中
        $objActSheet->getStyle('A1:'.$mark_customer.'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); //垂直居中
        $objActSheet->getRowDimension(1)->setRowHeight(28); //设置行高
        $objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);
        //给单元格填充背景色
        $objActSheet->getStyle('A1:'.$max_customer_column.'1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('#ff9900');
        $content = $field_list['model_name'].'信息（*代表必填项）';
        $objActSheet->setCellValue('A1', $content);
        $objActSheet->getStyle('A1:'.$max_customer_column.'1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        $objActSheet->getStyle('A1')->getBorders()->getRight()->getColor()->setARGB('#000000');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_end_clean();
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=".$field_list['model_name']."信息导入模板".date('Y-m-d',time()).".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
        exit;
    }

    // 18 推送消息
    public function GetNoticeFollowMsg() {
        $admin_common = new AdminCommon();

        $now_hour = date("H",time());
        if ($now_hour >= 9 && $now_hour <= 21) {
            $start_time = date("Y-m-d H:i:s",time());
            $end_time = date('Y-m-d H:i:s',strtotime("+10 minute"));

            $next_time_where = " AND ( FROM_UNIXTIME( next_time,  '%Y-%m-%d %H:%i:%s' ) between '{$start_time}' AND '{$end_time}' )  ";
            // 查询客户需要跟进提醒的
            $customer_params[':next_time'] = 0;
            $customer_sql = "SELECT f.cid as info_id,f.bid,f.uid,f.next_time,f.cname as title,'customer' as table_name FROM ( SELECT cid,bid,uid,cname,next_time FROM ".$this->prefix."follw WHERE 1 ".$next_time_where." ORDER BY `cid` DESC LIMIT 100 ) f GROUP BY cid";
            $customer_arr = $this->di->follw->queryAll($customer_sql,[]);

            // 代理商
            $agnet_params[':type'] = 0;
            $agnet_params[':next_time'] = 0;
            $agnet_sql = "SELECT f.info_id,'' as bid,f.uid,f.next_time,a.flower_name as title,'agent' as table_name FROM ( SELECT info_id,uid,next_time FROM ".$this->prefix."public_follow WHERE type = :type ".$next_time_where." ORDER BY `info_id` DESC LIMIT 100 ) f LEFT JOIN ".$this->prefix."agent a ON f.info_id = a.id GROUP BY f.info_id";

            $agent_arr = $this->di->public_follow->queryAll($agnet_sql,$agnet_params);

            // 项目方
            $project_params[':type'] = 1;
            $project_params[':next_time'] = 0;
            $project_sql = "SELECT f.info_id,'' as bid,f.uid,f.next_time,p.flower_name as title,'project_side' as table_name FROM ( SELECT info_id,uid,next_time FROM ".$this->prefix."public_follow WHERE type = :type ".$next_time_where." ORDER BY `info_id` DESC LIMIT 100 ) f LEFT JOIN ".$this->prefix."project_side p ON f.info_id = p.id GROUP BY f.info_id";

            $project_arr = $this->di->public_follow->queryAll($project_sql,$project_params);

            // 师资
            $teacher_params[':type'] = 2;
            $teacher_params[':next_time'] = 0;
            $teacher_sql = "SELECT f.info_id,'' as bid,f.uid,f.next_time,t.name as title,'teacher' as table_name FROM ( SELECT info_id,uid,next_time FROM ".$this->prefix."public_follow WHERE type = :type ".$next_time_where." ORDER BY `info_id` DESC LIMIT 100 ) f LEFT JOIN ".$this->prefix."teacher t ON f.info_id = t.id GROUP BY f.info_id";
            $teacher_arr = $this->di->public_follow->queryAll($teacher_sql,$teacher_params);
            $model_name_arr = array("customer"=>"客户","agent"=>"代理商","project_side"=>"一手项目方","teacher"=>"师资");
            $data_arr = array_merge($customer_arr,$agent_arr,$project_arr,$teacher_arr);

            if (!empty($data_arr)) {
                foreach ($data_arr as $key => $value) {
                    // 查询消息ID
                    $msg_id = $this->di->msg->where(array("type"=>1,"info_id"=>$value['info_id'],"table_name"=>$value['table_name'],"action"=>"notice","creatid"=>$value['uid'],"to_uid"=>$value['uid']))->fetchOne("id");
                    $msg_info = "您的".$model_name_arr[$value['table_name']].":".$value['title']."需要跟进！";
                    $is_login = \PhalApi\DI()->session->__get('uid');
                    if ($is_login == $value['uid'] && !empty($msg_info) && $msg_info['status'] == 0) {
                        $admin_common->PushMsgNow($msg_id,$value['uid'],$msg_info,1,$value['table_name'],$value['info_id'],$value['next_time'],0);
                    }
                }
            }
        }
        
        // return $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
    }
  
  	// 19 登录之后推送跟进消息
    public function PushFoloowMsg($uid) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        
        $admin_common = new AdminCommon();
        // $customer_common = new CustomerCommon();

        // $list = $customer_common->GetCustomerNumByLevel($uid);
        $admin_common->GetFollowMsg($uid);
        $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
    	return $rs;
    }

    public function PublicAction($uid) {
        // $customer_list = $this->di->customer->select("c.id,c.cname,c.creatid,cd.groupid,u.structure_id")->alias("c")->leftJoin('customer_data', 'cd', 'c.id = cd.cid')->leftJoin('user', 'u', 'c.creatid = u.id')->fetchAll();
        // $lists = array();
        // foreach ($customer_list as $key => $value) {
        //     $structure_arr = json_decode($value['structure_id']);
        //     $structure_id = array_pop($structure_arr);
        //     if ($structure_id !== $value['groupid']) {
        //         $lists[] = $value;
        //     }
        // }
        /***根据客户最新的部门id更新其他相关表的group_id***/
        /**$user_list = $this->di->user->where("new_group <> 0 AND `status` = 1")->fetchPairs("id","new_group");
        foreach ($user_list as $key => $value) {
            // UPDATE crm_share_join s,crm_user u SET s.groupid = u.new_groupid WHERE s.beshare_uid = u.id
            // UPDATE crm_customer_data cd,crm_customer c,crm_user u SET cd.groupid = u.new_groupid WHERE cd.cid = c.id AND c.creatid = u.id
            //UPDATE crm_teacher t,crm_user u SET t.groupid = u.new_groupid WHERE t.creatid = u.id
        }**/

        /****根据uid查询当前用户的客户数量****/
        /**$user_list = $this->di->user->where(array("is_delete"=>0))->fetchPairs("id","realname");
        $customer_num = array();
        foreach ($user_list as $key => $value) {
            // $sql = "SELECT "
            $num1 = $this->di->customer->where("creatid = {$key} AND sea_type = 0")->count("id");
            $num2 = $this->di->share_join->where("creat_id <> {$key} AND beshare_uid = {$key} AND sea_type = 0")->count("id");
            $num = $num1 + $num2;
            $this->di->user->where(array("id"=>$key))->update(array("getlimit"=>$num));
            // $customer_list = $this->di->customer->select("a.id,a.cname,u.realname")->alias("a")->leftJoin("user","u","a.creatid = u.id")->where("a.creatid = {$uid}")->limit(0,10)->fetchAll();
        }**/
        
        /**$customer_common = new CustomerModel();
        $admin_common = new AdminCommon;
        $phone_list = $this->di->phone->fetchPairs("id","phone");
        $uid  = 11;
        // $user_str = $admin_common->GetMyUserList($uid);
        foreach ($phone_list as $key => $value) {
            // 查询创建人是王丽峰老师的数据
            // $user_sql = "SELECT c.id,c.cname,c.creatid,c.charge_person FROM crm_customer c LEFT JOIN crm_customer_data cd ON c.id = cd.cid WHERE FIND_IN_SET(c.creatid,'{$user_str}') AND FIND_IN_SET('{$value}',CONCAT_WS(',',`cphone`,`cphonetwo`,`cphonethree`))";
            $user_sql = "SELECT c.id,c.cname,c.creatid,c.charge_person FROM crm_customer c LEFT JOIN crm_customer_data cd ON c.id = cd.cid WHERE c.creatid = {$uid} AND FIND_IN_SET('{$value}',CONCAT_WS(',',`cphone`,`cphonetwo`,`cphonethree`)) AND ( UNIX_TIMESTAMP(cd.create_time) between '15`85670400' AND '1587052800' ) ";
            $list = $this->di->customer->queryAll($user_sql,array());
            if (!empty($list)) {
                $lists[] = $list[0];
                // 删除客户
                // $res = $customer_common->PostDeleteCustomer(0,1,$list[0]['id']);
                if ($res['code'] == 1) {
                    $data = array();
                    $data['cid'] = $list[0]['id'];
                    $data['creatid'] = $list[0]['creatid'];
                    $data['charge_person'] = $list[0]['charge_person'];
                    $data['status'] = 1;
                    // $this->di->phone->where(array("phone"=>$value))->update($data);
                }
            }
        }**/
        // var_dump($customer_num);die;
        // $sql = "SELECT uid FROM crm_customer_log  WHERE 1 group BY uid";
        // $list = $this->di->customer_log->queryAll($sql,array());
        // foreach ($list as $key => $value) {
        //     $user_list .= $value['cid'].',';
        // }
        // var_dump($user_list);die;
        
        // 根据取消共享uid查询cid，查询相关数据 
        /**$list = $this->di->customer_log->select("cid,uid")->where(array("uid"=>$uid))->fetchAll();
        $customer_list = array();
        foreach ($list as $key => $value) {
            // 客户表信息
            $customer_list[$value['cid']]['customer'] = $this->di->customer->where(array("id"=>$value['cid']))->fetchOne();
            // 客户data表信息
            $customer_list[$value['cid']]['customer_data'] = $this->di->customer_data->where(array("cid"=>$value['cid']))->fetchOne();
            // share_join 
            $share_join_arr = $this->di->share_join->where(array("cid"=>$value['cid']))->fetchRows();
            $customer_list[$value['cid']]['share_join'] = $share_join_arr;
            $share_data = array();
            foreach ($share_join_arr as $k => $v) {
                $share_data[] = $this->di->share_customer->where(array("id"=>$v["bid"]))->fetchOne();
            }
            $customer_list[$value['cid']]['share_customer'] = $share_data;
        }**/
        
        // $this->di->customer_log->where("id NOT IN '{$id_str}'")->delete();
        // echo "<pre>";
        // print_r($customer_list);
        // exit;

        /**$sql = "SELECT
            a.cid,
            a.cphone,
            group_concat(a.groupid) as grous,
            c.creatid,
            c.charge_person,
            FROM_UNIXTIME(a.create_time,'%Y-%m-%d %H:%i:%s') as addtime
            FROM
            (
            SELECT
                cid,
                cphone,
                groupid,
                create_time
            FROM
                crm_customer_data
            UNION ALL
                SELECT
                    cd.cid,
                    cd.cphone,
                    s.groupid
                    ,cd.create_time
                FROM
                    crm_share_join s
                LEFT JOIN crm_customer_data cd ON s.cid = cd.cid
            ) a
            LEFT JOIN crm_customer c ON a.cid = c.id
            GROUP BY a.cphone
            HAVING ( LENGTH(group_concat(a.groupid)) - LENGTH(REPLACE(group_concat(a.groupid), ',', '')) ) >= 1 order BY a.create_time desc";
        $list = $this->di->customer_data->queryAll($sql,array());
        $lists = array();
        foreach ($list as $key => $value) {
            $customer_list = array();
            $group_arr = explode(",",$value['grous']);
            if (count($group_arr) != count(array_unique($group_arr))) {
                # 有重复部门
                // 获取去掉重复数据的数组   
                $unique_arr = array_unique ( $group_arr );   
                // 获取重复数据的数组   
                $repeat_arr = array_diff_assoc ( $group_arr, $unique_arr );
                $customer_list['cid'] = $value['cid'];
                $customer_list['cphone'] = $value['cphone'];
                $customer_list['group_arr'] = $value['grous'];

                // 组织部门
                
                $repeat_group_str = implode(",",$repeat_arr);
                $customer_list['repeat_group'] = $repeat_group_str;
                
                // 创建人
                $user_info = $this->di->user->where(array("id"=>$value['creatid']))->fetchOne("realname");
                $customer_list['creater'] = $user_info;

                $customer_list['creat_id'] = $value['creatid'];
                $customer_list['charge_person'] = $value['charge_person'];

                // 负责人
                $charge_person_str = "";
                // 负责人处理
                $charge_person_arr = explode(",",trim($value['charge_person'],","));
                foreach ($charge_person_arr as $k => $v) {
                    $username = $this->di->user->where(array("id"=>$v))->fetchOne("realname");
                    $charge_person_str .= $username.",";
                }
                $customer_list['charge_person_str'] = trim($charge_person_str,",");
                $this->di->test_rep->insert($customer_list);
                $lists[] = $customer_list;
            }
        }**/
        // $sql = "SELECT "
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_list,'info'=>$customer_list);
        return $rs;
    }

    // 21 用户登录日志
    public function GetUserLoginLog($newData) {
        $uid = isset($newData['uid']) ? intval($newData['uid']) : 0;
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $order_by = isset($newData['order_by']) && !empty($newData['order_by']) ? $newData['order_by'] : array();
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
         // 高级搜索
        if (!empty($where_arr)) {
            foreach ($where_arr as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] == "" || $value[1] == "") {
                        continue;
                    }
                }
                if (!empty($value) || $value == 0) {
                    if (is_array($value) && is_numeric($value[0])) {
                        $log_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                    } elseif (is_array($value)) {
                        $log_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[0]}' ) ";
                    } elseif (is_numeric($value)) {
                        $log_where .= " AND {$key} = {$value} ";
                    } else {
                        $log_where .= " AND {$key} LIKE '%{$value}%' ";
                    }
                }
            }
        }
        // 查询登录日志
        $log_list = $this->di->dolog->select("id,ip,operation,creat_time")->where("type = 7 AND uid = {$uid} AND DATE_SUB(CURDATE(),INTERVAL 1 WEEK) <= DATE(creat_time)".$log_where)->order("creat_time DESC")->limit($pagenum,$pagesize)->fetchAll();
        
        $address = new Yesapiphpsdk();
        if (!empty($log_list)) {
            // 总数
            $num = $this->di->dolog->select("id,ip,operation,creat_time")->where("type = 7 AND uid = {$uid} AND DATE_SUB(CURDATE(),INTERVAL 1 WEEK) <= DATE(creat_time)")->count('id');
            $data['num'] = $num ? $num : 0;
            foreach ($log_list as $key => $value) {
                // 根据IP获取城市
                $address_arr = $address->request('Ext.IP.GetInfo',array('ip' => $value['ip']));
                $log_list[$key]['city'] = $address_arr['data']['data']['city'];
            }
            $data['log_list'] = !empty($log_list) ? $log_list : array();
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

}
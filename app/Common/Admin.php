<?php
namespace App\Common;
use PhalApi\Model\NotORMModel as NotORM;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * 一些公共处理函数
 */
class Admin extends NotORM
{
    protected $di;
    protected $prefix;
    public function __construct()
    {
        $this->di = \PhalApi\DI()->notorm;
        $this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
    }

    /**
     * 处理并执行SQL
     * @author Wang Junzhe
     * @DateTime 2019-07-26T15:57:13+0800
     * @param    [string]                   $sql [需要处理的SQL]
     * @return   [boor]                     true   [处理结果]
     */
    public function sql_execute($sql)
    {
        $sql = preg_replace("/ENGINE=(InnoDB|MyISAM|MEMORY) DEFAULT CHARSET=([^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=utf8",$sql);
        $sql = str_replace("\r", "\n", $sql);
        $ret = array();
        $num = 0;
        $queriesarray = explode(";\n", trim($sql));
        unset($sql);
        foreach($queriesarray as $query) {
            $ret[$num] = '';
            $queries = explode("\n", trim($query));
            $queries = array_filter($queries);
            foreach($queries as $query) {
                $str1 = substr($query, 0, 1);
                if($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
            }
            $num++;
        }
        if(is_array($ret)) {
            foreach($ret as $sql) {
                if(trim($sql) != '') {
                    $result = $this->di->model->queryAll($sql);
                }
            }

        } else {
            $result = $this->di->model->queryAll($ret);

        }
        return true;
    }

    
    /**
     * 文件导入
     * @author Wang Junzhe
     * @DateTime 2019-07-30T15:35:53+0800
     * @param    [array]                   $filePath  [文件上传路径]
     * @param    [string]                   $table     [插入的数据表]
     * @param    [string]                   $field_arr [字段（多个字段用,链接）]
     * @return   [type]                              [description]
     */
    public function importExcel($filePath,$field_arr) {
        $filename = $filePath['tmp_name'];
        $excel = IOFactory::load($filename);//载入excel文件
        $sheet = $excel->getSheet(0);//读取第一个工作表
        $highestRow = $sheet->getHighestRow();//取得总行数
        $highestColumn = $sheet->getHighestColumn();//取得列数  字母abc...
        $highestColumn = Coordinate::columnIndexFromString($highestColumn);//将列数转换成123...
        //循环导入
        $count = 0;
        for($row = 2; $row <= $highestRow; $row++) {
            for ($column=1; $column <= $highestColumn; $column++) { 
                $value = $sheet->getCellByColumnAndRow($column, $row)->getValue();
                if (!empty($value)) {
                    $excelarr[$row][$column] = $value;
                }
            }
        }
        return $excelarr;
    }

    /**
     * 单文件上传
     * @param $files array 图片资源
     * @param $dir string 图片保存路径
     * @return array
     */
    public function SingleFileUpload($files, $dir)
    {
        if (is_array($files) && !empty($files)) {
            //先判断目录是否存在，不存在这创建
            $rootpath = $_SERVER['DOCUMENT_ROOT'];
            if (!file_exists($rootpath . $dir)) {
                mkdir($rootpath . $dir, 0777, true);
            }
            //获取文件临时存放目录
            $tmp_name = $files['tmp_name'];
            //获取文件名称
            $file_name = $files['name'];
            //获取文件扩展名
            $ext = substr(strrchr($file_name, '.'), 1);
            //上传文件重命名
            $filename = date("YmdHis") . rand(1000, 9999) . "." . $ext;
            //保存路径
            $save_dir = $rootpath . $dir . $filename;
            if (move_uploaded_file($tmp_name, $save_dir)) {
                $data = $dir . $filename;
                return $data;
            }
        }
    }

    /**
     * 多文件上传函数
     * @param $files array 图片资源
     * @param $dir string 图片保存路径
     * @return array
     */
    public function MultiFileUploads($files, $dir)
    {
        if (is_array($files) && !empty($files)) {
            //先判断目录是否存在，不存在这创建
            $rootpath = $_SERVER['DOCUMENT_ROOT'];
            if (!file_exists($rootpath . $dir)) {
                mkdir($rootpath . $dir, 0777, true);
            }
            $num = count($files);

            for ($i = 1; $i <= $num; $i++) {
                $j = "pic" . $i;
                $file_name = $files[$j]['name'];
                $tmp_name = $files[$j]['tmp_name'];
                //获取文件扩展名
                $ext = substr(strrchr($file_name, '.'), 1);
                //上传文件重命名
                $filename = date("YmdHis") . rand(1000, 9999) . "." . $ext;
                //保存路径
                $save_dir = $rootpath . $dir . $filename;

                if (move_uploaded_file($tmp_name, $save_dir)) {
                    $com_pic_url[] = $dir . $filename;
                }
            }
            $data = array();
            $data['com_pic_url'] = $com_pic_url;
            return $data;
        }
    }

    /**
     * 基础数据与灵活字段合并
     * @author Wang Junzhe
     * @DateTime 2019-09-05T15:06:42+0800
     * @param    [array]                   $list      [原始数组]
     * @param    [array]                   $field_arr [灵活字段数组]
     */
    public function GetFieldListArr($list,$field_arr) {
        if (is_array($field_arr) && !empty($field_arr)) {
            foreach ($field_arr as $key => $value) {
                if (is_array($value)) {
                    $field_arr[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
                }
            }
            if (array_key_exists('field_list',$list)) {
                unset($list['field_list']);
            }
            $data_list = array_merge($list,$field_arr);
        } else {
            $data_list = $list;
        }
        return $data_list;
    }

    /**
     * 发送消息
     * @author Wang Junzhe
     * @DateTime 2019-10-11T14:05:31+0800
     * @param    [int]                      $uid     发送人uid
     * @param    [int]                      $to_uid  接收人uid
     * @param    [string]                   $title   标题
     * @param    [string]                   $content 内容
     * @param    [int]                      $type    消息类型
     * @param    [string]                   $action   操作分类
     * @param    [string]                   $table_name   操作表
     * 
     */
    public function SendMsgNow($uid,$to_uid,$title,$content,$type,$action,$table_name,$info_id) {
        $type_arr = array('系统通知','提醒消息');
        
        $msg_data = array(
            "type" => $type,
            "type_name" => $type_arr[$type],
            "title" => $title,
            "content" => $content,
            "creatid" => $uid,
            "to_uid" => $to_uid,
            "addtime" => time(),
            "notice_num" => 0,
            "status" => 0,
            "action" => $action,
            "table_name" => $table_name,
            "info_id" => $info_id
        );
        $res = $this->di->msg->insert($msg_data);
        $msg_id = $this->di->msg->insert_id();
        if ($msg_id && $type != 1) {
            $this->PushMsgNow($msg_id,$to_uid,$title,0,$table_name,$info_id,time(),0);
        }
        return $msg_id;
    }

    /**
     * 推送即时消息
     * @author Wang Junzhe
     * @DateTime 2019-10-09T15:10:18+0800
     * @param    [int]                      $to_uid 用户ID
     * @param    [string]                   $msg 消息内容
     */
    public function PushMsgNow($msg_id,$to_uid,$msg,$type,$table_name,$info_id,$next_follow,$status) {
        // 判断当前用户是否登录
        // 推送的url地址，使用自己的服务器地址
        $push_api_url = \PhalApi\DI()->config->get('common.PUSH_API_URL');
        $msg_arr = array(
            "to" => $to_uid, 
            "msg_id" => $msg_id,
            "msg" => $msg,
            "type" => $type,
            "table_name" => $table_name,
            "info_id" => $info_id,
            "status" => $status,
            "next_follow" => $next_follow
        );
        $msg_str = json_encode($msg_arr);
        $post_data = array(
           "type" => "publish",
           "content" => $msg_str,
           "to" => $to_uid, 
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $push_api_url );
        curl_setopt($ch, CURLOPT_POST, 1 );
        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8")); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        $return = curl_exec($ch);
        curl_close($ch);
        if ($return) {
            $code = 1;
        } else {
            $code = 0;
        }
        return $code;
    }

    
    /**
     * excel数据处理
     * @author Wang Junzhe
     * @DateTime 2019-11-01T16:31:44+0800
     * @param    integer                   $k                    [行号]
     * @param    [array]                   $fieldArr             [字段列表]
     * @param    [type]                    $fieldName            [当前字段名称]
     * @param    [type]                    $info                 [当前数据]
     * @param    [type]                    $model_field_name_arr [灵活字段列表]
     * @param    [type]                    $type                 [类型(区分表)]
     * @return   [array]                   $res                  [处理好的数据类型]
     */
    public function sheetData($k = 0, $fieldArr, $fieldName, $info, $model_field_name_arr,$type) {
        if ($info) {
            if ($fieldArr[$fieldName]['type'] == 'address') {
                $address = array();
                for ($i=0; $i<3; $i++) {
                    $address[] = $val[$k];
                    $k++;
                }
                $data[$fieldArr[$fieldName]['name']] =  implode(chr(10), $address);
            } elseif ($fieldArr[$fieldName]['type'] == 'sql') {
                if (strpos($info,'_')!==false) {
                    $info_arr = explode('_',$info);
                    if ($fieldArr[$fieldName]['name'] == "agent" && $type == 'customer') {
                        $data['agent_name'] = $info_arr[0];
                        $data['agent_num'] = $info_arr[1];
                    } else {
                        $data[$fieldArr[$fieldName]['name']] = $type == 'customer' ? $info_arr[1] : $info_arr[0];
                    } 
                } else {
                    // 根据数据匹配
                    $table_name = $fieldArr[$fieldName]['from_table'];
                    $field = $fieldArr[$fieldName]['field'];
                    if ($fieldArr[$fieldName]['name'] == "agent" && $type == 'customer') {
                        $data_info = $this->di->$table_name->select("flower_name,number")->where(array($field=>$info))->fetchOne();
                        $data['agent_name'] = $data_info['flower_name'];
                        $data['agent_num'] = $data_info['number'];

                    } else {
                        $data_info = $this->di->$table_name->where(array($field=>$info))->fetchOne($field);
                        $data[$fieldArr[$fieldName]['name']] = !empty($data_info) ? $data_info : "";
                    }
                    
                }
                
                $k++;
            } elseif ($fieldArr[$fieldName]['type'] == 'date') {
                $data[$fieldArr[$fieldName]['name']] = $info ? date('Y-m-d',strtotime($info)) : '';
                $k++;
            } elseif ($fieldArr[$fieldName]['type'] == 'datetime') {
                $data[$fieldArr[$fieldName]['name']] = $info ? strtotime($info) : 0;
                $k++;
            } elseif (!in_array($fieldName,$model_field_name_arr) && $fieldArr[$fieldName]['type'] == 'radio' ) {
                // 非自定义字段
                $setting = $fieldArr[$fieldName]['setting'];
                foreach ($setting as $sk => $sv) {
                    if ($info == $sv) {
                        $setting_value = $sk;
                        break;
                    }
                }
                $data[$fieldArr[$fieldName]['name']] = $setting_value ? $setting_value : 0;
                $k++;
            } elseif (in_array($fieldName,$model_field_name_arr) && ($fieldArr[$fieldName]['type'] == 'radio' || $fieldArr[$fieldName]['type'] == 'select') ) {
                $data[$fieldArr[$fieldName]['name']] = strval($info);
                $k++;
            } elseif (in_array($fieldName,$model_field_name_arr) && $fieldArr[$fieldName]['type'] == 'checkbox') {
                $info_arr = explode(',',$info);
                $data[$fieldArr[$fieldName]['name']] = json_encode($info_arr,JSON_UNESCAPED_UNICODE);
                $k++;
            }else {   
                $data[$fieldArr[$fieldName]['name']] = strval($info) ? : '';
                $k++;
            }                       
        } else {
            if (!in_array($fieldName,$model_field_name_arr) && $fieldArr[$fieldName]['type'] == 'radio' ) {
                # 非自定义字段
                $setting = $fieldArr[$fieldName]['setting'];
                $data[$fieldArr[$fieldName]['name']] = $setting[0];
            } elseif ($fieldArr[$fieldName]['type'] == 'price' || $fieldArr[$fieldName]['type'] == 'datetime') {
                $data[$fieldArr[$fieldName]['name']] = 0;
            } else {
                $data[$fieldArr[$fieldName]['name']] = '';
            }
            $k++;   
        }
        $res['data'] = $data;
        $res['k'] = $k;
        return $res;
    }

    //二维数组转一维数组
    public function changeArr($arr)
    {
        $newArr = [];
        foreach ($arr as $v) {
            if ($v && is_array($v)) {
                $newArr = array_merge($newArr,$v);
            } else {
                continue;
            }
        }
        return $newArr;
    }

    /**
     * 根据部门ID查询部门名称
     * @author Wang Junzhe
     * @DateTime 2019-12-18T13:54:56+0800
     * @param    [int]                   $struct_id [部门id]
     */
    public function GetStructNameById($struct_id) {
        if (empty($struct_id)) {
            return false;
        }
        $sql = "SELECT CONCAT_WS('/',s.name,c.name) as group_name FROM ".$this->prefix."structure c LEFT JOIN ".$this->prefix."structure s ON c.pid = s.id WHERE c.id = ".$struct_id;
        $struct_name_arr = $this->di->structure->queryAll($sql, array());
        $struct_name = !empty($struct_name_arr[0]['group_name']) ? $struct_name_arr[0]['group_name'] : "";
        return $struct_name;
    }

    /**
     * 用户登录消息推送
     * @author Wang Junzhe
     * @DateTime 2019-12-27T10:36:06+0800
     * @param    [itn]                   $uid [用户ID]
     */
    public function GetFollowMsg($uid) {
        $start_time = date("Y-m-d 21:00:00",strtotime("-1 day"));
        $end_time = date('Y-m-d 8:59:59',time());
        $next_time_where = " AND ( FROM_UNIXTIME( next_time,  '%Y-%m-%d %H:%i:%s' ) between '{$start_time}' AND '{$end_time}' )  ";
        // 查询客户需要跟进提醒的
        $customer_sql = "SELECT f.cid as info_id,f.bid,f.uid,f.next_time,f.cname as title,'customer' as table_name FROM ( SELECT cid,bid,uid,cname,next_time FROM ".$this->prefix."follw WHERE uid = {$uid} ".$next_time_where." ORDER BY `cid` DESC LIMIT 100 ) f GROUP BY info_id";
        $customer_arr = $this->di->follw->queryAll($customer_sql,array());

        // 代理商
        $agnet_params[':type'] = 0;
        $agnet_sql = "SELECT f.info_id,'' as bid,f.uid,f.next_time,a.flower_name as title,'agent' as table_name FROM ( SELECT info_id,uid,next_time FROM ".$this->prefix."public_follow WHERE type = :type AND uid = {$uid} ".$next_time_where." ORDER BY `info_id` DESC LIMIT 100 ) f LEFT JOIN ".$this->prefix."agent a ON f.info_id = a.id GROUP BY f.info_id";
        $agent_arr = $this->di->public_follow->queryAll($agnet_sql,$agnet_params);

        // 项目方
        $project_params[':type'] = 1;
        $project_sql = "SELECT f.info_id,'' as bid,f.uid,f.next_time,p.flower_name as title,'project_side' as table_name FROM ( SELECT info_id,uid,next_time FROM ".$this->prefix."public_follow WHERE type = :type AND uid = {$uid} ".$next_time_where." ORDER BY `info_id` DESC LIMIT 100 ) f LEFT JOIN ".$this->prefix."project_side p ON f.info_id = p.id GROUP BY f.info_id";
        $project_arr = $this->di->public_follow->queryAll($project_sql,$project_params);

        // 师资
        $teacher_params[':type'] = 2;
        $teacher_sql = "SELECT f.info_id,'' as bid,f.uid,f.next_time,t.name as title,'teacher' as table_name FROM ( SELECT info_id,uid,next_time FROM ".$this->prefix."public_follow WHERE type = :type AND uid = {$uid} ".$next_time_where." ORDER BY `info_id` DESC LIMIT 100 ) f LEFT JOIN ".$this->prefix."teacher t ON f.info_id = t.id GROUP BY f.info_id";

        $teacher_arr = $this->di->public_follow->queryAll($teacher_sql,$teacher_params);
        $model_name_arr = array("customer"=>"客户","agent"=>"代理商","project_side"=>"一手项目方","teacher"=>"师资");
        $data_arr = array_merge($customer_arr,$agent_arr,$project_arr,$teacher_arr);
        // 判断当前用户是否登录
        $is_login = \PhalApi\DI()->session->__get('uid');
        if (!empty($data_arr)) {
            foreach ($data_arr as $key => $value) {
                // 查询消息ID
                $msg_info = $this->di->msg->select("id,status")->where(array("type"=>1,"info_id"=>$value['info_id'],"table_name"=>$value['table_name'],"action"=>"notice","creatid"=>$value['uid'],"to_uid"=>$value['uid']))->fetchOne();
                if ($is_login == $value['uid'] && !empty($msg_info) && $msg_info['status'] == 0) {
                    $msg_content = "您的".$model_name_arr[$value['table_name']].":".$value['title']."需要跟进！";
                    $this->PushMsgNow($msg_info['id'],$value['uid'],$msg_content,1,$value['table_name'],$value['info_id'],$value['next_time'],0);
                }
            }
        }
        
        // return $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
    }
	
    /**
     * 获取领导所管辖部门
     * @author Wang Junzhe
     * @DateTime 2020-03-19T11:42:08+0800
     * @param    [int]                   $structure_id [当前用户部门id]
     * @param    [int]                   $is_leader    [角色1主管2经理]
     * @param    [int]                   $parent_id    [其他管辖部门]
     * 
     */
    public function GetUserChargeStruct($structure_id,$is_leader,$parent_id) {
        $user_structure_str = "";
        if ($is_leader == 1 && empty($parent_id)) {
            // 部门主管&&没有管理其他部门
            $user_structure_arr[] = array_pop($structure_id);
        } else {
            // 经理或其他
            $structure_arr = $this->di->structure->where(array("pid"=>$structure_id[0]))->fetchPairs("id","name");
            if (!empty($structure_arr)) {
                $user_structure_arr = array_keys($structure_arr);
            } else {
                $user_structure_arr[] = $structure_id[0];
            }
            if (!empty($parent_id)) {
                $parent_struct_arr = explode(',',trim($parent_id,","));
                $user_structure_arr = array_merge($user_structure_arr,$parent_struct_arr);
            }
        }
        return $user_structure_arr;
    }

    
    /**
     * 查询部门下级员工
     * @author Wang Junzhe
     * @DateTime 2020-04-15T16:07:43+0800
     * @param    [int]                   $uid [用户id]
     */
    public function GetMyUserList($uid) {
        $user_info = $this->di->user->select("is_leader,structure_id")->where("id",$uid)->fetchOne();
        if ($user_info['is_leader'] == 2) {
            # 领导
            $structure_id = json_decode($user_info['structure_id']);
            $sql = "SELECT id FROM crm_user WHERE JSON_CONTAINS(structure_id, '[".'"'.$structure_id[0].'"'."]')";
            $list = $this->di->user->queryAll($sql,array());
            $user_list = array_column($list, "id");
            $user_str = implode(",",$user_list);
        } else {
            $user_str = "";
        }
        return $user_str;
    }

    /**
     * 查询所管辖部门
     * @author Wang Junzhe
     * @DateTime 2020-04-17T09:24:38+0800
     * @param    [int]                   $is_leader      [领导级别]1主管2经理
     * @param    [array]                 $user_structure [description]
     * @param    [int]                   $parent_id      [description]
     */
    public function GetMyStructure($is_leader,$user_structure,$parent_id) {
        $user_structure_str = "";
        switch ($is_leader) {
            case 1:
                # 主管
                $user_structure_id = array_pop($user_structure);
                $user_structure_str .= $user_structure_id;
                if (!empty($parent_id)) {
                    $structure_str = $parent_id;
                    $structure_arr = $this->di->structure->where("FIND_IN_SET(pid, '{$structure_str}')")->fetchPairs("id","name");
                    $user_structure_arr = array_keys($structure_arr);
                    $user_structure_str .= ','.implode(",",$user_structure_arr);
                }
                break;
            case 2:
                # 经理
                if (count($user_structure) == 1) {
                    $num = 0;
                } else {
                    $num = count($user_structure) - 2;
                }
                $structure_str = $user_structure[$num].",";
                $user_structure_str .= $structure_str;
                if (!empty($parent_id)) {
                    $structure_str .= $parent_id;
                    $user_structure_str .= $structure_str;
                }
                $structure_arr = $this->di->structure->where("FIND_IN_SET(pid, '{$structure_str}')")->fetchPairs("id","name");
                $user_structure_arr = array_keys($structure_arr);
                $user_structure_str .= implode(",",$user_structure_arr);
                break;
            case 3:
                # 非主管经理管辖其他部门
                if (!empty($parent_id)) {
                    $structure_str = $parent_id;
                    $structure_arr = $this->di->structure->where("FIND_IN_SET(pid, '{$structure_str}')")->fetchPairs("id","name");
                    $user_structure_arr = array_keys($structure_arr);
                    $user_structure_str .= implode(",",$user_structure_arr);
                    $user_structure_str .= $structure_str;
                } else {
                    $user_structure_str = $user_structure[0];
                }
                break;
            default:
                # 平民
                break;
        }
        return $user_structure_str;
    }
}
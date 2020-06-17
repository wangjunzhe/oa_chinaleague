<?php
namespace App;

function hello() {
    return 'Hey, man~';
}
/**
 * 字符串转换数组（以逗号隔开）
 * @param
 * @author Michael_xu
 * @return
 */
function stringToArray($string)
{
    if (is_array($string)) {
        $data_arr = array_unique(array_filter($string));
    } else {
        $data_arr = $string ? array_unique(array_filter(explode(',', $string))) : [];
    }
    $data_arr = $data_arr ? array_merge($data_arr) : [];
    return $data_arr ? : [];
}

/**
 * 获取redis指定键值
 * @author Dai Ming
 * @DateTime 13:38 2020/4/24 0024
 * @desc    （未完成）（已完成）
 * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
 * @return int code 操作码，1表示成功，其他都表示失败
 * @return array data 接口请求成功以后返回的数据
 *
 * @return string msg 提示信息
 */
function Getkey($uid,$keys)
{
    $data=\PhalApi\DI()->redis->get_forever($uid,'userinfo');
    $userinfo=\PhalApi\DI()->notorm->user->where('id',$uid)->fetchOne();
    $structure_id = json_decode($userinfo['structure_id']);
    $user_structure_id = end($structure_id);
    $string=['share','capacity'];
    if(is_array($keys)){
        foreach ($keys as $k){
            if(@array_key_exists($k,$data)){
                if(in_array($k,$string)){
                    $newdata[$k]=\PhalApi\DI()->notorm->structure->where('id',$user_structure_id)->fetchOne($k);
                }else{
                    $newdata[$k]=$data[$k];
                }

            }else{

                if(in_array($k,$string)){
                   $newdata[$k]=\PhalApi\DI()->notorm->structure->where('id',$user_structure_id)->fetchOne($k);
                }else{
                   $newdata[$k]=\PhalApi\DI()->notorm->user->where('id',$uid)->fetchOne($k);
                }
            }
        }

        return $newdata;
    }else{
        if(@array_key_exists($keys,$data)){
            if(in_array($data,$string)){
                $newdata=\PhalApi\DI()->notorm->structure->where('id',$user_structure_id)->fetchOne('share');
            }else{
                $newdata=$data[$keys];
            }
            return $newdata;
        }else{
            $user= \PhalApi\DI()->notorm->user->where('id',$uid)->fetchOne($keys);
            if(empty($user)){
                $user= \PhalApi\DI()->notorm->structure->where('id',$user_structure_id)->fetchOne($keys);
            }
            return $user;
        }
    }

}
/*
 *
 * */

function filter_array($arr, $values = ['', null, false, 0, '0',[]]) {
    foreach ($arr as $k => $v) {
        if (is_array($v) && count($v)>0) {
            if(empty($v['cid'])){
                unset($arr);
                return [];
            }else{
                return $arr;
            }
        }

    }

}
/**
 * 退出登录
 */
function LoginOut($uid){
    \PhalApi\DI()->session->destroy();
    $newdata=array(
        'uid'=>$uid,
        'type'=>8,
        'ip'=>\PhalApi\Tool::getClientIp(),
        'operation'=>'退出登录',
        'sqldata'=>'成功',
        'contont'=>'手动退出登录成功',
        'title'=>'退出登录',
        'creat_time'=>date('Y-m-d H:i:s',time()),
    );
    \PhalApi\DI()->notorm->dolog->insert($newdata);
    \PhalApi\DI()->redis->del($uid,'userinfo');
}
/**
 * 数组转换字符串（以逗号隔开）
 * @param
 * @author Michael_xu
 * @return
 */
function arrayToString($array)
{
    if (!is_array($array)) {
        $data_arr[] = $array;
    } else {
        $data_arr = $array;
    }
    $data_arr = array_filter($data_arr); //数组去空
    $data_arr = array_unique($data_arr); //数组去重
    $data_arr = array_merge($data_arr);
    $string = $data_arr ? ','.implode(',', $data_arr).',' : '';
    return $string ? : '';
}
//**判断部门身份
function GetStruMedel($id){
    $str_array=array('0'=>"未设置",'1'=>"中台",'2'=>"前台",'3'=>"渠道",'4'=>"后台");
    $stru=\PhalApi\DI()->notorm->structure->select('capacity,name')->where('id',$id)->fetchOne();
    $struc_name=$stru['name'].'-'.$str_array[$stru['capacity']];
    return $struc_name;

}

/**
 * 二维数组转换字符串（以逗号隔开）
 * @param
 * @author Michael_xu
 * @return
 */
function arr2str ($arr)
{
    foreach ($arr as $v)
    {
        $v = join(",",$v); //可以用implode将一维数组转换为用逗号连接的字符串
        $temp[] = $v;
    }
    $t="";
    foreach($temp as $v){
        $t.=$v.",";
    }
    $t=substr($t,0,-1);
    return $t;
}
/*取得文件后缀*/
function getExtension($filename){
    $mytext = substr($filename, strrpos($filename, '.')+1);
    return $mytext;
}

/**
 * 解析获取php.ini 的upload_max_filesize（单位：byte）
 * @param $dec int 小数位数
 * @return float （单位：byte）
 * */
function get_upload_max_filesize_byte($dec=2){
    $max_size=ini_get('upload_max_filesize');
    preg_match('/(^[0-9\.]+)(\w+)/',$max_size,$info);
    $size=$info[1];
    $suffix=strtoupper($info[2]);
    $a = array_flip(array("B", "KB", "MB", "GB", "TB", "PB"));
    $b = array_flip(array("B", "K", "M", "G", "T", "P"));
    $pos = $a[$suffix]&&$a[$suffix]!==0?$a[$suffix]:$b[$suffix];
    return round($size*pow(1024,$pos),$dec);
}
/**
 * 处理 字符串转数组  入库
 * @author zhi
 * @param  [type] $data 字符串
 * @return [type] $setting  转数组后
 */
function setting($data)
{
    $setting = 'array(';
    $i = 0;
    $options = explode(' ',$data);
    $s = array();
    foreach($options as $v){
        $v = trim(str_replace(chr(13),'',trim($v)));
        if($v != '' && !in_array($v ,$s)){
            $setting .= "$i=>'$v',";
            $i++;
            $s[] = $v;
        }
    }
    return $setting = substr($setting,0,strlen($setting) -1 ) .')';
}
/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }
    return $obj;
}

/**
 * 数组 转 对象
 *
 * @param array $arr 数组
 * @return object
 */
function array_to_object($arr) {
    if (gettype($arr) != 'array') {
        return;
    }
    foreach ($arr as $k => $v) {
        if (gettype($v) == 'array' || getType($v) == 'object') {
            $arr[$k] = (object)array_to_object($v);
        }
    }
    return (object)$arr;
}

/**
 * 返回对象
 * @param $array 响应数据
 */
function resultArray($array)
{
    if (!empty($data)) {
        $rs = array('code'=>1,'msg'=>'000000','data'=>$array,'info'=>$array);
    } else {
        $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());

    }
    return $rs;
}

/**
 * 调试方法
 * @param  array   $data  [description]
 */
function p($data,$die=1)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    if ($die) die;
}

/**
 * 用户密码加密方法
 * @param  string $str      加密的字符串
 * @param  [type] $auth_key 加密符
 * @param  [string] $username 用户名
 * @return string           加密后长度为32的字符串
 */
function user_md5($str, $auth_key = '', $username = '')
{
    return '' === $str ? '' : md5(sha1($str) . md5($str.$auth_key));
}

/**
 * 多维数组根据某一个键值排序
 * @author Wang Junzhe
 * @DateTime 2019-08-29T10:31:06+0800
 * @param    [array]                  $datalist  [要排序的数组]
 * @param    [string]                 $field     [description]
 * @param    string                   $sort_type [description]
 * @return   [array]                  $list      [description]
 */
function sortArrayByfield($datalist,$field)
{
    $last_names = array_column($datalist,$field);
    array_multisort($last_names,SORT_ASC,$datalist);
    return $datalist;
}
/**
 * 生成唯一订单号如果位数不够的话可以进行修改增加订单号位数
 * @return string
 */
function CreateOrderNo()
{
    mt_srand((double)microtime() * 1000000);
    return date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * 获取首字母
 * @param string $str 汉字字符串
 * @return string 首字母
 */
function getFirstCharter($str)
{
    if (empty($str)) {
        return '';
    }
    $fchar = ord($str{0});
    if ($fchar >= ord('A') && $fchar <= ord('z'))
        return strtoupper($str{0});
    $s1 = iconv('UTF-8', 'gb2312', $str);
    $s2 = iconv('gb2312', 'UTF-8', $s1);
    $s = $s2 == $str ? $s1 : $str;
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;

    if ($asc >= -20319 && $asc <= -20284){
        return 'A';
    }
    if ($asc >= -20283 && $asc <= -19776){
        return 'B';
    }
    if ($asc >= -19775 && $asc <= -19219){
        return 'C';
    }
    if ($asc >= -19218 && $asc <= -18711){
        return 'D';
    }
    if ($asc >= -18710 && $asc <= -18527){
        return 'E';
    }
    if ($asc >= -18526 && $asc <= -18240){
        return 'F';
    }
    if ($asc >= -18239 && $asc <= -17923){
        return 'G';
    }
    if ($asc >= -17922 && $asc <= -17418){
        return 'H';
    }
    if ($asc >= -17417 && $asc <= -16475){
        return 'J';
    }
    if ($asc >= -16474 && $asc <= -16213){
        return 'K';
    }
    if ($asc >= -16212 && $asc <= -15641){
        return 'L';
    }
    if ($asc >= -15640 && $asc <= -15166){
        return 'M';
    }
    if ($asc >= -15165 && $asc <= -14923){
        return 'N';
    }
    if ($asc >= -14922 && $asc <= -14915){
        return 'O';
    }
    if ($asc >= -14914 && $asc <= -14631){
        return 'P';
    }
    if ($asc >= -14630 && $asc <= -14150){
        return 'Q';
    }
    if ($asc >= -14149 && $asc <= -14091){
        return 'R';
    }
    if ($asc >= -14090 && $asc <= -13319){
        return 'S';
    }
    if ($asc >= -13318 && $asc <= -12839){
        return 'T';
    }
    if ($asc >= -12838 && $asc <= -12557){
        return 'W';
    }
    if ($asc >= -12556 && $asc <= -11848){
        return 'X';
    }
    if ($asc >= -11847 && $asc <= -11056){
        return 'Y';
    }
    if ($asc >= -11055 && $asc <= -10247){
        return 'Z';
    }
    return null;
}
//查询部门名称
function  getgroup($id){

    $name=\PhalApi\DI()->notorm->structure->select('name')->where('id',$id)->fetchOne();
    return $name['name'];
}
function Cus_log($uid,$cid,$type,$action,$note) {
    // 当前IP
    $ip = \PhalApi\Tool::getClientIp();
    $action_data = array(
        'uid' => $uid,
        'cid' => $cid,
        'type' => $type,
        'action' => $action,
        'note' => $note,
        'addtime' => time(),
        'updatetime' => time(),
        'ip' => $ip,
    );
    $action = \PhalApi\DI()->notorm->customer_log->insert($action_data);
    return $action;
}

function AddShareLog($id,$share_uid,$beshare_uid,$type,$action,$note) {
    if (empty($id) || empty($share_uid) || empty($beshare_uid)) {
        return false;
    }
    $ip = \PhalApi\Tool::getClientIp();
    $share_log_data = array(
        'info_id' => $id,
        'share_uid' => $share_uid,
        'beshare_uid' => $beshare_uid,
        'type' => $type,
        'action' => $action,
        'note' => $note,
        'addtime' => time(),
        'ip' => $ip
    );
    $action = \PhalApi\DI()->notorm->share_log->insert($share_log_data);
    return $action;
}
//查询人员信息
function GetFiledInfo($tavle,$title,$id){
    $list=$action = \PhalApi\DI()->notorm->$tavle->select($title)->where('id',$id)->fetchOne($title);
    return $list;
}
//查询人员信息
function GetFiledInfo2($tavle,$title,$id){
    $list=$action = \PhalApi\DI()->notorm->$tavle->select($title)->where('contract_id',$id)->fetchOne($title);
    return $list;
}
//查询数据字段
function GetFild($tavle,$type,$value,$filed){
    $list= \PhalApi\DI()->notorm->$tavle->where($type,$value)->fetchOne($filed);
    return $list;
}
function get_weeks($time = '', $format='Y-m-d'){
    $time = $time != '' ? $time : time();
    //获取当前周几
    $week = date('w', $time);
    $date = [];
    for ($i=1; $i<=7; $i++){
        $date[$i] = date($format ,strtotime( '+' . $i-$week .' days', $time));
    }
    return $date;

}
function getMonth($time = '', $format='Y-m-d'){
    $time = $time != '' ? $time : time();
    //获取当前周几
    $week = date('d', $time);
    $date = [];
    for ($i=1; $i<= date('t', $time); $i++){
        $date[$i] = date($format ,strtotime( '+' . $i-$week .' days', $time));
    }
    return $date;
}
//function GetMonth($sign="1")
//{
//    //得到系统的年月
//    $tmp_date=date("Ym");
//    //切割出年份
//    $tmp_year=substr($tmp_date,0,4);
//    //切割出月份
//    $tmp_mon =substr($tmp_date,4,2);
//    $tmp_nextmonth=mktime(0,0,0,$tmp_mon+1,1,$tmp_year);
//    $tmp_forwardmonth=mktime(0,0,0,$tmp_mon-1,1,$tmp_year);
//    if($sign==0){
//        //得到当前月的下一个月
//        return $fm_next_month=date("Ym",$tmp_nextmonth);
//    }else{
//        //得到当前月的上一个月
//        return $fm_forward_month=date("Ym",$tmp_forwardmonth);
//    }
//}

function array_sort($array,$keys,$type='desc'){

//$array为要排序的数组,$keys为要用来排序的键名,$type默认为升序排序

    $keysvalue = $new_array = array();

    foreach ($array as $k=>$v){

        $keysvalue[$k] = $v[$keys];

    }

    if($type == 'desc'){

        asort($keysvalue);

    }else{

        arsort($keysvalue);

    }

    reset($keysvalue);

    foreach ($keysvalue as $k=>$v){

        $new_array[$k] = $array[$k];

    }

    return $new_array;

}
function Getyear($y="",$m=""){

    if($y=="") $y=date("Y");
    if($m=="") $m=date("m");

    $y=str_pad(intval($y),4,"0",STR_PAD_RIGHT);
    $m>12||$m<1?$m=1:$m=$m;
    for($i=1;$i<=$m;$i++){
        $i=sprintf("%02d",intval($i));
        $firstday=strtotime($y.$i."01000000");
        $firstdaystr=date("Y-m-01",$firstday);
        $lastday = strtotime(date('Y-m-d 23:59:59', strtotime("$firstdaystr +1 month -1 day")));
        $year[$i]=array(
            'firstday'=>$firstday,
            'lastday'=>$lastday
        );
    }
    return $year;



}

/**
 * 根据生日计算年龄
 * @author Wang Junzhe
 * @DateTime 2019-10-21T16:40:46+0800
 * @param    [string]                   $birthday [生日时间戳]
 * @return   [int]                      $age      [年龄]
 */
function getAge($birthday) {
    $byear = date('Y',$birthday);
    $bmonth = date('m',$birthday);
    $bday = date('d',$birthday);

    // 格式化当前年月日
    $tyear = date('Y');
    $tmonth = date('m');
    $tday = date('d');

    // 计算年龄
    $age = $tyear - $byear;
    if ($bmonth>$tmonth || $bmonth==$tmonth && $bday>$tday) {
        $age--;
    }
    return $age;
}

/**
 * 检查该字段若必填，加上"*"
 * @param is_null     是否为空 0否  1是
 * @param name 字段名称
 **/
function sign_required($is_null, $name){
    if ($is_null == 1) {
        return '*'.$name;
    } else {
        return $name;
    }
}

/**
 * 字符串截取
 * @author Wang Junzhe
 * @DateTime 2019-11-20T10:10:23+0800
 * @param    [string]                   $str   [要截取的字符串]
 * @param    [int]                      $start [前面保留下标]
 * @param    [int]                      $end   [后面截取下标]
 * @return   [string]                          [description]
 */
function string_substr($str,$start,$end){
    //保留前三位和后三位
    if ($start == 0) {
        $new_str = '***'.substr($str, strlen($str)-$end, strlen($str));
    } else {
        $new_str = substr($str, 0, $start) . '*****' . substr($str, $end, strlen($str));
    }

    return $new_str;
}

function GetPhoneadress($phone){
    $apiurl = 'http://mobsec-dianhua.baidu.com/dianhua_api/open/location?tel='.$phone;
//    $params = array(
//        'key' => '61965c05735f657f1d685cac247c4f8e', //您申请的手机号码归属地查询接口的appkey
//        'phone' => $phone //要查询的手机号码
//    );
//    $paramsString = http_build_query($params);
    $content = @file_get_contents($apiurl);
    $result = json_decode($content,true);

    return $result['response'][$phone]['location'];

}
function GetPhoneType($group_id){
    $type = \PhalApi\DI()->notorm->group->where('FIND_IN_SET(90,rules)')->and('id',$group_id)->fetchOne('id');
    if($type){
        return true;
    }else{
        return false;
    }
}

/**
 *  系统日志添加
 * @author Dai Ming
 * @DateTime 14:33 2019/10/29
 * @desc    （未完成）（已完成）
 * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误[1添加2编辑3删除4导出5导入6置顶7登录]
 * @return int code 操作码，1表示成功，其他都表示失败
 * @return array data 接口请求成功以后返回的数据
 *
 * @return string msg 提示信息
 */
/**
 * 操作日志
 * @author Wang Junzhe
 * @DateTime 2019-10-30T15:43:50+0800
 * @param    [itn]                    $uid       [用户ID]
 * @param    [int]                    $type      [类型1添加2编辑3删除4导出5导入6置顶7登录]
 * @param    [string]                 $operation [日志描述]
 * @param    [string]                 $sqldata   [sql语句]
 * @param    [string]                 $contont   [内容]
 * @param    [string]                 $title     [标题]
 */
function setlog($uid,$type,$operation,$sqldata,$contont,$title)
{
    $data=array(
        'uid'=>$uid,
        'type'=>$type,
        'ip'=>\PhalApi\Tool::getClientIp(),
        'operation'=>$operation,
        'sqldata'=>$sqldata,
        'contont'=>$contont,
        'title'=>$title,
        'creat_time'=>date('Y-m-d H:i:s',time())


    );
    \PhalApi\DI()->notorm->dolog->insert($data);

}
function randMobile($num = 1){
    //手机号2-3为数组
    $numberPlace = array(30,31,32,33,34,35,36,37,38,39,50,51,58,59,89);
    for ($i = 0; $i < $num; $i++){
        $mobile = 1;
        $mobile .= $numberPlace[rand(0,count($numberPlace)-1)];
        $mobile .= str_pad(rand(0,99999999),8,0,STR_PAD_LEFT);
        $result[] = $mobile;
    }
    return $result;
}

/**
 * 根据数组指定键名排序数组
 * @param $array array  被排序数组
 * @param $key_name string 数组键名
 * @param $sort   string  desc|asc  升序或者降序
 * @return array 返回排序后的数组
 * 例子:传入数组 $array=array(array('id'=>1,'sort'=>20),array('id'=>2,'sort'=>10),array('id'=>3,'sort'=>30));
 * gw_sort($array,'sort','asc');
 * 伪代码结果: sort:10,sort:20,sort:30
 */
function gw_sort($array,$key_name,$sort){
    $key_name_array = array();//保存被排序数组键名
    foreach($array as $key=>$val){
        $key_name_array[] = $val[$key_name];
    }
    if($sort=="desc"){
        rsort($key_name_array);
    }else if($sort=="asc"){
        sort($key_name_array);
    }
    $key_name_array = array_flip($key_name_array);//反转键名和值得到数组排序后的位置
    $result = array();
    foreach($array as $k=>$v){
        $this_key_name_value = $v[$key_name];//当前数组键名值依次是20,10,30
        $save_position = $key_name_array[$this_key_name_value];//获取20,10,30排序后存储位置
        $result[$save_position] = $v;//当前项存储到数组指定位置
    }
    ksort($result);
    return $result;
}

function GetPhonerepeatData($uid,$phone){
    $uid_num=\PhalApi\DI()->notorm->user->where('id',$uid)->fetchOne('structure_id');
    $charge_person=\PhalApi\DI()->notorm->user->where('structure_id',$uid_num)->fetchAll();
    $where='(';
    if(!empty($charge_person)){
        foreach ($charge_person as $k=>$v){
            $where.= "FIND_IN_SET('".$v['id']."',c.charge_person) OR ";
        }

    }
    $where = substr($where,0,strlen($where)-4).')';
    $where2=" FIND_IN_SET('".$phone."',CONCAT_WS(',',cphone,cphonetwo,cphonethree,telephone)) AND ".$where;
    $sql="SELECT c.id,s.cphone,s.cphonetwo,s.cphonethree,s.telephone,s.create_time,s.wxnum from crm_customer c LEFT JOIN crm_customer_data s ON c.id=s.cid WHERE  ".$where2;
    $cus =\PhalApi\DI()->notorm->customer->queryAll($sql,[]);
    return $cus;

}
function write_static_cache($cache_name, $caches)
{

    $cache_file_path = ROOT_PATH . '/' . $cache_name . '.php';
    $content = "<?php\r\n";
    $content .= "\$data = " . var_export($caches, true) . ";\r\n";
    $content .= "?>";
    file_put_contents($cache_file_path, $content, LOCK_EX);
}

function SetLimit($uid,$num){
    $getlimit=\PhalApi\DI()->notorm->user->where('id',$uid)->fetchOne('getlimit');
    if($getlimit>=0 && $num > 0){
        \PhalApi\DI()->notorm->user->where('id',$uid)->updateCounter('getlimit',$num);
    }else if($getlimit>0 && $num < 0){
        \PhalApi\DI()->notorm->user->where('id',$uid)->updateCounter('getlimit',$num);
    }

}
function deep_in_array($value, $array) {
    foreach($array as $item) {
        if(!is_array($item)) {
            if ($item == $value) {
                return true;
            } else {
                continue;
            }
        }

        if(in_array($value, $item)) {
            return true;
        } else if(deep_in_array($value, $item)) {
            return true;
        }
    }
    return false;
}
function curl_file_get_contents($durl, $post_data){
    $headers = array(
        "token:1111111111111",
        "over_time:22222222222",
    );
    //初始化
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $durl);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, false);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, true);
    // 设置post请求参数
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    // 添加头信息
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    // CURLINFO_HEADER_OUT选项可以拿到请求头信息
    curl_setopt($curl, CURLINFO_HEADER_OUT, true);
    // 不验证SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    //执行命令
    $data = curl_exec($curl);
    // 打印请求头信息
//        echo curl_getinfo($curl, CURLINFO_HEADER_OUT);
    //关闭URL请求
    curl_close($curl);
    //显示获得的数据
    return $data;
}




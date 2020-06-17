<?php
namespace App\Common;
use PhalApi\Model\NotORMModel as NotORM;
use App\Common\Customer;
/**
 * 分类管理
 */
Class Category extends NotORM {

    private $model;                                                          //分类的数据表模型
    private $rawList = array();                                              //原始的分类数据
    private $formatList = array();                                           //格式化后的分类
    private $error = "";                                                     //错误信息
    private $icon = array('', '', '');  //格式化的字符
    private $fields = array();   //字段映射，分类id，上级分类fid,分类名称name,格式化后分类名称fullname
    protected $di;
    protected $prefix;

    /**
     * 构造函数，对象初始化
     * @param array,object  $model      数组或对象，基于TP5.0的数据表模型名称,若不采用TP，可传递空值。
     * @param array         $field      字段映射，分类cid，上级分类fid,分类名称,格式化后分类名称fullname
     */

    public function __construct($model = '', $fields = array()) {
        $this->di = \PhalApi\DI()->notorm;
        $this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
        if (is_string($model) && (!empty($model))) {

        }
        if (is_object($model))
            $this->model = &$model;
        if($model!=''){
            $this->fields['cid'] = $fields['0'] ? $fields['0'] : 'cid';
            $this->fields['fid'] = $fields['1'] ? $fields['1'] : 'fid';
            $this->fields['name'] = $fields['2'] ? $fields['2'] : 'name';
            $this->fields['fullname'] = $fields['3'] ? $fields['3'] : 'fullname';
        }


    }

    /**
     * 获取分类信息数据
     * @param array,string  $condition  查询条件
     * @param string        $orderby    排序
     */
    private function _findAllCat($model,$condition, $orderby = NULL) {
        $this->rawList = !empty($condition) ? \PhalApi\DI()->notorm->$model->where($condition)->fetchAll() : \PhalApi\DI()->notorm->$model->fetchAll();
    }

    /**
     * 返回给定上级分类$fid的所有同一级子分类
     * @param   int     $fid    传入要查询的fid
     * @return  array           返回结构信息
     */
    public function getChild($fid) {
        $childs = array();
        foreach ($this->rawList as $Category) {
            if ($Category[$this->fields['fid']] == $fid)
                $childs[] = $Category;
        }
        return $childs;
    }

    /**
     * 递归格式化分类前的字符
     * @param   int     $cid    分类cid
     * @param   string  $space
     */
    private function _searchList($cid = 0, $space = "",$level=1,$p_name='') {
        $childs = $this->getChild($cid);

        //下级分类的数组
        //如果没下级分类，结束递归
        if (!($n = count($childs)))
            return;
        $m = 1;
        //循环所有的下级分类
        for ($i = 0; $i < $n; $i++) {
            $pad = '';
            $childs[$i]['level'] = $level;
            $childs[$i]['label'] = $childs[$i]['name'];
            $this->formatList[] = $childs[$i];
            $this->_searchList($childs[$i][$this->fields['cid']], $space . $pad . "  ",$level+1,$childs[$i]['pid']);//递归下一级分类
            $m++;
        }
    }

    /**
     * 不采用数据模型时，可以从外部传递数据，得到递归格式化分类
     * @param   array,string     $condition    条件
     * @param   int              $cid          起始分类
     * @param   string           $orderby      排序
     * @return  array            返回结构信息
     */
    public function getList($model='',$condition = NULL, $cid = 0, $orderby = NULL,$is_raw=0) {

        unset($this->rawList, $this->formatList);

        $this->_findAllCat($model,$condition, $orderby, $orderby);
        $this->_searchList($cid);
        if ($is_raw == 1) {
            return $this->rawList;
        } else {
            return $this->formatList? $this->formatList: $this->rawList;
        }
        
    }

    /**
     * 获取结构
     * @param   array            $data         二维数组数据
     * @param   int              $cid          起始分类
     * @return  array           递归格式化分类数组
     */
    public function getTree($data, $cid = 0) {
        unset($this->rawList, $this->formatList);
        $this->rawList = $data;
        $this->_searchList($cid);
        return $this->formatList;
    }

    /**
     * 获取错误信息
     * @return  string           错误信息字符串
     */
    public function getError() {
        return $this->error;
    }

    /**
     * 检查分类参数$cid,是否为空
     * @param   int              $cid          起始分类
     * @return  boolean           递归格式化分类数组
     */
    private function _checkCatID($cid) {
        if (intval($cid)) {
            return true;
        } else {
            $this->error = "参数分类ID为空或者无效！";
            return false;
        }
    }

    /**
     * 检查分类参数$cid,是否为空
     * @param   int         $cid        分类cid
     */
    private function _searchPath($cid) {
        //检查参数
        if (!$this->_checkCatID($cid))
            return false;
        $rs = $this->model->find($cid);                                        //初始化对象，查找上级Id；
        $this->formatList[] = $rs;                                            //保存结果
        $this->_searchPath($rs[$this->fields['fid']]);
    }

    /**
     * 查询给定分类cid的路径
     * @param   int         $cid        分类cid
     * @return  array                   数组
     */
    public function getPath($cid) {
        unset($this->rawList, $this->formatList);
        $this->_searchPath($cid);                                               //查询分类路径
        return array_reverse($this->formatList);
    }

    /**
     * 添加分类
     * @param   array         $data        一维数组，要添加的数据，$data需要包含上级分类ID。
     * @return  boolean                    添加成功，返回相应的分类ID,添加失败，返回FALSE；
     */
    public function add($data) {
        $user_list='';
        $list=$this->di->structure->where('name',$data['name'])->fetchOne();
        if($list['pid']==$data['pid'] && $list != null){
            $rs = array('code'=>0,'msg'=>'同级部门名称不能相同!','data'=>[],'info'=>[]);
            return $rs;
        }else {
            $this->di->beginTransaction('db_master');
            $str['name'] = $data['name'];
            $director = isset($data['director']) ? [] : $data['director'];
            $str['pid'] = $data['pid'];//上级id
            $str['nrepeat'] = empty($data['nrepeat'])?1:$data['nrepeat'];//判定标准
            $str['project_num'] = $data['project_num'];//所属公司
            $str['capacity'] = $data['capacity'];//部门类型
            $str['is_sea'] = $data['is_sea'];//是否开启独立公海
            $str['sea_type'] =!isset($data['sea_type']) ? 0:$data['sea_type'];//公海类型0:查看公海 1:合并重复数据并查看公海
            $str['share'] = empty($data['share']) || !isset($data['share']) ? '':$data['share'];//公海类型0:查看公海 1:合并重复数据并查看公海
            //1:判断是否有公海
            //2:判断公海类型
            //3:根据类型判断公海数据的处理(合并)
            if ($str['pid'] == 0) {
                //新增主部门
                if (!empty($data['share']) && $data['sea_type'] == 1) {
                    $rs = array('code' => 0, 'msg' => '新增主部门不能合并其他部门数据!', 'data' => [], 'info' => []);
                    return $rs;
                }
            }else {
                //存在子部门
                //1:合并公海
                if ($data['sea_type'] == 1) {
                    $msg= $this->CustomerMerge($data['uid'],$data['pid'],$data['share'],1);
                   foreach ($msg as $k=>$v){
                       $nww[$v['zd']][]=['cid'=>$v['cid'],'create_time'=>$v['create_time']];
                   }
                    //对数据进行合并

                    //并对涉及的子部门进行更新
                }
            }
            //有独立公海
            if (isset($data['share']) && $data['share'] != '' && $data['share'] != 'all') {
                //share != all
                $share_list = explode(',', $data['share']);
                foreach ($share_list as $n => $m) {
                    if (empty($m)) {
                        unset($share_list[$n]);
                    }
                }
                foreach ($share_list as $k => $v) {
                    $pid = $this->di->structure->where('id', $v)->fetchOne('pid');
                    $pid_count = $this->di->structure->where('pid', $v)->count();
                    if ($pid == 0 && $pid_count > 0) {
                        $rs = array('code' => 0, 'msg' => '不能选择主部门!', 'data' => [], 'info' => []);
                        return $rs;
                    }
                    if($str['pid'] != 0 && $data['sea_type'] == 1){
                        if($pid==$str['pid']){
//                            $this->di->structure->where('id', $v)->update(['share' => $data['share']]);
                        }

                    }

                }
            }
            //负责人
            if (count($director) >= 1) {
                foreach ($director as $n => $m) {
                    $dirs = $this->di->structure->select('id,pid,director,share,name')->where("FIND_IN_SET('{$m}',director)")->and('director <> ?', '')->fetchAll();
                    $user_info = $this->di->user->select('is_leader,structure_id,parent_id')->where('id', $m)->fetchOne();
                    foreach ($dirs as $h => $l) {
                        if ($l['id'] == $data['pid']) {
                            $rs = array('code' => 0, 'msg' => '该用户已经是 ' . $l['name'] . ' 的经理,已拥有对该子部门的管理权限!', 'data' => [], 'info' => []);
                            return $rs;
                        }
                    }
                    $user_list .= $m . ',';
                    if ($data['pid'] == 0) {
                        $new_user[$m]['is_leader'] = $user_info['is_leader'] == 1 ? 2 : $user_info['is_leader'];
                    }
                    else {
                        $new_user[$m]['is_leader'] = $user_info['is_leader'] == 0 ? 3 : $user_info['is_leader'];
                    }
                }
            }
            else {
                $user_list = '';
            }
            $str['director'] = $user_list;
//            var_dump($str);die;
            $this->di->structure->insert($str);
            $stru_id = $this->di->structure->insert_id();
            if($str['pid']!=0 && $data['share']==''){
                $this->di->structure->where('id', $stru_id)->update(['share' => $stru_id . ',']);
            }
            foreach ($new_user as $k => $v) {
                $parent_id = $this->di->user->where('id', $k)->fetchOne('parent_id');
                $parent_id = empty($parent_id) ? $stru_id . ',' : $parent_id . $stru_id . ',';
                $this->di->user->where('id', $v)->update(['is_leader' => $v['is_leader'], 'parent_id' => $parent_id]);
            }
            if($stru_id){
                $this->di->commit('db_master');
            }else{
                // 回滚事务
                $this->di->rollback('db_master');
            }
            \PhalApi\DI()->redis->del('medel_list','user');
            $rs = array('code' => 1, 'msg' => '创建成功!', 'data' => [], 'info' => []);
            return $rs;
        }
    }

    /**
     * 修改部门分类
     */
    public function edit($id,$uid,$data) {
        $director = isset($data['director']) && !empty($data['director']) ? $data['director'] :[];
        $is_sea = $data['is_sea'];

        if (empty($data)){
            $rs = array('code' => 1, 'msg' => '您未修改任何数据', 'data' => [], 'info' => []);
            return $rs;
        }
        $dirs_list='';
        $a=\PhalApi\DI()->notorm->structure->where('id', $id)->fetchOne();
        $count_a=\PhalApi\DI()->notorm->structure->where('pid', $id)->and('pid <> ?',0)->count('id');
        $stru_array['nrepeat'] = isset($data['nrepeat']) && !empty($data['nrepeat']) ? $data['nrepeat']:$a['nrepeat'];
        $list=$this->di->structure->where('name',$data['name'])->and('pid',$a['pid'])->and('id <> ?',$id)->fetchOne();
        if($list != null){
            $rs = array('code'=>0,'msg'=>'同级部门名称不能相同!','data'=>[],'info'=>[]);
            return $rs;
        }else if ($a==''){
            $rs = array('code'=>0,'msg'=>'参数有误!','data'=>[],'info'=>[]);
            return $rs;
        }
        $stru_array['name']=$data['name'];
        $stru_array['sea_type']=$data['sea_type'];
        $stru_array['is_sea']=$is_sea;
        $stru_array['capacity']=$data['capacity'];
        if($is_sea==0){
            if (!empty($data['share']) && $data['share'] != 'all' && $data['share'] != ',') {
                $share_list = explode(',', $data['share']);
                foreach ($share_list as $n => $m) {
                    if (empty($m)) {
                        unset($share_list[$n]);
                    }
                }
                foreach ($share_list as $k => $v) {
                    $pid = $this->di->structure->where('id', $v)->fetchOne('pid');
                    $capacity = $this->di->structure->where('id', $v)->fetchOne('capacity');
                    $pid_count = $this->di->structure->where('pid', $v)->count();
                    if ($pid == 0 && $pid_count > 0 && $id != $v) {
                        $rs = array('code' => 0, 'msg' => '不能选择主部门!', 'data' => [], 'info' => []);
                        return $rs;
                    }
                    if ($a['sea_type'] == 1 && $a['capacity'] != $capacity) {
                        $rs = array('code' => 0, 'msg' => '不能选择部门类型不同的部门', 'data' => [], 'info' => []);
                        return $rs;
                    }
                    else if ($a['sea_type'] == 1 && $a['capacity'] == $capacity) {
                            $this->di->structure->where('id', $v)->update(['share' => $data['share'], 'sea_type' => 1]);
                    }
                }
            }
            else if (!empty($data['share'])) {
                $stru_array['share'] = $data['share'] . ',';//独立公海 0:有独立公海 1:没有独立公海(不显示公海)
            }


        }else{
            $stru_array['share']='';
            $stru_array['sea_type']=1;
            $structure_list=$this->di->structure->where("FIND_IN_SET({$id},`share`)")->and('sea_type',1)->fetchAll();
            if(count($structure_list)>0){
                foreach ($structure_list as $k=>$v){
                    if($v['capacity']==2){
                        $new_share_list_array=[];
                        $share_list_array=explode(',',$v['share']);
                        foreach ($share_list_array as $n=>$m){
                            if($id==$m){
                                unset($m);
                            }
                            $new_share_list_array[]=$m;
                        }
                        $new_share_list_string=implode(',',$new_share_list_array);
                        $this->di->structure->where("id", $v['id'])->update(['share' => $new_share_list_string]);
                    }
                }
            }
        }

        if ($data['pid'] == 0) {
            //新增主部门
            if (!empty($data['share']) && $data['sea_type'] == 1) {
                $rs = array('code' => 0, 'msg' => '主部门无需选择其子部门或其他部门的子部门!', 'data' => [], 'info' => []);
                return $rs;
            }
        }else {
            //存在子部门
            //1:合并公海
            if ($data['sea_type'] == 1) {
                //不能包含其他非子部门数据
                $msg= $this->CustomerMerge($data['uid'],$data['pid'],$data['share'],1);
                foreach ($msg as $k=>$v){
                    $nww[$v['zd']][]=['cid'=>$v['cid'],'create_time'=>$v['create_time']];
                }

                //对数据进行合并
                //并对涉及的子部门进行更新
            }
        }
        if(!empty($director) || !in_array('', $director)){
            //多个主管
            foreach ($director as $v){
                $dirs = $this->di->structure->select('id,pid,director,share,name')->where("FIND_IN_SET('{$v}',director)")->and('director', '<>', '')->fetchAll();
                if(count($dirs)>=1){
                    //如果设置的主管有多个部门的管理
                    foreach ($dirs as $k=>$n){
                        if ($n['id'] == $a['pid']) {
                            //如果设置成子部门的主管则不允许
                            $realname=\App\GetFiledInfo('user','realname',$v);
                            $rs = array('code' => 0, 'msg' => $realname.' 已经是 ' . $n['name'] . ' 的经理,已拥有对该子部门的管理权限!', 'data' => [], 'info' => []);
                            return $rs;
                        }
                    }
                }
                $user_info = $this->di->user->select('is_leader,structure_id,parent_id')->where('id', $v)->fetchOne();
                $structure_id = json_decode($user_info['structure_id']);
                $new_parent=$user_info['parent_id'];
                if($user_info['parent_id']!=''){
                    $parent_array=explode(',',$user_info['parent_id']);
                    array_pop($parent_array);
                    if(!in_array($id,$parent_array)){
                        $new_parent=$user_info['parent_id'].$id.',';
                    }
                }else{
                    if(!in_array($id,$structure_id)){
                        $new_parent=$id.',';
                    }

                }
                if(in_array($id,$structure_id) && $a['pid']==0){
                    //经理
                    $is_leader=$user_info['is_leader'] != 2 ? 2 :$user_info['is_leader'];
                }else if(in_array($id,$structure_id) && $a['pid']!=0 ){
                    //主管
                    $is_leader=1;
                }else{
                    $is_leader=$user_info['is_leader']==2 || $user_info['is_leader']==1?$user_info['is_leader']:3;
                }
                $user_new[]=['id'=>$v,'is_leader'=>$is_leader,'parent_id'=>$new_parent];
                $dirs_list.=$v.',';
            }
            $stru_array['director']=$dirs_list;

        }else{
            $dirs_list='';

        }

        if(!empty($user_new)){
            foreach ($user_new as $k=>$v){
                $this->di->user->where('id',$v['id'])->update(['is_leader'=>$v['is_leader'],'parent_id'=>$v['parent_id']]);
            }
        }
        if($data['share']==''&& empty($data['share'])){
            $stru_array['share']=$id.',';
        }

        $sta = $this->di->structure->where('id', $id)->update($stru_array);
        if($sta!==false){
            \PhalApi\DI()->redis->del('medel_list','user');
            $rs = array('code' => 1, 'msg' => '000039', 'data' => [], 'info' => []);
        }else{
            $rs = array('code' => 1, 'msg' => '您未修改任何数据', 'data' => [], 'info' => []);

        }
        return $rs;
    }


    /**
     * 删除分类
     * @param   int         $cid        分类cid
     * @return  boolean                 删除成功，返回相应的分类ID,删除失败，返回FALSE
     */
    public function del($cid) {
       $id= $this->di->customer_data->where('groupid',$cid)->count('id');
       $stru_id=$this->di->structure->where('pid',$cid)->count('id');
        if(!empty($id)){
            $rs = array('code' => 0, 'msg' => '删除失败,部门中有' . $id . '客户,不能删除!', 'data' => array(), 'info' => array());
            return $rs;
        }
        $user_list=$this->di->user->where('new_groupid',$cid)->count('id');
        if(!empty($user_list)){
            $rs = array('code' => 0, 'msg' => '删除失败,部门中有' . $user_list . '个用户,需切换后才能删除!', 'data' => array(), 'info' => array());
            return $rs;
        }
       if(!empty($id) && !empty($stru_id)){
           $msg='此部门有客户数据'.$id.'条,子部门有'.$stru_id.'条.不能删除';
           $rs = array('code' => 0, 'msg' => '您未修改任何数据', 'data' => [], 'info' => []);
       }else{
           $msg='删除成功';
           $rs = array('code' => 1, 'msg' => '删除成功', 'data' => [], 'info' => []);
           \PhalApi\DI()->notorm->structure->where('id', $cid)->delete();
       }

       return $rs;
    }

    /**
     * 共享项目方
     */
    public function PostProjectShare($uid,$id,$share_uid,$tb_name){
      //查出分享人
        $data=$this->di->$tb_name->where('id',$id)->and('status',1)->fetchOne();
       if(empty($data)){
           $rs = array('code'=>0,'msg'=>'999979','data'=>array(),'info'=>array());
           return $rs;
       }
        $share_person=explode(',',$data['charge_person']);
       if(count($share_person)!=1){
           $share_person=array_pop($share_person);
       }
        if(count($share_uid)>4){
            $rs = array('code'=>0,'msg'=>'分享失败,人数超过限制!','data'=>array(),'info'=>array());
            return $rs;
        }
        $new_share=$data['creatid'].',';
        foreach ($share_person as $k=>$v){
            $new_share.=$v.',';
            $share_user_name = $this->di->user->where(array("id"=>$v,"status"=>1))->fetchOne("username");
           \App\AddShareLog($id,$uid,$v,2,'share','共享给'.$share_user_name);
        }
        $status=$this->di->$tb_name->where('id',$id)->update(['charge_person'=>$new_share]);
        if ($status) {
            $rs = array('code'=>1,'msg'=>'000056','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    /**
     * 指定部门客户公海共享合并
     * @author Dai Ming
     * @DateTime 14:05 2020/5/13 0013
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function SetShareCustomerData($group,$uid)
    {
        $sh_arr=array();
            $cha=['cphone','cphonetwo','cphonethree','wxnum'];
            foreach ($cha as $n){
                $where="FIND_IN_SET(c.groupid,'111,112,113,114,115,116,117') ";
//            $group="FIND_IN_SET(c.groupid,'".$group."')";
                $where.="AND  c.sea_type = 0 AND  s.".$n." <> '' GROUP BY s.".$n." ) w where num > 1";
                $sql="SELECT * from (SELECT	count(c.id) as num,	c.cid,s.cphone,	s.cphonetwo,s.cphonethree,s.wxnum,c.groupid,t.`name`,s.groupid as create_grouid,c.addtime as create_time,t.capacity FROM crm_share_join c LEFT JOIN crm_structure t ON c.groupid = t.id LEFT JOIN crm_customer_data s ON c.cid = s.cid where ".$where;
                $data=\PhalApi\DI()->notorm->share_join->queryAll($sql, []);
                if (!empty($data[0]['cid'])) $sh_arr += $data;
            }
            if(count($sh_arr) > 1){

            }else{
                $rs = array('code'=>1,'msg'=>'没有重复数据','data'=>array(),'info'=>array());
            }
            return $rs;
    }

    /**
     *  公海共享数据合并优先级数据
     * @author Dai Ming
     * @DateTime 16:30 2020/5/13 0013
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetNewData($data,$qudao)
    {
        $getyear = $this->di->admin_config->where(array("title" => "REPEAT_DATA"))->fetchOne('value');
        $last_time = time() - $getyear * 31536000;
        $first_time = time();
        $qd=$this->di->structure->where('capacity',3)->fetchPairs('id','capacity');
        $zt=$this->di->structure->where('capacity',2)->fetchPairs('id','capacity');
        $qt=$this->di->structure->where('capacity',1)->fetchPairs('id','capacity');
        $ht=$this->di->structure->where('capacity',4)->fetchPairs('id','capacity');
        if(!empty($qd)){
            foreach ($data as $k=>$v){
                if(in_array($v['create_grouid'],$qd)){
                   $new_qd[]=$v;
                }
            }

        }else{

        }

        foreach ($sh_arr as $k => $v) {
            if ($last_time <= $v['create_time'] && $v['create_time'] <= $first_time) {
                $year_nei[] = $v;
            }
            else if ($last_time >= $v['create_time']) {
                $year_wai[] = $v;
            }
        }
        if (isset($year_nei) && count($year_nei) > 0) {
            if (count($year_nei) == 1) {
                $rs = $this->tixing($year_nei, $phone, $wx, $uid, $type);
                return $rs;
            }
            else {
                $ages = array();
                foreach ($year_nei as $user) {
                    $ages[] = $user['create_time'];
                }
                array_multisort($ages, SORT_ASC, $year_nei);
                $year_nei_new[] = reset($year_nei);
                $rs = $this->tixing($year_nei_new, $phone, $wx, $uid, $type);
                return $rs;

            }
        }
        else if (isset($year_wai) && count($year_wai) > 0 && count($year_nei) <= 0) {

            $ages = array();
            foreach ($year_wai as $user) {
                $ages[] = $user['create_time'];
            }
            array_multisort($ages, SORT_DESC, $year_wai);
            $year_wai_new[] = reset($year_wai);

            $rs = $this->tixing($year_wai_new, $phone, $wx, $uid, $type);
            return $rs;


        }

    }


    /**
     * 公海客户合并
     * @author Dai Ming
     * @DateTime 09:23 2020/4/27 0027
     * @desc 客户合并（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function CustomerMerge($uid,$struid,$sea_data,$type)
    {
        $date_time=$this->di->admin_config->where('title','REPEAT_DATA')->fetchOne('value');
        $date_time=$date_time*365*86400;
        $now_time=time();
        $last_time=$now_time-$date_time;
//        合并规则：1年之内的数据：谁先录入算谁的
//                2、超过1年的：谁后录入算谁的。
//                比如说天津分院的的2个公海人工智能项目组+呼叫中心项目组合并成1个公海，会产生重复数据，重复数据按照如上规则处理。
        //1.查询此部门之前的公海部门数据
        switch ($type){

            case 1:
                //新增
                if(!empty($sea_data) && count($sea_data) >0){
                    //公海判断 ,当前的公海部门为多个
                    $sea_data_array=explode(',',$sea_data);
                    foreach ($sea_data_array as $k){
                        $stu_data=$this->di->structure->where('id',$k)->fetchOne();
                        if($stu_data['capacity']==2){
                            if($stu_data['nrepeat']==1){
                                //以手机号为主:
                                $zt_sj_data[]=$k;
                            }else{
                                //以微信号为主
                                $zt_wx_data[]=$k;
                            }
                            //只判断中台
                        }
                    }
                    if(isset($zt_sj_data) && count($zt_sj_data)>=2) {
                        //执行客户合并 以手机号为主
                        $cus_data=$this->di->customer_data->select('A.cphone,count(A.id) as num') // 获取字段
                        ->alias('A') // 主表别名为A
                        ->leftJoin('customer', 'B', 'A.cid = B.id')
                        ->leftJoin('share_join', 'C', 'C.cid = A.id')
                        ->where('A.groupid',$zt_sj_data)
                        ->where('C.sea_type',1)
                        ->group('A.cphone','count(A.id) >= 2')
                        ->fetchAll();
                        if(count($cus_data)>0){
                           //查询到重复数据
                            foreach ($cus_data as $k=>$v){
                                $customer_now=$this->di->customer_data->select('cphone as zd,cid,create_time')
                                ->where('cphone',$v['cphone'])
                                ->fetchAll();
                            }
                        }
                    }else if((isset($zt_wx_data) && count($zt_wx_data)>=2)){
                        //执行客户合并 以微信号为主
                        //执行客户合并 以手机号为主
                        $cus_data=$this->di->customer_data->select('A.wxnum,count(A.id) as num') // 获取字段
                        ->alias('A') // 主表别名为A
                        ->leftJoin('customer', 'B', 'A.cid = B.id')
                        ->leftJoin('share_join', 'C', 'C.cid = A.id')
                            ->where('A.groupid',$zt_sj_data)
                            ->and('C.sea_type = 1')
                            ->group('A.wxnum','count(A.id) >= 2')
                            ->fetchAll();
                        if(count($cus_data)>0){
                            //查询到重复数据
                            foreach ($cus_data as $k=>$v){
                                $customer_now=$this->di->customer_data->select('wxnum as zdcid,create_time')
                                    ->where('wxnum',$v['wxnum'])
                                    ->fetchAll();
                            }

                        }
                    }
                    return $customer_now;
                }

                break;
            default:
                break;
        }
        if(!empty($struid)){
            //编辑的时候.
            $old_data=$this->di->structure->where('id',$struid)->fetchOne();

        }else{


        }

    }
}
?>
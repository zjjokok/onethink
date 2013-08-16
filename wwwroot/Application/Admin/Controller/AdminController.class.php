<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------


/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class AdminController extends Action {

    /* 保存禁止通过url访问的公共方法,例如定义在控制器中的工具方法 ;deny优先级高于allow*/
    static protected $deny  = array();

    /* 保存允许所有管理员访问的公共方法 */
    static protected $allow = array();
    
    /**
     * 节点配置  
     *   菜单节点必须配置title元素和url元素(供U函数作使用)
     *   array(
     *       //值的元素  title:节点名字；url:链接; group:链接组; tip:链接提示文字
     *       array( 'title'=>'节点标题','url'=>'action?query=vaule', 'group'=>'扩展','tip'=>''),
     *   )
     */ 
    static protected $nodes = array();

    /**
     * 主节点配置示例:  
     *   菜单节点必须配置title元素和url元素(供U函数作使用)
     *   array(
     *       //值的元素  title:节点名字；url:链接; controller:从哪些控制器查询节点,多个逗号分隔; tip:链接提示文字
     *       array( 'title'=>'节点标题', 'url'=>'Index/index?param=value','controllers'=>'', 'tip'=>''),
     *        ......
     *     )
     *   
     */ 
    static private $menus = array(
        array( 'title'=>'首页','url'=>'Index/index','controllers'=>'Index',),
        array( 'title'=>'内容','url'=>'Article/index','controllers'=>'Article',),
        array( 'title'=>'用户','url'=>'User/index','controllers'=>'User,AuthManager'),
        array( 'title'=>'扩展','url'=>'Addons/index','controllers'=>'Addons',),
        array( 'title'=>'系统','url'=>'System/index','controllers'=>'System',),
    );

    final protected function _initialize()
    {
        //TODO:登陆检测
        
        $ac = $this->accessControl();
        if ( $ac===false ) {
            $this->error('403:禁止访问',__APP__);
        }elseif( $ac===null ){
            // import('ORG.Util.Auth');
            // $auth = new Auth();
            // if(!$auth->check(MODULE_NAME.'-'.ACTION_NAME,session('uid'))){
                // $this->error('你没有权限');
            // }
        }
        $controller = CONTROLLER_NAME.'Controller';
        $this->assign( 'base_menu', $controller::getMenus() );

        $this->_init();
    }

    protected function _init()
    {
    }
    
    /**
     * action访问控制,在 **登陆成功** 后执行的第一项权限检测任务
     * 
     * @return true|false|null  返回值必须使用 `===` 进行判断
     * 
     *   返回false,不允许任何人访问,子类自行决定错误处理方式
     *   返回true, 允许任何管理员访问,无需执行权限检测
     *   返回null, 需要继续执行权限检测决定是否允许访问
     *   
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    final protected function accessControl(){
        $controller = CONTROLLER_NAME.'Controller';
        if ( !is_array($controller::$deny)||!is_array($controller::$allow) ){
            $this->error('内部错误:deny和allow属性必须为数组,即将返回首页',__APP__);
        }
        $deny  = $this->getDeny();
        $allow = $this->getAllow();
        // dump($deny);
        // dump($allow);
        if ( !empty($deny) && in_array(ACTION_NAME,$deny) ) {
            return false;
        }
        if ( !empty($allow) && in_array(ACTION_NAME,$allow) ) {
            return true;
        }
        return null;
    }

    /**
     * 对数据表中的单行或多行记录执行修改 GET参数id为数字或逗号分隔的数字
     *
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $data  修改的数据
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    final protected function editRow ( $model ,$data, $where , $msg )
    {
        if( $_REQUEST['model']||$_REQUEST['where']||$_REQUEST['msg']){
            $this->error('非法请求',__APP__); //安全检测,防止通过参数绑定修改数据
        }
        $where   = array_merge( array('id' => array('in', I('id',0)),(array)$where ));
        $msg     = array_merge( array( 'success'=>'操作成功！', 'error'=>'操作失败！', 'url'=>'' ,'ajax'=>IS_AJAX) , (array)$msg );
        if( D($model)->where($where)->save($data) ) {
            $this->success($msg['success'],$msg['url'],$msg['ajax']);
        }else{
            $this->error($msg['error'],$msg['url'],$msg['ajax']);
        } 
    }

    /**
     * 禁用条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息,可以设置四个元素 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    public function forbid ( $model , $where = array() , $msg = array( 'success'=>'状态禁用成功！', 'error'=>'状态禁用失败！'))
    {
        $data    = array('status' => 0);
        $where   = array_merge(array('status' => 1),$where);
        $this->editRow( $model , $data, $where, $msg);
    }

    /**
     * 恢复条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    public function resume (  $model , $where = array() , $msg = array( 'success'=>'状态恢复成功！', 'error'=>'状态恢复失败！'))
    {
        $data    = array('status' => 1);
        $where   = array_merge(array('status' => 0),$where);
        $this->editRow(   $model , $data, $where, $msg);
    }

    /**
     * 条目假删除
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    public function delete ( $model , $where = array() , $msg = array( 'success'=>'删除成功！', 'error'=>'删除失败！'))
    {
        $data    = array('status' => -1);
        $this->editRow(   $model , $data, $where, $msg);
    }

    /**
     * $deny属性的get方法
     * 
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    final protected function getDeny( array $deny )
    {
        $controller = CONTROLLER_NAME.'Controller';
        $data = array();
        if ( is_array( $controller::$deny) ) {
            $deny = array_merge( $controller::$deny, self::$deny );
            foreach ( $deny as $key => $value){
                if ( is_numeric($key) ){
                    $data[] = $value;
                }else{
                    //TODO: 功能扩展
                } 
            }
        }
        return $data;
    }
    
    /**
     * 获取控制器中允许所有管理员通过url访问的方法
     * 
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    final protected function getAllow()
    {
        $controller = CONTROLLER_NAME.'Controller';
        $data = array();
        if ( is_array( $controller::$allow) ) {
            $allow = array_merge( $controller::$allow, self::$allow );
            foreach ( $allow as $key => $value){
                if ( is_numeric($key) ){
                    $data[] = $value;
                }else{
                    //TODO: 功能扩展
                } 
            }
        }
        return $data;
    }

    /**
     * 获取控制器的节点配置
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    final static public function getNodes($controller){
        if ( !$controller || !is_string($controller) || !is_array($controller::$nodes) ) {
            return false;
        }
        $nodes = array('default'=>array());
        foreach ($controller::$nodes as $value){
            if ( is_array($value) ) {
                // $value['url'] = U($value['url']);
                //为节点分组,默认分组为default
                $group = empty($value['group']) ?'default': $value['group'];
                unset($value['group']);
                $nodes[$group][] = $value;
            }
        }
        return $nodes;
    } 

    /*
     * 获取控制器菜单数组
     * 子类中 $this->getMenus() 调用
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    final static public function getMenus(){
//        if ( S('menu'.$controller) ) {
//            return S('menu'.$controller);
//        }
        $menus['main']  = self::$menus;                       //获取主节点

        //处理其他控制器中的节点
        foreach ($menus['main'] as $key=>$item){
            // $menus['main'][$key]['url'] = U( $item['url'] );
            if( $item['controllers'] && is_string($item['controllers'])) {
                $other_controller = explode(',',$item['controllers']);
				if ( in_array( CONTROLLER_NAME, $other_controller ) ) {
                    $menus['main'][$key]['class']='current';
					foreach ($other_controller as $c){
						//如果指定了从其他控制器中读取节点
						$child = $c.'Controller';
						$child_nodes = $child::getNodes($child);      //其他控制器中的节点
						foreach ( $child_nodes as $group => $value ) {
							if ( $menus['child'][$group] ) {
								//如果分组已存在,合并到分组中
								$menus['child'][$group] = array_intersect_assoc($menus['child'][$group],$value);
							}else{
								//否则直接保存
								$menus['child'][$group]=$value;
							}
						}
					}
				}
            }
        }
//        S('menu'.CONTROLLER_NAME,$menus);
        // dump($menus);
        return $menus;
    }
}

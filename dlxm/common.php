<?php

use app\common\model\Category;
use fast\Form;
use fast\Tree;
use fast\QRcode;
use think\Db;
use app\admin\model\ledger\Ledger;

if(!function_exists('ledgerQRcode')){
    function ledgerQRcode($code_content, $ledger_number, $username, $install_address){
//        $code_name = 'ledger'.time();
//        $code_dir = '/uploads/ledger-qrcode/'.$code_name.'.png';
//        $code_file = $_SERVER['DOCUMENT_ROOT'].$code_dir;
//        $code_log_width = 6;
//        $qrcode = new QRcode();
//        $qrcode::png($code_content, $code_file, 'L', $code_log_width, 2, TRUE);
        $mLedger = new Ledger();
        $code_file = ROOT_PATH.'public'.$mLedger->urlToQRcode('dianli/pages/index/index','5&'.$ledger_number);
        if(!is_file($code_file)){
            $this->error('二维码生成失败');
        }
        $path_base = $_SERVER['DOCUMENT_ROOT']."/assets/img/back.jpg";
        $path_logo = $code_file;
        $image_logo = imagecreatefromjpeg($path_logo);
        $image_base = imagecreatefromjpeg($path_base );
        $imgWidth = 240;
        $imgHeight = 240;
        imagecopyresampled($image_base, $image_logo, 130, 30, 0, 0,$imgWidth,$imgHeight,imagesx($image_logo), imagesy($image_logo));
        $new_path = 'qrcode'.time();
        $new_path = '/uploads/ledger-qrcode/'.$new_path.'.png';
        imagejpeg($image_base,$_SERVER['DOCUMENT_ROOT'].$new_path);
        $fontfile = realpath($_SERVER['DOCUMENT_ROOT'].'/assets/fonts/simsun.ttc');
        $str = "台账编号：$ledger_number\n商家：$username\n地址：$install_address";
        $new_pic = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'].$new_path);
        $color = imagecolorallocatealpha($new_pic,0, 0, 0, 0);
        imagettftext($new_pic,15,0,100,320,$color,$fontfile,$str);
        imagejpeg($new_pic,$_SERVER['DOCUMENT_ROOT'].$new_path);

        return $new_path;
    }
}

if(!function_exists('getTree')){
    /**
     * 无限极分类（包含等级）
     *
     * @param array $array
     * @param integer $pid
     * @param integer $level
     *
     * @return array
     */
    function getTree($array, $pid =0, $level = 0){

        //声明静态数组,避免递归调用时,多次声明导致数组覆盖
        static $list = [];
        foreach ($array as $key => $value){
            //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点
            if ($value['pid'] == $pid){
                //父节点为根节点的节点,级别为0，也就是第一级
                $value['level'] = $level;
                //$value['level'] = $level;
                //把数组放到list中
                $list[] = $value;
                //把这个节点从数组中移除,减少后续递归消耗
                unset($array[$key]);
                //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                getTree($array, $value['id'], $level + 1);

            }
        }
        return $list;
    }
}

if (!function_exists('build_select')) {

    /**
     * 生成下拉列表
     * @param string $name
     * @param mixed $options
     * @param mixed $selected
     * @param mixed $attr
     * @return string
     */
    function build_select_data($model,$where=[]){
        is_object($model) or $model = model($model);
        // 必须将结果集转换为数组
        $list = collection($model->where($where)->order('id', 'asc')->select())->toArray();
        Tree::instance()->init($list);
        $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0), 'name');
        //print_r($result);exit;
        $selectData = [0 => __('None')];
        foreach ($result as $k => &$v) {
            $selectData[$v['id']] = $v['name'];
        }
        unset($v);
        return $selectData;
    }

    /**
     * 生成下拉列表
     * @param string $name
     * @param mixed $options
     * @param mixed $selected
     * @param mixed $attr
     * @return string
     */
    function build_select($name, $options, $selected = [], $attr = [])
    {
        $options = is_array($options) ? $options : explode(',', $options);
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        return Form::select($name, $options, $selected, $attr);
    }
}

if (!function_exists('build_radios')) {

    /**
     * 生成单选按钮组
     * @param string $name
     * @param array $list
     * @param mixed $selected
     * @return string
     */
    function build_radios($name, $list = [], $selected = null)
    {
        $html = [];
        $selected = is_null($selected) ? key($list) : $selected;
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        foreach ($list as $k => $v) {
            $html[] = sprintf(Form::label("{$name}-{$k}", "%s {$v}"), Form::radio($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
        }
        return '<div class="radio">' . implode(' ', $html) . '</div>';
    }
}

if (!function_exists('build_checkboxs')) {

    /**
     * 生成复选按钮组
     * @param string $name
     * @param array $list
     * @param mixed $selected
     * @return string
     */
    function build_checkboxs($name, $list = [], $selected = null)
    {
        $html = [];
        $selected = is_null($selected) ? [] : $selected;
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        foreach ($list as $k => $v) {
            $html[] = sprintf(Form::label("{$name}-{$k}", "%s {$v}"), Form::checkbox($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
        }
        return '<div class="checkbox">' . implode(' ', $html) . '</div>';
    }
}


if (!function_exists('build_category_select')) {

    /**
     * 生成分类下拉列表框
     * @param string $name
     * @param string $type
     * @param mixed $selected
     * @param array $attr
     * @param array $header
     * @return string
     */
    function build_category_select($name, $type, $selected = null, $attr = [], $header = [])
    {
        $tree = Tree::instance();
        $tree->init(Category::getCategoryArray($type), 'pid');
        $categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = $header ? $header : [];
        foreach ($categorylist as $k => $v) {
            $categorydata[$v['id']] = $v['name'];
        }
        $attr = array_merge(['id' => "c-{$name}", 'class' => 'form-control selectpicker'], $attr);
        return build_select($name, $categorydata, $selected, $attr);
    }
}

if (!function_exists('build_toolbar')) {

    /**
     * 生成表格操作按钮栏
     * @param array $btns 按钮组
     * @param array $attr 按钮属性值
     * @return string
     */
    function build_toolbar($btns = null, $attr = [])
    {
        $auth = \app\admin\library\Auth::instance();
        $controller = str_replace('.', '/', strtolower(think\Request::instance()->controller()));
        $btns = $btns ? $btns : ['refresh', 'add', 'edit', 'del', 'import'];
        $btns = is_array($btns) ? $btns : explode(',', $btns);
        $index = array_search('delete', $btns);
        if ($index !== false) {
            $btns[$index] = 'del';
        }
        $btnAttr = [
            'refresh' => ['javascript:;', 'btn btn-primary btn-refresh', 'fa fa-refresh', '', __('Refresh')],
            'add'     => ['javascript:;', 'btn btn-success btn-add', 'fa fa-plus', __('Add'), __('Add')],
            'edit'    => ['javascript:;', 'btn btn-success btn-edit btn-disabled disabled', 'fa fa-pencil', __('Edit'), __('Edit')],
            'del'     => ['javascript:;', 'btn btn-danger btn-del btn-disabled disabled', 'fa fa-trash', __('Delete'), __('Delete')],
            'import'  => ['javascript:;', 'btn btn-info btn-import', 'fa fa-upload', __('Import'), __('Import')],
        ];
        $btnAttr = array_merge($btnAttr, $attr);
        $html = [];
        foreach ($btns as $k => $v) {
            //如果未定义或没有权限
            if (!isset($btnAttr[$v]) || ($v !== 'refresh' && !$auth->check("{$controller}/{$v}"))) {
                continue;
            }
            list($href, $class, $icon, $text, $title) = $btnAttr[$v];
            //$extend = $v == 'import' ? 'id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"' : '';
            //$html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '" ' . $extend . '><i class="' . $icon . '"></i> ' . $text . '</a>';
            if ($v == 'import') {
                $template = str_replace('/', '_', $controller);
                $download = '';
                if (file_exists("./template/{$template}.xlsx")) {
                    $download .= "<li><a href=\"/template/{$template}.xlsx\" target=\"_blank\">XLSX模版</a></li>";
                }
                if (file_exists("./template/{$template}.xls")) {
                    $download .= "<li><a href=\"/template/{$template}.xls\" target=\"_blank\">XLS模版</a></li>";
                }
                if (file_exists("./template/{$template}.csv")) {
                    $download .= empty($download) ? '' : "<li class=\"divider\"></li>";
                    $download .= "<li><a href=\"/template/{$template}.csv\" target=\"_blank\">CSV模版</a></li>";
                }
                $download .= empty($download) ? '' : "\n                            ";
                if (!empty($download)) {
                    $html[] = <<<EOT
                        <div class="btn-group">
                            <button type="button" href="{$href}" class="btn btn-info btn-import" title="{$title}" id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"><i class="{$icon}"></i> {$text}</button>
                            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" title="下载批量导入模版">
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu" role="menu">{$download}</ul>
                        </div>
EOT;
                } else {
                    $html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '" id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"><i class="' . $icon . '"></i> ' . $text . '</a>';
                }
            } else {
                $html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '"><i class="' . $icon . '"></i> ' . $text . '</a>';
            }
        }
        return implode(' ', $html);
    }
}

if (!function_exists('build_heading')) {

    /**
     * 生成页面Heading
     *
     * @param string $path 指定的path
     * @return string
     */
    function build_heading($path = null, $container = true)
    {
        $title = $content = '';
        if (is_null($path)) {
            $action = request()->action();
            $controller = str_replace('.', '/', request()->controller());
            $path = strtolower($controller . ($action && $action != 'index' ? '/' . $action : ''));
        }
        // 根据当前的URI自动匹配父节点的标题和备注
        $data = Db::name('auth_rule')->where('name', $path)->field('title,remark')->find();
        if ($data) {
            $title = __($data['title']);
            $content = __($data['remark']);
        }
        if (!$content) {
            return '';
        }
        $result = '<div class="panel-lead"><em>' . $title . '</em>' . $content . '</div>';
        if ($container) {
            $result = '<div class="panel-heading">' . $result . '</div>';
        }
        return $result;
    }
}

if (!function_exists('up_user_level')) {
    //升级用户等级
    function up_user_level($user_id){
        if(!$user_id){
            return false;
        }
        //当前用户等级
        $user_info = \app\admin\model\User::where('id','=',$user_id)->find()->toArray();
        $next_level = \app\admin\model\member\Level::where('min_amount','<=',$user_info['total_amount'])->whereOr('min_score','<=',$user_info['total_score'])->order('level','desc')->find();
        if($next_level){
            if($next_level['id'] != $user_info['level_id']){
                $next_level = $next_level->toArray();
            }else{
                return true;
            }
        }else{
            return true;
        }
        $res = \app\admin\model\User::where('id','=',$user_id)->update(['level_id'=>$next_level['id']]);
        return $res ? true : false;
    }
}



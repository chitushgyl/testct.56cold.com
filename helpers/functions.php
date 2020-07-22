<?php
use app\models\AdminRole,
    yii\helpers\Url,
    app\models\AdminPermissions,
    app\models\Account;
/**
 * Created by Joker.
 * Date: 2019/7/4
 * Time: 13:53
 */
if(!function_exists("pr")){
    function pr($data=[]){
        echo '<pre>';
        var_dump($data);
    }
}

if(!function_exists("pj")){
    function pj($data){
        echo json_encode($data);
    }
}  


function str_to_utf8 ($str = '') {
    $current_encode = mb_detect_encoding($str, array("ASCII","GB2312","GBK",'BIG5','UTF-8')); 
    $encoded_str = mb_convert_encoding($str, 'UTF-8', $current_encode);
    return $encoded_str;

}

    /**
     * 单文件上传
     * 
     * 
     * path：文件保存目录
     */
    function base64_image_content($base64_image_content,$path){
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
            $type = $result[2];
            $new_file = dirname(__DIR__).'/web/uploads/'.$path.'/'.date('Y-m-d');
            if(!file_exists($new_file)){
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0777,true);
            }
            $picname=mt_rand(0,99).time().".{$type}";
            $new_file = $new_file.'/'.$picname;
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
                return '/uploads/'.$path.'/'.date('Y-m-d').'/'.$picname;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 判断是否是允许的图片格式
     * name：
     * ext： 
     * path：
     */
    function is_image($ext){
        $imgs = ['jpg','jpeg','png','gif'];
        if (in_array($ext,$imgs)) {
            return true;
        } else {
            return false;
        }
    }

if(!function_exists("precaution_xss")){
    /**
     * Created by Joker
     * Date: 2019/5/17
     * Time: 2:14 PM
     * Desc: 页面的 list 列表输出之前的对于数据的转义,避免 web 页面的 xss 攻击;
     * 由于页面的 list 是通过前端框架发送 ajax 来返回参数的,页面不能使用{{}}输出内容({{}}输出是自带转义的),所以不会对数据进行转义,
     * 所以需要在输出之前对于参数同一转义;
     * @param $data: 需要转义的参数
     * @return array|string
     */
    function precaution_xss($data){
        // 如果不是数组
        if(!is_array($data)){
            $data = htmlspecialchars($data);
        }else{
            foreach($data as $k=>$v){
                if (is_array($v)){
                    $data[$k] = precaution_xss($v);
                }else{
                    $data[$k] = htmlspecialchars($v);
                }
            }
        }
        return $data;
    }
}

if(!function_exists("bm_preg_match")){
    /**
     * Created by Joker
     * Date: 2019/5/20
     * Time: 1:24 PM
     * Desc: 验证手机号, 验证码格式, 密码强度 是否合法
     * @param $data
     * @param $type
     * @return bool
     */
    function bm_preg_match($data,$type){
        if(empty($data) || empty($type)){
            return false;
        }
        switch($type){
            case "mobile":
                // 手机号码
                if (!preg_match("/^\d{4,}$/", $data)){
                    return false;
                }
                break;
            case "password":
                if(strlen($data)<6 || strlen($data)>12){
                    return false;
                }
                break;
            case "idCard":
                if(!preg_match("/^\d{17}([0-9]|X)$/i", $data)){
                    return false;
                }
                break;
            case "email":
                if(!preg_match("/^[a-zA-Z0-9]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/ims", $data)){
                    return false;
                }
                break;
            default: return false;
        }
        return true;
    }
}

    /**
     * Created by pysh
     * Date: 2020/2/5
     * Time: 
     * Desc: 验证手机号
     * @param $price
     * @return mixed
     */
    function checkMobile($mobile){
        if (preg_match("/^\d{4,}$/", $mobile)){
            return true;
        } else {
            return false;  
        }
    }

if(!function_exists("format_price")){
    /**
     * Created by Joker
     * Date: 2019/5/29
     * Time: 11:05 AM
     * Desc: 价格，带小数点返回小数点后两位，否则显示整数
     * @param $price
     * @return mixed
     */
    function format_price($price){
        $price_arr = explode('.',$price);
        if (!isset($price_arr[1]))
            return $price;
        $decimal = $price_arr[1];
        if (intval($decimal) > 0)
            return bcadd($price,0,2);
        else
            return $price_arr[0];
    }
}

if(!function_exists("activity_code")){
    /**
     * Desc: 生成活动编号:全字母
     * Created by Joker
     * Date: 2019/6/6
     * Time: 17:13
     * @return string
     */
    function activity_code(){
        return date("YmdHis") . rand(1000,9999);
    }
}

if(!function_exists("array_revKey")){
    /**
     * Desc: 将二维数组的最底层 key 值赋值为第二层的指定 key 的 value 值
     * Created by Joker
     * Date: 2019/6/10
     * Time: 10:34
     * @param $arr
     * @param $key
     * @return array
     */
    function array_revKey($arr,$key){
        $array = [];
        foreach($arr as $v){
            if(isset($v[$key])){
                $array[$v[$key]] = $v;
            }
        }
        return $array;
    }
}

if(!function_exists("array_order")){
    /**
     * Desc: 二维数组,按照指定字段的大小排序
     * Created by Joker
     * Date: 2019/6/12
     * Time: 11:36
     * @param $arr 要排序的数组
     * @param $key 指定的字段
     * @param $order_type 排序要求: desc 倒序  asc 正序
     * @return mixed
     */
    function array_order($arr,$key,$order_type){
        foreach($arr as $k=>$v){
            if(!isset($v[$key])){
                return $arr;
            }
        }
        $arr_count = count($arr);
        for ($i=0;$i<$arr_count-1;$i++){
            for ($j=0;$j<$arr_count-1;$j++){
                $temp_1 = $arr[$j+1];
                $temp_2 = $arr[$j];
                if($arr[$j][$key] < $arr[$j+1][$key]){
                    if($order_type == 'DESC'){// 倒序
                        $arr[$j] = $temp_1;
                        $arr[$j+1] = $temp_2;
                    }
                }elseif($arr[$j][$key] > $arr[$j+1][$key]){
                    if($order_type == 'ASC'){// 倒序
                        $arr[$j] = $temp_2;
                        $arr[$j+1] = $temp_1;
                    }
                }
            }
        }
        return $arr;
    }
}

if(!function_exists("create_admin_password")){
    /**
     * Desc:
     * Created by Joker
     * Date: 2019/7/4
     * Time: 15:05
     * @param $arr
     * @param $salt
     * @return mixed
     */
    function create_admin_password($arr,$salt){
        $arr = sha1(substr(sha1($arr),0,20) . substr(sha1($salt),20,20));
        return $arr;
    }
}

if(!function_exists("create_password")){
    /**
     * Desc:
     * Created by Joker
     * Date: 2019/7/4
     * Time: 15:05
     * @param $arr
     * @param $salt
     * @return mixed
     */
    function create_password($arr,$salt){
        $arr = sha1(substr(sha1($arr),0,30) . substr(sha1($salt),20,30));
        return $arr;
    }
}

if(!function_exists('can')){
    /**
     * Desc: 验证登录用户是否有路由的权限
     * Created by pysh
     * Date: 2019/2/5
     * Time: 10:27
     * @param $url
     * @return bool
     */
    function can($url){
        // 先判断该路由是否在权限控制表中,如果不在默认有权限,如果在则判断是否给该用户配置了权限
        $isAdd = AdminPermissions::find()->select(['id'])->where(['route'=>$url])->asArray()->one();
        if(!$isAdd){
            return true;
        }else{
            $admin_id = $_SESSION['admin_id'];
            $user = Account::findOne(['id' => $admin_id]);
            if ($user->username == 'admin') {
                return true;
            } else {
                
                    $permissions = AdminRole::find()->select(['permissions'])->where(['id'=>$user['position']])->asArray()->one();
                    $permissions['permissions'] = $permissions['permissions']?$permissions['permissions']:'';
                    $permissions = explode(',',$permissions['permissions']);
                
                
                $adminP = AdminPermissions::find()->where(['id'=>$isAdd['id']])->asArray()->one();
                if(in_array($isAdd['id'],$permissions) && $adminP['status'] == 0){
                    return  true;
                }else{
                    return false;
                }
            }
        }
    }
}

if(!function_exists('route')){
    /**
     * Desc: 路由函数
     * Created by Joker
     * Date: 2019/7/5
     * Time: 10:42
     * @param $url
     * @param string $data
     * @return mixed
     */
    function route($url,$data=''){
        $url = '/' . str_replace('.','/',$url);
        return Url::to([$url . '?' . $data]);
    }
}

if(!function_exists('list_to_tree')) {
    function list_to_tree($list)
    {
        $tree = array();// 创建Tree

        if(is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data['id']] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId =  $data['parent_id'];
                if (0 == $parentId) {
                    $tree[] =& $list[$key];
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent['son'][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }
}

if(!function_exists('treeOption')) {
    function treeOption($tree,$info,$leravel = 0)
    {
        foreach($tree as $v) {
            if((!empty($info['id']) && $v['id'] != $info['id'])  || empty($info['id'])){// 在修改权限时,不能选择自己或者自己权限下面的子级权限
                echo "<option value='" , $v['id'] , "'";
                if(!empty($info)){
                    if ($v['id'] == $info['parent_id']) {
                        echo "selected";
                    }
                }
                echo ">";
                if ($leravel == 0) {
                    echo $v['display_name'],"</option>";
                } else {
                    echo "┗",str_repeat("━",$leravel*2),$v['display_name'],"</option>";
                }

                if (isset($v['son'])) {
                    treeOption($v['son'],$info,$leravel+1);
                }
            }
        }

    }
}

if(!function_exists('setOwn')) {
    function setOwn($tree,$arr)
    {
        foreach($tree as $k=>$v) {
            if(in_array($v['id'],$arr)){
                $tree[$k]['own'] = 'checked';
            }else{
                $tree[$k]['own'] = '';
            }

            if(!empty($v['son'])){
                $tree[$k]['son'] = setOwn($v['son'],$arr);
            }
        }
        return $tree;
    }
}

if(!function_exists("createContent")){
    /**
     * Desc: 公共的修改信息模板文件的內容
     * Created by: Joker
     * Date: 2019/8/2
     * Time: 16:46
     * @param $content
     * @param $data
     * @return mixed
     */
    function createContent($content,$data){
        foreach($data as $k=>$v){
            $content = str_replace('{{'.$k.'}}',$v,$content);
        }
        return $content;
    }
}

if (!function_exists('get_week')){
    function get_week($date){
        //获取数字型星期几
        $number_wk = date("w",strtotime($date));

        //自定义星期数组
        $weekArr = array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
        $weekArrEn = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");

        //获取数字对应的星期
        return [$weekArr[$number_wk],$weekArrEn[$number_wk]];
    }
}

if (!function_exists('get_ymd')){
    function get_ymd($data){
        $data = date("Y-n-j",strtotime($data));
        $arr = explode('-',$data);
        $month = [
            '1'=>'January',
            '2'=>'February',
            '3'=>'March',
            '4'=>'April',
            '5'=>'May',
            '6'=>'June',
            '7'=>'July',
            '8'=>'August',
            '9'=>'September',
            '10'=>'October',
            '11'=>'November',
            '12'=>'December'
        ];
        return  ['year'=>$arr[0],'month'=>$arr[1],'day'=>$arr[2],'month_en'=>$month[$arr[1]]];
    }
}

if (!function_exists("create_sign_str")){
    function create_sign_str($data){
        return sha1(sha1(md5(json_encode($data).\Yii::$app->params['SIGN_STR'])));
    }
}

if (!function_exists("create_check_mac_value")){
    /**
     * Desc: 生成綠界檢查碼
     * Created by: Joker
     * Date: 2019/8/12
     * Time: 15:26
     * @param $data
     * @return string
     */
    function create_check_mac_value($data){
        ksort($data);
        $str = 'HashKey='.\Yii::$app->params['EC_PAY']['HashKey'].'&';
        foreach($data as $k=>$v){
            $str .= $k.'='.$v.'&';
        }
        $str .= 'HashIV='.\Yii::$app->params['EC_PAY']['HashIV'];
        return strtoupper(md5(strtolower(urlencode($str))));
    }
}

if (!function_exists("create_merchant_trade_no")){
    /**
     * Desc: 自動生成綠界訂單號
     * Created by: Joker
     * Date: 2019/8/13
     * Time: 15:01
     * @param $flag: 1後台添加
     * @param $type: 訂單商品種類 該值小於10  1 表示套餐 2 表示單次卡 3 表示實物商品4 表示其他 5特惠卡 6入会金
     * @return string
     */
    function create_merchant_trade_no($type=1,$flag=0){
        $type = substr($type,0,1);
        if($flag){
            $prefix = 'HT';// 後臺添加
        }else{
            $prefix = 'BM';// 用戶自助下單
        }
        $mid = create_code(1,17);
        return $prefix . $type . $mid;
    }
}

if (!function_exists("getNextMonthDays")){
    /**
     * Desc:
     * Created by: Joker
     * Date: 2019/9/3
     * Time: 16:18
     * @param $date
     * @param $months
     * @return false|string
     */
    function getNextMonthDays($date,$months){
        $first_day = date('Y-m-01', strtotime($date));
        $months ++;
        $next_last_day = strtotime("$first_day +$months month -1 day");
        $day_last_day = date('d', $next_last_day); //获取下个月份的最后一天
        $day_ben_last_day = date('t', strtotime($first_day)); //获取本月份的最后一天

        //获取当天日期
        $same_day = date('d', strtotime($date));
        //判断当天是否是最后一天   或 下月最后一天 等于 本月的最后一天
        if($same_day == $day_ben_last_day && $same_day >= $day_last_day){
            $day = $day_last_day;
        }else{
            $day = $same_day;
        }
        $day = date('Y',$next_last_day).'-'.date('m',$next_last_day).'-'.$day;
        return $day;
    }
}

if (!function_exists("distanceMonths")){
    /**
     * Desc: 获取两个时间的相差多少个月
     * Created by: Joker
     * Date: 2019/9/6
     * Time: 18:32
     * @param $big
     * @param $little
     * @return false|float|int|string
     */
    function distanceMonths($big,$little){
        $bigY = date("Y",strtotime($big));
        $bigM = date("n",strtotime($big));
        $littleY = date("Y",strtotime($little));
        $littleM = date("n",strtotime($little));
        $diffM = ($bigY - $littleY)*12 + ($bigM - $littleM);
        return $diffM;
    }
    /*
     * 发送短信
     * */
    function send_sms($type,$mobile,$str=''){
        header("Content-Type: text/html; charset=UTF-8");
        $flag = 0;
        $params='';//要post的数据
        $verify = mt_rand(1000,9999);//获取4位随机验证码
        if($type == '1'){
            $content ='您的验证码为:'.$verify.',有效期10分钟,请及时完成验证操作';
        }elseif($type == '2'){
            $content = "您好,".$str." 的验证码为：".$verify;
        }

        //以下信息自己填
        $argv = array(
            'name'=>'chitushgyl',     //必填参数。用户账号
            'pwd'=>'31EADA99CDDCF8B08C230CF995EA',     //必填参数。（web平台：基本资料中的接口密码）
            'content'=>$content,   //必填参数。发送内容（1-500 个汉字）UTF-8编码
            'mobile'=>$mobile,   //必填参数。手机号码。多个以英文逗号隔开
            'stime'=>'',   //可选参数。发送时间，填写时已填写的时间发送，不填时为当前时间发送
            'sign'=>'赤途冷链',    //必填参数。用户签名。
            'type'=>'pt',  //必填参数。固定值 pt
            'extno'=>''    //可选参数，扩展码，用户定义扩展码，只能为数字
        );

        //构造要post的字符串
        foreach ($argv as $key=>$value) {
            if ($flag!=0) {
                $params .= "&";
                $flag = 1;
            }
            $params.= $key."="; $params.= urlencode($value);// urlencode($value);
            $flag = 1;
        }

        $url = "http://web.duanxinwang.cc/asmx/smsservice.aspx?".$params; //提交的url地址
        $con= substr( file_get_contents($url), 0, 1 );  //获取信息发送后的状态

        if($con == '0'){
            $result['status'] = '1'; //发送成功
        }else{
            $result['status'] = '2';//发送失败
        }
        $result['verify'] =$verify;
        return $result;
    }

    /*
     * 百度接口获取经纬度
     * $type 1市到市距离 2具体区域到具体区域距离
     * $city 市
     * $area 区具体位置
     * return 经纬度
     * */
    function bd_local($type,$city,$area){
        //$ak = '49tGEmabyb7q6NMwks789zvHfZ39dTsh';
        $ak ="SdRptW2rs3xsjHhVhQOy17QzP6Gexbp6";
        if($type == '1'){
            $address = $city."市委";
        }else{
            $address = $area;
        }
        $url ="http://api.map.baidu.com/geocoder/v2/?callback=renderOption&output=json&address=".$address."&city=".$city."&ak=".$ak;
        $renderOption = file_get_contents($url);
        preg_match("/.*\((.*)\)/",$renderOption,$result);
        $res = json_decode($result[1],true);
        if($res['status'] == '0'){
            $finlly = $res['result']['location'];
        }else{
            $finlly = '';
        }
        return $finlly;
    }

    /*
 * 根据经纬度获取行车距离
 * */
    function direction($lat1, $lng1, $lat2, $lng2){
        if(empty($lat1) || empty($lng1) || empty($lat2) || empty($lng2)){
            return '';
        }
        $ak ="SdRptW2rs3xsjHhVhQOy17QzP6Gexbp6";
        $url = "http://api.map.baidu.com/direction/v2/driving?output=json&tactics=0&origin=".$lat1.",".$lng1."&destination=".$lat2.",".$lng2."&ak=".$ak;

        $renderOption = file_get_contents($url);
        $result = json_decode($renderOption,true);

        if ($result['status'] == '0') {
            $res['distance'] = $result['result']['routes'][0]['distance'];
            $res['duration'] = $result['result']['routes'][0]['duration'];
        }else{
            $res='';
        }

        return $res;
    }
}

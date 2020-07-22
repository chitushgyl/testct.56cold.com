<?php

namespace app\modules\api\controllers;

use app\models\AppCartype;
use app\models\AppCommonAddress;
use app\models\AppCommonContacts;
use app\models\AppGroup;
use app\models\AppOrder;
use app\models\AppPayment;
use app\models\AppReceive;
use app\models\AppVehical;
use app\models\Customer;
use app\models\Carriage;
use app\models\Car;
use app\models\District;
use Yii;
/**
 * Default controller for the `api` module
 */
class ExcelController extends CommonController
{

    /*
     * 整车订单导入
     * */
    public function actionVehical_into(){
        $input = \Yii::$app->request->post();
//        $token = $input['token'];
        $file = $_FILES['file'];
        $group_id = $input['group_id'];
//        $check_result = $this->check_token($token,true);//验证令牌
//        $user = $check_result['user'];
        $path =  $this->Upload('order',$file);
        $flag = '';
        $float = '';
        $res = [];
        $arr = [];
        if ($file != ''){
            $list = $this->reander(Yii::$app->basePath.'/web/'.$path);//导入
//            var_dump($list);
//            exit();
            foreach ($list as $key =>$value){
                if (!(array_filter($value))){
                    continue;
                }
                $vehical = new AppVehical();
                $vehical->ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                $vehical->takenumber = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                $vehical->startcity = $value['B'];
                $vehical->endcity = $value['C'];
                $time_start = gmdate('Y-m-d H:i:s', ($value['D'] - 25569) * 3600 * 24);

                if ($time_start){
                    $vehical->time_start = $time_start;
                }else{
                    $flag = 'D';
                }
                $vehical->order_own = 1;
                $group= AppGroup::find()->where(['id'=>$group_id])->one();
                if ($group->level_id != 3){
                    if ($value['E'] == ''){
                        $flag = 'E';
                        $float = '客户公司不能为空';
                    }
                    $customer = Customer::find()->where(['id'=>$group_id])->andWhere(['like','all_name',$value['E']])->one();
                    if ($customer){
                        $vehical->company_id = $customer->id;
                        $vehical->order_own = 2;
                    }else{
                        $flag = 'E';
                        $float = '没有找到该客户公司';
                    }
                }
                $vehical->cargo_name = $value['F'];
                $startstr = $this->decirde_address($value['G']);
                $endstr = $this->decirde_address($value['H']);
                $pname = ['contant'=>$value['I'],'tel'=>$value['J']];
                $sname = ['contant'=>$value['K'],'tel'=>$value['L']];
                $startstr = json_encode([array_merge($startstr,$pname)],JSON_UNESCAPED_UNICODE);
                $endstr = json_encode([array_merge($endstr,$sname)],JSON_UNESCAPED_UNICODE);

                $vehical->startstr = $startstr;
                $vehical->endstr = $endstr;
                $vehical->temperture = $value['M'];
                $vehical->remark = $value['N'];
                if ($value['O'] == 'Y'){
                    $vehical->picktype = 1;
                }else{
                    $vehical->picktype = 2;
                }
                if ($value['P'] == 'Y'){
                    $vehical->sendtype = 1;
                }else{
                    $vehical->sendtype = 2;
                }
                if(preg_match("/^\d+(\.\d+)?$/",$value['Q'])){
                    $vehical->pickprice = $value['Q'];
                }else{
                    $flag = 'Q';
                    $float = '请填写正确的价格';
                }
                if(preg_match("/^\d+(\.\d+)?$/",$value['R'])){
                    $vehical->sendprice = $value['R'];
                }else{
                    $flag = 'R';
                    $float = '请填写正确的价格';
                }
                if(preg_match("/^\d+(\.\d+)?$/",$value['S'])){
                    $vehical->price = $value['S'];
                }else{
                    $flag = 'S';
                    $float = '请填写正确的价格';
                }
                if(preg_match("/^\d+(\.\d+)?$/",$value['T'])){
                    $vehical->otherprice = $value['T'];
                }else{
                    $flag = 'T';
                    $float = '请填写正确的价格';
                }
                $vehical->otherprice = $value['T'];
                $vehical->order_type = 1;
//                $vehical->create_user_id = $user['id'];
//                $vehical->create_user_name = $user['name'];
//                $vehical->group_id = $group_id;
//                $vehical->group_name = $user['id'];
                $vehical->total_price = floor($vehical->pickprice) + floor($vehical->sendprice) + floor($vehical->price) + floor($vehical->otherprice);

                $insert = $vehical->save();
                if ($insert){
                    $res1 = true;
                    array_push($res,$vehical->id);
                }else{
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $res1 = false;
                }
                array_push($arr,$res1);
            }
                if(!(in_array(false,$arr))){
                    $data = ['code'=>200,'msg'=>'导入成功'];
                    return $this->resultInfo($data);
                }else{
                    foreach($res as $key =>$value){
                        $model = AppVehical::findOne($value);
                        $model->delete();
                    }
                    $data = ['code'=>400,'msg'=>'导入失败','data'=>$error];
                   return $this->resultInfo($data);
                }

        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择导入数据']);
            return $this->resultInfo($data);
        }
    }

    public function actionVehical_order_model(){
        $input = \Yii::$app->request->post();
        $token = $input['token'];
        $file = $_FILES['file'];
        $group_id = $input['group_id'];
        $check_result = $this->check_token($token,true);//验证令牌
        $excel_type = array('xlsx');
        $file_types = explode ( ".", $file['name'] );
        $excel_type = array('xlsx');
        if (!in_array(strtolower(end($file_types)),$excel_type)){
            $data = $this->encrypt(['code'=>400,'msg'=>'不是Excel文件，请重新上传']);
            return $this->resultInfo($data);
        }

        $path =  $this->Upload('excel',$file);
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');  
        $objRead = new \PHPExcel_Reader_Excel2007();
        $obj = $objRead->load(Yii::$app->basePath.'/web/'.$path);  //建立excel对象
        $currSheet = $obj->getSheet(0);   //获取指定的sheet表  
        $columnH = $currSheet->getHighestColumn();   //取得最大的列号  
        $columnCnt = array_search($columnH, $cellName);  
        $rowCnt = $currSheet->getHighestRow();   //获取总行数
        $data = array();  
        // 行
        for($_row=5; $_row<=$rowCnt; $_row++){  //读取内容  
            // 列
            for($_column=0; $_column<=$columnCnt; $_column++){  
                $cellId = $cellName[$_column].$_row;  
                $cellValue = $currSheet->getCell($cellId)->getValue();  //获取内容

                $data[$_row][$cellName[$_column]] = $cellValue; 
            }  

        }  
        $data = $this->encrypt(['code'=>400,'msg'=>'highestRow','arr'=>$data]);
        return $this->resultInfo($data);
    }
    public function actionCustomer_upload(){
        header('content-type:application:json;charset=utf8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $input = \Yii::$app->request->post();
        $file = $_FILES['file'];
        $group_id = $input['group_id'];
        $this->check_upload_file($file['name']);
        $company_id = $input['customer_id'];
        $info= [];
        $startstrs = [];
        $endstrs = [];
        $start= [];
        $end = [];

        if ($file['tmp_name'] != ''){
            $path =  $this->Upload('vehical',$file);
            $list = $this->reander_more(Yii::$app->basePath . '/web/' . $path);//导入
            if (!$list) {
                $data = $this->encrypt(['code'=>400,'msg'=>'导入数据不能为空']);
                return $this->resultInfo($data);
            }

            $save_start_arr = [];
            $save_end_arr = [];
            foreach ($list as $key =>$value){
                $arr = [];
                $ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                $arr['ordernumber'] = $ordernumber;
                $arr['takenumber'] = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                $arr['group_id'] = $group_id;
                $arr['startcity'] = $value['M'];
                $arr['endcity'] = $value['S'];
                if ($value['B']){
                    $arr['time_start'] = gmdate('Y-m-d H:i:s', \PHPExcel_Shared_Date::ExcelToPHP($value['B']));
                }else{
                    $flag = 'B';
                    $float = '请填写装车时间';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if ($value['C']){
                    $arr['time_end'] = gmdate('Y-m-d H:i:s', \PHPExcel_Shared_Date::ExcelToPHP($value['C']));
                }else{
                    $flag = 'C';
                    $float = '请填写要求到达时间';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if ($value['D']){
                    $cartype = AppCartype::find()->where(['carparame'=>$value['D']])->one();
                    if (empty($cartype->car_id)) {
                        $flag = 'D';
                        $float = '车辆类型错误';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                    $arr['cartype'] = $cartype->car_id;
                }else{
                    $flag = 'D';
                    $float = '请填写车辆类型';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $group= AppGroup::find()->where(['id'=>$group_id])->one();
                if ($group->main_id != 1) {
                    $group= AppGroup::find()->where(['id'=>$group->group_id])->one();
                }

                $customer = Customer::find()->where(['group_id'=>$group_id,'all_name'=>$value['E']])->one();
                if ($customer){
                    $arr['company_id'] = $customer->id;
                    $arr['paytype'] = $customer->paystate;
                }else{
                    $arr['company_id'] = $company_id;
                    $arr['paytype'] = $customer->paystate;
                }

                if (!$value['F']) {
                    $flag = 'F';
                    $float = '货品名称不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['name'] = $value['F'];

                if (!$value['G']) {
                    $flag = 'G';
                    $float = '温度不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {
                    $arr_tem = ['冷冻','冷藏','常温','恒温','冷冻/冷藏'];
                    if (!in_array($value['G'],$arr_tem)) {
                        $flag = 'G';
                        $float = '温度必须选择：冷冻、冷藏、常温、恒温、冷冻/冷藏';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }
                $arr['temperture'] = $value['G'];

                if ($value['G'] == '冷冻/冷藏') {
                    if (!$value['H']) {
                        $flag = 'H';
                        $float = '温度是冷冻/冷藏，必须填写冷冻件数';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                    if (!$value['I']) {
                        $flag = 'I';
                        $float = '温度是冷冻/冷藏，必须填写非冷冻件数';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }

                $arr['number'] = $value['I'];
                $arr['number2'] = $value['H'];
                $arr['weight'] = $value['J'];
                $arr['volume'] = $value['K'];

                $start_pro = $value['L'];
                $start_city = $value['M'];
                $start_area = $value['N'];
                $start_info = $value['O'];

                $start_flag = $this->check_address($start_pro,$start_city,$start_area);
                if ($start_flag['position'] != 'ok') {
                    if ($start_flag['position'] == 'pro') {
                        $flag = 'L';
                    } else if($start_flag['position'] == 'city') {
                        $flag = 'M';
                    } else if($start_flag['position'] == 'area') {
                        $flag = 'N';
                    }
                    $float = $start_flag['msg'];
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$start_info) {
                    $flag = 'O';
                    $float = '提货详细地址不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['P']) {
                    $flag = 'P';
                    $float = '提货联系人不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['name'] = $value['F'];

                if (!$value['Q']) {
                    $flag = 'Q';
                    $float = '提货联系人电话不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $start_arr = ['pro'=>$start_pro,'city'=>$start_city,'area'=>$start_area,'info'=>$start_info,'contant'=>$value['P'],'tel'=>$value['Q']];
                $save_start_arr[] = $start_arr;
                $arr['startstr'] = json_encode([$start_arr],JSON_UNESCAPED_UNICODE);

                $end_pro = $value['R'];
                $end_city = $value['S'];
                $end_area = $value['T'];
                $end_info = $value['U'];
                $end_flag = $this->check_address($end_pro,$end_city,$end_area);
                if ($end_flag['position'] != 'ok') {
                    if ($end_flag['position'] == 'pro') {
                        $flag = 'R';
                    } else if($end_flag['position'] == 'city') {
                        $flag = 'S';
                    } else if($end_flag['position'] == 'area') {
                        $flag = 'T';
                    }
                    $float = $end_flag['msg'];
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$end_info) {
                    $flag = 'U';
                    $float = '送货详细地址不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['V']) {
                    $flag = 'V';
                    $float = '送货联系人不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['W']) {
                    $flag = 'W';
                    $float = '送货联系人电话不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $end_arr = ['pro'=>$end_pro,'city'=>$end_city,'area'=>$end_area,'info'=>$end_info,'contant'=>$value['V'],'tel'=>$value['W']];
                $save_end_arr[] = $end_arr;
                $arr['endstr'] = json_encode([$end_arr],JSON_UNESCAPED_UNICODE);

                $is_yn = ['需要','不需要'];
                $picktype = strtoupper($value['X']);
                if (!$picktype) {
                    $flag = 'X';
                    $float = '司机/物流装货不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {

                    if (!in_array($picktype,$is_yn)) {
                        $flag = 'X';
                        $float = '司机/物流装货只能填写:按要求填写';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }
                if ($picktype == '需要') {
                    $arr['picktype'] = 1;
                    if ($value['Z']) {
                        $arr['pickprice'] = (float)$value['Z'];
                    }else{
                        $flag = 'Z';
                        $float = '请填写装货价格';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                } else if ($picktype == '不需要') {
                    $arr['picktype'] = 2;
                    $arr['pickprice'] = '';
                }

                $sendtype = strtoupper($value['Y']);
                if (!$sendtype) {
                    $flag = 'Y';
                    $float = '司机/物流装货不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {
                    if (!in_array($sendtype,$is_yn)) {
                        $flag = 'Y';
                        $float = '司机/物流卸货只能填写:需要、不需要';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }

                if ($sendtype == '需要') {
                    $arr['sendtype'] = 1;
                    if ($value['AA']) {
                        $arr['sendprice'] = (float)$value['AA'];
                    }else{
                        $flag = 'AA';
                        $float = '请填写卸货价格';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                } else if ($sendtype == '不需要') {
                    $arr['sendtype'] = 2;
                    $arr['sendprice'] = '';
                }

                if (!$value['AB']) {
                    $flag = 'AB';
                    $float = '运输费不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['price'] = (float)$value['AB'];
                $arr['more_price'] = (float)$value['AC'];
                $arr['otherprice'] = (float)$value['AD'];
                $arr['remark'] = $value['AE'];


                $arr['order_type'] = 1;

                $arr['create_time'] = $arr['update_time'] = date('Y-m-d H:i:s',time());
                $arr['total_price'] = (float)$arr['pickprice'] + (float)$arr['sendprice'] + $arr['price'] + $arr['otherprice'] + $arr['more_price'];
                $arr['money_state'] = 'N';
                $info[] = $arr;

                $receive['compay_id'] = $arr['company_id'];
                $receive['receivprice'] = $arr['total_price'];
                $receive['trueprice'] = 0;
                $receive['receive_info'] ='';
//                $receive['create_user_id'] = $user->id;
//                $receive['create_user_name'] = $user->name;
                $receive['group_id'] = $group_id;
                $receive['paytype'] = $arr['paytype'];
                $receive['ordernumber'] = $arr['ordernumber'];
                $receive['type'] = 1;
                $receive['create_time'] = $receive['update_time'] = date('Y-m-d H:i:s',time());
                $receive_info[] = $receive;
            }

            $transaction= AppOrder::getDb()->beginTransaction();
            try{
                $res = Yii::$app->db->createCommand()->batchInsert(AppOrder::tableName(), ['ordernumber','takenumber','group_id','startcity','endcity','time_start','time_end','cartype','company_id','paytype', 'name','temperture','number','number2','weight','volume','startstr','endstr','picktype','pickprice','sendtype','sendprice','price','more_price','otherprice','remark','order_type','create_time','update_time','total_price','money_state'], $info)->execute();
                $res_r = Yii::$app->db->createCommand()->batchInsert(AppReceive::tableName(), ['compay_id','receivprice','trueprice','receive_info','group_id','paytype','ordernumber','type','create_time','update_time'], $receive_info)->execute();
                $arr = $this->insert_id($receive_info);
                if ($res && $arr && $res_r){
                    $transaction->commit();
                    $data = $this->encrypt(['code'=>200,'msg'=>'导入成功']);
                    return $this->resultInfo($data);
                }else{
                    $transaction->rollBack();
                    $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                    return $this->resultInfo($data);
                }
            }catch(\Exception $e){
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择导入数据']);
            return $this->resultInfo($data);
        }
    }

    public function insert_id($arr){
        $flag = true;
        foreach ($arr as $key =>$value){
            $order = AppOrder::find()->where(['ordernumber'=>$value['ordernumber']])->one();
            $receive = AppReceive::find()->where(['ordernumber'=>$value['ordernumber']])->one();
            $receive->order_id = $order->id;
            $res = $receive->save();
            if (!$res){
                $flag = false;
                break;
            }
        }
        return $flag;
    }
    /*
     * 客户端零担订单导入
     * */
    public function actionCarriage_upload(){
        header('content-type:application:json;charset=utf8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $input = \Yii::$app->request->post();

        $file = $_FILES['file'];
        $group_id = $input['group_id'];
        $customer_id = $input['customer_id'];
        $this->check_upload_file($file['name']);
        $info= [];
        $startstrs = [];
        $endstrs = [];
        $start= [];
        $end = [];
        if ($file['tmp_name'] != ''){
            $path =  $this->Upload('bulk',$file);
            $list = $this->reander_more(Yii::$app->basePath . '/web/' . $path);//导入
            if (!$list) {
                $data = $this->encrypt(['code'=>400,'msg'=>'导入数据不能为空']);
                return $this->resultInfo($data);
            }
            $save_start_arr = [];
            $save_end_arr = [];
            foreach ($list as $key =>$value){

                $arr = [];
                $ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                $arr['ordernumber'] = $ordernumber;
                $arr['takenumber'] = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                $arr['group_id'] = $group_id;
                $arr['startcity'] = $value['K'];
                $arr['endcity'] = $value['Q'];
                if ($value['B']){
                    $arr['time_start'] = gmdate('Y-m-d H:i:s', \PHPExcel_Shared_Date::ExcelToPHP($value['B']));
                }else{
                    $flag = 'B';
                    $float = '请填写装车时间';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $group= AppGroup::find()->where(['id'=>$group_id])->one();
                if ($group->main_id != 1) {
                    $group= AppGroup::find()->where(['id'=>$group->group_id])->one();
                }
                $customer = Customer::find()->where(['group_id'=>$group_id,'all_name'=>$value['C']])->one();
                if ($customer){
                    $arr['company_id'] = $customer->id;
                    $arr['paytype'] = $customer->paystate;
                }else{
                    $arr['company_id'] = $customer_id;
                    $arr['paytype'] = $customer->paystate;
                }

                if (!$value['D']) {
                    $flag = 'D';
                    $float = '货品名称不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['name'] = $value['D'];

                if (!$value['E']) {
                    $flag = 'E';
                    $float = '温度不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {
                    $arr_tem = ['冷冻','冷藏','常温','恒温','冷冻/冷藏'];
//                    var_dump(in_array($value['F'],$arr_tem));
//                    exit();
                    if (!in_array($value['E'],$arr_tem)) {
                        $flag = 'E';
                        $float = '温度必须选择：冷冻、冷藏、常温、恒温、冷冻/冷藏';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }
                $arr['temperture'] = $value['E'];
                if ($value['G'] == '冷冻/冷藏') {
                    if (!$value['F']) {
                        $flag = 'F';
                        $float = '温度是冷冻/冷藏，必须填写冷冻件数';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                    if (!$value['G']) {
                        $flag = 'G';
                        $float = '温度是冷冻/冷藏，必须填写非冷冻件数';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }

                $arr['number'] = $value['F'];
                $arr['number2'] = $value['G'];
                $arr['weight'] = $value['H'];
                $arr['volume'] = $value['I'];

                $start_pro = $value['J'];
                $start_city = $value['K'];
                $start_area = $value['L'];
                $start_info = $value['M'];

                $start_flag = $this->check_address($start_pro,$start_city,$start_area);
                if ($start_flag['position'] != 'ok') {
                    if ($start_flag['position'] == 'pro') {
                        $flag = 'J';
                    } else if($start_flag['position'] == 'city') {
                        $flag = 'K';
                    } else if($start_flag['position'] == 'area') {
                        $flag = 'L';
                    }
                    $float = $start_flag['msg'];
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$start_info) {
                    $flag = 'M';
                    $float = '提货详细地址不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['N']) {
                    $flag = 'N';
                    $float = '提货联系人不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['name'] = $value['D'];

                if (!$value['O']) {
                    $flag = 'O';
                    $float = '提货联系人电话不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $start_arr = ['pro'=>$start_pro,'city'=>$start_city,'area'=>$start_area,'info'=>$start_info,'contant'=>$value['N'],'tel'=>$value['O']];
                $save_start_arr[] = $start_arr;
                $arr['startstr'] = json_encode([$start_arr],JSON_UNESCAPED_UNICODE);

                $end_pro = $value['P'];
                $end_city = $value['Q'];
                $end_area = $value['R'];
                $end_info = $value['S'];
                $end_flag = $this->check_address($end_pro,$end_city,$end_area);
                if ($end_flag['position'] != 'ok') {
                    if ($end_flag['position'] == 'pro') {
                        $flag = 'P';
                    } else if($end_flag['position'] == 'city') {
                        $flag = 'Q';
                    } else if($end_flag['position'] == 'area') {
                        $flag = 'R';
                    }
                    $float = $end_flag['msg'];
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$end_info) {
                    $flag = 'S';
                    $float = '送货详细地址不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['T']) {
                    $flag = 'T';
                    $float = '送货联系人不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['U']) {
                    $flag = 'U';
                    $float = '送货联系人电话不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $end_arr = ['pro'=>$end_pro,'city'=>$end_city,'area'=>$end_area,'info'=>$end_info,'contant'=>$value['T'],'tel'=>$value['U']];
                $save_end_arr[] = $end_arr;
                $arr['endstr'] = json_encode([$end_arr],JSON_UNESCAPED_UNICODE);

                $is_yn = ['需要','不需要'];
                $picktype = strtoupper($value['V']);
                if (!$picktype) {
                    $flag = 'V';
                    $float = '提货服务不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {

                    if (!in_array($picktype,$is_yn)) {
                        $flag = 'V';
                        $float = '提货服务只能填写:按要求填写';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }
                if ($picktype == '需要') {
                    $arr['picktype'] = 1;
                    if ($value['X']) {
                        $arr['pickprice'] = (float)$value['X'];
                    }else{
                        $flag = 'X';
                        $float = '提货费不能为空';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                } else if ($picktype == '不需要') {
                    $arr['picktype'] = 2;
                    $arr['pickprice'] = '';
                }

                $sendtype = strtoupper($value['W']);
                if (!$sendtype) {
                    $flag = 'W';
                    $float = '配送服务不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {
                    if (!in_array($sendtype,$is_yn)) {
                        $flag = 'W';
                        $float = '配送服务只能填写:需要、不需要';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }

                if ($sendtype == '需要') {
                    $arr['sendtype'] = 1;
                    if ($value['Y']) {
                        $arr['sendprice'] = (float)$value['Y'];
                    }else{
                        $flag = 'Y';
                        $float = '配送费不能为空';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                } else if ($sendtype == '不需要') {
                    $arr['sendtype'] = 2;
                    $arr['sendprice'] = '';
                }

                if (!$value['Z']) {
                    $flag = 'Z';
                    $float = '运输费不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['price'] = (float)$value['Z'];
                $arr['more_price'] = (float)$value['AA'];
                $arr['otherprice'] = (float)$value['AB'];
                $arr['remark'] = $value['AC'];


                $arr['order_type'] = 2;

                $arr['create_time'] = $arr['update_time'] = date('Y-m-d H:i:s',time());
                $arr['total_price'] = (float)$arr['pickprice'] + (float)$arr['sendprice'] + $arr['price'] + $arr['otherprice'] + $arr['more_price'];
                $arr['money_state'] = 'N';
                $info[] = $arr;

                $receive['compay_id'] = $arr['company_id'];
                $receive['receivprice'] = $arr['total_price'];
                $receive['trueprice'] = 0;
                $receive['receive_info'] ='';
//                $receive['create_user_id'] = $user->id;
//                $receive['create_user_name'] = $user->name;
                $receive['group_id'] = $group_id;
                $receive['paytype'] = $arr['paytype'];
                $receive['ordernumber'] = $arr['ordernumber'];
                $receive['type'] = 2;
                $receive['create_time'] = $receive['update_time'] = date('Y-m-d H:i:s',time());
                $receive_info[] = $receive;
            }

            $transaction= AppOrder::getDb()->beginTransaction();
            try{
                $res = Yii::$app->db->createCommand()->batchInsert(AppOrder::tableName(), ['ordernumber','takenumber','group_id','startcity','endcity','time_start','company_id','paytype', 'name','temperture','number','number2','weight','volume','startstr','endstr','picktype','pickprice','sendtype','sendprice','price','more_price','otherprice','remark','order_type','create_time','update_time','total_price','money_state'], $info)->execute();
                $res_r = Yii::$app->db->createCommand()->batchInsert(AppReceive::tableName(), ['compay_id','receivprice','trueprice','receive_info','group_id','paytype','ordernumber','type','create_time','update_time'], $receive_info)->execute();
                $arr = $this->insert_id($receive_info);
                if ($res && $arr && $res_r){
                    $transaction->commit();
                    $data = $this->encrypt(['code'=>200,'msg'=>'导入成功']);
                    return $this->resultInfo($data);
                }else{
                    $transaction->rollBack();
                    $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                    return $this->resultInfo($data);
                }
            }catch(\Exception $e){
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择导入数据']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 系统整车导入
     * */
    public function actionVehical(){
        header('content-type:application:json;charset=utf8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $input = \Yii::$app->request->post();
        $token = $input['token'];
        $file = $_FILES['file'];
        $group_id = $input['group_id'];
        $this->check_upload_file($file['name']);
        $check_result = $this->check_token($token,true);//验证令牌

        $user = $check_result['user'];

        $company_id = '';
        $info= [];
        $startstrs = [];
        $endstrs = [];
        $start= [];
        $end = [];

        if ($file['tmp_name'] != ''){
            $path =  $this->Upload('vehical',$file);
            $list = $this->reander_more(Yii::$app->basePath . '/web/' . $path);//导入
            if (!$list) {
                $data = $this->encrypt(['code'=>400,'msg'=>'导入数据不能为空']);
                return $this->resultInfo($data);
            }

            $save_start_arr = [];
            $save_end_arr = [];
            foreach ($list as $key =>$value){
                $arr = [];
                $ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                $arr['ordernumber'] = $ordernumber;
                $arr['takenumber'] = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                $arr['group_id'] = $group_id;
                $arr['startcity'] = $value['M'];
                $arr['endcity'] = $value['S'];
                if ($value['B']){
                    $arr['time_start'] = gmdate('Y-m-d H:i:s', \PHPExcel_Shared_Date::ExcelToPHP($value['B']));
                }else{
                    $flag = 'B';
                    $float = '请填写装车时间';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if ($value['C']){
                    $arr['time_end'] = gmdate('Y-m-d H:i:s', \PHPExcel_Shared_Date::ExcelToPHP($value['C']));
                }else{
                    $flag = 'C';
                    $float = '请填写要求到达时间';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }                

                if ($value['D']){
                    $cartype = AppCartype::find()->where(['carparame'=>$value['D']])->one();
                    if (empty($cartype->car_id)) {
                        $flag = 'D';
                        $float = '车辆类型错误';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                    $arr['cartype'] = $cartype->car_id;
                }else{
                    $flag = 'D';
                    $float = '请填写车辆类型';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $group= AppGroup::find()->where(['id'=>$group_id])->one();
                if ($group->main_id != 1) {
                    $group= AppGroup::find()->where(['id'=>$group->group_id])->one();
                }

                    if (!$value['E']){
                        $flag = 'E';
                        $float = '客户公司不能为空';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                    $customer = Customer::find()->where(['group_id'=>$group_id,'all_name'=>$value['E']])->one();
                    if ($customer){
                        $arr['company_id'] = $customer->id;
                        $arr['paytype'] = $customer->paystate;
                    }else{
                        $flag = 'E';
                        $float = '没有找到（'.$value['E'].'）该客户公司';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }

                if (!$value['F']) {
                    $flag = 'F';
                    $float = '货品名称不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['name'] = $value['F'];

                if (!$value['G']) {
                    $flag = 'G';
                    $float = '温度不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {
                    $arr_tem = ['冷冻','冷藏','常温','恒温','冷冻/冷藏'];
                    if (!in_array($value['G'],$arr_tem)) {
                        $flag = 'G';
                        $float = '温度必须选择：冷冻、冷藏、常温、恒温、冷冻/冷藏';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }
                $arr['temperture'] = $value['G'];

                if ($value['G'] == '冷冻/冷藏') {
                    if (!$value['H']) {
                        $flag = 'H';
                        $float = '温度是冷冻/冷藏，必须填写冷冻件数';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                    if (!$value['I']) {
                        $flag = 'I';
                        $float = '温度是冷冻/冷藏，必须填写非冷冻件数';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }

                $arr['number'] = $value['I'];
                $arr['number2'] = $value['H'];
                $arr['weight'] = $value['J'];
                $arr['volume'] = $value['K'];

                $start_pro = $value['L'];
                $start_city = $value['M'];
                $start_area = $value['N'];
                $start_info = $value['O'];  

                $start_flag = $this->check_address($start_pro,$start_city,$start_area);
                if ($start_flag['position'] != 'ok') {
                    if ($start_flag['position'] == 'pro') {
                        $flag = 'L';
                    } else if($start_flag['position'] == 'city') {
                        $flag = 'M';
                    } else if($start_flag['position'] == 'area') {
                        $flag = 'N';
                    }
                    $float = $start_flag['msg'];
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }  

                if (!$start_info) {
                    $flag = 'O';
                    $float = '提货详细地址不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['P']) {
                    $flag = 'P';
                    $float = '提货联系人不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['name'] = $value['F'];

                if (!$value['Q']) {
                    $flag = 'Q';
                    $float = '提货联系人电话不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $start_arr = ['pro'=>$start_pro,'city'=>$start_city,'area'=>$start_area,'info'=>$start_info,'contant'=>$value['P'],'tel'=>$value['Q']];   
                $save_start_arr[] = $start_arr;
                $arr['startstr'] = json_encode([$start_arr],JSON_UNESCAPED_UNICODE);     

                $end_pro = $value['R'];
                $end_city = $value['S'];
                $end_area = $value['T'];
                $end_info = $value['U'];
                $end_flag = $this->check_address($end_pro,$end_city,$end_area);
                if ($end_flag['position'] != 'ok') {
                    if ($end_flag['position'] == 'pro') {
                        $flag = 'R';
                    } else if($end_flag['position'] == 'city') {
                        $flag = 'S';
                    } else if($end_flag['position'] == 'area') {
                        $flag = 'T';
                    }
                    $float = $end_flag['msg'];
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }  

                if (!$end_info) {
                    $flag = 'U';
                    $float = '送货详细地址不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['V']) {
                    $flag = 'V';
                    $float = '送货联系人不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['W']) {
                    $flag = 'W';
                    $float = '送货联系人电话不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $end_arr = ['pro'=>$end_pro,'city'=>$end_city,'area'=>$end_area,'info'=>$end_info,'contant'=>$value['V'],'tel'=>$value['W']];  
                $save_end_arr[] = $end_arr; 
                $arr['endstr'] = json_encode([$end_arr],JSON_UNESCAPED_UNICODE);  

                $is_yn = ['需要','不需要'];
                $picktype = strtoupper($value['X']);
                if (!$picktype) {
                    $flag = 'X';
                    $float = '司机/物流装货不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {
                    
                    if (!in_array($picktype,$is_yn)) {
                        $flag = 'X';
                        $float = '司机/物流装货只能填写:按要求填写';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }
                if ($picktype == '需要') {
                    $arr['picktype'] = 1;
                    if ($value['Z']) {
                        $arr['pickprice'] = (float)$value['Z'];
                    }else{
                        $flag = 'Z';
                        $float = '请填写装货价格';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                } else if ($picktype == '不需要') {
                    $arr['picktype'] = 2;
                    $arr['pickprice'] = 0;
                }

                $sendtype = strtoupper($value['Y']);
                if (!$sendtype) {
                    $flag = 'Y';
                    $float = '司机/物流装货不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {
                    if (!in_array($sendtype,$is_yn)) {
                        $flag = 'Y';
                        $float = '司机/物流卸货只能填写:需要、不需要';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }

                if ($sendtype == '需要') {
                    $arr['sendtype'] = 1;
                    if ($value['AA']) {
                        $arr['sendprice'] = (float)$value['AA'];
                    }else{
                        $flag = 'AA';
                        $float = '请填写卸货价格';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                } else if ($sendtype == '不需要') {
                    $arr['sendtype'] = 2;
                    $arr['sendprice'] = 0;
                }

                if (!$value['AB']) {
                    $flag = 'AB';
                    $float = '运输费不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['price'] = (float)$value['AB'];
                $arr['more_price'] = (float)$value['AC'];
                $arr['otherprice'] = (float)$value['AD'];
                $arr['remark'] = $value['AE'];

                
                $arr['order_type'] = 1;
                $arr['create_user_id'] = $user['id'];
                $arr['create_user_name'] = $user['name'];

                $arr['create_time'] = $arr['update_time'] = date('Y-m-d H:i:s',time());
                $arr['total_price'] = (float)$arr['pickprice'] + (float)$arr['sendprice'] + $arr['price'] + $arr['otherprice'] + $arr['more_price'];
                $arr['money_state'] = 'N';
                $info[] = $arr;

                $receive['compay_id'] = $arr['company_id'];
                $receive['receivprice'] = $arr['total_price'];
                $receive['trueprice'] = 0;
                $receive['receive_info'] ='';
                $receive['create_user_id'] = $user->id;
                $receive['create_user_name'] = $user->name;
                $receive['group_id'] = $user->group_id;
                $receive['paytype'] = $arr['paytype'];
                $receive['ordernumber'] = $arr['ordernumber'];
                $receive['type'] = 1;
                $receive['create_time'] = $receive['update_time'] = date('Y-m-d H:i:s',time());
                $receive_info[] = $receive;
            }
            $transaction= AppOrder::getDb()->beginTransaction();
            try{
                $res = Yii::$app->db->createCommand()->batchInsert(AppOrder::tableName(), ['ordernumber','takenumber','group_id','startcity','endcity','time_start','time_end','cartype','company_id','paytype', 'name','temperture','number','number2','weight','volume','startstr','endstr','picktype','pickprice','sendtype','sendprice','price','more_price','otherprice','remark','order_type','create_user_id','create_user_name','create_time','update_time','total_price','money_state'], $info)->execute();
                $res_r = Yii::$app->db->createCommand()->batchInsert(AppReceive::tableName(), ['compay_id','receivprice','trueprice','receive_info','create_user_id','create_user_name','group_id','paytype','ordernumber','type','create_time','update_time'], $receive_info)->execute();
                $arr = $this->insert_id($receive_info);
                if ($res && $arr && $res_r){
                    $transaction->commit();
                    @$this->leading_in_address_user($save_start_arr,$user['parent_group_id'],$user['id']);
                    @$this->leading_in_address_user($save_end_arr,$user['parent_group_id'],$user['id']);
                    $data = $this->encrypt(['code'=>200,'msg'=>'导入成功']);
                    return $this->resultInfo($data);
                }else{
                    $transaction->rollBack();
                    $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                    return $this->resultInfo($data);
                }
            }catch(\Exception $e){
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'导入失败','data'=>$e]);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择导入数据']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 零担订单导入
     * */
    public function actionBulk(){
        header('content-type:application:json;charset=utf8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $input = \Yii::$app->request->post();
        $token = $input['token'];
        $file = $_FILES['file'];
        $group_id = $input['group_id'];
        $this->check_upload_file($file['name']);
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $company_id = '';
        $info= [];
        $startstrs = [];
        $endstrs = [];
        $start= [];
        $end = [];
        if ($file['tmp_name'] != ''){
            $path =  $this->Upload('bulk',$file);
            $list = $this->reander_more(Yii::$app->basePath . '/web/' . $path);//导入
            if (!$list) {
                $data = $this->encrypt(['code'=>400,'msg'=>'导入数据不能为空']);
                return $this->resultInfo($data);
            }
            $save_start_arr = [];
            $save_end_arr = [];
            foreach ($list as $key =>$value){

                $arr = [];
                $ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                $arr['ordernumber'] = $ordernumber;
                $arr['takenumber'] = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                $arr['group_id'] = $group_id;
                $arr['startcity'] = $value['K'];
                $arr['endcity'] = $value['Q'];
                if ($value['B']){
                    $arr['time_start'] = gmdate('Y-m-d H:i:s', \PHPExcel_Shared_Date::ExcelToPHP($value['B']));
                }else{
                    $flag = 'B';
                    $float = '请填写装车时间';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $group= AppGroup::find()->where(['id'=>$group_id])->one();
                if ($group->main_id != 1) {
                    $group= AppGroup::find()->where(['id'=>$group->group_id])->one();
                }

                if (!$value['C']){
                    $flag = 'C';
                    $float = '客户公司不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $customer = Customer::find()->where(['group_id'=>$group_id,'all_name'=>$value['C']])->one();
                if ($customer){
                    $arr['company_id'] = $customer->id;
                    $arr['paytype'] = $customer->paystate;
                }else{
                    $flag = 'C';
                    $float = '没有找到（'.$value['C'].'）该客户公司';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['D']) {
                    $flag = 'D';
                    $float = '货品名称不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['name'] = $value['D'];

                if (!$value['E']) {
                    $flag = 'E';
                    $float = '温度不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {
                    $arr_tem = ['冷冻','冷藏','常温','恒温','冷冻/冷藏'];
                    if (!in_array($value['E'],$arr_tem)) {
                        $flag = 'E';
                        $float = '温度必须选择：冷冻、冷藏、常温、恒温、冷冻/冷藏';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }
                $arr['temperture'] = $value['E'];

                if ($value['E'] == '冷冻/冷藏') {
                    if (!$value['F']) {
                        $flag = 'F';
                        $float = '温度是冷冻/冷藏，必须填写冷冻件数';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                    if (!$value['G']) {
                        $flag = 'G';
                        $float = '温度是冷冻/冷藏，必须填写非冷冻件数';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }

                $arr['number'] = $value['F'];
                $arr['number2'] = $value['G'];
                $arr['weight'] = $value['H'];
                $arr['volume'] = $value['I'];

                $start_pro = $value['J'];
                $start_city = $value['K'];
                $start_area = $value['L'];
                $start_info = $value['M'];

                $start_flag = $this->check_address($start_pro,$start_city,$start_area);
                if ($start_flag['position'] != 'ok') {
                    if ($start_flag['position'] == 'pro') {
                        $flag = 'J';
                    } else if($start_flag['position'] == 'city') {
                        $flag = 'K';
                    } else if($start_flag['position'] == 'area') {
                        $flag = 'L';
                    }
                    $float = $start_flag['msg'];
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$start_info) {
                    $flag = 'M';
                    $float = '提货详细地址不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['N']) {
                    $flag = 'N';
                    $float = '提货联系人不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['name'] = $value['D'];

                if (!$value['O']) {
                    $flag = 'O';
                    $float = '提货联系人电话不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $start_arr = ['pro'=>$start_pro,'city'=>$start_city,'area'=>$start_area,'info'=>$start_info,'contant'=>$value['N'],'tel'=>$value['O']];
                $save_start_arr[] = $start_arr;
                $arr['startstr'] = json_encode([$start_arr],JSON_UNESCAPED_UNICODE);

                $end_pro = $value['P'];
                $end_city = $value['Q'];
                $end_area = $value['R'];
                $end_info = $value['S'];
                $end_flag = $this->check_address($end_pro,$end_city,$end_area);
                if ($end_flag['position'] != 'ok') {
                    if ($end_flag['position'] == 'pro') {
                        $flag = 'P';
                    } else if($end_flag['position'] == 'city') {
                        $flag = 'Q';
                    } else if($end_flag['position'] == 'area') {
                        $flag = 'R';
                    }
                    $float = $end_flag['msg'];
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$end_info) {
                    $flag = 'S';
                    $float = '送货详细地址不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['T']) {
                    $flag = 'T';
                    $float = '送货联系人不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if (!$value['U']) {
                    $flag = 'U';
                    $float = '送货联系人电话不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                $end_arr = ['pro'=>$end_pro,'city'=>$end_city,'area'=>$end_area,'info'=>$end_info,'contant'=>$value['T'],'tel'=>$value['U']];
                $save_end_arr[] = $end_arr;
                $arr['endstr'] = json_encode([$end_arr],JSON_UNESCAPED_UNICODE);

                $is_yn = ['需要','不需要'];
                $picktype = strtoupper($value['V']);
                if (!$picktype) {
                    $flag = 'V';
                    $float = '提货服务不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {

                    if (!in_array($picktype,$is_yn)) {
                        $flag = 'V';
                        $float = '提货服务只能填写:按要求填写';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }
                if ($picktype == '需要') {
                    $arr['picktype'] = 1;
                    if ($value['X']) {
                        $arr['pickprice'] = (float)$value['X'];
                    }else{
                        $flag = 'X';
                        $float = '提货费不能为空';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                } else if ($picktype == '不需要') {
                    $arr['picktype'] = 2;
                    $arr['pickprice'] = 0;
                }

                $sendtype = strtoupper($value['W']);
                if (!$sendtype) {
                    $flag = 'W';
                    $float = '配送服务不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                } else {
                    if (!in_array($sendtype,$is_yn)) {
                        $flag = 'W';
                        $float = '配送服务只能填写:需要、不需要';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }

                if ($sendtype == '需要') {
                    $arr['sendtype'] = 1;
                    if ($value['Y']) {
                        $arr['sendprice'] = (float)$value['Y'];
                    }else{
                        $flag = 'Y';
                        $float = '配送费不能为空';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                } else if ($sendtype == '不需要') {
                    $arr['sendtype'] = 2;
                    $arr['sendprice'] = 0;
                }

                if (!$value['Z']) {
                    $flag = 'Z';
                    $float = '运输费不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['price'] = (float)$value['Z'];
                $arr['otherprice'] = (float)$value['AA'];
                $arr['remark'] = $value['AB'];

                $arr['order_type'] = 2;
                $arr['create_user_id'] = $user['id'];
                $arr['create_user_name'] = $user['name'];

                $arr['create_time'] = $arr['update_time'] = date('Y-m-d H:i:s',time());
                $arr['total_price'] = (float)$arr['pickprice'] + (float)$arr['sendprice'] + $arr['price'] + $arr['otherprice'] + $arr['more_price'];
                $arr['money_state'] = 'N';
                $info[] = $arr;

                $receive['compay_id'] = $arr['company_id'];
                $receive['receivprice'] = $arr['total_price'];
                $receive['trueprice'] = 0;
                $receive['receive_info'] ='';
                $receive['create_user_id'] = $user->id;
                $receive['create_user_name'] = $user->name;
                $receive['group_id'] = $user->group_id;
                $receive['paytype'] = $arr['paytype'];
                $receive['ordernumber'] = $arr['ordernumber'];
                $receive['type'] = 2;
                $receive['create_time'] = $receive['update_time'] = date('Y-m-d H:i:s',time());
                $receive_info[] = $receive;
            }

            $transaction= AppOrder::getDb()->beginTransaction();
            try{
                $res = Yii::$app->db->createCommand()->batchInsert(AppOrder::tableName(), ['ordernumber','takenumber','group_id','startcity','endcity','time_start','company_id','paytype','name','temperture','number','number2','weight','volume','startstr','endstr','picktype','pickprice','sendtype','sendprice','price','otherprice','remark','order_type','create_user_id','create_user_name','create_time','update_time','total_price','money_state'], $info)->execute();
                $res_r = Yii::$app->db->createCommand()->batchInsert(AppReceive::tableName(), ['compay_id','receivprice','trueprice','receive_info','create_user_id','create_user_name','group_id','paytype','ordernumber','type','create_time','update_time'], $receive_info)->execute();
                $arr = $this->insert_id($receive_info);
                if ($res && $arr && $res_r){
                    $transaction->commit();
                    @$this->leading_in_address_user($save_start_arr,$user['parent_group_id'],$user['id']);
                    @$this->leading_in_address_user($save_end_arr,$user['parent_group_id'],$user['id']);
                    $data = $this->encrypt(['code'=>200,'msg'=>'导入成功']);
                    return $this->resultInfo($data);
                }else{
                    $transaction->rollBack();
                    $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                    return $this->resultInfo($data);
                }
            }catch(\Exception $e){
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择导入数据']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 整车订单导出
     * */
    public function ActionVehical_derive(){
        $input = Yii::$app->request->post();
        $ids = $input['id'];
        $list = AppOrder::find()->where(['delete_flag'=>'Y'])->where(['in','id',$ids])->asArray()->all();
        foreach($list as $key =>$value){
            $ordernumber = $value['ordernumber'];
            $startcity = $value['startcity'];
            $endcity = $value['endcity'];
            $this->excelOut2008();
        }
    }

    /*
    * 判断地址
    * */
    public function decirde_address($address){
        $province = '';
        $city = '';
        $area = '';
        $flag = preg_match('/(.*?(省|自治区|内蒙古|新疆|宁夏|香港|澳门|西藏|北京市|天津市|上海市|重庆市))/', $address, $matches);
        if (count($matches) > 1) {
            $province = $matches[count($matches) - 2];
            if ($province == '上海市' ||$province == '北京市'||$province == '天津市'||$province == '重庆市'){
                $address = $address;
            }else{
                $address = str_replace($province, '', $address);
            }

//            $res = District::find()->select(['name'])->where(['name'=>$province,'level'=>1])->one();
        }
        preg_match('/(.*?(市|自治州|地区|区划|盟|岛|族|县))/', $address, $matches);
        if (count($matches) > 1) {
            $city = $matches[count($matches) - 2];
            $address = str_replace($city, '', $address);
//            $res1 = District::find()->select(['name'])->where(['name'=>$city,'level'=>2])->one();

        }
        preg_match('/(.*?(区|县|镇|乡|街道|旗))/', $address, $matches);
        if (count($matches) > 1) {
            $area = $matches[count($matches) - 2];
            $address = str_replace($area, '', $address);
//            $res2 = District::find()->select(['name'])->where(['name'=>$area,'level'=>3])->one();
        }
        return array('pro'=>$province,'city'=>$city,'area'=>$area,'info'=>$address);
    }

    // '/^(([京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领][A-Z](([0-9]{5}[DF])|([DF]([A-HJ-NP-Z0-9])[0-9]{4})))|([京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领][A-Z][A-HJ-NP-Z0-9]{4}[A-HJ-NP-Z0-9挂学警港澳使领]))$/'

    /*
     * 客户公司导入
     * */
    public function actionCustomer_into(){
        $input = \Yii::$app->request->post();
        $token = $input['token'];
        $file = $_FILES['file'];
        $this->check_upload_file($file['name']);
        $group_id = $input['group_id'];
        if (!$group_id) {
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误！']);
            return $this->resultInfo($data);
        }
        $token = $input['token'];
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        
        $title = '';
        $tax_number = '';
        $bank = '';
        $bank_number = '';
        $com_address = '';
        $com_tel = '';
        $address = $contact_name = $contact_tel =  $remark =  $paystate = '';

        $info = [];
        if ($file['tmp_name'] != '') {
            $path =  $this->Upload('customer',$file);
            $list = $this->reander(Yii::$app->basePath . '/web/' . $path);//导入
            if (!$list) {
                $data = $this->encrypt(['code'=>400,'msg'=>'导入数据不能为空']);
                return $this->resultInfo($data);
            }
            foreach ($list as $key => $value) {
                if ($value['B']){
                    $name = $value['B'];
                    $flag = Customer::find()->where(['group_id'=>$group_id,'all_name'=>$name])->one();
                    if ($flag) {
                        $flag = 'B';
                        $float = '客户公司名称已存在！';
                        $error = '第'.$key.'行'.$flag.'列'.'数据重复:'.$float.'，请修改或删除！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }else{
                    $flag = 'B';
                    $float = '客户公司名称不能为空';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }

                if($value['C']){
                    if($value['C'] == '现付'){
                        $paystate = 1;
                    }elseif($value['C'] == '月结'){
                        $paystate = 2;
                    }else{
                        $flag = 'C';
                        $float = '请按照要求填写结算方式';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }

                if ($value['D']){
                     if (preg_match('/^[A-Za-z0-9]{18}$/',$value['E'])){
                        $title = $value['D'];
                        $tax_number = $value['E'];
                    }else{
                        $flag = 'E';
                        $float = '请填写正确的税号';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = ['code'=>400,'msg'=>$error];
                        $data = $this->encrypt($data);
                        return $this->resultInfo($data);
                    }
                }
                if ($value['E']){
                    if ($value['D']){
                        $title = $value['D'];
                        $tax_number = $value['E'];
                    }else{
                        $flag = 'D';
                        $float = '发票抬头不能为空';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        $data = $this->encrypt($data);
                        return $this->resultInfo($data);
                    }
                }
                if($value['F']){
                    $bank = $value['F'];
                }
                if($value['G']){
                    $bank_number = str_replace(' ','',$value['G']);
                }
                if($value['H']){
                    $com_address = $value['H'];
                }
                if($value['I']){
                    $com_tel = $value['I'];
                }
                if($value['J']){
                    $address = $value['J'];
                }
                if($value['K']){
                    $contact_name = $value['K'];
                }
                if($value['L']){
                    $contact_tel = $value['L'];
                }
                
                if($value['M']){
                    $remark = $value['M'];
                }
                $create_time = $update_time = date('Y-m-d H:i:s',time());
                $info1 = ['address'=>$address,'contact_name'=>$contact_name,'contact_tel'=>$contact_tel,'paystate'=>$paystate,'remark'=>$remark,'group_id'=>$group_id,'all_name'=>$name,'title'=>$title,'tax_number'=>$tax_number,'bank'=>$bank,'bank_number'=>$bank_number,'com_address'=>$com_address,'com_tel'=>$com_tel,'create_time'=>$create_time,'update_time'=>$update_time];
                $info[] = $info1;
            }
            $res = Yii::$app->db->createCommand()->batchInsert(Customer::tableName(), ['address','contact_name','contact_tel','paystate','remark','group_id','all_name','title','tax_number','bank','bank_number','com_address','com_tel','create_time','update_time'], $info)->execute();
            if ($res){
                $data = $this->encrypt(['code'=>200,'msg'=>'导入成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择导入文件']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 客户公司导出
     * */
    public function actionCustomer_out(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];

        $ids = explode(',',$input['id']);
        $list = Customer::find()->where(['delete_flag'=>'Y'])->where(['in','id',$ids])->asArray()->all();
        $i = 1;
        foreach($list as $key =>$value){
            if ($value['paystate'] == 1){
                $paystate = '现付';
            }else{
                $paystate = '月结';
            }
            $data[] = array($i,$value['all_name'],$value['address'],$value['title'],$value['tax_number'],$value['bank'],$value['bank_number'],$value['com_address'],$value['com_tel'],$value['contact_name'],$value['contact_tel'],$paystate,$value['remark']);
            $i++;
        }
        $title = array('编号','客户公司名称','地址','发票抬头','税号','开户银行','开户行账号','企业地址','企业电话','联系人','联系方式','结算方式','备注');
        $w = [5,20,40,20,20,20,30,40,20,10,20,10,30];
        $filename = '客户公司表'.date('YmdHi');
        $res = $this->excelOut($title,$data,$filename,$w,1);
        $this->hanldlog($user->id,'导出客户');
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$res]);
        return $this->resultInfo($data);
    }

    /*
     * 承运商导入
     * */
    public function actionCarriage_into(){
        $input = \Yii::$app->request->post();
        $token = $input['token'];
        $file = $_FILES['file'];
        $group_id = $input['group_id'];
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $info = [];
        $address = $contact_name = $contact_tel =  $remark =  $paystate = '';
        if ($file['tmp_name'] != '') {
            $path = $this->Upload('customer', $file);
            $list = $this->reander(Yii::$app->basePath . '/web/' . $path);//导入
            foreach ($list as $key =>$value){
                 if ($value['B']){
                     $name = $value['B'];
                 }else{
                     $flag = 'B';
                     $float = '请按照要求填写结算方式';
                     $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                     $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                     return $this->resultInfo($data);
                 }
                 if ($value['C']){
                     $address = $value['C'];
                 }
                 if ($value['D']){
                     $contact_name = $value['D'];
                 }
                 if ($value['E']){
                     $contact_tel = $value['E'];
                 }
                if($value['F']){
                    if($value['F'] == '现付'){
                        $paystate = 1;
                    }elseif($value['F'] == '月结'){
                        $paystate = 2;
                    }else{
                        $flag = 'F';
                        $float = '请按照要求填写结算方式';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }
                 if ($value['G']){
                     $remark = $value['G'];
                 }
                 $create_time = $update_time = date('Y-m-d H:i:s',time());
                $info1 = [['name'=>$name,'group_id'=>$group_id,'address'=>$address,'contact_name'=>$contact_name,'contact_tel'=>$contact_tel,'paystate'=>$paystate,'remark'=>$remark,'create_time'=>$create_time,'update_time'=>$update_time]];
                $info = array_merge($info,$info1);
            }
            $res = Yii::$app->db->createCommand()->batchInsert(Carriage::tableName(), ['name','group_id','address','contact_name','contact_tel','paystate','remark','create_time','update_time'], $info)->execute();
            if ($res){
                $data = $this->encrypt(['code'=>200,'msg'=>'导入成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择导入文件']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 承运商导出
     * */
    public function actionCarriage_out(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];

        $ids = explode(',',$input['id']);
        $list = Carriage::find()->where(['delete_flag'=>'Y'])->where(['in','cid',$ids])->asArray()->all();
        $i = 1;
        foreach($list as $key =>$value){
            if ($value['paystate'] == 1){
                $paystate = '现付';
            }else{
                $paystate = '月结';
            }
            $data[] = array($i,$value['name'],$value['address'],$value['contact_name'],$value['contact_tel'],$paystate,$value['remark']);
            $i++;
        }
        $title = array('编号','承运公司名称','地址','联系人','联系方式','结算方式','备注');
        $w = [5,20,40,10,15,10,30];
        $filename = '承运公司表'.date('YmdHi');
        $res = $this->excelOut($title,$data,$filename,$w,1);
        $this->hanldlog($user->id,'导出承运商');
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$res]);
        return $this->resultInfo($data);
    }

    /*
     * 车辆导入
     * */
    public function actionCar_into(){
        $input = \Yii::$app->request->post();
        $token = $input['token'];
        $file = $_FILES['file'];
        $group_id = $input['group_id'];
        $this->check_upload_file($file['name']);
        $group_id = $input['group_id'];
        if (!$group_id) {
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误！']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $info = [];

        if ($file['tmp_name'] != '') {
            $path = $this->Upload('car', $file);
            $list = $this->reander(Yii::$app->basePath . '/web/' . $path);//导入
            // $data = $this->encrypt(['code'=>400,'msg'=>'ok','list'=>$list]);
            // return $this->resultInfo($data);
            $time = date('Y-m-d H:i:s',time());
            foreach ($list as $key => $value) {
                $arr = [];
                if (preg_match('/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新使]{1}[A-Z]{1}[0-9a-zA-Z]{5}$/u',$value['B'])){
                    $carnumber = $value['B'];
                    $flag = Car::find()->where(['group_id'=>$group_id,'carnumber'=>$carnumber])->one();
                    if ($flag) {
                        $flag = 'B';
                        $float = '车牌号('.$carnumber.')已存在';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }else{
                    $flag = 'B';
                    $float = '请填写正确的车牌号';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['carnumber'] = $carnumber;
                if ($value['C']){
                    $cartype = AppCartype::find()->where(['carparame'=>$value['D']])->one();
                    if (empty($cartype->car_id)) {
                        $flag = 'C';
                        $float = '车辆类型错误';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                    $arr['cartype'] = $cartype->car_id;
                }else{
                    $flag = 'C';
                    $float = '请按照要求填写车型';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['cartype'] = $cartype;

                if ($value['D']){
                    $control = $value['D'];
                    $control_types = ['冷冻','冷藏','常温','恒温','冷冻/冷藏'];
                    if (!in_array($control,$control_types)) {
                        $flag = 'D';
                        $float = '请按照要求填写温控';
                        $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                        $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                        return $this->resultInfo($data);
                    }
                }else{
                    $flag = 'D';
                    $float = '请按照要求填写温控';
                    $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                    $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                    return $this->resultInfo($data);
                }
                $arr['control'] = $control;

                $arr['group_id'] = $group_id;
                if ($value['E']){
                    $arr['check_time'] = gmdate('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($value['E']));
                } else {
                    $arr['check_time'] = '';
                } 

                if ($value['F']){
                    $arr['board_time'] = gmdate('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($value['F']));
                } else {
                    $arr['board_time'] = '';
                }
                if ($value['G']){
                    $arr['driver_name'] = $value['G'];
                } else {
                    $arr['driver_name'] = '';
                }
                if ($value['H']){
                    $arr['mobile'] = $value['H'];
                } else {
                    $arr['mobile'] = '';
                }
                if ($value['I']){
                    $arr['remark'] = $value['I'];
                } else {
                    $arr['remark'] = ''; 
                }
                $arr['create_time'] = $arr['update_time'] = $time;
                $info[] = $arr;
            }
            $res = Yii::$app->db->createCommand()->batchInsert(Car::tableName(), [
                'carnumber',
                'cartype',
                'control',
                'group_id',
                'check_time',
                'board_time',
                'driver_name',
                'mobile',
                'remark',
                'create_time',
                'update_time'
            ], $info)->execute();
            if ($res){
                $data = $this->encrypt(['code'=>200,'msg'=>'导入成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择导入文件']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 车辆导出
     * */
    public function actionCar_out(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];

        $ids = explode(',',$input['id']);
        $list = Car::find()
            ->alias('a')
            ->select(['b.carparame','a.control','a.cartype','a.carnumber','a.create_time','a.check_time','a.board_time','a.driver_name','a.mobile','a.type','a.remark'])
            ->leftJoin('app_cartype b','a.cartype = b.car_id')
            ->where(['in','a.id',$ids])
            ->andWhere(['a.delete_flag'=>'Y'])
            ->orderBy(['update_time'=>SORT_DESC])
            ->asArray()
            ->all();
        $i = 1;
        foreach($list as $key =>$value){
            if ($value['type'] == 1){
                $type = '自有车辆';
            }elseif($value['type'] == 2){
                $type = '承运商车辆';
            }elseif($value['type'] == 3){
                $type = '临时车辆';
            }
            $data[] = array($i,$value['carnumber'],$value['carparame'],$value['control'],$type,$value['driver_name'],$value['mobile'],$value['check_time'],$value['board_time'],$value['remark']);
            $i++;
        }
        $title = array('编号','车牌号','车型','温控','车辆类别','司机名称','联系方式','验车日期','初始上牌日期','备注');
        $w = [5,15,15,10,10,10,20,20,20,30];
        $filename = '自有车辆表'.date('YmdHi').$user['id'];
        $res = $this->excelOut($title,$data,$filename,$w,1);
        $this->hanldlog($user->id,'导出车辆');
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$res]);
        return $this->resultInfo($data);
    }

    /*
     * 导入常用联系人
     * */
    public function actionContact_into(){
        $input = \Yii::$app->request->post();
        $token = $input['token'];
        $file = $_FILES['file'];
        $group_id = $input['group_id'];
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $info = [];
        if ($file['tmp_name'] != '') {
            $path = $this->Upload('contact', $file);
            $list = $this->reander(Yii::$app->basePath . '/web/' . $path);//导入
//            var_dump($list);
//            exit();
            foreach ($list as $key => $value) {
               if ($value['B']){
                   $contact_name = $value['B'];
               }else{
                   $flag = 'B';
                   $float = '请填写司机姓名';
                   $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                   $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                   return $this->resultInfo($data);
               }
               if (preg_match('/^1[345789]\d{9}$/',$value['C'])){
                   $contact_tel = $value['C'];
               }else{
                   $flag = 'C';
                   $float = '请填写正确的手机号码';
                   $error = '第'.$key.'行'.$flag.'列'.'数据有误:'.$float.'，请认真核实！';
                   $data = $this->encrypt(['code'=>400,'msg'=>$error]);
                   return $this->resultInfo($data);
               }
               $info1 = array(array('name'=>$contact_name,'tel'=>$contact_tel));
               $info = array_merge($info,$info1);
            }
//            var_dump($info);

            foreach($info as $key =>$value){
                $contact = AppCommonContacts::find()->where(['user_id'=>1,'name'=>$value['name'],'tel'=>$value['tel']])->one();
                if ($contact){
                   $res = $contact->updateCounters(['views'=>1]);
                }else{
                    $contact = new AppCommonContacts();
                    $contact->user_id = 1;
                    $contact->name = $value['name'];
                    $contact->tel = $value['tel'];
                    $contact->create_user = $user->name;
                    $contact->create_userid = $user->id;
                    $res = $contact->save();

                }
            }
            if ($res){
                $data = $this->encrypt(['code'=>200,'msg'=>'导入成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'导入失败']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'文件为空']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 导入常用地址
     * */
    public function actionAddress_into(){
        $input = \Yii::$app->request->post();
//        $token = $input['token'];
        $file = $_FILES['file'];
        $group_id = $input['group_id'];
//        $check_result = $this->check_token($token,true);//验证令牌
//        $user = $check_result['user'];
        $info = [];
        if ($file['tmp_name'] != '') {
            $path = $this->Upload('address', $file);
            $list = $this->reander(Yii::$app->basePath . '/web/' . $path);//导入
            var_dump($list);
            exit();
            foreach ($list as $key => $value) {

            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'文件为空']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 导出统计流水
     * */
    public function actionOut_stream(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $starttime = $input['starttime'];
        $endtime = $input['endtime'];

        if (!$starttime){
            $data['msg'] = '请选择开始时间';
            return json_encode($data);
        }
        if (!$endtime){
            $data['msg'] = '请选择结束时间';
            return json_encode($data);
        }

        $time1 = (strtotime($endtime) - strtotime($starttime))/24/3600;
        if ($time1>31){
            $data['msg'] = '时间不能超过一个月';
            return json_encode($data);
        }
        $get_data = [];
        for($i=0;$i<=$time1;$i++){
            $time = '';
            if ($i == 0) {
                $time = $endtime;
            } else {
                if ($i == 1) {
                    $time = date('Y-m-d',strtotime('-'.$i.' day',strtotime($endtime.' 23:59:59')));
                } else {
                    $time = date('Y-m-d',strtotime('-'.$i.' days',strtotime($endtime.' 23:59:59')));
                }
            }
            $starttime_sel = $time.' 00:00:00';
            $endtime_sel = $time.' 23:59:59';
            // echo $endtime_sel;
            $payment = AppPayment::find()
                ->select('sum(pay_price),sum(truepay)')
                ->where(['group_id'=>$user->group_id])
                ->andWhere(['between','create_time',$starttime_sel,$endtime_sel])
                ->asArray()
                ->one();
            if (!$payment['sum(pay_price)']){
                $payment['sum(pay_price)'] = '0.00';
            }
            if (!$payment['sum(truepay)']){
                $payment['sum(truepay)'] = '0.00';
            }
            $receive = AppReceive::find()
                ->select('sum(receivprice),sum(trueprice)')
                ->where(['group_id'=>$user->group_id])
                ->andWhere(['between','create_time',$starttime_sel,$endtime_sel])
                ->asArray()
                ->one();
            if (!$receive['sum(receivprice)']){
                $receive['sum(receivprice)'] = '0.00';
            }
            if (!$receive['sum(trueprice)']){
                $receive['sum(trueprice)'] = '0.00';
            }
            $arr = [];
            $arr['time'] =  date('m/d',strtotime($time));
            $arr['pay_price'] = $payment['sum(pay_price)'];
            $arr['truepay'] = $payment['sum(truepay)'];
            $arr['receivprice'] = $receive['sum(receivprice)'];
            $arr['trueprice'] = $receive['sum(trueprice)'];
            $get_data[] = $arr;
        }

        $i = 1;
        foreach($get_data as $key =>$value){
            $data[] = array($i,$value['time'],$value['pay_price'],$value['truepay'],$value['receivprice'],$value['trueprice']);
            $i++;
        }
        $title = array('编号','日期','应付','实际应付','应收','实际应收');
        $w = [5,15,15,20,15,20];
        $filename = '统计流水表'.date('YmdHi').$user['id'];
        $res = $this->out_stream($title,$data,$filename,$w,1);
        $this->hanldlog($user->id,'统计流水');
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$res]);
        return $this->resultInfo($data);
    }


}
<?php

namespace app\modules\api\controllers;

use app\models\AppGroup;
use app\models\Car;
use app\models\Customer;
use app\models\Upload;
use app\models\User;
use app\models\AppCartype;
use Yii\web\UploadedFile;
use Yii;

/**
 * Default controller for the `api` module
 */
class CarController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $carnumber = $input['carnumber'] ?? '';
        $use_flag = $input['use_flag'] ?? '';
        $chitu = $input['chitu'];
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (empty($token)){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        
        $check_result = $this->check_token_list($token,$chitu);//验证令牌
        $user = $check_result['user'];


        $list = Car::find()
            ->alias('c')
            ->select(['c.*','t.carparame'])
            ->leftJoin('app_cartype t','c.cartype=t.car_id')
            ->where(['c.delete_flag'=>'Y']);
        if ($carnumber) {
            $list->andWhere(['like','c.carnumber',$carnumber])->orWhere(['like','c.driver_name',$carnumber])->orWhere(['like','c.mobile',$carnumber]);
        }
        if ($use_flag) {
            $list->andWhere(['c.use_flag'=>$use_flag]);
        }
        if ($group_id) {
            $list->andWhere(['c.group_id'=>$group_id]);
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['c.update_time'=>SORT_DESC,'c.use_flag'=>SORT_DESC])
            ->asArray()
            ->all();
        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'auth' => $check_result['auth'],
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }
    /*
     * 添加车辆
     * */
    public function actionAdd(){
        $request = \Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];//令牌
        $carnumber = $input['carnumber'];//车牌号
        $cartype = $input['cartype'] ? $input['cartype'] : 1;//车型
        $group_id = $input['group_id'];//所属公司ID
        $control = $input['control'];//温度
        $check_time = $input['check_time'];// 验车时间
        $board_time = $input['board_time'];//注册日期
        $driver_name = $input['driver_name'];//司机名称
        $mobile = $input['mobile'];//手机
        $weight = $input['weight'];//承重
        $volam = $input['volam'];//体积
        $state = $input['state'];//状态
        $remark = $input['remark'];//备注

        // $data = $this->encrypt(['code'=>400,'msg'=>'参数错误','data'=>$input]);
        // return $this->resultInfo($data);

        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        if (empty($carnumber)){
            $data = $this->encrypt(['code'=>400,'msg'=>'车牌号不能为空！']);
            return $this->resultInfo($data);
        }
        // if(empty($cartype)){
        //     $data = $this->encrypt(['code'=>400,'msg'=>'请选择车辆类型！']);
        //     return $this->resultInfo($data);
        // }

        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);
        $group = AppGroup::find()->where(['id'=>group_id])->one();
        if ($group->level_id == 1){
            $has_car = Car::find()->where(['group_id'=>$group_id])->one();
            if ($has_car){
                $data = $this->encrypt(['code'=>400,'msg'=>'已添加车辆，请勿重复添加']);
                return $this->resultInfo($data);
            }
        }
        $time = date('Y-m-d H:i:s',time());
        $model = new Car();
        $model->carnumber = $carnumber;
        $model->cartype = $cartype;
        $model->group_id = $group_id;
        $model->control = $control;
        $model->check_time = $check_time;
        $model->create_name = $user->name;
        $model->create_id = $user->id;
        $model->board_time = $board_time;
        $model->driver_name = $driver_name;
        $model->mobile = $mobile;
        $model->weight = $weight;
        $model->volam = $volam;
        $model->state = $state;
        $model->remark = $remark;
        $model->create_time = $time;
        $model->update_time = $time;
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'添加车辆:'.$model->carnumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 修改车辆
     * */
    public function actionEdit(){
        $request = \Yii::$app->request;
        $input = $request->post();
        $id = $input['id'];//令牌
        $token = $input['token'];//令牌
        $carnumber = $input['carnumber'];//车牌号
        $cartype = $input['cartype'] ? $input['cartype'] : '1';//车型
        $group_id = $input['group_id'];//所属公司ID
        $control = $input['control'];//温度
        $check_time = $input['check_time'];// 验车时间
        $board_time = $input['board_time'];//注册日期
        $driver_name = $input['driver_name'];//司机名称
        $mobile = $input['mobile'];//手机
        $weight = $input['weight'];//承重
        $volam = $input['volam'];//体积
        $state = $input['state'];//状态
        $remark = $input['remark'];//备注
        // $type = $input['type'] ?? 1;//公司类别

        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        if (empty($carnumber)){
            $data = $this->encrypt(['code'=>400,'msg'=>'车牌号不能为空！']);
            return $this->resultInfo($data);
        }
        if(empty($cartype)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择车辆类型！']);
            return $this->resultInfo($data);
        }
 
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = Car::findOne($id);
        $this->check_group_auth($model->group_id,$user);
        $time = date('Y-m-d H:i:s',time());

        $model->carnumber = $carnumber;
        $model->cartype = $cartype;
        $model->group_id = $group_id;
//        $model->group_name = $group_name;
        $model->control = $control;
        $model->check_time = $check_time;
        $model->board_time = $board_time;
        $model->driver_name = $driver_name;
        $model->mobile = $mobile;
        $model->volam = $volam;
        $model->weight = $weight;
        $model->state = $state;
        $model->remark = $remark;
        $model->update_time = $time;

        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'编辑车辆:'.$model->carnumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'编辑成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'编辑失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 车辆详情
     * */
    public function actionView(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        $groups = AppGroup::group_list($user);
        $car_list = AppCartype::get_list();
        if ($id) {
            $model = Car::find()
                ->where(['id'=>$id])
                ->asArray()
                ->one();
        } else {
            $model = new Car();
        }

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$model,'groups'=>$groups,'car_list'=>$car_list]);
        return $this->resultInfo($data);
    }

    // 车辆类型列表
    public function actionCartypes(){
        $list = AppCartype::get_list();
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list]);
        return $this->resultInfo($data);
    }


    /*
     * 删除车辆
     * */
    public function actionDel()
    {
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = Car::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->delete_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'删除车辆:'.$model->carnumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'删除成功']);
            return $this->resultInfo($data);
        }

        $data = $this->encrypt(['code'=>400,'msg'=>'删除失败']);
        return $this->resultInfo($data);
    }
        /*
         *
         * 启用车辆
         * */
        public function actionUse_y(){
            $input = Yii::$app->request->post();
            $token = $input['token'];
            $id = $input['id'];
            if (empty($token) || empty($id)){
                $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
                return $this->resultInfo($data);
            }
            $check_result = $this->check_token($token,true);//验证令牌
            $user = $check_result['user'];
            $model = Car::find()->where(['id'=>$id])->one();
            $this->check_group_auth($model->group_id,$user);
            $model->use_flag = 'Y';
            $res = $model->save();
            if ($res){
                $this->hanldlog($user->id,$user->name.'启用车辆：'.$model->carnumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
                return $this->resultInfo($data);
            }
        }

    /*
     * 禁用车辆
     * */
        public function actionUse_n(){
            $input = Yii::$app->request->post();
            $token = $input['token'];
            $id = $input['id'];
            if (empty($token) || empty($id)){
                $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
                return $this->resultInfo($data);
            }
            $check_result = $this->check_token($token,true);//验证令牌
            $user = $check_result['user'];
            $model = Car::find()->where(['id'=>$id])->one();
            $this->check_group_auth($model->group_id,$user);
            $model->use_flag = 'N';
            $res = $model->save();
            if ($res){
                $this->hanldlog($user->id,$user->name.'禁用车辆'.$model->carnumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
                return $this->resultInfo($data);
            }
        }

    /*
     * 检索车辆
     * */
    public function actionSelect()
    {
        $input = Yii::$app->request->post();
        $group_id = $input['group_id'];
        $val = $input['val'];

        $list = Car::find()
            ->alias('c')
            ->select(['c.*','t.carparame'])
            ->leftJoin('app_cartype t','c.cartype=t.car_id');
        if ($val) {
            $list->orWhere(['like','c.carnumber',$val])
                ->orWhere(['like','t.carparame',$val])
                ->orWhere(['like','c.driver_name',$val])
                ->orWhere(['like','c.mobile',$val]);
        }

        $list->andWhere(['c.group_id' => $group_id,'c.use_flag'=>'Y','c.delete_flag'=>'Y']);

        $l = json_encode($list);
        $list = $list
            ->orderBy(['c.update_time' => SORT_DESC])
            ->limit(20)
            ->asArray()
            ->all();

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list,'input'=>$input]);
        return $this->resultInfo($data);
    }
















































}


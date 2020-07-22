<?php
namespace app\modules\api\controllers;

use app\models\AppCartype;
use app\models\AppCommonAddress;
use app\models\AppCommonContacts;
use app\models\AppGroup;
use app\models\AppOrder;
use app\models\AppPayment;
use app\models\AppReceive;
use app\models\Customer;
use Yii;

class VehicleController extends CommonController{

    /*
     * 编辑订单
     * */
    public function actionEdit(){


    }


    /*
     * 订单详情(编辑)
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
        if ($id) {
            $model = AppOrder::find()->where(['id'=>$id])->asArray()->one();
        } else {
            $model = new AppOrder();
        }

        $groups = AppGroup::group_list($user);
        $car_list = AppCartype::get_list();

        if ($id) {
            $group_id = $model['group_id'];
        } else {
            $group_id = $groups[0]['id'];
        }
        $customer = Customer::get_list($group_id);
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'groups'=>$groups,'customer'=>$customer,'group_id'=>$group_id,'car_list'=>$car_list]);
        return $this->resultInfo($data);
    }
    /*
     *
     *                         .==.       .==.
     *                        //`^\\     //^`\\
     *                       //^^ ^\(\_/)/^ ^^\\
     *                      //^ ^^ ^/6 6\^ ^^ ^\\
     *                     //^ ^^ ^/(. .)\^ ^^ ^\\
     *                    // ^^ ^/\| v““v |/\^ ^^\\
     *                   // ^^/\/ /  `~~`  \ \/\^^\\
     *                   -----------------------------
     *             what is it? a bat,or a Dragon BB? I don't konw.
     * */
}

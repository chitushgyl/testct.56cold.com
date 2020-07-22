<?php
namespace app\modules\api\controllers;

use app\models\AppBalance;
use app\models\AppGroup;
use app\models\AppPaymessage;
use app\models\AppWithdraw;
use Yii;

/**
 * Default controller for the `api` module
 */
class BalanceController extends CommonController
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
        $starttime = $input['starttime'] ?? '';
        $endtime = $input['endtime'] ?? '';
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
        // $check_result = $this->check_token_list($token);//验证令牌
        // $user = $check_result['user'];

        $list = AppBalance::find()->where(['group_id'=>$group_id]);

        if ($starttime && $endtime){
            $list->andWhere(['between','create_time',$starttime.' 00:00:00',$endtime.' 23:59:59']);
        } else {
            if ($starttime){
                $list->andWhere(['>=','create_time',$starttime.' 00:00:00']);
            }if ($endtime){
                $list->andWhere(['<=','create_time',$endtime." 23:59:59"]);
            }
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['create_time'=>SORT_DESC])
            ->asArray()
            ->all();
        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }

    /*
     * 申请提现
     * */
    public function actionAsk_withdraw(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $account = $input['account'];
        $name = $input['name'];
        $price = $input['price'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误！']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        if (empty($account)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写支付宝账号']);
            return $this->resultInfo($data);
        }
        if (empty($name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写收款人真实姓名']);
            return $this->resultInfo($data);
        }
        if (empty($price)){
            $data = $this->encrypt(['code'=>400,'msg'=>'金额不能为空']);
            return $this->resultInfo($data);
        }
        $group = AppGroup::findOne($user->group_id);
        if ($group->balance < $price){
            $data = $this->encrypt(['code'=>400,'msg'=>'提现金额必须大于余额']);
            return $this->resultInfo($data);
        }
        $transaction = Yii::$app->db->beginTransaction();
        try{
            $model = new AppWithdraw();
            $model->ordernumber = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
            $model->account = $account;
            $model->name = $name;
            $model->price = $price ;
            $model->group_id = $user->group_id;
            $res = $model->save();
            $pay = new AppPaymessage();
            $pay->orderid = $model->ordernumber;
            $pay->paynum = $price;
            $pay->create_time = date('Y-m-d H:i:s', time());
            $pay->userid = $user->id;
            $pay->paytype = 1;
            $pay->type = 1;
            $pay->state = 6;
            $pay->group_id = $user->group_id;
            $res_c = $pay->save();

            $balance = new AppBalance();
            $balance->pay_money = $price;
            $balance->order_content = '提现';
            $balance->action_type = 10;
            $balance->userid = $user->id;
            $balance->create_time = date('Y-m-d H:i:s', time());
            $balance->ordertype = 2;
            $balance->orderid = $model->id;
            $balance->group_id = $user->group_id;
            $res_b = $balance->save();

            $pay_price = $group->balance ;
            $group->balance = $pay_price - $price;
            $res_g = $group->save();

            if ($res  &&$res_c && $res_b && $res_g){
                $transaction->commit();
                $this->hanldlog($user->id,'申请提现'.$user->name);
                $data = $this->encrypt(['code'=>200,'msg'=>'提现金额将会在2-24小时内到达支付宝账户']);
                return $this->resultInfo($data);
            }

        }catch(\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'提现失败']);
            return $this->resultInfo($data);
        }
    }

        /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex_withdraw(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $starttime = $input['starttime'] ?? '';
        $endtime = $input['endtime'] ?? '';
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

        $list = AppWithdraw::find()->where(['group_id'=>$group_id]);

        if ($starttime && $endtime){
            $list->andWhere(['between','create_time',$starttime.' 00:00:00',$endtime.' 23:59:59']);
        } else {
            if ($starttime){
                $list->andWhere(['>=','create_time',$starttime.' 00:00:00']);
            }if ($endtime){
                $list->andWhere(['<=','create_time',$endtime." 23:59:59"]);
            }
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['create_time'=>SORT_DESC])
            ->asArray()
            ->all();
        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }

}
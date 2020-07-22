<?php
namespace app\controllers\admin;


use Yii;
use app\models\AppBalance;
use app\models\AppGroup;
use app\models\AppPaymessage;
use app\models\AppWithdraw;

Class WithdrawController extends AdminBaseController{
    /*
     * 提现列表
     * */
    public function actionIndex(){
        $keyword = $this->request->get('keyword');
        if($this->request->isAjax){
            $list = AppWithdraw::find()
                ->alias('a')
                ->select('a.*,b.group_name')
                ->leftJoin('app_group b','a.group_id = b.id');
            if($keyword){
                $list->andWhere(['like','a.ordernumber',$keyword])
                    ->orWhere(['like','a.account',$keyword])
                    ->orWhere(['like','a.name',$keyword])
                    ->orWhere(['like','b.group_name',$keyword]);
            }
            $count = $list->count();
            $list = $list->offset(($this->request->get('page',1) - 1) * $this->request->get('limit',10))
                ->limit($this->request->get('limit',10))
                ->orderBy(['a.create_time'=>SORT_DESC])
                ->asArray()
                ->all();
            $data = [
                'code' => 0,
                'msg'   => '正在請求中...',
                'count' => $count,
                'data'  => precaution_xss($list)
            ];
            return json_encode($data);
        }else{
            return $this->render('index');
        }
    }
    /*
     * 提现成功
     * */
    public function actionSuccess(){
        $input = $this->request->post();
        $id = $input['id'];
        $order = AppWithdraw::findOne($id);
        if ($order->state == 2){
            return json_encode(['code'=>4000,'msg'=>'已提现成功，请勿重复提交']);
        }
        if($this->request->isAjax) {
             $res = $this->Alipay_withdraw($order->ordernumber,$order->account,$order->name,$order->price);
             if ($res){
                 return json_encode(['code'=>2000,'msg'=>'提现成功']);
             }
        }else{
            return json_encode(['code'=>4000,'msg'=>'提现失败']);
        }
    }

    /*
     * 提现失败
     * */
    public function actionFail(){
        $input = $this->request->post();
        $id = $input['id'];
        $reason = $input['reason'];
//        var_dump();
        $order = AppWithdraw::findOne($id);
        if($this->request->isAjax) {
            $reason = $this->request->post('reason');
            $order->state = 3;
            $order->reason = $reason;
            $pay = new AppPaymessage();
            $pay->orderid = $order->ordernumber;
            $pay->paynum = $order->price;
            $pay->create_time = date('Y-m-d H:i:s', time());
            $pay->paytype = 1;
            $pay->type = 1;
            $pay->state = 3;
            $pay->group_id = $order->group_id;

            $balance = new AppBalance();
            $balance->pay_money = $order->price;
            $balance->order_content = '提现失败退款';
            $balance->action_type = 7;

            $balance->create_time = date('Y-m-d H:i:s', time());
            $balance->ordertype = 2;
            $balance->orderid = $order->id;
            $balance->group_id = $order->group_id;

            $group = AppGroup::find()->where(['id'=>$order->group_id])->one();
            $money = $group->balance;
            $group->balance = $money + $order->price;

            $transaction = Yii::$app->db->beginTransaction();
            try{
                $res = $order->save();
                $group->save();
                $pay->save();
                $balance->save();
                $transaction->commit();
                return json_encode(['code'=>2000,'msg'=>'操作成功']);
            }catch (\Exception $e){
                $transaction->rollBack();
                return json_encode(['code'=>4000,'msg'=>'操作失败']);
            }
        }else{
            return json_encode(['code'=>4000,'msg'=>'操作失败']);
        }
    }
}

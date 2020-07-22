<?php
namespace app\controllers\admin;


use app\models\AppAskForCompany;
use Yii;


Class AttestationController extends AdminBaseController{
    public function actionIndex(){
        $keyword = $this->request->get('keyword');
        if($this->request->isAjax){
            $list = AppAskForCompany::find()
                ->alias('a')
                ->select('a.*,b.group_name as company_name')
                ->leftJoin('app_group b','a.group_id = b.id');
            if($keyword){
                $list->orWhere(['like','b.group_name',$keyword]);
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
     * 详情
     * */
    public function actionView(){
        $id = $_GET['id'];
        $model = AppAskForCompany::find()
            ->alias('a')
            ->select('a.*,b.group_name as company_name')
            ->leftJoin('app_group b','a.group_id = b.id')
            ->where(['a.id'=>$id])
            ->asArray()
            ->one();
        return $this->render('view',['model'=>$model]);

    }

    /*
     * 认证成功
     * */
    public function actionSuccess(){
        $input = $this->request->post();
        $id = $input['id'];
        $order = AppAskForCompany::findOne($id);
        if ($order->state != 1){
            return json_encode(['code'=>4000,'msg'=>'已审核，请勿重复操作']);
        }
        if($this->request->isAjax) {
            $account = User::find($order->account_id);
            $account->level_id = 3;
            $arr = $account->save();
            $order->state = 2;
            $res = $order->save();
            if ($arr && $res){
                return json_encode(['code'=>2000,'msg'=>'审核成功']);
            }
        }else{
            return json_encode(['code'=>4000,'msg'=>'审核失败']);
        }
    }

    /*
     * 认证失败
     * */
    public function actionFail(){
        $input = $this->request->post();
        $id = $input['id'];
        $order = AppAskForCompany::findOne($id);
        if ($order->state != 1){
            return json_encode(['code'=>4000,'msg'=>'已审核，请勿重复提交']);
        }
        if($this->request->isAjax) {
            $order->reason = $input['reason'];
            $order->state = 3;
            $res = $order->save();
            if ($res){
                return json_encode(['code'=>2000,'msg'=>'审核成功']);
            }
        }else{
            return json_encode(['code'=>4000,'msg'=>'审核失败']);
        }
    }

}

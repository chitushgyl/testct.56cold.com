<?php
namespace app\modules\api\controllers;


use app\models\AppReceive;
use app\models\Customer;
use Yii;

/**
 * Default controller for the `api` module
 */
class ReceiveController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {

    	$request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $keyword = $input['keyword'] ?? '';
        $receive_status = $input['receive_status'] ?? '';
        $end_time = $input['end_time'] ?? '';
        $start_time = $input['start_time'] ?? '';
        $company_type = $input['company_type'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $chitu = $input['chitu'];

        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (empty($token) || !$group_id){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $check_result = $this->check_token_list($token,$chitu);//验证令牌
        if ($company_type == 1) {
            $list = AppReceive::find()
                ->alias('r')
                ->select(['r.*','g.all_name group_name','r.ordernumber'])
                ->leftJoin('app_customer g','r.compay_id=g.id');
        } else {
            $list = AppReceive::find()
            	->alias('r')
            	->select(['r.*','g.group_name','r.ordernumber'])
            	->leftJoin('app_group g','r.compay_id=g.id');
        }
        if ($end_time && $start_time) {
            $list->andWhere(['between','r.update_time',$start_time.' 00:00:00',$end_time.' 23:59:59']);
        } else {
            if ($start_time) {
                $list->andWhere(['>=','r.update_time',$start_time.' 00:00:00',$end_time.' 23:59:59']);
            } else if($end_time) {
                $list->andWhere(['<=','r.update_time',$end_time.' 23:59:59']);
            }
        }   

        if ($receive_status) {
            $list->andWhere(['r.status'=>$receive_status]);
        }

        if ($company_type == 1) {
            $list->andWhere(['r.company_type'=>1]);
            if ($keyword) {
                $list->andWhere(['like','g.all_name',$keyword]);
            }
        } else if($company_type == 2) {
            $list->andWhere(['in','company_type',[2,3]]);
            if ($keyword) {
                $list->andWhere(['like','g.group_name',$keyword]);
            }
        }

        $list->andWhere(['r.group_id'=>$group_id]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['r.update_time'=>SORT_DESC])
            ->asArray()
            ->all();
        if ($company_type == 3) {
            foreach($list as $k => $v) {
                if ($v['company_type'] == 1) {
                    $one = Customer::findOne($v['compay_id']);
                    if ($one) {
                        $list[$k]['group_name'] = $one->all_name;
                    } else {
                        $list[$k]['group_name'] = '';
                    }
                }
            }
        }

        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'auth' => $check_result['auth'],
            'data'  => precaution_xss($list),
//            'totalRow'=>json_encode(['receivprice'=>'1000'])
        ];
        return json_encode($data);

    }
    // 修改应收金额
    public function actionEdit_price(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $price = $input['price'];
        $remark = $input['remark'];
        $token = $input['token'];
        if (empty($token) && !$id){
            $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $model = AppReceive::findOne($id);
        if ($model) {
            if ($model->status != 1) {
                $data = $this->encrypt(['code'=>'400','msg'=>'已完成对账!']);
                return $this->resultInfo($data);
            }
            $this->check_group_auth($model->group_id,$user);
            $model->receivprice = $price;
            $model->remark = $remark;
            $res = $model->save();
            if ($res) {
                $this->hanldlog($user->id,'修改应收金额：'.$model->order_id);
                $data = $this->encrypt(['code'=>'200','msg'=>'修改成功！']);
                return $this->resultInfo($data);
            } else {
                $data = $this->encrypt(['code'=>'400','msg'=>'修改失败！']);
                return $this->resultInfo($data);
            }
        } else {
            $data = $this->encrypt(['code'=>'400','msg'=>'错误，请刷新重试！']);
            return $this->resultInfo($data);
        }

    }    

    // 应收已完成
    public function actionReceive_over(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        if (empty($token) && !$id){
            $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $model = AppReceive::findOne($id);
        if ($model) {
            if ($model->status != 1) {
                $data = $this->encrypt(['code'=>'400','msg'=>'已完成对账!']);
                return $this->resultInfo($data);
            }
            $this->check_group_auth($model->group_id,$user);
            $model->status = 3;
            $model->trueprice = $model->receivprice;
            $res = $model->save();
            if ($res) {
                $this->hanldlog($user->id,'对账成功：'.$model->order_id);
                $data = $this->encrypt(['code'=>'200','msg'=>'对账成功！']);
                return $this->resultInfo($data);
            } else {
                $data = $this->encrypt(['code'=>'400','msg'=>'对账失败！']);
                return $this->resultInfo($data);
            }
        } else {
            $data = $this->encrypt(['code'=>'400','msg'=>'错误，请刷新重试！']);
            return $this->resultInfo($data);
        }

    }    

}
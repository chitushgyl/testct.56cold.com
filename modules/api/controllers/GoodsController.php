<?php
namespace app\modules\api\controllers;

use app\models\AppGoods;
use app\models\User;
use app\models\AppGroup;
use Yii;
use yii\web\Request;

/**
 * Customer controller for the `api` module
 */
class GoodsController extends CommonController
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
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $use_flag = $input['use_flag'] ?? '';
        $city = $input['city'] ?? '';
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
        $check_result = $this->check_token_list($token);//验证令牌
        $list = AppGoods::find()
            ->alias('r')
            ->select(['r.*','g.group_name'])
            ->leftJoin('app_group g','r.group_id = g.id');

        if ($city) {
            $list->orWhere(['like','r.startcity',$city])->orWhere(['like','r.endcity',$city]);
        }
        if ($use_flag) {
            $list->andWhere(['r.use_flag'=>$use_flag]);
        }        
        if ($group_id) {
            $list->andWhere(['r.group_id'=>$group_id]);
        }
        $list->andWhere(['r.delete_flag'=>'Y']);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['r.use_flag'=>SORT_DESC,'r.update_time'=>SORT_DESC])
            ->asArray()
            ->all();
        foreach ($list as $key =>$value){
            $list[$key]['startstr'] = json_decode($value['startstr'],true);
            $list[$key]['endstr'] = json_decode($value['endstr'],true);
        }
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
     * 在线列表
     * */
    public function actionOnline_index(){
            $request = Yii::$app->request;
            $input = $request->post();
            $page = $input['page'] ?? 1;
            $limit = $input['limit'] ?? 10;
            $use_flag = $input['use_flag'] ?? '';
            $startcity = $input['startcity'] ?? '';
            $endcity = $input['endcity'] ?? '';
            $data = [
                'code' => 200,
                'msg'   => '',
                'status'=>400,
                'count' => 0,
                'data'  => []
            ];
            $list = AppGoods::find();
            if ($startcity) {
                $list->andWhere(['like','startcity',$startcity]);
            }            

            if ($endcity) {
                $list->andWhere(['like','endcity',$endcity]);
            }

            $list->andWhere(['use_flag'=>'Y','delete_flag'=>'Y']);

            $count = $list->count();
            $list = $list->offset(($page - 1) * $limit)
                ->limit($limit)
                ->orderBy(['use_flag'=>SORT_DESC,'update_time'=>SORT_DESC])
                ->asArray()
                ->all();
            foreach ($list as $key =>$value){
                $list[$key]['startstr'] = json_decode($value['startstr'],true);
                $list[$key]['endstr'] = json_decode($value['endstr'],true);
            }
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
     * 详情
     * */
    public function actionView(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $id = $input['id'];
        $check_result = $this->check_token($token);
        $user = $check_result['user'];
        $groups = AppGroup::group_list($user);
        if ($id) {
            $model = AppGoods::find()->where(['id'=>$id])->asArray()->one();
        } else {
            $model = new AppGoods();
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'ok','data'=>$model,'groups'=>$groups]);
        return $this->resultInfo($data);
    }      

/*
 * 添加货源
 * */
    public function actionAdd(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $goodsname = $input['goodsname'];
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $startstr = $input['startstr'];
        $endstr = $input['endstr'];
        $ordertype = $input['ordertype'];
        $volume = $input['volume'];
        $number = $input['number'];
        $weight = $input['weight'];
        $price = $input['price'];
        $contact_name = $input['contact_name'];
        $contact_tel = $input['contact_tel'];
        $remark = $input['remark'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($startstr)){
            $data = $this->encrypt(['code'=>400,'msg'=>'起始地不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($endstr)){
            $data = $this->encrypt(['code'=>400,'msg'=>'目的地不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($price)){
            $data = $this->encrypt(['code'=>400,'msg'=>'价格不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($contact_name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'联系人不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($contact_tel)){
            $data = $this->encrypt(['code'=>400,'msg'=>'联系电话不能为空']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);//验证令牌
        $user = $check_result['user'];
//        $this->check_group_auth($user->group_id,$user);
        $model = new AppGoods();
        $model->goodsname = $goodsname;
        $model->group_id = $user->group_id;
        $model->startstr = $startstr;
        $model->endstr = $endstr;
        $model->startcity = $startcity;
        $model->endcity = $endcity;
        $model->ordertype = $ordertype;
        $model->volume = $volume;
        $model->number = $number;
        $model->weight = $weight;
        $model->price = $price;
        $model->contact_name = $contact_name;
        $model->contact_tel = $contact_tel;
        $model->remark = $remark;
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'添加货源：'.$model->goodsname);
            $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 编辑货源
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        $goodsname = $input['goodsname'];
        $startstr = $input['startstr'];
        $endstr = $input['endstr'];
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $ordertype = $input['ordertype'];
        $volume = $input['volume'];
        $number = $input['number'];
        $weight = $input['weight'];
        $price = $input['price'];
        $contact_name = $input['contact_name'];
        $contact_tel = $input['contact_tel'];
        $remark = $input['remark'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($startstr)){
            $data = $this->encrypt(['code'=>400,'msg'=>'起始地不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($endstr)){
            $data = $this->encrypt(['code'=>400,'msg'=>'目的地不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($price)){
            $data = $this->encrypt(['code'=>400,'msg'=>'价格不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($contact_name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'联系人不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($contact_tel)){
            $data = $this->encrypt(['code'=>400,'msg'=>'联系电话不能为空']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = AppGoods::findOne($id);
        $this->check_group_auth($model->group_id,$user);
        $model->goodsname = $goodsname;
        $model->group_id = $user->group_id;
        $model->startstr = $startstr;
        $model->endstr = $endstr;
        $model->startcity = $startcity;
        $model->endcity = $endcity;
        $model->ordertype = $ordertype;
        $model->volume = $volume;
        $model->number = $number;
        $model->weight = $weight;
        $model->price = $price;
        $model->contact_name = $contact_name;
        $model->contact_tel = $contact_tel;
        $model->remark = $remark;
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'编辑货源：'.$model->goodsname);
            $data = $this->encrypt(['code'=>200,'msg'=>'编辑成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'编辑失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 删除货源
     * */
    public function actionDel(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = AppGoods::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->delete_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'删除货源：'.$model->goodsname);
            $data = $this->encrypt(['code'=>200,'msg'=>'删除成功']);
            return $this->resultInfo($data);
        }
        $data = $this->encrypt(['code'=>400,'msg'=>'删除失败']);
        return $this->resultInfo($data);
    }

    /*
     * 禁用
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
        $model = AppGoods::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->use_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'禁用货源信息：'.$model->goodsname);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 启用
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
        $model = AppGoods::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->use_flag = 'Y';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'启用货源信息：'.$model->goodsname);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }
}

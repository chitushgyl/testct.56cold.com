<?php

namespace app\modules\api\controllers;

use app\models\AppIpApply;
use app\models\AppIp;
use Yii;

/**
 * AppIp controller for the `api` module
 */
class AppIpController extends CommonController
{
    /*
     * 域名申请列表
     * */
    public function actionIndex(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $status = $input['status'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;

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
        $user = $check_result['user'];
        $group_id = $user->parent_group_id;
        $list = AppIpApply::find();
  
        if ($group_id) {
            $list->where(['group_id'=>$group_id]);
        }  else {
            $data['msg'] = '参数错误!';
            return json_encode($data);
        }      

        if ($status) {
            $list->andWhere(['status'=>$status]);
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['status'=>SORT_ASC,'update_time'=>SORT_DESC])
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
     * 申请域名
     * */
    public function actionAdd(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $url = $input['url'];
        $name = $input['name'];
        // $full_name = $input['full_name'];
        $logo = $input['logo'];
        $index = $input['index'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($url)){
            $data = $this->encrypt(['code'=>400,'msg'=>'登录域名不能为空！']);
            return $this->resultInfo($data);
        }        
        if (empty($name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'公司名称不能为空！']);
            return $this->resultInfo($data);
        }        
        // if (empty($full_name)){
        //     $data = $this->encrypt(['code'=>400,'msg'=>'公司全称不能为空！']);
        //     return $this->resultInfo($data);
        // }        
        if (empty($logo)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请上传浏览器头部LOGO！']);
            return $this->resultInfo($data);
        }        
        if (empty($index)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请上传页面头部LOGO！']);
            return $this->resultInfo($data);
        }

        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $group_id = $user->parent_group_id;

        $flag = AppIp::find()->where(['url'=>$url])->andWhere(['!=','group_id',$group_id])->one();
        if ($flag) {
            $data = $this->encrypt(['code'=>400,'msg'=>'该域名已被使用！']);
            return $this->resultInfo($data);
        }

        $is_apply = AppIpApply::find()->where(['group_id'=>$group_id,'status'=>2])->one();
        if ($is_apply) {
            $data = $this->encrypt(['code'=>400,'msg'=>'如需变更，请修改正在申请的记录！']);
            return $this->resultInfo($data);
        }
        $time = date('Y-m-d H:i:s',time());
        $model = new AppIpApply();
        $model->url = $url;
        $model->name = $name;
        // $model->full_name = $full_name;
        $model->logo = $logo;
        $model->index = $index;
        $model->group_id = $group_id;
        $model->status = 2;
        $model->create_time = $time;
        $model->update_time = $time;
        $model->remark = '';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'申请域名：'.$url);
            $data = $this->encrypt(['code'=>200,'msg'=>'申请成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'申请失败']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 申请修改域名
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $url = $input['url'];
        $name = $input['name'];
        // $full_name = $input['full_name'];
        $logo = $input['logo'];
        $index = $input['index'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($url)){
            $data = $this->encrypt(['code'=>400,'msg'=>'域名名称不能为空！']);
            return $this->resultInfo($data);
        }        
        if (empty($name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'公司名称不能为空！']);
            return $this->resultInfo($data);
        }        
        // if (empty($full_name)){
        //     $data = $this->encrypt(['code'=>400,'msg'=>'公司全称不能为空！']);
        //     return $this->resultInfo($data);
        // }        
        if (empty($logo)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请上传浏览器头部LOGO！']);
            return $this->resultInfo($data);
        }        
        if (empty($index)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请上传页面头部LOGO！']);
            return $this->resultInfo($data);
        }

        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $group_id = $user->parent_group_id;

        $flag = AppIp::find()->where(['url'=>$url])->andWhere(['!=','group_id',$group_id])->one();
        if ($flag) {
            $data = $this->encrypt(['code'=>400,'msg'=>'该域名已被使用！']);
            return $this->resultInfo($data);
        }
        $time = date('Y-m-d H:i:s',time());
        $model = AppIpApply::findOne($id);
        $model->url = $url;
        $model->name = $name;
        $model->full_name = $full_name;
        $model->logo = $logo;
        $model->index = $index;
        $model->status = 2;
        $model->update_time = $time;
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'修改申请域名：'.$url);
            $data = $this->encrypt(['code'=>200,'msg'=>'修改申请成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'修改申请失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 申请域名详情
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
 
        if ($id) {
            $model = AppIpApply::find()->where(['id'=>$id])->asArray()->one();
        } else {
            $model = new AppIpApply();
        }

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$model]);
        return $this->resultInfo($data);
    }

    /*
     *取消申请
     * */
    public function actionCancel(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = AppIpApply::find()->where(['id'=>$id])->one();
        if ($model->status != 2) {
            $data = $this->encrypt(['code'=>400,'msg'=>'状态已改变，取消失败']);
            return $this->resultInfo($data);
        }
        $model->status = 4;
        $model->update_time = date('Y-m-d H:i:s',time());
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'取消域名申请:'.$model->url);
            $data = $this->encrypt(['code'=>200,'msg'=>'取消成功']);
            return $this->resultInfo($data);
        }

        $data = $this->encrypt(['code'=>400,'msg'=>'取消失败']);
        return $this->resultInfo($data);
    }

}
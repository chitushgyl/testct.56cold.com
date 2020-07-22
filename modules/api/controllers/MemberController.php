<?php

namespace app\modules\api\controllers;



use app\models\AppMemberOrder;

/**
 * Default controller for the `api` module
 */
class MemberController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {

    }

    public function actionAdd(){
        $input = \Yii::$app->request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $month = $input['month'];
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $member = new AppMemberOrder();
        $member->ordernumber = 'M'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $member->month = $month;
        $member->user_id = $user->id;
        $member->group_id = $group_id;
        $res = $member->save();
        if ($res){
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功','data'=>$member->id]);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作成功','data'=>$member->id]);
            return $this->resultInfo($data);
        }
    }

}

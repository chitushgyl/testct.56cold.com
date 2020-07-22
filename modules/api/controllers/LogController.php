<?php

namespace app\modules\api\controllers;

use app\models\AppLog; 
use app\models\User; 
use Yii;

/**
 * Log controller for the `api` module
 */
class LogController extends CommonController
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
        $check_result = $this->check_token_list($token);//验证令牌

        $admin_list = User::find()
            ->select(['id'])
            ->where(['group_id'=>$group_id])
            ->asArray()
            ->all();
        $admin_ids = [];
        if ($admin_list) {
            foreach ($admin_list as $v) {
                $admin_ids[] = $v['id'];
            }
        }

        $count = 0;
        $list = [];

        if ($admin_ids) {
            $list = AppLog::find()
                ->alias('l')
                ->select(['l.content','l.update_time','a.name'])
                ->leftJoin('app_admin a','l.admin_id=a.id')
                ->where(['in','l.admin_id',$admin_ids]);
            $count = $list->count();

            $list = $list->offset(($page - 1) * $limit)
                ->limit($limit)
                ->orderBy(['l.update_time'=>SORT_DESC])
                ->asArray()
                ->all();
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
    
}

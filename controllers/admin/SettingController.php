<?php
/**
 * Created by pysh.
 * Date: 2020/06/07
 * Time: 13:26
 */

namespace app\controllers\admin;

use app\models\Note;
use app\models\AppSetting;

class SettingController extends AdminBaseController
{
    public function actionIndex(){
        if($this->request->isAjax){
            $model = AppSetting::find();

            $count = $model->count();
            $list = $model->offset(($this->request->get('page',1) - 1) * $this->request->get('limit',10))
                ->limit($this->request->get('limit',10))
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

     /**
     * Desc: 編輯
     * Created by pysh
     * Date: 2020/06/07
     * Time: 13:47
     */
    public function actionEdit(){
        if($this->request->isPost){
            $flag_error = true;
            $id = $this->request->bodyParams['id'];
            $value = $this->request->bodyParams['value'] ?? 0;
            if(!$id){
                $flag_error = false;
                $this->withErrors('错误，请刷新重试！');
            }

            // 更新
            $model = AppSetting::findOne(['id'=>$id]);
            $model->value = $value;

            if ($flag_error) {
                if($model->save()){
                    AddLogController::addSysLog(AddLogController::setting,'修改'.$model->name.':'.$value);
                    return $this->resultInfo(['retCode'=>1000,'retMsg'=>'修改成功!']);
                } else {
                    return $this->resultInfo(['retCode'=>1001,'retMsg'=>'修改失败!']);
                }
            }
        }
    }    

}
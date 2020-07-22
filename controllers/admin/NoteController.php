<?php
/**
 * Created by Joker.
 * Date: 2019/7/9
 * Time: 10:05
 */

namespace app\controllers\admin;

use app\models\Note;

class NoteController extends AdminBaseController
{
    public function actionIndex(){
        if($this->request->isAjax){
            $model = Note::find()
                ->select(['n.*','a.username'])
                ->alias('n')
                ->leftJoin('ct_account a','n.admin=a.id');

            // c
            if($this->request->get('c')){
                $model->andWhere(['n.c'=>$this->request->get('c')]);
            }
            // username
            if($this->request->get('username')){
                $model->andWhere(['a.username'=>$this->request->get('username')]);
            }

            // 时间
            if($this->request->get('time_start')){
                $model->andWhere(['>=','n.addtime',strtotime($this->request->get('time_start').' 00:00:00')]);
            }
            if($this->request->get('time_end')){
                $model->andWhere(['<=','n.addtime',strtotime($this->request->get('time_end').' 23:59:59')]);
            }

            $count = $model->count();
            $list = $model->offset(($this->request->get('page',1) - 1) * $this->request->get('limit',10))
                ->limit($this->request->get('limit',10))
                ->orderBy(['n.addtime'=>SORT_DESC])
                ->asArray()
                ->all();
            foreach ($list as $k=>$v) {
                $list[$k]['addtime'] = date('Y-m-d H:i:s');
            }
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

}
<?php
namespace app\controllers\admin;


use app\models\Car;

class CarController extends AdminBaseController{
    /*
     * 车辆审核列表
     * */
    public function actionIndex(){
        $keyword = $this->request->get('keyword');
        if($this->request->isAjax){
            $list = Car::find()
                ->alias('a')
                ->select('a.*,b.group_name')
                ->leftJoin('app_group b','a.group_id = b.id')
                ->where(['b.level_id'=>1]);
//            if($keyword){
//                $list->andWhere(['like','a.ordernumber',$keyword])
//                    ->orWhere(['like','a.account',$keyword])
//                    ->orWhere(['like','a.name',$keyword])
//                    ->orWhere(['like','b.group_name',$keyword]);
//            }
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
        $model = Car::find()
            ->alias('a')
            ->select('a.*,b.group_name as company_name')
            ->leftJoin('app_group b','a.group_id = b.id')
            ->where(['a.id'=>$id])
            ->asArray()
            ->one();
        return $this->render('view',['model'=>$model]);

    }
}
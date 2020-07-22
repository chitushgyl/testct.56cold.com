<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 17:00
 */

namespace app\controllers\admin;
use Yii,
    app\models\AppIp,
    app\models\AppIpApply,
    app\models\AppGroup;

class AppIpApplyController extends AdminBaseController
{
    /**
     * Desc: 域名申请列表
     * Created by pysh
     * Date: 2020/2/2
     * Time: 17:42
     */
    public function actionIndex(){

        if($this->request->isAjax){
            $list = AppIpApply::find()
                ->alias('a')
                ->select(['a.*','g.group_name'])
                ->leftJoin('app_group g','a.group_id = g.id');
            $status = $this->request->get('status');
            if($status){
                $list->andWhere(['=','a.status',$status]);
            }
            $count = $list->count();
            $list = $list
                ->offset(($this->request->get('page',1) - 1) * $this->request->get('limit',10))
                ->limit($this->request->get('limit',10))
                ->orderBy(['a.update_time'=>SORT_DESC])
                ->asArray()
                ->all();
            $data = [
                'code' => 0,
                'msg'   => '正在请求中...',
                'count' => $count,
                'data'  => precaution_xss($list)
            ];
            return json_encode($data);
        }else{
            return $this->render('index');
        }
    }     

    /**
     * Desc: 編輯left
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:47
     */
    public function actionEdit(){
        $id = $this->request->get('id');
        if($this->request->isPost){
            $flag_error = true;
            if(empty($this->request->bodyParams['name'])){
                $flag_error = false;
                $this->withErrors('等级名称不能为空!!');
            }
            // $flag_error = false;
                // $this->withErrors('路由'.$id);
            // 如果路由不为空,查看是否已经存在该路由
            if(!empty($this->request->bodyParams['name'])){
                $have = AppLevel::find()->where(['name'=>$this->request->bodyParams['name']])
                    ->andWhere(["<>",'level_id',$id])
                    ->asArray()
                    ->all();
                if($flag_error && !empty($have)){
                    $flag_error = false;

                    $this->withErrors(json_encode($have));
                    $this->withErrors('等级名称已经存在,请重新添加!!');
                }
            }
            // 更新
            $model = AppLevel::findOne(['level_id'=>$id]);
            $model->name = $this->request->bodyParams['name'];
            $model->update_time = date('Y-m-d H:i:s');


            if ($flag_error) {
                if($model->save()){
                    AddLogController::addSysLog(AddLogController::appLevel,'修改公司等级,等级名称为:'.$model->name);
                    return $this->withSuccess('修改成功!!')->redirect(route('admin.app-level.index'));
                } else {
                    $this->withErrors('保存失败!');
                }
            }
        }
        $info = AppLevel::find()->where(['level_id'=>$id])->asArray()->one();
        return $this->render('edit',['info'=>$info]);
    }    

    /**
     * Desc: 取消域名申请
     * Created by pysh
     * Date: 2020/05/05
     * Time: 17:00
     */
    public function actionRefuse(){
        $id = $this->request->post('id');

        if(!$id){
            return $this->resultInfo(['retCode'=>1001,'retMsg'=>'参数错误!']);
        }
        $remark = $this->request->post('remark');
        // 更新
        $model = AppIpApply::find()->where(['id'=>$id])->one();
        if ($model->status != 2) {
            return $this->resultInfo(['retCode'=>1001,'retMsg'=>'状态已改变，请刷新重试!']);
        }
        $model->status = 3;
        $model->remark = $remark;
        $model->update_time = date('Y-m-d H:i:s',time());
        if($model->save()){
            AddLogController::addSysLog(AddLogController::appIpApply,'拒绝域名申请:'.$model->url);
            return $this->resultInfo(['retCode'=>1000,'retMsg'=>'操作成功!']);
        } else {
            return $this->resultInfo(['retCode'=>1001,'retMsg'=>'操作失败!']);
        }
    }    

    /**
     * Desc: 同意域名申请
     * Created by pysh
     * Date: 2020/05/05
     * Time: 17:00
     */
    public function actionAgree(){
        $id = $this->request->post('id');

        if(!$id){
            return $this->resultInfo(['retCode'=>1001,'retMsg'=>'参数错误!']);
        }
        // $remark = $this->request->post('remark');
        // 更新
        $model = AppIpApply::find()->where(['id'=>$id])->one();
        if ($model->status != 2) {
            return $this->resultInfo(['retCode'=>1001,'retMsg'=>'状态已改变，请刷新重试!']);
        }

        $time = date('Y-m-d H:i:s',time());

        $transaction = AppIpApply::getDb()->beginTransaction();
        try {
            $model_ip = AppIp::find()->where(['group_id'=>$model->group_id])->one();

            $model->status = 1;
            $model->update_time = $time;
            $model->save();

            if (!$model_ip) {
                $model_ip = new AppIp();
            }
            // if ($model_ip && $model_ip->use_flag == 'N') {
            //     return $this->resultInfo(['retCode'=>1001,'retMsg'=>'该公司域名禁用状态，操作失败!']);
            // }
            
            // ...其他 DB 操作...
            $model_ip->update_time = $time;
            $model_ip->url = $model->url;
            $model_ip->name = $model->name;
            $model_ip->full_name = $model->full_name;
            $model_ip->group_id = $model->group_id;
            $model_ip->apply_id = $model->id;
            $model_ip->logo = $model->logo;
            $model_ip->index = $model->index;

            $model_ip->save();
            AddLogController::addSysLog(AddLogController::appIpApply,'同意域名申请:'.$model->url);
            $transaction->commit();
            return $this->resultInfo(['retCode'=>1000,'retMsg'=>'操作成功!']);
        } catch(\Exception $e) {
            $transaction->rollBack();
            return $this->resultInfo(['retCode'=>1001,'retMsg'=>'操作失败1!']);
        } catch(\Throwable $e) {
            $transaction->rollBack();
            return $this->resultInfo(['retCode'=>1001,'retMsg'=>'操作失败2!']);
        }
        
    }

    /**
     * Desc: 新增left menu
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:48
     */
    public function actionAdd(){
        $model = new AppLevel();
        if($this->request->isPost){
            $flag_error = true;
            if(empty($this->request->bodyParams['name'])){
                $flag_error = false;
                $this->withErrors('等级名称不能为空!!');
            }
            // 如果路由不为空,查看是否已经存在该路由
            if(!empty($this->request->bodyParams['route'])){
                $have = AppLevel::find()->where(['route'=>$this->request->bodyParams['route']])
                    ->asArray()
                    ->all();
                if($flag_error && !empty($have)){
                    $flag_error = false;
                    $this->withErrors('该等级名称已经存在,请重新添加!');
                }
            }
            // 新增
            $model->name = $this->request->bodyParams['name'];
            $model->update_time = date('Y-m-d H:i:s');
            if ($flag_error) {
                if(!$model->save()){
                    $this->withErrors('系统错误!');
                }
                AddLogController::addSysLog(AddLogController::appLevel,'新增公司等级,等级名称为:'.$model->name);
                return $this->withSuccess('新增成功!')->redirect(route('admin.app-level.index'));
            }
        }
        return $this->render('add',['info'=>$model]);
    }

}
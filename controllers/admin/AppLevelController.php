<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 17:00
 */

namespace app\controllers\admin;
use Yii,
    app\models\AppLevel,
    app\models\AppAuthLeft,
    app\models\AppAuthTop;

class AppLevelController extends AdminBaseController
{
    /**
     * Desc: 菜单栏
     * Created by pysh
     * Date: 2020/2/2
     * Time: 17:42
     */
    public function actionIndex(){

        if($this->request->isAjax){
            $list = AppLevel::find();
            $count = $list->count();
            $list = $list
                ->offset(($this->request->get('page',1) - 1) * $this->request->get('limit',10))
                ->limit($this->request->get('limit',10))
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

    /**
     * Desc: 給公司等级分配权限
     * Created by pysh
     * Date: 2020/2/2
     * Time: 14:04
     */
    public function actionPermission(){
        $id = $this->request->get('id');
        $model = AppLevel::findOne(['level_id'=>$id]);
        if($this->request->isPost){
            $flag_error = true;
            if (!$this->now_auth) {
                $flag_error = false;
                $this->withErrors('权限不足!');
            }

            $data = $this->request->bodyParams;
            $auths = $data['permissions'];
            $auths_top = $data['permissions_top'];
            if(!empty($auths)){
                $permissions = implode(',',$auths);
            }else{
                $permissions = '';
            }

            if(!empty($auths_top)){
                $permissions_top = implode(',',$auths_top);
            }else{
                $permissions_top = '';
            }
            $model->auth = $permissions;
            $model->top_auth = $permissions_top;

            if ($flag_error) {
                if($model && $model->update()){
                    AddLogController::addSysLog(AddLogController::permission,'公司等级分配为:'.$model->name);
                    return $this->withSuccess('修改成功!')->redirect(route('admin.app-level.index'));
                } else {
                    return $this->withErrors('保存失败，请返回重试!');
                }
            }
        }

        
        $list = $this->getPermissionTree();
        $list = setOwn($list,explode(',',$model->auth)); 

        $list_top = $this->getPermissionTreeTop();
        $list_top = setOwn($list_top,explode(',',$model->top_auth));
        // print_r($list);
        return $this->render('permission',['info'=>$model,'list'=>$list,'list_top'=>$list_top]);
        
    }

     /**
     * Desc: 获取所有权限数形关系数组 左边栏
     * Created by pysh
     * Date: 2020/04/23
     * Time: 15:46
     * @return array
     */
    private function getPermissionTree(){
        $model = AppAuthLeft::admin_get_list();
        $tree = list_to_tree($model);
        return $tree;
    }     

    /**
     * Desc: 获取所有权限数形关系数组 菜单栏
     * Created by pysh
     * Date: 2020/04/23
     * Time: 15:46
     * @return array
     */
    private function getPermissionTreeTop(){
        $model = AppAuthTop::getList();
        $tree = list_to_tree($model);
        return $tree;
    }

}
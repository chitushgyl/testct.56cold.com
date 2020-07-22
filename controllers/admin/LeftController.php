<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 17:00
 */

namespace app\controllers\admin;
use Yii,
    app\models\AdminPermissions,
    app\models\AppAuthLeft;

class LeftController extends AdminBaseController
{
    /**
     * Desc: 菜单栏
     * Created by pysh
     * Date: 2020/2/2
     * Time: 17:42
     */
    public function actionIndex(){
        $pid = $this->request->get('pid',0);
        if($this->request->isAjax){
            $list = AppAuthLeft::find()->where(['parent_id'=>$pid]);
            $count = $list->count();
            $list = $list
                ->offset(($this->request->get('page',1) - 1) * $this->request->get('limit',10))
                ->limit($this->request->get('limit',10))
                ->orderBy(['sort'=> SORT_ASC])
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
            return $this->render('index',['pid'=>$pid]);
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
            if(empty($this->request->bodyParams['display_name'])){
                $flag_error = false;
                $this->withErrors('路由显示名称不能为空!!');
            }
            // $flag_error = false;
                // $this->withErrors('路由'.$id);
            // 如果路由不为空,查看是否已经存在该路由
            if(!empty($this->request->bodyParams['route'])){
                $have = AppAuthLeft::find()->where(['route'=>$this->request->bodyParams['route']])
                    ->andWhere(["<>",'id',$id])
                    ->asArray()
                    ->all();
                if($flag_error && !empty($have)){
                    $flag_error = false;

                    $this->withErrors(json_encode($have));
                    $this->withErrors('该路由名称已经存在,请重新添加!!');
                }
            }
            // 更新
            $permission = AppAuthLeft::findOne(['id'=>$id]);
            $permission->parent_id = $this->request->bodyParams['parent_id'];
            $permission->display_name = $this->request->bodyParams['display_name'];
            $permission->use_flag = $this->request->bodyParams['use_flag'];
            $permission->route = $this->request->bodyParams['route']?trim($this->request->bodyParams['route']):'';
            $permission->icon = $this->request->bodyParams['icon']?$this->request->bodyParams['icon']:'';
            $permission->update_time = date('Y-m-d H:i:s');
            $permission->sort = $this->request->bodyParams['sort']?$this->request->bodyParams['sort']:0;

            if ($flag_error) {
                if($permission->save()){
                    AddLogController::addSysLog(AddLogController::left,'修改权限,权限为:'.$permission->display_name);
                    return $this->withSuccess('修改成功!!')->redirect(route('admin.left.index','pid='.$this->request->bodyParams['parent_id']));
                } else {
                    $this->withErrors('保存失败!');
                }
            }
        }
        $info = AppAuthLeft::find()->where(['id'=>$id])->asArray()->one();
        return $this->render('edit',['info'=>$info,'tree'=>$this->getPermissionTree()]);
    }

    /**
     * Desc: 新增left menu
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:48
     */
    public function actionAdd(){
        $permission = new AppAuthLeft();
        if($this->request->isPost){
            $flag_error = true;
            if(empty($this->request->bodyParams['display_name'])){
                $flag_error = false;
                $this->withErrors('路由显示名称不能为空!!');
            }
            // 如果路由不为空,查看是否已经存在该路由
            if(!empty($this->request->bodyParams['route'])){
                $have = AppAuthLeft::find()->where(['route'=>$this->request->bodyParams['route']])
                    ->asArray()
                    ->all();
                if($flag_error && !empty($have)){
                    $flag_error = false;
                    $this->withErrors('该路由名称已经存在,请重新添加!');
                }
            }
            // 新增
            $permission->parent_id = $this->request->bodyParams['parent_id'];
            $permission->display_name = $this->request->bodyParams['display_name'];
            $permission->use_flag = $this->request->bodyParams['use_flag'];

            $permission->route = $this->request->bodyParams['route']?trim($this->request->bodyParams['route']):'';
            $permission->sort = $this->request->bodyParams['sort']?$this->request->bodyParams['sort']:0;

            $permission->icon = $this->request->bodyParams['icon']?$this->request->bodyParams['icon']:'';
            $permission->update_time = date('Y-m-d H:i:s');
            if ($flag_error) {
                if(!$permission->save()){
                    $this->withErrors('系统错误!');
                }
                AddLogController::addSysLog(AddLogController::left,'新增权限,权限为:'.$permission->display_name);
                return $this->withSuccess('新增成功!')->redirect(route('admin.left.index','pid='.$this->request->bodyParams['parent_id']));
            }
        }
        $pid = $this->request->get('pid');
        return $this->render('add',['info'=>$permission,'tree'=>$this->getPermissionTree(),'pid'=>$pid]);
    }

    /**
     * Desc: 删除left menu
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:48
     */
    public function actionDel(){
       if($this->request->isAjax){
            $id = $this->request->post('id');
            $model = AppAuthLeft::findOne(['id'=>$id]);
            $children = AppAuthLeft::find()
                ->where(['parent_id'=>$id])
                ->asArray()
                ->all();
            if ($children) {
                return $this->resultInfo(['retCode'=>1001,'retMsg'=>'请先删除子权限!']);
            }
            if($model && $model->delete()){
                AddLogController::addSysLog(AddLogController::left,'刪除权限,权限 id 为:'.$id);
                return $this->resultInfo(['retCode'=>1000,'retMsg'=>'删除成功!']);
            }else{
                return $this->resultInfo(['retCode'=>1001,'retMsg'=>'删除失败!']);
            }
        }else{
            return $this->resultInfo(['retCode'=>'000000','retMsg'=>'失败，请刷新重试!']);
        }
    }

     /**
     * Desc: 获取所有权限数形关系数组
     * Created by Joker
     * Date: 2019/7/5
     * Time: 15:46
     * @return array
     */
    private function getPermissionTree(){
        // $permission = AdminPermissions::find()->orderBy('Sort asc,id asc')->asArray()->all();
        $permission = AppAuthLeft::getList();
        $tree = list_to_tree($permission);
        return $tree;
    }

}
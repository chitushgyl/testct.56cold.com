<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 11:43
 */

namespace app\controllers\admin;
use app\models\AdminRole,
    app\models\Account,
    app\models\AdminPermissions;

class AdminRoleController extends AdminBaseController
{
    /**
     * Desc:职位管理
     * Created by pysh
     * Date: 2020/2/2
     * Time: 14:04
     * @return string
     */
    public function actionIndex(){
        if($this->request->isAjax){
            $list = AdminRole::find();

            $count = $list->count();
            $list = $list->offset(($this->request->get('page',1) - 1) * $this->request->get('limit',10))
                ->limit($this->request->get('limit',10))
                ->orderBy(['addtime'=>SORT_DESC])
                ->asArray()
                ->all();

            $list = precaution_xss($list);
            foreach ($list as $k=>$v) {
                $list[$k]['addtime'] = date('Y-m-d H:i:s',$v['addtime']);
            }
            $data = [
                'code' => 0,
                'msg'   => '正在请求中...',
                'count' => $count,
                'data'  => $list
            ];
            return json_encode($data);
        }else{
            return $this->render('index');
        }
    }

    /**
     * Desc: 增加职位
     * Created by pysh
     * Date: 2020/2/2
     * Time: 14:04
     */
    public function actionAdd(){
        $model = new AdminRole();
        if($this->request->isPost){
            $flag_error = true;
            if (!$this->now_auth) {
                $flag_error = false;
                $this->withErrors('权限不足!');
            }
            $data = $this->request->bodyParams;
            $role = $data['role'];
            if($flag_error && empty($role)){
                $flag_error = false;
                $this->withErrors('职位名称不能为空!');
            }
            $isHave= AdminRole::findOne(['role'=>$role]);
            if($flag_error && $isHave){
                $flag_error = false;
                $this->withErrors('该职位已经存在!');
            }
            $model->role = $role;
            $model->permissions = '0';
            $model->addtime = time();
            if ($flag_error) {
                if($model->save()){
                    AddLogController::addSysLog(AddLogController::permission,AddLogController::add,'新增职位为:'.$role);
                    return $this->withSuccess('新增成功!')->redirect(route('admin.admin-role.index'));
                } else { 
                    $this->withErrors('保存失败，请返回重试!');
                }
            }
        }
        return $this->render('add',['info'=>$model]);
    }

    /**
     * Desc: 刪除职位
     * Created by pysh
     * Date: 2020/2/2
     * Time: 14:04
     */
    public function actionDel(){
        if($this->request->isAjax){
            if (!$this->now_auth) {
                return $this->resultInfo(['retCode'=>1001,'retMsg'=>'权限不足!']);
            }
            $id = $this->request->post('role_id');
            $account = Account::findOne(['position'=>$id]);
            if ($account) {
                return $this->resultInfo(['retCode'=>1001,'retMsg'=>'职位正在使用，无法删除!']);
            }
            $model = AdminRole::findOne(['id'=>$id]);
            $role = $model->role;
            if($model && $model->delete()){
                AddLogController::addSysLog(AddLogController::permission,AddLogController::delete,'刪除职位为:'.$role);
                return $this->resultInfo(['retCode'=>1000,'retMsg'=>'删除成功!']);
            }else{
                return $this->resultInfo(['retCode'=>1001,'retMsg'=>'删除失败!']);
            }
        }else{
            return $this->resultInfo(['retCode'=>1001,'retMsg'=>'错误，请刷新重试!']);
        }

    }

    /**
     * Desc: 修改职位信息
     * Created by pysh
     * Date: 2020/2/2
     * Time: 14:04
     */
    public function actionEdit(){
        $role_id = $this->request->get('role_id');
        if($this->request->isPost){
            $flag_error = true;
            if (!$this->now_auth) {
                $flag_error = false;
                $this->withErrors('权限不足!');
            }
            $data = $this->request->bodyParams;
            $role = $data['role'];

            if($flag_error && empty($role)){
                $flag_error = false;
                $this->withErrors('职位名称不能为空!');
            }
            $model = AdminRole::findOne(['id'=>$role_id]);

            $model->role = $role;
            if ($flag_error) {
                if($model->save()){
                    AddLogController::addSysLog(AddLogController::permission,'修改职位为:'.$role);
                    return $this->withSuccess('修改成功!')->redirect(route('admin.admin-role.index'));
                } else {
                    $this->withErrors('保存失败，请返回重试!');
                }
            }
        }
        $info = AdminRole::find()->where(['id'=>$role_id])->asArray()->one();
        return $this->render('edit',['info'=>$info]);
    }

    /**
     * Desc: 給职位分配权限
     * Created by pysh
     * Date: 2020/2/2
     * Time: 14:04
     */
    public function actionPermission(){
        $role_id = $this->request->get('role_id');
        $model = AdminRole::findOne(['id'=>$role_id]);
        if($this->request->isPost){
            $flag_error = true;
            if (!$this->now_auth) {
                $flag_error = false;
                $this->withErrors('权限不足!');
            }

            $data = $this->request->bodyParams;
            $auths = $data['permissions'];
            if(!empty($auths)){
                $permissions = implode(',',$auths);
            }else{
                $permissions = '';
            }
            $model->permissions = $permissions;

            if ($flag_error) {
                if($model && $model->update()){
                    AddLogController::addSysLog(AddLogController::permission,'职位分配权限,职位为:'.$model->role);
                    return $this->withSuccess('修改成功!')->redirect(route('admin.admin-role.index'));
                } else {
                    return $this->withErrors('保存失败，请返回重试!');
                }
            }
        }

        $list = AdminPermissions::getList(1);
        $list = list_to_tree($list);
        $list = setOwn($list,explode(',',$model->permissions));
        return $this->render('permission',['info'=>$model,'list'=>$list]);
        
    }

}
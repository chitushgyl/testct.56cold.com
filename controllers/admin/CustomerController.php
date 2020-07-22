<?php
/**客户管理
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 17:00
 */

namespace app\controllers\admin;
use app\models\Customer,
    Yii;

class CustomerController extends AdminBaseController
{
    /**
     * Desc: 客户列表
     * Created by pysh
     * Date: 2020/2/2
     * Time: 17:42
     */
    public function actionIndex(){
        if($this->request->isAjax){
            $keyword = $this->request->get('keyword');
            $list = Customer::find()
                ->alias('c')
                ->leftJoin('ct_account a','c.aid=a.id');
            if($keyword){
                $list->andWhere(['like','c.all_name',$keyword])
                    ->orWhere(['like','c.contact_name',$keyword])
                    ->orWhere(['like','c.ellipsis_name',$keyword]);
            }

            $count = $list->count();
            $list = $list->select(['c.*','a.company'])
                ->offset(($this->request->get('page',1) - 1) * $this->request->get('limit',10))
                ->orderBy(['c.id'=>SORT_DESC])
                ->limit($this->request->get('limit',10))
                ->asArray()->all();
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
     * Desc: 编辑客户
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:48
     */
    public function actionEdit(){
        $id = $_GET['id'];
        $model = Customer::find()->where(['id'=>$id])->one();
        if($this->request->isPost){
            $flag_error = true;
            if (!$this->now_auth) {
                $flag_error = false;
                $this->withErrors('权限不足!');
            }

            $data = $this->request->bodyParams;

            $contact_name = $data['contact_name'];
            $ellipsis_name = $data['ellipsis_name'];
            $all_name = $data['all_name'];
            $province = $data['province'] ? $data['province'] : 0;
            $city = isset($data['city']) ? $data['city'] : 0;
            $area = isset($data['area']) ? $data['area'] : 0;
            $address = $data['address'];
            $contact_phone = $data['contact_phone'];
            $business = $data['business'];
            
            if($flag_error && !$contact_name){
                $flag_error = false;
                $this->withErrors('联系人不能空!');
            }            

            if($flag_error && !$ellipsis_name){
                $flag_error = false;
                $this->withErrors('公司简称不能空!');
            }            

            if($flag_error && !$all_name){
                $flag_error = false;
                $this->withErrors('公司全称不能空!');
            }

            if($flag_error && !$address){
                $flag_error = false;
                $this->withErrors('公司地址不能空!');
            }            
            if($flag_error && !$contact_phone){
                $flag_error = false;
                $this->withErrors('请添加职位!');
            }
            $old_img = $model->business;

            if ($business) {
                $img = base64_image_content($business,'customer');
                if ($flag_error && $img) {
                    $model->business = $img;
                } else {
                    $flag_error = false;
                    $this->withErrors('图片上传失败!');
                }
            }

            $model->contact_name = $contact_name;
            $model->ellipsis_name = $ellipsis_name;
            $model->all_name = $all_name;
            $model->province = $province;
            $model->city = $city;
            $model->area = $area;
            $model->address = $address;
            $model->contact_phone = $contact_phone;
            
            if ($flag_error) {
                $res = $model->save();
                if($res){
                    if ($old_img && isset($img) && $img) {
                        @unlink(ltrim($old_img,'/'));
                    }
                    AddLogController::addSysLog(AddLogController::customer,'编辑客户,客户为:'.$contact_name);
                    return $this->withSuccess('编辑成功!')->redirect(route('admin.customer.index'));
                } else {
                    $this->withErrors('编辑失败，请重试!');
                }
            }
        }
        return $this->render('add',['info'=>$model]);
    }    

    /**
     * Desc: 新增客户
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:48
     */
    public function actionAdd(){
        $model = new Customer();
        if($this->request->isPost){
            $flag_error = true;
            if (!$this->now_auth) {
                $flag_error = false;
                $this->withErrors('权限不足!');
            }

            $data = $this->request->bodyParams;

            $contact_name = $data['contact_name'];
            $ellipsis_name = $data['ellipsis_name'];
            $all_name = $data['all_name'];
            $province = $data['province'] ? $data['province'] : 0;
            $city = $data['city'] ? $data['city'] : 0;
            $area = $data['area'] ? $data['area'] : 0;
            $address = $data['address'];
            $contact_phone = $data['contact_phone'];
            $business = $data['business'];
            
            if($flag_error && !$contact_name){
                $flag_error = false;
                $this->withErrors('联系人不能空!');
            }            

            if($flag_error && !$ellipsis_name){
                $flag_error = false;
                $this->withErrors('公司简称不能空!');
            }            

            if($flag_error && !$all_name){
                $flag_error = false;
                $this->withErrors('公司全称不能空!');
            }

            if($flag_error && !$address){
                $flag_error = false;
                $this->withErrors('公司地址不能空!');
            }            
            if($flag_error && !$contact_phone){
                $flag_error = false;
                $this->withErrors('请添加职位!');
            }

            if ($business) {
                $img = base64_image_content($business,'customer');
                if ($flag_error && $img) {
                    $model->business = $img;
                } else {
                    $flag_error = false;
                    $this->withErrors('图片上传失败!');
                }
            }

            $model->contact_name = $contact_name;
            $model->ellipsis_name = $ellipsis_name;
            $model->all_name = $all_name;
            $model->province = $province;
            $model->city = $city;
            $model->area = $area;
            $model->address = $address;
            $model->contact_phone = $contact_phone;
            
            if ($flag_error) {
                $model->aid = $this->root_id;
                $model->addtime = (string)time();
                $res = $model->save();
                if($res){
                    AddLogController::addSysLog(AddLogController::customer,'新增客户,客户为:'.$contact_name);
                    return $this->withSuccess('新增成功!')->redirect(route('admin.customer.index'));
                } else {
                    $this->withErrors('新增失败，请重试!');
                }
            }
        }
        return $this->render('add',['info'=>$model]);
    }

    /**
     * Desc: 删除用户
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:48
     */
    public function actionDel(){
        if($this->request->isAjax){
            if (!$this->now_auth) {
                return $this->resultInfo(['retCode'=>1001,'retMsg'=>'权限不足!']);
            }
            $id = $this->request->post('id');
            $model = Customer::findOne(['id'=>$id]);
            $contact_name = $model->contact_name;
            $business = $model->business;
            if($model && $model->delete()){
                AddLogController::addSysLog(AddLogController::customer,'刪除客户,客户为:'.$contact_name);
                if ($business) {
                    @unlink(ltrim($business,'/'));
                }
                return $this->resultInfo(['retCode'=>1000,'retMsg'=>'删除成功!']);
            }else{
                return $this->resultInfo(['retCode'=>1001,'retMsg'=>'删除失败!']);
            }
        }else{
            return $this->resultInfo(['retCode'=>'00000','retMsg'=>'错误!']);
        }
    }

}
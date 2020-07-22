<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 17:15
 */
// echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>

<input name="_csrf" type="hidden" id="_csrf" value="<?php echo Yii::$app->request->csrfToken ?>">
<div class="layui-form-item">
    <label for="" class="layui-form-label"><span class="required_red">*</span>联系人</label>
    <div class="layui-input-block">
        <input type="text" name="contact_name" value="<?php echo $info->contact_name;?>" lay-verify="contact_name" placeholder="请输入联系人" class="layui-input">
    </div>
</div>
<div class="layui-form-item">
    <label for="" class="layui-form-label"><span class="required_red">*</span>公司简称</label>
    <div class="layui-input-block">
        <input type="text" name="ellipsis_name" value="<?php echo $info->ellipsis_name;?>"  lay-verify="ellipsis_name" placeholder="请输入公司简称" class="layui-input" >
    </div>
</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label"><span class="required_red">*</span>公司全称</label>
    <div class="layui-input-block">
        <input type="text" name="all_name" value="<?php echo $info->all_name;?>"  lay-verify="required" placeholder="请输入公司全称" class="layui-input" >
    </div>
</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label"><span class="required_red">*</span>公司地址</label>
    <div class="layui-input-inline address-all" id="pro">
        <select name="province" lay-verify="" class="pro" lay-filter="pro">
            
        </select>
    </div>    

    <div class="layui-input-inline address-all" id="city">
        <select name="city" lay-verify="" class="city" lay-filter="city">
            
        </select>
    </div>    

    <div class="layui-input-inline address-all" id="area">
        <select name="area" lay-verify="" class="area" lay-filter="area">

        </select>
    </div>    

    <div class="layui-input-inline address-all">
        <input type="text" name="address" value="<?php echo $info->address;?>"  lay-verify="address" placeholder="请输入公司地址" class="layui-input address" >
    </div>

</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label"><span class="required_red">*</span>联系方式</label>
    <div class="layui-input-block">
        <input type="number" name="contact_phone" value="<?php echo $info->contact_phone;?>"  placeholder="请输入联系方式" class="layui-input" lay-verify="contact_phone">
    </div>
</div>

<div class="layui-form-item">
    <label class="layui-form-label" >营业执照</label>
    <div>
        <img class="layui-upload-img"  id="business" src="<?php if($info->business){echo $info->business;}else{echo '/image/plus.jpg';}?>" height="125" width="104" tabindex="1">
        <input type="text" name="business" value="" id="business_url" style="display: none;">
    </div>
</div>

<div class="layui-form-item">
    <div class="layui-input-block">
        <button type="submit" class="layui-btn" lay-submit="" lay-filter="*">确 认</button>
        <a class="layui-btn" href="<?php echo route('admin.customer.index');?>" >返 回</a>
    </div>
</div>
<script type="text/javascript" src="/js/address.js"></script>

<script type="text/javascript">

        layui.use(['layer','form'],function () {
            var layer = layui.layer;
            var form = layui.form;
            var id = '<?php echo $info->id;?>';
            if (id) {
                var pro = '<?php echo $info->province;?>';
                var city = '<?php echo $info->city;?>';
                var area = '<?php echo $info->area;?>';

                $('.pro option[value="'+pro+'"]').attr('selected',true);
                getData(pro,$('.city'),'city',form,city);
                $('.city option[value="'+city+'"]').attr('selected',true);
                getData(city,$('.area'),'area',form,area);
                $('.area option[value="'+area+'"]').attr('selected',true);
               
                form.render('select');
            }
        });

</script>
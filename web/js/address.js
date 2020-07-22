
	var element = ''; //
    /**
     * @description:  定义函数，获取数据库的省份数据  
     * @param index {string} 父级地址库id
     * @param element {string} 数据展示的class
     */
    function getData(index,ele,class_,form,selected_id){
        element = ele;
        // 每次往select节点写入option前先将原有的option节点清掉
        ele.html('');
        if (class_ == 'city'){
            $('.area').html('');
        }

        if (index == 0 && class_ == 'city') {
            $('.city').html('');
            $('.area').html('');
            return false;
        }        

        if (index == 0 && class_ == 'area') {
            $('.area').html('');
            return false;
        }
        if (index == 0) {
            var address = localStorage.getItem("pro_list");
            if (address) {
                updataEle(JSON.parse(address),ele,class_,form,selected_id);
                return false;
            }
        }


        // 定义url  
        var url = "/common/get-address";
        // 定义参数  
        var data={id:index};  
        // 调用ajax 进行交互  
        $.ajax({
        	url: url,
        	type: 'POST',
        	dataType: 'json',
        	data: data,
        })
        .done(function(response) {
            if (index == 0) {
                localStorage.setItem("pro_list",JSON.stringify(response));
            }
        	updataEle(response,ele,class_,form,selected_id);
        })
        .fail(function() {
        	console.log("error");
        })
        .always(function() {
        	console.log("complete");
        });
    }
    /**
     * @description:  定义函数，更新select  
     * @param xhr {string} 返回对应的省市区数据
     */
    function updataEle(xhr,element,class_,form,selected_id){
        //将服务器端返回的jason格式的字符串转化为对象  
        var obj = xhr; 
        obj.splice(0,0,{"id":0,"name":"--不限--"});
        var options = '';
        //在此将jason数组对象的下表为id的作为option的value值，将下表为name的值作为文本节点追加给  
        for(var i=0;i<obj.length;i++){
            if (selected_id == obj[i].id) {
                options += '<option value="'+obj[i].id+'" selected >'+obj[i].name+'</option>';
            }  else {
                options += '<option value="'+obj[i].id+'"  >'+obj[i].name+'</option>';
            }
            
        }  
        element.html(options);
        if (class_ == 'city' || class_ == 'area') {
            form.render('select');
        }
    }

    layui.use(['layer','table','form','upload'],function () {
        var layer = layui.layer;
        var form = layui.form;

        form.on('select(pro)', function(data){
            var id = data.value;
            var obj = $('.city');
            getData(id,obj,'city',form,selected_id);
            form.render('select');
        }); 

        form.on('select(city)', function(data){
            var id = data.value;
            var obj = $('.area');
            getData(id,obj,'area',form,selected_id);
            form.render('select');
        }); 
    });
    // 页面加载调用初始化省
    var selected_id = 0;
    getData(0,$('.pro'),'pro',layui.form,selected_id);

(function(e){
    number = function (obj){
        obj.value = obj.value.replace(/[^\d]/g,""); // 清除"数字以外的字符
    };

    checkMobile = function(obj){
        if((/^[1][3,4,5,6,7,8,9][0-9]{9}$/.test(obj))){
            return 1;
        } else {
            return '手机号格式错误！';
        }
    };    

    checkEmail = function(obj){
        if((/^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$/.test(obj))){
            return 1;
        } else {
            return '邮箱格式错误！';
        }
    };    

    viewData = function(obj){
        var length = obj.length;
        var node = '';
        for(var i=0;i<length;i++) {
            node += '<tr>';
                node += '<td>'+obj[i].key+'</td>';
                node += '<td>'+obj[i].value+'</td>';
            node += '</tr>';
        }
        return node;
    };

    set_number = function(that){
        var value = $(that).val();
        if (value.length>0) {
            var value = Math.ceil(value);
            $(that).val('').val(value);
        }
    };

    numberTwoDecimals = function (obj){
        obj.value = obj.value.replace(/[^\d.]/g,""); //清除"数字"和"."以外的字符
        obj.value = obj.value.replace(/^\./g,""); //验证第一个字符是数字
        obj.value = obj.value.replace(/\.{2,}/g,"."); //只保留第一个, 清除多余的
        obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
        obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3'); //只能输入两个小数
    };    

    numberOneDecimals = function (obj){
        obj.value = obj.value.replace(/[^\d.]/g,""); //清除"数字"和"."以外的字符
        obj.value = obj.value.replace(/^\./g,""); //验证第一个字符是数字
        obj.value = obj.value.replace(/\.{1,}/g,"."); //只保留第一个, 清除多余的
        obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
        obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d).*$/,'$1$2.$3'); //只能输入两个小数
    };

    date = function(format,time){
        var t=new Date((time-0)*1000);
        var Y=t.getFullYear();  //取得4位数的年份
        var m=t.getMonth()+1;  //取得日期中的月份，其中0表示1月，11表示12月
        var d=t.getDate();      //返回日期月份中的天数（1到31）
        var h=t.getHours();     //返回日期中的小时数（0到23）
        var i=t.getMinutes(); //返回日期中的分钟数（0到59）
        var s=t.getSeconds(); //返回日期中的秒数（0到59）
        return format.replace(/Y/g,Y).replace(/m/g,m).replace(/d/g,d).replace(/h/g,h).replace(/i/g,i).replace(/s/g,s); 

    };

})(window);



<?php view::layout('layout')?>

<?php view::begin('content');?>
	
<div class="mdui-container-fluid">

<div class="nexmoe-item" style="padding: 100px!important;">
	<div class="mdui-typo-display-3-opacity" style="text-align:center;">免费Onedrive图床</div>
	<div class="mdui-row-xs-3">
	  <div class="mdui-col"></div>

	</div>

    <div class="mdui-container-fluid">
        <div class="nexmoe-item">

            <img class="mdui-img-fluid mdui-center">

            <div class="mdui-textfield">
                <label class="mdui-textfield-label">下载地址</label>
                <input id="download" class="mdui-textfield-input" type="text"/>
            </div>
            <div class="mdui-textfield">
                <label class="mdui-textfield-label">HTML 引用地址</label>
                <input id="getHtml" class="mdui-textfield-input" type="text"/>
            </div>
            <div class="mdui-textfield">
                <label class="mdui-textfield-label">Markdown 引用地址</label>
                <input id="getMD" class="mdui-textfield-input" type="text">
            </div>
            <button type="button" class="layui-btn" id="uploadImg">
                <i class="layui-icon" style="text-align:center">&#xe67c;</i>上传图片
            </button>
        </div>

    </div>

	
</div>

</div>
    <script src="../assets/layui/layui.js"></script>
    <script>
        layui.use(['upload','jquery'], function(){
            var upload = layui.upload;
            var $=layui.jquery;
            var layer=layui.layer;

            //执行实例
            var uploadInst = upload.render({
                elem: '#uploadImg' //绑定元素
                ,url: '/images/upload' //上传接口
                ,done: function(res){
                    //上传完毕回调
                    layer.alert('上传成功，正在生成图片信息',{icon: 1});
                    console.log(res);
                    window.location.href=res.data;
                }
                ,error: function(){
                    //请求异常回调
                }
            });
        });
    </script>
<?php view::end('content');?>
<script src="https://cdn.staticfile.org/jquery/1.10.2/jquery.min.js"></script>
<script src="/js/base64.js"></script>
<div class="api-default-index">
    <h1><?= $this->context->action->uniqueId ?></h1>
    <p>
        This is the view content for action "<?= $this->context->action->id ?>".
        The action belongs to the controller "<?= get_class($this->context) ?>"
        in the "<?= $this->context->module->id ?>" module.
    </p>
    <p>
        You may customize this page by editing the following file:<br>
        <code><?= __FILE__ ?></code>
    </p>
    <ul>
        <?php foreach($list as $k => $v){ ;?>
        <li><?= $v['id'];?> --- <?php echo $v['username'];?></li>
        <?php } ;?>
    </ul>
</div>
<script>
var data = '<?php echo $data;?>';
var obj = JSON.parse(BASE64.decode(data));
console.log(data);
console.log(obj);
console.log(obj.code);
</script>

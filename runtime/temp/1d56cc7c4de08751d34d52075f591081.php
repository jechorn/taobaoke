<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:56:"D:\wamp\www\taobaoke/install/index\view\index\index.html";i:1499770693;s:51:"D:\wamp\www\taobaoke/install/index\view\header.html";i:1499770424;s:51:"D:\wamp\www\taobaoke/install/index\view\footer.html";i:1354772440;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo lang('page_title'); ?></title>
    <link rel="stylesheet" type="text/css" href="__STATIC__/install/css/install.css" />
    <script type="text/javascript" src="__STATIC__/home/vender/js/jquery-1.11.1.min.js"></script>
</head>

<body>
<div class="header">
    <div class="logo fl"></div>
    <div class="fr">
        <h2 class="fr"><?php echo lang('wellcom_user_ftxia'); ?></h2>
    </div>
</div>
<div class="content">
    <div class="step fl">
        <h3></h3>
        <ul>
            <li <?php if($step_curr == 'eula'): ?>class="curr"<?php endif; ?>><span>1</span><h4><?php echo lang('step_eula'); ?></h4></li>
            <li <?php if($step_curr == 'check'): ?>class="curr"<?php endif; ?>><span>2</span><h4><?php echo lang('step_check'); ?></h4></li>
            <li <?php if($step_curr == 'setconf'): ?>class="curr"<?php endif; ?>><span>3</span><h4><?php echo lang('step_setconf'); ?></h4></li>
            <li <?php if($step_curr == 'install'): ?>class="curr"<?php endif; ?>><span>4</span><h4><?php echo lang('step_install'); ?></h4></li>
            <li <?php if($step_curr == 'finish'): ?>class="curr"<?php endif; ?>><span>5</span><h4><?php echo lang('step_finish'); ?></h4></li>
        </ul>
    </div>
<div class="c_main fr">
<form action="<?php echo url('index'); ?>" method="post">
    <div class="c_main_title"><?php echo lang('step_eula_desc'); ?></div>
    <div class="c_main_body eula">
        <?php if(isset($error_msg)): ?>
        <?php echo $error_msg; endif; ?>
        <?php echo $eula_html; ?>
    </div>
    <div class="act">
        <div class="accept"><label><input type="checkbox" name="accept" class="input_checkbox" value="1" />&nbsp;&nbsp;<?php echo \think\Lang::get('i_accept'); ?></label></div>
        <div class="btn"><input type="submit" value="<?php echo lang('next'); ?>" class="btn_next" /></div>
    </div>
</form>
</div>
</div>
</body>
</html>
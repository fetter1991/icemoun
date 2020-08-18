<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>后台登录</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="width=device-width, initial-scale=1" name="viewport" />
	<meta content="" name="description" />
	<meta content="" name="author" />
	<link rel="icon" sizes="any" mask="" href="/Public/metronic/apps/img/logo.png">
	<link href="/Public/metronic/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
	<link href="/Public/metronic/global/plugins/simple-line-icons/simple-line-icons.min.css" rel="stylesheet" type="text/css" />
	<link href="/Public/metronic/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="/Public/metronic/global/plugins/uniform/css/uniform.default.css" rel="stylesheet" type="text/css" />
	<link href="/Public/metronic/global/plugins/bootstrap-switch/css/bootstrap-switch.min.css" rel="stylesheet" type="text/css" />
	<link href="/Public/metronic/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
	<link href="/Public/metronic/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="/Public/metronic/global/css/components.min.css" rel="stylesheet" id="style_components" type="text/css" />
	<link href="/Public/metronic/global/css/plugins.min.css" rel="stylesheet" type="text/css" />
	<link href="/Public/metronic/pages/css/login.min.css" rel="stylesheet" type="text/css" />

<body class=" login">
<div class="menu-toggler sidebar-toggler"></div>
<div class="content">
	<form class="login-form" action="<?php echo U('Public/login');?>" method="post">
		<h3 class="form-title font-green">总后台登录</h3>
		<div class="alert alert-danger display-hide">
			<button class="close" data-close="alert"></button>
			<span> 请输入完整信息 </span>
		</div>
		<div class="form-group">
			<label class="control-label visible-ie8 visible-ie9">用户名</label>
			<input class="form-control form-control-solid placeholder-no-fix" type="text" required autocomplete="off" name="account" placeholder="输入帐号" /> </div>
		<div class="form-group">
			<label class="control-label visible-ie8 visible-ie9">密码</label>
			<input class="form-control form-control-solid placeholder-no-fix" type="password" required autocomplete="off" name="password" placeholder="输入密码"  /> </div>
		<div class="form-group">
			<label class="control-label visible-ie8 visible-ie9">验证码</label>
			<div class="input-group">
				<input class="form-control form-control-solid placeholder-no-fix" type="text" required autocomplete="off"  name="verify" placeholder="输入验证码" />
				<span class="input-group-btn">
					<img style="cursor:pointer;" src="<?php echo U('public/verify1');?>"  title="看不清楚？点击刷新" onclick="this.src = '<?php echo U('public/verify1');?>?'+new Date().getTime()">
				</span>
			</div>
		</div>
		<div class="form-actions">
			<button type="submit" class="btn green uppercase">登录</button>
			<label class="rememberme check">
				<input type="checkbox" name="auto" value="1" checked />自动登录 </label>
		</div>
	</form>
</div>
<!--[if lt IE 9]>
<script src="/Public/metronic/global/plugins/respond.min.js"></script>
<script src="/Public/metronic/global/plugins/excanvas.min.js"></script>
<![endif]-->
<script src="/Public/metronic/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="/Public/metronic/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="/Public/metronic/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
<script src="/Public/metronic/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
<script src="/Public/metronic/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
<script src="/Public/metronic/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
<script src="/Public/metronic/global/plugins/jquery-validation/js/jquery.validate.min.js" type="text/javascript"></script>
<script src="/Public/metronic/global/plugins/jquery-validation/js/additional-methods.min.js" type="text/javascript"></script>
<script src="/Public/metronic/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
<script src="/Public/metronic/global/scripts/app.min.js" type="text/javascript"></script>
<script src="/Public/metronic/pages/scripts/login.min.js" type="text/javascript"></script>
</body>

</html>
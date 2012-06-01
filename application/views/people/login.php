<form action="<?php echo Route::url('login') ?>" method="post" accept-charset="utf-8">
	<p>
		<label for="openid_identifier">Enter your OpenID:</label>
		<input type="text" id="openid_identifier" name="openid_identifier" />
	</p>
	<!--p>
		<label for="email_address">Email address:</label>
		<input type="text" name="email_address" id="focus-me"
			   value="<?php if (isset($_POST['email_address'])) echo $_POST['email_address'] ?>" />
	</p>
	<p>
		<label for="password">Password:</label>
		<input type="password" name="password" id="password" />
		<a href="<?php echo URL::site('user/remind') ?>">Forgotten your password?</a>
	</p-->
	<p>
		<input type="submit" value="Log in &rArr;" />
	</p>
</form>

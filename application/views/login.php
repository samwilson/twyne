<form action="<?php echo Route::url('login') ?>" method="post" accept-charset="utf-8">
	<p>
		<label for="openid_identifier">Enter your OpenID:</label>
		<input type="text" id="openid_identifier" name="openid_identifier" size="50" />
		<input type="submit" value="Log In" />
	</p>
</form>

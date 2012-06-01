<form action="<?php echo Route::url('person', array('id'=>$person->id)) ?>" method="post">
	<p>
		<?php echo Form::label('name', 'Name:').Form::input('name', $person->name) ?>
	</p>
	<p>
		<?php echo Form::label('email_address', 'Email address:').Form::input('email_address', $person->email_address) ?>
	</p>
	<p>
		<?php echo Form::label('auth_level', 'Auth Level:').Form::input('auth_level', $person->auth_level) ?>
	</p>
	<p>
		<?php echo Form::label('notes', 'Notes:').Form::textarea('notes', $person->notes) ?>
	</p>
	<p class="submit">
		<?php //if ($person->loaded()) echo Form::hidden('id',$person->id) ?>
		<?php echo HTML::anchor('people', 'Return to index') ?>
		<?php echo Form::submit('save', 'Save') ?>
	</p>
</form>

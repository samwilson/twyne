
<?php echo Form::open() ?>

<h2>
	<?php echo Form::input('title', $entry->title, array('size'=>50)) ?>
</h2>
<p>
	<?php
	echo Form::label('date_and_time', 'Date and time: ')
	.Form::input('date_and_time', $entry->date_and_time)
	?>
	<span class="auth_level_id"><?php
	echo Form::label('auth_level_id', 'Auth level: ')
	.Form::input('auth_level_id', $entry->auth_level->id, array('size'=>5))
	?>
	</span>
</p>
<p class="entry_text">
	<?php echo Form::textarea('entry_text', $entry->entry_text, array('rows'=>24, 'cols'=>80, 'class'=>'mono')) ?>
</p>

<script type="text/javascript">
	var tags = ["<?php echo ORM::factory('Tags')->get_list(TRUE) ?>"]
</script>
<p class="tags">
	<label for="tags">Tags:</label>
	<input id="tags" name="tags" size="50" value="<?php echo htmlentities($entry->tags->get_list(FALSE)) ?>" />
</p>

<p class="submit">
	<a href="http://docutils.sourceforge.net/docs/user/rst/quickref.html"
	   target="_blank"
	   title="ReStructuredText documentation at Sourceforce.net (opens in a new tab)">
		ReST Documentation
	</a>
	<input type='submit' name='save' value='Save' />
	<?php if ($entry->loaded()) echo HTML::anchor('/journal/view/'.$entry->id, '[Cancel]') ?>
</p>

<?php echo Form::close() ?>

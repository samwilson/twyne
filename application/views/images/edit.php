<a name="form"></a>
<div class="image">

	<?php echo HTML::anchor('images/edit/'.$accession_prev->id.'#form', '&larr; #'.$accession_prev->id) ?>
	| Accessional |
	<?php echo HTML::anchor('images/edit/'.$accession_next->id.'#form', '#'.$accession_next->id.' &rarr;') ?><br />

	<?php echo HTML::anchor('images/edit/'.$chronology_prev->id.'#form', '&larr; '.$chronology_prev->date_and_time) ?>
	| Chronological |
	<?php echo HTML::anchor('images/edit/'.$chronology_next->id.'#form', $chronology_next->date_and_time.' &rarr;') ?><br />

	<a href="<?php echo URL::site('images/view/'.$image->id) ?>">
		<img src='<?php echo url::site("images/render/$image->id/view") ?>' style='max-width:100%' />
	</a><br />
	Rotate
	<?php echo html::anchor("images/rotate/$image->id/90", "90&deg;") ?>,
	<?php echo html::anchor("images/rotate/$image->id/180", "180&deg;") ?>, or
	<?php echo html::anchor("images/rotate/$image->id/270", "270&deg;") ?> clockwise.
	&nbsp;
	<?php echo html::anchor("images/delete/$image->id", "Delete") ?>.
</div>
<form action='<?php echo URL::site("images/save") ?>' method='post'>
	<p class='hide'>
		<input type='hidden' name='save_image' />
		<input type='hidden' name='id' value='<?php echo $image->id ?>' />
	</p>
	<p>
		<?php echo Form::label('date_and_time', 'Date and time:') ?>
		<?php echo Form::input('date_and_time', $image->date_and_time, array('size'=>12)) ?>
	</p>
    <p>
        <label for="author_id">Author:</label>
		<?php echo Form::select('author_id', $people, $image->author_id, array('id'=>'author_id')) ?>
    </p>
	<p>
		<?php echo Form::label('auth_level_id', 'Auth Level:') ?>
		<?php echo Form::input('auth_level_id', $image->auth_level_id, array('size'=>2)) ?>
    </p>
	<p>
		<?php echo Form::textarea('caption', $image->caption) ?>
	</p>
	<script type="text/javascript">
	var tags = ["<?php echo ORM::factory('Tags')->get_list(TRUE) ?>"]
	</script>
	<p>
		<label for="tags">Tags:</label>
		<input id="tags" type="text" name="tags" value="<?php echo htmlspecialchars($image->tags->get_list(FALSE)) ?>" />
	</p>
    <p>
        <label for="licence_id">Licence:</label>
		<?php echo Form::select('licence_id', $licences, $image->licence_id, array('id'=>'licence_id')) ?>
    </p>
	<p>
		<strong>Save</strong> and:
		<input type='submit' name='save_and_edit' value='keep editing' />
		<input type='submit' name='save_and_process' value='process next image' />
		<input type='submit' name='save_and_next' value='go to next image' />
		or
		<input type='submit' name='save_and_view' value='view' />
	</p>
</form>

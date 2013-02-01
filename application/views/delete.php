
<?php if ($image->loaded()): ?>

<div class="error message" style="text-align:center">

	<h2>Are you sure you want to delete image #<?php echo $image->id ?>?!</h2>

	<p>
		<img src='<?php echo Route::url('render', array('id'=>$image->id)) ?>' alt='Image to be deleted' />
	</p>

	<p>
		<?php echo HTML::anchor($image->id.'/delete?confirm=yes', '[Yes]') ?>
		<?php echo HTML::anchor($image->id.'/edit', '[No]') ?>
	</p>

</div>

<?php else: ?>

<p class="notice message">Image not found.</p>

<?php endif ?>
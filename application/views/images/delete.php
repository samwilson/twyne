
<?php if ($image->loaded()): ?>

<div class="error message" style="text-align:center">

	<h2>Are you sure you want to delete image #<?php echo $image->id ?>?!</h2>

	<p>
		<?php echo HTML::image('images/render/'.$image->id.'/view') ?>
	</p>

	<p>
		<?php echo HTML::anchor('images/delete/'.$image->id.'?confirm=yes', '[Yes]') ?>
		<?php echo HTML::anchor('images/edit/'.$image->id, '[No]') ?>
	</p>

</div>

<?php else: ?>

<p class="notice message">Image not found.</p>

<?php endif ?>
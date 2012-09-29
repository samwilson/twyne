
<h2>Upload a new photo</h2>
<form action="<?php echo Route::url('upload') ?>" method="post" enctype="multipart/form-data">
	<p><input type="file" name="uploaded_file" size="80" /></p>
	<p>
		<input type="submit" name="upload" value="Upload" />
		<input type="hidden" value="<?php ini_get('upload_max_filesize') ?>" name="MAX_FILE_SIZE">
	</p>
</form>


<?php if (count($pending_files) > 0) { ?>
<h2>Process uploaded photos</h2>
<ol>
	<?php foreach ($pending_files as $file) { ?>
	<li>
		<?php $url = Route::url('upload', array('filename'=>$file)) ?>
		<a href="<?php echo $url ?>" title="Add this file to the database">
			<?php echo $file ?>
		</a>
	</li>
	<?php } ?>
</ol>
<?php } ?>
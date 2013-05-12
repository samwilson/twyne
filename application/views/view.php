<p>
	<a href="<?php echo Route::url('render', array('id'=>$image->id, 'size'=>'full')) ?>" title="View full-sized photo">
		<img src="<?php echo Route::url('render', array('id'=>$image->id)) ?>" alt="A photo, encaptioned: <?php echo $image->caption ?>" />
	</a>
</p>
<div class="caption">
	<?php echo $image->caption ?>
</div>

<div class="metadata">

	<p>
		&middot; <?php echo $image->date_and_time ?>
		<!-- &middot;
		<a href="<?php echo Route::url('view',array('action'=>'view', 'id'=>$image->id, 'format'=>'pdf')) ?>"
		   title="Get a one-page PDF of this photo and its metadata">
			PDF
		</a>-->
		&middot;
	</p>

	<?php if (count($tags = $image->tags->order_by('name')->find_all()) > 0): ?>
	<p class="tags">&middot;
		<?php foreach ($tags as $tag): ?>
		<a href="<?php echo Route::url('tags',array('tag_ids'=>'+'.$tag->id)) ?>" rel="tag">
			<?php echo $tag->name ?>
		</a> &middot;
		<?php endforeach ?>
	</p>
	<?php endif ?>

	<?php if($user->auth_level_id > 1): ?>
	<p>
		<?php echo " &middot; Auth level $image->auth_level_id." ?>
		<?php if ($user->auth_level_id >= 10): ?>
		&middot; <a href="<?php echo Route::url('image',array('action'=>'edit', 'id'=>$image->id)) ?>#form">Edit</a>
		&middot; <a href="<?php echo Route::url('image',array('action'=>'delete', 'id'=>$image->id)) ?>#form">Delete</a>
		<?php endif ?>
		&middot;
	</p>
	<?php endif ?>

	<p class="licence">
		&middot; By
		<?php echo $image->author->name ?>
		&middot; <?php echo HTML::anchor($image->licence->link_url, $image->licence->name) ?>.
		&middot;
	</p>

</div>

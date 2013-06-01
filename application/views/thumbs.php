
<div class="thumbslist">

	<?php foreach ($photos as $photo): ?>

	<div class="photo">
		<a name="<?php echo $photo->id ?>"></a>
		<p class="img">
			<a href="<?php echo Route::url('view', array('id'=>$photo->id), true) ?>" title="<?php echo htmlentities($photo->caption, ENT_QUOTES) ?>">
				<img src="<?php echo Route::url('render', array('id'=>$photo->id, 'size'=>'thumb')) ?>" title="" alt="" />
			</a>
		</p>
		
		<p class="metadata">
			&middot;
			<?php echo date('D j M Y', strtotime($photo->date_and_time)) ?>
			<?php if($user->auth_level->id > 1) echo ' &middot; <dfn title="Auth Level">'.$photo->auth_level->name.'</dfn>' ?>
			<?php if ($user->auth_level_id >= 10): ?>
			&middot; <a href="<?php echo Route::url('image',array('action'=>'edit', 'id'=>$photo->id)) ?>#form">Edit</a>
			&middot; <a href="<?php echo Route::url('image',array('action'=>'delete', 'id'=>$photo->id)) ?>#form">Delete</a>
			<?php endif ?>
			&middot;
			<?php if (count($tags = $photo->tags->order_by('name')->find_all()) > 0): ?>
				Tags:
				<?php $tag_links = array(); foreach ($tags as $tag): ?>
					<?php $tag_links[] = '<a href="'.$tag->url("+$tag->id").'" rel="tag">'.$tag->name.'</a>' ?>
				<?php endforeach; echo join(', ', $tag_links); ?>
				&middot;
			<?php endif ?>
		</p>
		<div class="clear"></div>
	</div>

	<?php endforeach ?>

	<?php if (count($photos)<1): ?>
	<p class="notice message">Nothing to display for <?php echo $title ?>.</p>
	<?php endif ?>

</div>

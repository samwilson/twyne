<p><?php echo HTML::image('images/render/'.$image->id.'/view', array('alt'=>$image->date_and_time)) ?></p>

<div class="caption">
	<?php echo Text::typeset($image->caption) ?>
</div>

<div class="metadata">

	<p>
        &middot; <?php echo $image->date_and_time ?>
        &middot;
    </p>

	<?php if (count($tags = $image->tags->order_by('title')->find_all()) > 0): ?>
	<p class="tags">&middot;
			<?php foreach ($tags as $tag): ?>
				<?php echo HTML::anchor('tag/'.urlencode($tag->title), $tag->title, array('rel'=>'tag')) ?> &middot;
			<?php endforeach ?>
	</p>
	<?php endif ?>

	<p>
		<?php if($user->auth_level > 0) echo " &middot; Auth level $image->auth_level." ?>
		<?php
		if ($user->auth_level >= 10)
			echo ' &middot; '.HTML::anchor('images/edit/'.$image->id.'#form', 'Edit')
				.' &middot; '.HTML::anchor('images/delete/'.$image->id, 'Delete')
		?>
		&middot;
	</p>

    <p class="licence">
        &middot; By
        <?php echo $image->author->name ?>,
        <?php echo HTML::anchor($image->licence->link_url, $image->licence->name) ?>.
        &middot;
    </p>

</div>

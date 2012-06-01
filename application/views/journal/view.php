

<p class="date_and_time"><?php echo date('g:iA l, F j<\s\up>S</\s\up> Y', strtotime($entry->date_and_time)) ?></p>

<div class="prose">
	<?php echo Text::typeset($entry->entry_text) ?>
</div>

<div class="metadata">

	<?php if (count($tags = $entry->tags->order_by('name')->find_all()) > 0): ?>
	<p class="tags">&middot;
			<?php foreach ($tags as $tag): ?>
				<?php echo HTML::anchor('tag/'.urlencode($tag->name), $tag->name, array('rel'=>'tag')) ?> &middot;
			<?php endforeach ?>
	</p>
	<?php endif ?>

	<p>&middot; <?php
	$uri = 'journal/view/'.$entry->id;
	$title =  'Journal Entry #'.$entry->id;
	$attrs = array('title'=>'Canonical URI for this journal entry.');
	echo HTML::anchor($uri,$title, $attrs);
	?> &middot;</p>

	<?php if ($user->auth_level->id > 0): ?>
	<p>
		&middot;
		Auth level <?php echo $entry->auth_level->id ?>
		<?php if ($user->auth_level->id >= 10) echo ' &middot; '.HTML::anchor('journal/edit/'.$entry->id, 'Edit') ?>
		&middot;
	</p>
	<?php endif ?>

</div>

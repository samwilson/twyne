
<ol class="tag-cloud">
	<?php foreach ($tags as $tag): ?>
	<li class="tag count-<?php echo $tag->count ?>">
		<?php echo $tag->name.' <span class="count">('.$tag->count.')</span>' ?>
		
		<?php if (strpos($current_tags, '+'.$tag->id)===FALSE): ?>
		<a href="<?php echo $tag->url($current_tags.'+'.$tag->id) ?>" class="include" title="Include photos that have this tag">
			+
		</a>
		<?php endif ?>
		
		<?php if (strpos($current_tags, '-'.$tag->id)===FALSE): ?>
		<a href="<?php echo $tag->url($current_tags.'-'.$tag->id) ?>" class="exclude" title="Exclude photos that have this tag">
			-
		</a>
		<?php endif ?>
		
	</li>
	<?php endforeach ?>
</ol>

<div class="current-filters">
	<strong>Currently-Selected Tags:</strong>
	<ol>
		<?php foreach (Model_Tags::parse($current_tags) as $tag_id=>$sign): ?>
		<li class="<?php echo ($sign=='-') ? 'exclude' : 'include' ?>">
			<em><?php echo ($sign=='-') ? 'Excluding' : 'Including' ?></em>
			<?php echo ORM::factory('Tags', $tag_id)->name ?>
			<a href="<?php echo $tag->url($current_tags, $tag_id) ?>" class="remove-filter" title="Remove this tag filter">
				[X]
			</a>
		</li>
		<?php endforeach ?>
	</ol>
</div>


<?php if (strlen($current_tags) > 0): ?>
<?php echo View::factory('thumbs')
	->bind('photos', $photos)
	->bind('title', $title)
	->render() ?>
<?php endif ?>


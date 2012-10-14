
<div class="menu">
	<ol>
		<?php foreach ($years as $year): ?>

			<?php
			$year_title = ($year->year=='0000') ? 'Year Unknown' : $year->year;
			if (isset($current_year) && $year->year==$current_year): ?>
		<li class="year selected">
			<a class="year"><?php echo $year_title; ?></a>
			<ol class="months">
			<?php foreach ($months as $month): ?>

					<?php
					if ($month->month=='00')
					{
						$month_name = 'unknown';
						$month_title = 'Month Unknown';
					} else
					{
						$month_title = date('F', strtotime("2010-$month->month-01"));
						$month_name = $month_title;
					}
					?>

				<?php if ($month->month==$current_month): ?>
				<li class="month selected">
					<a><?php echo $month_title ?></a>
				</li>
				
				<?php else: ?>
				<li class="month">
					<a href="<?php echo Route::url('dates', array('year'=>$current_year, 'month'=>$month->month)) ?>">
						<?php echo $month_title ?>
					</a>
				</li>
				<?php endif ?>

			<?php endforeach ?>
			</ol>
		</li>
			<?php else: ?>
		<li>
			<a href="<?php echo Route::url('dates', array('year'=>$year->year, 'month'=>$current_month)) ?>">
				<?php echo $year_title ?>
			</a>
		</li>
			<?php endif ?>

		<?php endforeach ?>
	</ol>
</div>




<div class="photos">

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
			<?php echo date('D jS', strtotime($photo->date_and_time)) ?>
			<?php if($user->auth_level->id > 1) echo ' &middot; <dfn title="Auth Level">'.$photo->auth_level->name.'</dfn>' ?>
			<?php if ($user->auth_level_id >= 10): ?>
			&middot; <a href="<?php echo Route::url('image',array('action'=>'edit', 'id'=>$photo->id)) ?>#form">Edit</a>
			&middot; <a href="<?php echo Route::url('image',array('action'=>'delete', 'id'=>$photo->id)) ?>#form">Delete</a>
			<?php endif ?>
			&middot;
			<?php if (count($tags = $photo->tags->order_by('name')->find_all()) > 0): ?>
				Tags:
				<?php $tag_links = array(); foreach ($tags as $tag): ?>
					<?php $tag_links[] = HTML::anchor('tag/'.urlencode($tag->name), $tag->name, array('rel'=>'tag')) ?>
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


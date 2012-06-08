
<div class="menu">
	<ol>
		<?php foreach ($years as $year): ?>

			<?php
			$year_title = ($year->year=='0000') ? 'Year Unknown' : $year->year;
			if (isset($current_year) && $year->year==$current_year): ?>
		<li class="selected">
			<a>&rarr; <?php echo $year_title; ?></a>
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

							<?php if ($month->month==$current_month_number): ?>
				<li class="selected">
					<a>&rarr; <?php echo $month_title ?></a>
				</li>
							<?php else: ?>
				<li>
									<?php echo HTML::anchor('blog/'.$current_year.'/'.$month_name, $month_title) ?>
				</li>
							<?php endif ?>

						<?php endforeach ?>
			</ol>
		</li>
			<?php else: ?>
		<li>
					<?php echo HTML::anchor('blog/'.$year->year.'/'.$current_month_name, $year_title) ?>
		</li>
			<?php endif ?>

		<?php endforeach ?>
	</ol>
</div>




<div class="items">

	<?php foreach ($items as $item): ?>

	<div class="item <?php echo ($item instanceof Model_Images) ? 'image' : '' ?>">
		<a name="<?php echo $item->id ?>"></a>

		<?php
		if ($item instanceof Model_Images)
		{
			$item_controller_name = 'images';
			echo '<p class="img">';
			$img = HTML::image('images/render/'.$item->id.'/view', array('alt'=>$item->date_and_time));
			echo HTML::anchor('images/view/'.$item->id, $img);
			echo '</p>';
			echo '<div class="caption">';
			echo Text::typeset($item->caption);
			echo '</div>';
		} 

		if ($item instanceof Model_JournalEntries)
		{
			$item_controller_name = 'journal';
			if (!empty($item->title)) echo '<h2>'.HTML::anchor('journal/view/'.$item->id, $item->title).'</h2>';
			echo '<div class="prose">'.Text::typeset($item->entry_text).'</div>';
		}
		?>

		<p class="metadata">&middot; <?php
				echo HTML::anchor($item_controller_name.'/view/'.$item->id, $item->date_and_time, array('title'=>'Permalink'));
				if($user->auth_level->id > 1) echo ' &middot; Auth level '.$item->auth_level->name.'.';
				if ($user->auth_level->id >= 10)
				{
					echo ' &middot; '.HTML::anchor($item_controller_name.'/edit/'.$item->id.'#form', 'Edit');
					echo ' &middot; '.HTML::anchor($item_controller_name.'/delete/'.$item->id, 'Delete');
				}
				?> &middot;
		</p>

		<p class="tags">
				<?php if (count($tags = $item->tags->order_by('name')->find_all()) > 0): ?>
			&middot;
					<?php foreach ($tags as $tag): ?>
						<?php echo HTML::anchor('tag/'.urlencode($tag->name), $tag->name, array('rel'=>'tag')) ?> &middot;
					<?php endforeach ?>
				<?php endif ?>
		</p>
	</div>

	<?php endforeach ?>


	<?php if (count($items)<1): ?>
	<p class="notice message">Nothing to display for <?php echo $title ?>.</p>
	<?php endif ?>

</div>



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

<p class="pdf-link"><?php
$url = Route::url('dates', array('format'=>'pdf','year'=>$current_year,'month'=>$current_month), TRUE);
echo HTML::anchor($url, "Download a PDF album of these photos.");
?></p>

<?php echo View::factory('thumbs')
	->bind('photos', $photos)
	->bind('title', $title)
	->render() ?>

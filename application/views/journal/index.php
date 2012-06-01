
Years:
<ol class="columnar">
	<?php foreach ($years as $y): ?>
	<li><?php echo HTML::anchor('journal/index/'.$y->year, $y->year) ?></li>
	<?php endforeach ?>
</ol>

<h2 class="clear"><?php echo $year ?></h2>


<?php
$old_month = NULL;
foreach ($journal_entries as $entry): ?>

<?php $month = date('F',strtotime($entry->date_and_time)) ?>

<?php if ($month != $old_month)
{
	echo "<h3>$month</h3>";
	$old_month = $month;
} ?>

<?php if ($entry->title) echo "<h4>$entry->title</h4>" ?>

<?php echo View::factory('journal/view')->bind('entry',$entry)->render() ?>

<?php endforeach ?>
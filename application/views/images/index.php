<ol class="columnar">
	<?php foreach ($years as $y): ?>
	<li>
		<a href="<?php echo Route::url('dates', array('year'=>$y, 'month'=>))"
		<?php echo HTML::anchor('images/index/'.$y->year, $y->year) ?></li>
	<?php endforeach ?>
</ol>

<h2 class="clear"><?php echo $year ?></h2>
<ol class="thumbs">
	<?php foreach ($images as $i): ?>
	<li>
			<?php
			$img = HTML::image('images/render/'.$i->id.'/thumb', array('alt'=>$i->caption));
			echo HTML::anchor('images/view/'.$i->id, $img, array('title'=>$i->caption));
			?>
	</li>
	<?php endforeach ?>
</ol>



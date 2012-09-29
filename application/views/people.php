
<p class="new noprint">
	<?php echo HTML::anchor('person', '[New Record]') ?>
</p>

<ol class="columnar">
	<?php foreach ($people as $person): ?>
		<li>
			<a href="<?php echo Route::url('person', array('id'=>$person->id)) ?>">
				<?php echo $person->name ?>
			</a>
			<?php
			if (!empty($person->email_address))
				echo ' &lt;'.$person->email_address.'&gt;';
			if (!empty($person->notes))
				echo ' '.$person->notes;
			?>
		</li>
	<?php endforeach ?>
</ol>

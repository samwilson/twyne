<?php
$last_email = FALSE;
$count = 0;
foreach ($emails as $email): ?>

<div class='email <?php if($email->from->is_main_user()) echo 'from-me' ?>'>
	<p class="metadata">
		<?php if ($count==count($emails)-1) echo "<a name='most-recent'></a>" ?>
		<span class='from'><?php echo $email->from->name ?></span>
		<?php echo date('l, F jS, g:iA',strtotime($email->date_and_time)) ?> &nbsp;&nbsp;
		<strong><?php echo $email->subject ?></strong> &nbsp;&nbsp;
		<!--span class='small quiet'>
		  <a href='?table_name=emails&edit&id=".$email['id']."'>[e]</a>
		  <del><a href='?table_name=emails&delete&id=".$email['id']."'>[d]</a></del>
		</span-->
	</p>
	<pre><?php echo trim(wordwrap(htmlentities(utf8_decode($email->message_body)), 78)) ?></pre>
</div>


<?php
$last_email = $email;
$count++;
endforeach;
$subject = '';
?>


<?php if ($with->loaded()): ?>
<form action="<?php echo URL::site("emails/index?year=$year&with=$with") ?>" method="post" class="email from-me">
	
	<p class="hide">
		<?php
		echo Form::hidden('to_id', $with->id);
		if ($last_email) {
			echo Form::hidden('last_date_and_time', $last_email->date_and_time);
			echo Form::hidden('last_body', $last_email->message_body);
			$subject = (stristr($last_email->subject,'re')!=0) ? 'RE: '.$last_email->subject : $last_email->subject;
		}
		?>
	</p>
	
	<p id="form">
		<?php
		echo Form::label('to', 'To:');
		echo Form::input('to', $with->name.' <'.$with->email_address.'>', array('size'=>50));
		?>
	</p>
	<p class="subject">
		<?php
		echo Form::label('subject','Subject:');
		
		echo Form::input('subject', $subject, array('size'=>50));
		?>
	</p>
	<p>
		<?php echo Form::textarea('message_body', NULL, array('rows'=>24, 'cols'=>80)) ?>
	</p>
	<p class="submit">
		<?php echo Form::submit('send', 'Send') ?>
	</p>
</form>
<?php endif ?>


<ol class="columnar">
<?php foreach ($people as $person):
	//if (!$person->most_recent_email->loaded()) continue;
	$year = ($person->most_recent_email->loaded()) ? $person->most_recent_email->year() : date('Y');
	?>

<li class="<?php //if (!$person->most_recent_email->from->is_main_user()) echo 'unanswered' ?>">
	<?php echo HTML::anchor('emails/index?year='.$year.'&with='.$person.'#most-recent', $person->name) ?>
</li>

<?php endforeach ?>
</ol>

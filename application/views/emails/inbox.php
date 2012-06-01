<?php if (isset($email)): ?>
<form action="<?php echo URL::site('emails/inbox') ?>" method="post" class="email from-me">
	<p>
		<?php
		echo Form::label('date_and_time', 'Date:');
		echo Form::input('date_and_time', $email->date_and_time);
		?>
	</p>
	<p>
		<?php echo Form::label('from_id', 'From:').Form::select('from_id', $people, $email->from->id) ?>
		<?php if ($email->from->id==NULL)
			echo '<br />'.$headers['FROM'][0]['PERSONAL_NAME'].' &lt;'.$headers['FROM'][0]['EMAIL'].'&gt;'
		?>
	</p>
	<p>
		<?php echo Form::label('to_id', 'To:').Form::select('to_id', $people, $email->to->id) ?>
		<?php if ($email->to->id==NULL)
			echo '<br />'.$headers['TO'][0]['PERSONAL_NAME'].' &lt;'.$headers['TO'][0]['EMAIL'].'&gt;'
		?>
	</p>
	<p class="subject">
		<?php
		echo Form::label('subject','Subject:');
		echo Form::input('subject', $email->subject, array('size'=>50));
		?>
	</p>
	<p>
		<?php
		//var_dump($email->message_body);
		//echo Form::textarea('message_body', $email->message_body, array('rows'=>24, 'cols'=>80));
		?>
		<textarea rows="24" cols="80" name="message_body"><?php echo $email->message_body ?></textarea>
	</p>
	<p class="submit">
		<?php
		echo Form::submit('save', 'Archive + Delete');
		echo Form::submit('save', 'Archive Only');
		echo Form::submit('delete', 'Delete Only');
		?>
	</p>
	<!--pre class="terminal"><?php //print_r($headers) ?></pre-->
</form>
<?php endif ?>

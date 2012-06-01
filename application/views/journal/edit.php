
<?php echo Form::open() ?>

<h2>
	<?php echo Form::input('title', $entry->title, array('size'=>50)) ?>
</h2>
<p>
	<?php
	echo Form::label('date_and_time', 'Date and time: ')
	.Form::input('date_and_time', $entry->date_and_time)
	?>
	<span class="auth_level"><?php
	echo Form::label('auth_level', 'Auth level: ')
	.Form::input('auth_level', $entry->auth_level->id, array('size'=>5))
	?>
	</span>
</p>
<p class="entry_text">
	<?php echo Form::textarea('entry_text', $entry->entry_text, array('rows'=>24, 'cols'=>80, 'class'=>'mono')) ?>
</p>

<script type="text/javascript">
	var tags = [<?php echo ORM::factory('Tags')->get_list() ?>]
	$(function(){
		function split(val) {
			return val.split(/,\s*/);
		}
		function extractLast(term) {
			return split(term).pop();
		}
		$("#tags").autocomplete({
			minLength: 0,
			source: function(request, response) {
				// delegate back to autocomplete, but extract the last term
				response($.ui.autocomplete.filter(tags, extractLast(request.term)));
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function(event, ui) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				// add placeholder to get the comma-and-space at the end
				terms.push("");
				this.value = terms.join(", ");
				return false;
			}
		});
	});
</script>
<p class="tags">
	<label for="tags">Tags:</label>
	<input id="tags" name="tags" size="50" value="<?php echo $entry->tags->get_list() ?>" />
</p>

<p class="submit">
	<a href="http://docutils.sourceforge.net/docs/user/rst/quickref.html" title="ReStructuredText documentation at Sourceforce.net">
		ReST Documentation
	</a>
	<input type='submit' name='save' value='Save' />
</p>

<?php echo Form::close() ?>

<?php echo '<?xml version="1.0" encoding="utf-8"?>' ?>

<feed xmlns="http://www.w3.org/2005/Atom">

	<title>Sam Wilson's Journal<?php if ($tag) echo ': Everything tagged '.$tag ?></title>
	<subtitle>One chap from Freo.</subtitle>
	<!--link href="<?php echo URL::site('blog/feed',TRUE) ?>" rel="self" /-->
	<link href="<?php echo URL::base(TRUE, TRUE) ?>" />
	<id><?php echo URL::base(TRUE, TRUE) ?></id>
	<updated><?php echo $entries[0]->date_and_time ?></updated>
	<author>
		<name>Sam Wilson</name>
	</author>

	<?php foreach ($entries as $entry): ?>
	
	<entry>
		<title><?php echo $entry->title ?></title>
		<link href="<?php echo URL::site($entry->controller.'/view/'.$entry->id, TRUE) ?>" />
		<link rel="alternate" type="text/html" href="<?php echo URL::site($entry->controller.'/view/'.$entry->id, TRUE) ?>"/>
		<id>tag:<?php echo $_SERVER['SERVER_NAME'].','.substr($entry->date_and_time,0,10).':'.$entry->controller.'_'.$entry->id ?></id>
		<updated><?php echo $entry->date_and_time ?></updated>
		<content type="html">
		<![CDATA[
			<?php echo (empty($entry->summary))
				? '<p>No summary available.</p>'
				: Text::typeset($entry->summary);
			?>
		]]>
		</content>
	</entry>
	
	<?php endforeach ?>

</feed>

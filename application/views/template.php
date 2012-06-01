<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width" />
		<meta name="generator" content="Parch, a Personal Archive database" />

		<title><?php echo $title ?></title>

		<link rel="alternate" type="application/atom+xml" title="Everything" href="<?php echo URL::site('blog/feed') ?>"/>
		<?php if (isset($tag) && !empty($tag)): ?>
			<link rel="alternate" type="application/atom+xml" title="Everything tagged <?php echo $tag ?>" href="<?php echo URL::site('blog/feed/'.urlencode($tag)) ?>" />
		<?php endif ?>

		<?php if (isset($prev_href)): ?>
			<link rel="prev" href="<?php echo url::site($prev_href) ?>" />
		<?php endif ?>
		<?php if (isset($next_href)): ?>
			<link rel="next" href="<?php echo url::site($next_href) ?>" />
		<?php endif ?>

		<?php
		if ($jquery)
			echo HTML::style('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/humanity/jquery-ui.css')."\n\t\t"
			.HTML::script('http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js')."\n\t\t"
			.HTML::script('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/jquery-ui.min.js')."\n\t\t"
			.HTML::script('resources/js/scripts.js')."\n\t\t"
			?>

		<?php echo HTML::style('resources/css/style.css')."\n\t\t" ?>
		<!-- Background images included here for portability. -->
		<style type="text/css">
			#openid_identifier { background-image: url("<?php echo URL::site('resources/img/openid.gif') ?>") }
		</style>

	</head>
	<body class="<?php echo $controller.' '.$action ?>">

		<div id="wrapper">

			<div id="header">

				<?php if ($user->loaded()): ?>
					<p class="user">Logged in as 
						<?php echo "$user->name (Auth level $user->auth_level)" ?>
					</p>
				<?php endif ?>

				<ol class="tabs" id="toplinks">
					<?php foreach ($toplinks as $link): ?>
						<?php if ($link['url'] == $selected_toplink): ?>
							<li class="selected">
								<a><?php echo $link['title'] ?></a>
							</li>
						<?php else: ?>
							<li>
								<?php echo HTML::anchor($link['url'], $link['title']) ?>
							</li>
						<?php endif ?>
					<?php endforeach ?>
                </ol>
            </div>

            <div id="view">

				<?php if (count($messages) > 0): ?>
					<ul class="messages noprint">
						<?php foreach ($messages as $message): ?>
							<li class="<?php echo $message['status'] ?> message">
								<?php echo $message['message'] ?>
							</li>
						<?php endforeach ?>
					</ul>
				<?php endif ?>

				<div class="content">

					<?php if ($title) echo "<h2>$title</h2>" ?>

					<?php echo $content ?>

				</div>

                <div class="clear"></div>

            </div>

            <div id="footer">
                <p>Th</p>

				<?php if (Kohana::$environment == Kohana::DEVELOPMENT): ?>
					<div class="kohana-profiler noprint">
						<?php echo View::factory('profiler/stats') ?>
					</div>
				<?php endif ?>

            </div>
        </div>
    </body>
</html>

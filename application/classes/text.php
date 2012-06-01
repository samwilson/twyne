<?php

defined('SYSPATH') or die('No direct access allowed.');

class Text extends Kohana_Text {

	public static function typeset($in, $format = 'html')
	{
		$benchmark = Profiler::start("Typesetting to $format", __FUNCTION__);
		$out = $in;
		/*
		  $out = "\n$in\n";
		  // Platform-independent newlines.
		  $out = preg_replace("/(\r\n|\r)/", "\n", $out);
		  // Paragraphs.
		  $out = preg_replace('|(.*)|', "<p>$1</p>", $out);
		  $out = preg_replace("|^|", "<p>", $out);
		  $out = preg_replace("|$|", "</p>\n\n", $out);
		  //$out = preg_replace("|\n+\s*\n+|", "</p>\n\n<p>", $out);
		  // Remove paragraphs if they contain nothing (including only whitespace).
		  $out = preg_replace('|<p>\s*</p>|', '', $out);
		  // Remove nested paragraphs (some pages already have paragraphs marked up).
		  $out = preg_replace('|<p>\s*<p>|', '<p>', $out);
		  $out = preg_replace('|</p>\s*</p>|', '</p>', $out);
		  // Turn applicable paragraphs into blockquotes.
		  $out = preg_replace('|<p>\:(.+)</p>|m', "<blockquote>\n<p>$1</p>\n</blockquote>", $out);
		  $out = preg_replace('|</blockquote>\n\n<blockquote>|', "", $out);
		  // Three-star divider paragraph.
		  $out = preg_replace("|<p>\*\*\*</p>|", "<p style='text-align:center; letter-spacing:0.4em'>* * *</p>", $out);
		  // Strong emphasis.
		  //$out = preg_replace("|\*\*(.*?)\*\*|s", "<strong>$1</strong>", $out);
		  // Emphasis.
		  $out = preg_replace("|\*(.*?)\*|s", "<em>$1</em>", $out);
		  // Monospacing.
		  $out = preg_replace("|@(.*?)@|s", "<code>$1</code>", $out);
		  // Proper full-stop spacing.
		  //$out = preg_replace("|\.  |", ".&nbsp; ", $out);
		  // Curly quotation marks.
		  //$out = preg_replace("/\"(.*)\"/", "&ldquo;$1&rdquo;", $out);
		  //$out = preg_replace("/'(.*)'/s", "&lsquo;$1&rsquo;", $out);
		  // Em Dashes.
		  //$out = preg_replace("/---/", "&thinsp;&mdash;&thinsp;", $out);
		  // Ellipses.
		  //$out = preg_replace("/\.\.\./", "&thinsp;&hellip;&thinsp;", $out);
		  // Links.
		  //$out = preg_replace("/\[\[([^|]*)\|([^\]]*)\]\]/", "<a href='$1'>$2</a>", $out);
		  //$out = preg_replace("|[^\"'](https?://([^\s]*))|", " <a href='$1'>$2</a>", $out);
		  // Lists.
		  $out = preg_replace("|\n\n<p>#|", "\n\n<ol>\n<p>#", $out); // begin ordered
		  $out = preg_replace("|<p>#(.*)</p>\n\n|", "<p>#$1</p>\n</ol>\n\n", $out); // end ordered
		  $out = preg_replace("|\n\n<p>\*|", "\n\n<ul>\n<p>*", $out); // begin unordered
		  $out = preg_replace("|<p>\*(.*)</p>\n\n|", "<p>*$1</p>\n</ul>\n\n", $out); // end unordered
		  $out = preg_replace("|<p>[*#](.*)</p>|", "<li>$1</li>", $out); // list items
		  //$out = preg_replace("|</p>\n<p>*|", "</li>\n<li>", $out);
		  //$out = preg_replace("|<li>(.*)</p>|", "<li>$1</li>\n</ul>", $out);
		  // Ordered lists.
		  //$out = preg_replace("|</p>\n<p>#|", "</li>\n<li>", $out);
		  //$out = preg_replace("|<li>(.*)</p>|", "<li>$1</li>\n</ol>", $out);
		  // Headings.
		  $out = preg_replace("|<p>===(.*)===</p>|", "\n<h3>$1</h3>\n", $out);

		  // LaTeX logo
		  $out = preg_replace("|LaTeX|", '<span class="latex">L<sup>a</sup>&Tau;<sub>&epsilon;</sub>&Chi;</span>', $out);
		 */

		// ReStructureText to HTML.
		$preamble = '';
		$standard_substitution_definition_sets = array('isonum', 'isolat1', 'isolat2');
		foreach ($standard_substitution_definition_sets as $set)
		{
			$preamble .= ".. include:: <$set.txt>\n\n";
		}
		$out = $preamble."..\n\n".$out;
		$tmp_name = md5($out);
		$cache_dir = APPPATH.'/cache/rst2html/';
		@mkdir($cache_dir);
		$html_file = $cache_dir.$tmp_name.'.html';
		if (file_exists($html_file))
		{
			touch($html_file);
			$out = file_get_contents($html_file);
		}
		else
		{
			$rest_file = $cache_dir.$tmp_name.'.txt';
			touch($rest_file);
			touch($html_file);
			chmod($rest_file, 0660);
			chmod($html_file, 0660);
			file_put_contents($rest_file, $out);
			$command = RST2HTML_CMD." --initial-header-level=3 $rest_file $html_file";
			$err = shell_exec($command);
			if (!empty($err))
				throw new Exception($err);
			$out = file_get_contents($html_file);
			if (!unlink($rest_file))
				throw new Exception("Could not delete ".$rest_file);
		}
		$out = preg_replace('/.*<body>\n(.*)<\/body>.*/s', '${1}', $out);

		Profiler::stop($benchmark);
		return $out;
	}

	public static function rest_to_latex($in)
	{
		$out = $in;
		/*
		  $preamble = '';
		  $standard_substitution_definition_sets = array('isonum', 'isolat1', 'isolat2');
		  foreach ($standard_substitution_definition_sets as $set)
		  {
		  $preamble .= ".. include:: <$set.txt>\n\n";
		  }
		  $out = $preamble."..\n\n".$out;
		 */

		$tmp_name = md5($out);
		$latex_file = APPPATH.'/tmp/'.$tmp_name.'.tex';
		if (file_exists($latex_file))
		{
			touch($latex_file);
			$out = file_get_contents($latex_file);
		}
		else
		{
			$rest_file = APPPATH.'/tmp/'.$tmp_name.'.txt';
			touch($rest_file);
			touch($latex_file);
			chmod($rest_file, 0660);
			chmod($latex_file, 0660);
			file_put_contents($rest_file, $out);
			$rst_command = 'rst2latex.py';
			if (file_exists("/home/aswilson/python/bin/rst2latex.py"))
				$rst_command = "/home/aswilson/python/bin/rst2latex.py";
			if (file_exists("/usr/local/bin/rst2latex.py"))
				$rst_command = '/usr/local/bin/rst2latex.py';
			$command = "$rst_command $rest_file $latex_file";
			$err = shell_exec($command);
			if (!empty($err))
				throw new Exception($err);
			$out = file_get_contents($latex_file);
			if (!unlink($rest_file))
				throw new Exception("Could not delete ".$rest_file);
			$out = preg_replace('/ -\{\}- /', '---', $out);
		}
		$out = preg_replace('/.*\\\begin{document}\n(.*)\\\end{document}.*/s', '${1}', $out);
		return $out;
	}

	public static function tex_esc($str)
	{
		$pat = array('/\\\(\s)/', '/\\\(\S)/', '/&/', '/%/', '/\$/', '/>>/', '/_/', '/\^/', '/#/', '/"(\s)/', '/"(\S)/');
		$rep = array('\textbackslash\ $1', '\textbackslash $1', '\&', '\%', '\textdollar ', '\textgreater\textgreater ', '\_', '\^', '\#', '\textquotedbl\ $1', '\textquotedbl $1');
		return preg_replace($pat, $rep, $str);
	}

}

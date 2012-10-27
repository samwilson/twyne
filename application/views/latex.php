\documentclass{book}
\usepackage[a4paper]{geometry}
\usepackage{grffile}
\usepackage{makeidx}
\makeindex
\usepackage{figsize}
\usepackage[margin=10pt,font=small,labelfont=bf,labelsep=period]{caption}
\renewcommand{\figurename}{}
\SetFigLayout[3]{2}{1}
\renewcommand{\listfigurename}{Contents}
\title{Photo Album}
\author{Alexander Samuel William Wilson}
\date{<?php echo $year ?>}
\begin{document}
\maketitle
\frontmatter
\listoffigures
\mainmatter
\chapter{Photographs}
<?php
$img_count = 0;
foreach ($images as $image)
{
	$filename = DATAPATH.'images/view/'.$image->id.'.jpg';
	if (file_exists($filename))
	{
		$weekday = date('\l', strtotime($image->date_and_time));
        $date = date($weekday.', F j\\\\\\t\ex\\t\s\u\p\e\\r\s\\c\\r\i\p\\t{S}, g:iA', strtotime($image->date_and_time));
		echo '
		\begin{figure}
			\begin{center}
				\includegraphics{'.$filename.'}
				\caption{'.$date.'. '.str_replace("\n", ' ', Text::wiki2latex($image->caption)).'}
		';
		foreach($image->tags->order_by('title')->find_all() as $tag)
		{
			echo '\index{'.$tag->title.'} ';
		}
		echo '
			\end{center}
		\end{figure}
		';
		if ($img_count%12==0) echo '\clearpage';
		$img_count++;
	}
}
?>

\printindex

\end{document}
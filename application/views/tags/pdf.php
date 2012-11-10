\documentclass{book}

\usepackage[a4paper]{geometry}

\usepackage{grffile}

\usepackage{makeidx}
\makeindex

\usepackage{chngcntr}
\counterwithout{figure}{chapter}

\usepackage{figsize}
\usepackage[margin=10pt,font=small,labelfont=bf,labelsep=period]{caption}
\renewcommand{\figurename}{}
\SetFigLayout[3]{2}{1}
\renewcommand{\listfigurename}{Contents}
\title{<?php echo $title ?>}
\begin{document}
\maketitle
\frontmatter
\listoffigures
\mainmatter
\chapter{Photographs}

\textbf{Currently-Selected Tags:}

\begin{enumerate}

<?php foreach (Model_Tags::parse($current_tags) as $tag_id=>$sign): ?>

	\item \emph{<?php echo ($sign=='-') ? 'Excluding' : 'Including' ?>}
	<?php echo ORM::factory('Tags', $tag_id)->name ?>
	
<?php endforeach ?>

\end{enumerate}

<?php
$img_count = 0;
foreach ($photos as $photo)
{
	$filename = DATAPATH.'images/view/'.$photo->id.'.jpg';
	if (file_exists($filename))
	{
		$weekday = date('\l', strtotime($photo->date_and_time));
		$date = date($weekday.', F j\\\\\\t\ex\\t\s\u\p\e\\r\s\\c\\r\i\p\\t{S} Y, g:iA', strtotime($photo->date_and_time));
		echo '
		\begin{figure}
			\begin{center}
				\setcounter{figure}{'.$photo->id.'}
				\includegraphics{'.$filename.'}
				\caption{'.$date.'. '.str_replace("\n", ' ', $photo->caption).'}
		';
		foreach($photo->tags->order_by('name')->find_all() as $tag)
		{
			echo '\index{'.$tag->name.'} ';
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

\addcontentsline{toc}{chapter}{Index}
\printindex

\end{document}
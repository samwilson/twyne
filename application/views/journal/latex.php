\documentclass{book}
\usepackage[a4paper]{geometry}
\usepackage{alltt}
\usepackage{url,gensymb}
\usepackage[normalem]{ulem}

\usepackage{fixltx2e} % LaTeX patches, \textsubscript
\usepackage{cmap} % fix search and cut-and-paste in PDF
\usepackage[T1]{fontenc}
\usepackage[utf8]{inputenc}
\usepackage{ifthen}

\renewcommand{\thesection}{\arabic{section}}
\setcounter{secnumdepth}{0}

\title{Journal}
\author{Alexander Samuel William Wilson}
\date{<?php echo $year ?>}

\begin{document}
\maketitle
\tableofcontents

<?php
$old_month = NULL;
$old_day = NULL;
foreach ($entries as $entry): 
	$month = date('F',strtotime($entry->date_and_time));
	?>

<?php if ($month!=$old_month): ?>
\chapter{<?php echo $month ?>}
<?php $old_month = $month; endif; ?>

<?php
$weekday = date('\l', strtotime($entry->date_and_time));
$format = $weekday.', F j\\\\\\t\ex\\t\s\u\p\e\\r\s\\c\\r\i\p\\t{S}';
$day = date($format, strtotime($entry->date_and_time));
?>

<?php if ($day!=$old_day): ?>
\section{<?php echo $day ?>}
<?php $old_day = $day; endif; ?>

<?php $time = date('g:iA', strtotime($entry->date_and_time)) ?>
<?php echo Text::rest_to_latex(' **'.$time.'** '.$entry->entry_text) ?>

<?php endforeach ?>

\end{document}



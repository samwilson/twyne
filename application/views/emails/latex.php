
\documentclass{book}
\usepackage[a4paper,margin=2cm]{geometry}
\usepackage[T1]{fontenc}
\usepackage{alltt}
\title{Emails}
\author{Alexander Samuel William Wilson}
\date{<?php echo $year ?>}
\setlength{\parindent}{0cm}
\begin{document}
\maketitle
\tableofcontents

<?php
foreach ($emails as $person_id=>$emails)
{
	if (count($emails)>0 && $person_id!=9)
	{
		$person = $people[$person_id];
		echo "\chapter{".Text::tex_esc($person->name)."}\n";
		foreach ($emails as $email)
		{
			$from = $people[$email->from_id];
			echo "\\textbf{".trim(Text::tex_esc($from->name)).", ".date('l, F jS, g:iA',strtotime($email->date_and_time)).".}\n\n";
			echo "\\textbf{".Text::tex_esc($email->subject)."}\n\n";
			echo wordwrap(trim(Text::tex_esc($email->message_body)))."\n\n";
			echo "\\vspace{0.3cm}\n";
		}
	}
}
?>

\end{document}



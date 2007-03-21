<?php

/*
  Subtitles Parser (SRT format)
  TEST PROGRAM
  author: Odilon Nelson (odilon _dot_ nelson _at_ gmail _dot_ com)
  
*/

require_once('subparser.php');

$p = new SubParser('test.srt');
$a = $p->parse();
$ok = $p->isSuceeded(); 
echo  $ok ? 'SUCCESS!' : 'ERROR! ';
echo '<br />';
if (!$ok) {
  $errs = $p->getErrors();
  foreach ($errs as $err)
    echo "$err<br />";
}
echo '<pre>';
print_r($a);
echo '</pre>';

?>

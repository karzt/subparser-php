<?php

/*
  Subtitles Parser (SRT format)
  author: Odilon Nelson (odilon _dot_ nelson _at_ gmail _dot_ com)
  Initial version: 01 : 2007-03-21 (17:40)
*/

/*
  GRAMMAR (for each item):
  
  subtitleItem         -> NUMBER subtitleItemRest
  subtitleItemRest     -> timeInterval subtitleItemRestRest
  subtitleItemRestRest -> text
  timeInterval         -> TIMESTAMP '-->' TIMESTAMP
*/

class SubParser {
  
  /* These MUST be considered private... */
  var $filename_,
      $file_,  //file contents
	  $relaxNumbering_,
	  $errors_,
	  $node_;
  
  function SubParser($filename, $relaxNumbering = FALSE) {
    $this->filename_ = trim($filename);
	$this->lastNum_ = 0;
	$this->relaxNumbering_ = $relaxNumbering;
	$this->suceeded_ = 0;
	$this->errors_ = array();
	$this->lineNum_ = 0;
	$this->file_ = file($this->filename_);
	if (count($this->file_) == 0)
	  $this->emitError_('Could not open');
///	echo "<pre>"; print_r($this->file_); echo "</pre>";
  }
  
  function parse() {
	$a = array();
	$this->lastNum = 0;
	$this->node_ = array('number'     => null,
                         'time_begin' => null,
                         'time_end'   => null,
                         'text'       => null
					);
	$s = $this->getLine_();
	while ($s != null) {
	  $this->handleNumber_($s);
	
      //parse node is populated...
	  if (!isset($this->node_)) {
	    $this->emitError_('Parse error: Number expected'); //1st number is missing
		return $a;
      }
	  else if (count($this->node_) > 0)
	    $a[] = $this->node_;
	  $s = $this->getLine_();
	}
		
	unset($this->node_);  //save a little mem
	return $a;
  }
  
  function isSuceeded() { return count($this->errors_)==0; }
  function getErrors()  { return $this->errors_;   }
  
  /* These MUST be considered private... */
  
  function getLine_() {
    if ($this->lineNum_ < count($this->file_)) {
	  $s = trim($this->file_[$this->lineNum_++]);
	  if (strlen($s) == 0) {
	    $s = trim($this->file_[$this->lineNum_++]);
	  }
	  return $s;
	}
	/* end of input */
	else return null;
  }
  
  function putLineBack_() {
    if ($this->lineNum_ > 0)
	  --$this->lineNum_;
  }
  
  function emitError_($msg) {
    $this->errors_[] = '[' . __CLASS__ . ']: ' .
                       ' file "' . $this->filename_ . '", line ' .
					   $this->lineNum_ . ': ' . $msg;
    unset($this->node_);  //save a little mem
  }
  
  function handleNumber_($buff) {
	if (is_numeric($buff)) {
	  $n = (int)$buff;
      if ($n > $this->lastNum_) {
		$this->lastNum_ = $n;
		$this->node_['number'] = $n;
		$s = $this->getLine_();
	    if ($s != null)
		  return $this->handleTimeInterval_($s);
		else return FALSE;
	  }
	  else if (!$this->relaxNumbering_) {
		$this->emitError_('Parse Error: Numbering out of order');
	    return FALSE;
	  }
	}
	else {
	  $this->emitError_('Parse Error: Number expected but "' . $buff . '" found');
	  return FALSE;
	}
  }
  
  function handleTimeInterval_($buff) {
	$pieces = explode('-->', $buff);
	$this->node_['time_begin'] = trim($pieces[0]);
	$this->node_['time_end'] = trim($pieces[1]);
	
	$s = $this->getLine_();
	if ($s != null)
      return $this->handleText_($s);
    else return FALSE;
  }
  
  /*Auxiliary for handleText_*/
  function getNonNumber_() {
    $s = $this->getLine_();
	if ($s == null) return null;
	/*if line is an integer, put it back for next iteration.
	  cannot use is_numeric, because something like 3. must pass as
	  legitimate text line.
	*/
	if (ereg('^[0-9]+$', $s)) {
	  $this->putLineBack_();
	  return null;
	}
	return $s;
  }
  
  function handleText_($buff) {
	$this->node_['text'] = $buff;
	//multiple-line text?
	$s = $this->getNonNumber_();
	while ($s != null) {
	  $this->node_['text'] .= '\n' . $s;
	  $s = $this->getNonNumber_();
	}
	return TRUE;
  }
}

?>

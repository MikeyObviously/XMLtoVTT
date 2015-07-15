<?php
/** ZIP FUNCTION **/
require_once 'zip.php';

/** TURNS ON OUTPUT BUFFERING **/
ob_start();
$files_to_zip = array();

/* LOOP OVER UPLOADED FILES */
for ($i = 0; $i < count($_FILES["file"]["tmp_name"]); $i++){
	$inputFile = $_FILES["file"]["tmp_name"][$i];

	/** SET FILENAME TO BE THE SAME W/ VTT EXTENSION */
	$filearray = explode(".", $_FILES['file']['name'][$i]);
	$filename = $filearray[0] . ".vtt";

	/* LOAD FILE INTO DOMDOCUMENT. TURN OFF ERROR REPORTING. */
	libxml_use_internal_errors(true);
	$dom = new DOMDocument;
	$dom->loadHTMLfile($inputFile);
	$vtt = fopen($filename, 'a');
	fwrite ($vtt, "WEBVTT\n\n");

	/** LOOPS OVER ALL <P> TAGS. PULLS TIME ATTRIBUTES AND WRITES. PULLS CAPTIONING AND REFORMATS **/
	foreach ($dom->getElementsByTagName('p') as $node) {

		/** WRITES BEGINNING TIME, MAKES SURE IN SS.MMM */
		$beginarray = explode (".",$node->getAttribute('begin'));
		if (strlen($beginarray[1]) == 2) {
			fwrite ($vtt, $node->getAttribute('begin') . "0 --> ");
		} else {
			fwrite ($vtt, $node->getAttribute('begin') . " --> ");			
		}
		
		/** WRITES END TIME, CALLS FUNCTION TO ADD TIMES TOGETHER **/
		fwrite ($vtt, sum_the_time($node->getAttribute('begin'), $node->getAttribute('dur')));
		//fwrite ($vtt, " A:middle\n");
		fwrite ($vtt, "\n");
		
		/** WRITES CAPTION **/
		$caption = strip_tags($dom->saveHtml($node), '<br>') . "\n\n";
		$caption = preg_replace('#<br\s*/?>#i', "\n", $caption);
		$caption = htmlspecialchars_decode($caption);
		fwrite ($vtt, $caption);
	}
	fclose ($vtt);
	array_push($files_to_zip, $filename);
}

/** SEND FILE **/
$zip = create_zip($files_to_zip, 'vtt.zip');
foreach($files_to_zip as &$del){
	unlink($del);
}
header("Content-disposition: attachment; filename=vtt.zip");
header("Content-type: application/zip");
readfile("vtt.zip");
unlink("vtt.zip");

/** FUNCTION TO ADD TWO TIMES IN HH:MM:SS.MMM FORMAT. **/
function sum_the_time($time1, $time2) {
  $times = array($time1, $time2);
  $seconds = 0;
  foreach ($times as $time)
  {
    list($hour,$minute,$second) = explode(':', $time);
	$second = number_format((float)$second, 3);
    $seconds += $hour*3600;
    $seconds += $minute*60;
    $seconds += $second;
  }
  $hours = floor($seconds/3600);
  $seconds -= $hours*3600;
  $minutes  = floor($seconds/60);
  $seconds -= $minutes*60;
  
  return sprintf('%02d:%02d:%06.3f', $hours, $minutes, $seconds);
}

?>
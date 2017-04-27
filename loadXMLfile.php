<?php 
#PHP-Skricpt to call Aleph-XML

include_once "parseXServer.php";
$xrequest = new ALXS();
$xrequest->load_bib_xml($argv[1]);

#echo get_value_of_cat($field,$ind1='',$ind2='', $subfield='',$parsingMetadataFormat)
$kat = $xrequest->get_value_of_cat($argv[2],'','','',$argv[3]);
var_dump($kat);
?>
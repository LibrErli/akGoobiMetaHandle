<?php


$xrequest = new ALXS();
$xrequest->xs_request("");

#echo $xrequest->set_number."<br/>";
#echo $xrequest->NoR;

#echo $xrequest->run_through_set_MWuG();




#var_dump($xrequest->alxs_url);
#var_dump($xrequest->set_number);

#echo $xrequest->set_number."<br/>";
#echo $xrequest->NoR;

#$xrequest->run_through_set('453','','','AC00041679','001');





class ALXS {
	
	public function __construct(){
		$str = file_get_contents("/opt/digiverso/goobi/rulesets/ruleset.xml");
		$config_xml = new SimpleXMLElement($str);
		$this->alxs_url = $config_xml->Aleph[0]['server'];	
		$this->set_number = "";
		$this->NoR = "";
	}
	
	public function xs_request($request,$code='WRD'){
		$url = $this->alxs_url."/X?op=find&base=AKW01&code=".$code."&request=".urlencode($request);
		#echo $url."<br/>";
		$str = file_get_contents($url);
		$xml = new SimpleXMLElement($str);
		#var_dump($xml);
		$this->set_number = $xml->set_number;
		$this->NoR = $xml->no_records;
	}
	
	public function load_set_entry($i=0){
		$url = $this->alxs_url."/X?op=present&set_entry=".$i."&set_number=".$this->set_number;
		#	echo $url."<br/>";
		$str = file_get_contents($url);
		$xml = new SimpleXMLElement($str);
		$this->bibXML = $xml;
		
	}
	
	public function load_bib_xml($file){
		$str = file_get_contents($file);
		try{
			$xml = new SimpleXMLElement($str);
			#var_dump($xml);
			$this->bibXML = $xml;
		}
		catch (Exception $e){
			echo 'Not parsing '.$file.': ', $e->getMessage(), "\n";
		}
	}
	
	function run_through_set($check_field='',$check_ind1='',$check_ind2='',$check_value='',$return_field=''){	
	
		$NoR = intval($this->NoR);
		for($i=1;$i<=$NoR;$i++){
			$url = $this->alxs_url."/X?op=present&set_entry=".$i."&set_number=".$this->set_number;
			$str = file_get_contents($url);
			$xml = new SimpleXMLElement($str);
			
			$xpath_str = "//varfield[@id='".$check_field."']/subfield[@label='a']";
			$check_val = $xml->xpath($xpath_str);
			#var_dump($check_val);
				if($check_val[0]==$check_value){
					$xpath_str = "//varfield[@id='".$return_field."']/subfield[@label='a']";
					$return_value = $xml->xpath($xpath_str);
					#var_dump($return_value);
					echo $return_value[0]."<br/>";
				}
			#echo $xpath_str
		}

	}
	
	public function get_value_of_cat($field,$ind1='',$ind2='', $subfield='',$parsingMetadataFormat){
		#echo $parsingMetadataFormat;
		if($parsingMetadataFormat=='oai_marc'){
			$xpath_str = "//varfield[@id='".$field."'";
				if(!empty($ind1)) { $xpath_str .= " and @i1='".trim($ind1,'*')."'"; }
			$xpath_str .= "]/subfield";
				if(!empty($subfield)){ $xpath_str.="[@label='".$subfield."']"; } 
			$xpath_str .= "/parent::*";
		}
		elseif($parsingMetadataFormat=='mab_xml'){
			$xpath_str = "//*[name()='datafield' and @tag='".$field."'";
				if(!empty($ind1)) { $xpath_str .= " and @ind1='".trim($ind1,'*')."'"; }
			$xpath_str .= "]/*[name()='subfield' ";
				if(!empty($subfield)){ $xpath_str.=" and @code='".$subfield."'"; } 
			$xpath_str .= "]/parent::*";
		}
		
		#var_dump($this->bibXML);
		
			#echo $xpath_str;	
			$return_value = $this->bibXML->xpath($xpath_str);
			
			#if($field=='902'){var_dump($return_value);}
			#if($field=='100'){ echo $xpath_str."<br/>"; var_dump($return_value); }
			return $return_value;
		
		
	}
	
	

	
	function run_through_set_MWuG(){
		$NoR = intval($this->NoR);
		for($i=1;$i<=$NoR;$i++){
			$url = $this->alxs_url."/X?op=present&set_entry=".$i."&set_number=".$this->set_number;
			$str = file_get_contents($url);
			$xml = new SimpleXMLElement($str);
			#echo $url."<br/>";
			$check_val = $xml->xpath("//varfield[@id='453']/subfield[@label='a']");
			$ret_arr = array();
			
			$file ='output.csv';
			if(array_key_exists(0,$check_val) and $check_val[0]=='AC00041679'){
				
				$mab_codes = array('001','331','456');
				$mab_values = array();
				for($j = 0; $j<count($mab_codes);$j++){
					$val = $xml->xpath("//varfield[@id='".$mab_codes[$j]."']/subfield[@label='a']");
					if(array_key_exists(0,$val)){ array_push($mab_values,$val[0]); } else { array_push($mab_values,""); }
				}
				$line = "";
				
				#var_dump($mab_values);
				
				for($j=0;$j<count($mab_values);$j++){
					$line .= $mab_values[$j];
					if($j<(count($mab_values)-1)){ $line.=";"; } else { $line .= "\n"; }
					if(!empty($line)){ file_put_contents($file,$line, FILE_APPEND); }
				}		
				
			}

		}
	}
	
	
}

function parseMetadata($xrequest,$ruleset_uri,$scope="",$docstrcttype="",$parsingMetadataFormat='oai_marc'){
	#Parameter:
	#parsingMetadataFormat 	: 	'oai_marc' to parse tagnames produced by aleph-X-Server (varfield/subfield)
	#							'mab_xml' to parse tagnames produced by aleph-dump (datafield/subfield)
	
	$return = array();
	foreach($ruleset_uri as $ruleset_file){
		$ruleset_str = file_get_contents($ruleset_file);
		
		if($ruleset_file!="/opt/digiverso/goobi/rulesets/ruleset.xml") {
			#echo $ruleset_str;
		}
		$ruleset_xml = new SimpleXMLElement($ruleset_str);

		#$aleph_mab = $ruleset_xml->Aleph->Map;
		
		$aleph_mab_xpath = "//Map[";
		if(!empty($scope)){ $aleph_mab_xpath .= "@scope='".$scope."'"; }
		if(!empty($scope) and !empty($docstrcttype)){ $aleph_mab_xpath .= " and "; }
		if(!empty($docstrcttype)){ $aleph_mab_xpath .= "contains(@docstrcttype,'".$docstrcttype."')"; }
		$aleph_mab_xpath .= "]";
		$aleph_mab = $ruleset_xml->xpath($aleph_mab_xpath);
		#echo $aleph_mab_xpath;
		
		#var_dump($aleph_mab);
		
		/*
		$attr = $aleph_mab[1]->attributes();
		$kategorie = $aleph_mab[1]->Mab->attributes();
		$goobi = $aleph_mab[1]->Goobi->attributes();
		$subfeld = $aleph_mab[1]->Condition->attributes();
		*/
		
		$goobi_res = array();

		#echo "<br/>".count($aleph_mab);

		for($i=0;$i<count($aleph_mab);$i++){
			$attr = $aleph_mab[$i]->attributes();
			#scopes: topstruct || firstchild
			
			#if( preg_match('/'.$scope.'/', $attr['scope']) or preg_match('/'.$docstrcttype.'/',$attr['docstrcttype']) ){
			#Loop to parse all Aleph2Goobi-Mapings listed in the ruleset.xml <Aleph>-section
			
			$kategorie = $aleph_mab[$i]->Mab->attributes(); 
				#1.Splitting by ","-Separator
			$goobi_feld = $aleph_mab[$i]->Goobi->attributes();
			
				#var_dump($xrequest->get_value_of_cat("902")[2])."<br/>";
				
				$kat = explode(",",$kategorie['field']);
					foreach($kat as $kat_sing){
						$gnd = 0;
						$kat_det = explode("$",$kat_sing);
							#echo $kat_det[0]."<br/>";
							
							if(array_key_exists(1,$kat_det) and $kat_det[1]!='**'){ $ind = $kat_det[1]; } else { $ind = ""; }
							if(array_key_exists(2,$kat_det) and $kat_det[2]!='**'){ $subf = $kat_det[2]; } else { $subf = ""; }
							
							if($aleph_mab[$i]->Condition and $aleph_mab[$i]->Condition->attributes()){
								$bedingung = $aleph_mab[$i]->Condition->attributes();
								#echo "Bedingung vorhanden"."<br/>";
							}
							else { unset($bedingung); }
							
							if($aleph_mab[$i]->Regex and $aleph_mab[$i]->Regex->attributes()){
								$regex = $aleph_mab[$i]->Regex->attributes();
								#echo "RegEx"."<br/>";
							}
							else { unset($regex); }
							
							$x = 0;
							if(isset($bedingung)){
								if(isset($bedingung['missing'])){
									#echo $bedingung['subfield']." fehlend?";
									if($xrequest->get_value_of_cat(trim($kat_det[0]),trim($ind),'',$bedingung['subfield'],$parsingMetadataFormat)) {  
										$x++;
										#echo $kat_det[0]."missing<br/>";
									}
									
								}
								if(isset($bedingung['contains'])){
									#echo  $bedingung['subfield']." contains '".$bedingung['contains']."'? ";
									$check_entry = $xrequest->get_value_of_cat(trim($kat_det[0]),trim($ind),'',$bedingung['subfield'],$parsingMetadataFormat);
									#var_dump($check_entry);
									if(!array_key_exists(0,$check_entry) or $check_entry[0]!=$bedingung['contains']){ 
										$x++; 
										#echo $kat_det[0]."contains<br/>"; 
									}
									
								}
								
							}
							
							if($x==0){		
								#echo $kat_det[0];
								#echo $x;
								$result = array();
								unset($val,$gnd_val);
								$val = array();
								$gnd = 0;
								$bib_i = 0;
								$bib_path = $xrequest->get_value_of_cat(trim($kat_det[0]),trim($ind),'',trim($subf),$parsingMetadataFormat);
								#if($kat_det[0]=='100'){	var_dump($bib_path)."<br/>"; }
								foreach($bib_path as $bib_entry){						
									#var_dump($bib_entry);
									#if(trim($kat_det[0])=='100'){ var_dump($bib_entry);}
									foreach ($bib_entry as $bibsub){
										#var_dump($bibsub);
										if(trim($kat_det[0])=='1000'){ 
											echo $parsingMetadataFormat;
											var_dump($bibsub->attributes()->label);
											var_dump($bibsub);
										}
										#var_dump($bibsub->attributes()->label);
										
										switch($parsingMetadataFormat){
											case 'oai_marc':
											$label = (string) $bibsub->attributes()->label;
											break;
											case 'mab_xml':
											$label = (string) $bibsub->attributes();
										}
										
										#var_dump($label);
										$val[$label] = (string) $bibsub;
										#var_dump((string) $bibsub);
										
										
										
									}
									
									
									if(!empty($val)){
										#var_dump($val)."<br/>";
										$goobi_feld_str = explode(",",(string) $goobi_feld);
											foreach($goobi_feld_str as $goobi_name){
												$result['name'] = trim($goobi_name);
												$result['value'] = $val;
												if(!empty($gnd_val)){ 
													$result['gnd'] = (string) $gnd_val; 
													#echo $gnd_val; 
												}
												array_push($return,$result);
											}
									}
								}
							}
						
						
					}
			#} #(if Preg_match($scope || $docstrcttype)
			
		}
	}
	#var_dump(json_encode($return));
	return json_encode($return);
}


?>

<?php

/*Test-comment
 * PHP-Class to handle to Goobi Meta-xmlfiles
 */

class handle_GoobiMetaXML{
	
	function __construct($file){
		$this->xml = $file;
		$this->dom = new DOMDocument;
		$this->dom->preserveWhiteSpace = false;
		$this->dom->formatOutput = true;
		$this->dom->load($this->xml);
		$this->xpath = new DOMXPath($this->dom);
		$this->xpath->registerNamespace('goobi','http://meta.goobi.org/v1.5.1/');
		$this->xpath->registerNamespace('mets','http://www.loc.gov/METS/');
	}
	
	function saveGoobiMetaXML(){
		$this->dom->saveXML($this->dom->documentElement);
		$this->dom->save($this->xml);
	}

	function getNodeValue($xpath){
		$elements = $this->xpath->query($xpath);
		if($elements->length>0){
			return $elements->item(0)->nodeValue;
		}
	}
	
	function getNumberofNodes($xpath){
		$elements = $this->xpath->query($xpath);
		return $elements->length;
	}
	
	function missingNode($xpath){
		$elements = $this->xpath->query($xpath);
		if($elements->length==0){
			return true;
		}
		else {
			return false;
		}
	}
	
	public function setMetadmdSec($string){
		$this->dmdSecID = $string; 
	}
		
	function updateGoobiMetaXML($metadata_name,$metadata_value,$subfield='',$MapType='default',$xpath='//goobi:goobi',$whatToUpdate=0){
		#$whatToUpdate -> 0: All (Value + Attributes) 1: GND-Attributes
		#update a specific <goobi:metadata/> value identified by name-attribute.
		$elements = $this->xpath->query($xpath);
		setMapType($metadata_name,$MapType);
		for($i=0;$i<$elements->length;$i++)
		{
			$this->Item = $elements->item($i);
			switch($whatToUpdate){
				case 1:
					
					break;
			}
			/*
			$oldNode = $elements->item($i);
			$newGoobi_metadata = $this->dom->createElement('goobi:metadata',$metadata_value);
			$newGoobi_metadata->setAttribute('name',$metadata_name);
			$oldNode->parentNode->replaceChild($newGoobi_metadata,$oldNode);
			*/
		}
		$this->saveGoobiMetaXML();
		$this->__construct($this->xml);
		
		/*New comment */
	}

	public function setMapType($metadata_name='',$MapType='default'){
		switch($metadata_name){
			case 'Classification':
			case 'TitleDocMain':
			case 'TitleDocMainShort':
				$this->MapType = $metadata_name;
				break;
			default:
				$this->MapType = $MapType;
				break;
		}
	}
	
	function insertGoobiMetaXML($metadata_name,$metadata_value,$subfield='',$MapType='default',$xpath='//goobi:goobi'){
		#insert a new metadata field as last child of <goobi:goobi />
		$this->metadata_name = $metadata_name;
		$this->GND = '';
		
		setMapType($metadata_name,$MapType);
		
		if(is_array($metadata_value)){ 
			if(!empty($subfield)){
				$this->metadata_value = $metadata_value[$subfield]; 
			}
			if(array_key_exists(9,$metadata_value)){
				$this->GND = $metadata_value[9];
			}
		}
		else { 	$this->metadata_value = $metadata_value; }
		
		
		$this->parentNode = $this->xpath->query($xpath);
		$this->element = $this->dom->createElement('goobi:metadata');
		$this->element->setAttribute('name',$metadata_name);
		
		$this->writeMetadata();
		$this->parentNode->item(0)->appendChild($this->element);	
		$this->saveGoobiMetaXML();	
		$this->__construct($this->xml);
		echo "Set ".$this->metadata_name." with value: ".$this->metadata_value;
		if(!empty($this->GND)) { echo " ".$this->GND; }
		echo "\n";
	}
	
	public function writeMetadata(){
		switch($this->MapType){
			case 'person':
				$this->element->setAttribute('type','person');
				#var_dump($meta_arr[$j]);
				$author = explode(",",$this->metadata_value);
				$lastName = $this->dom->createElement('goobi:lastName',$author[0]);
				$this->element->appendChild($lastName);
				if(array_key_exists(1,$author)){
					$firstName = $this->dom->createElement('goobi:firstName',$author[1]);
					$this->element->appendChild($firstName);
				}
				$displayName = $this->dom->createElement('goobi:displayName',$this->metadata_value);
				$this->element->appendChild($displayName);
				if(preg_match('/(?<=\(DE-588\)).{5,9}/',$this->GND,$gnd_id)){
					#var_dump($gnd_id);
					$authorityID = $this->dom->createElement('goobi:authorityID',"gnd");
					$this->element->appendChild($authorityID);
					$authorityURI = $this->dom->createElement('goobi:authorityURI',"http://d-nb.info/gnd/");
					$this->element->appendChild($authorityURI);
					$authorityValue = $this->dom->createElement('goobi:authorityValue',$gnd_id[0]);
					$this->element->appendChild($authorityValue);
				}
				break;
			case 'TitleDocMain':
			case 'TitleDocMainShort':
				preg_match('/^(<<)([\w]*)(>>)/',$this->metadata_value,$match);
				$rest_titel = preg_split('/^(<<)([\w]*)(>>)/',$this->metadata_value);
				if(array_key_exists(2,$match)){
					if(trim($meta_arr[$j]['name'])=='TitleDocMain'){
						$this->element->nodeValue = $match[2]." ".trim($rest_titel[1]);
					}
					else {
						$this->element->nodeValue = trim($rest_titel[1]);
					}
				}
				else {
					$this->element->nodeValue = $this->metadata_value;
				}
				break;
			case 'Classification':
				$this->element->nodeValue = $this->metadata_value;
				if(preg_match('/(?<=\(DE-588\)).{5,9}/',$this->GND,$gnd_id)){
					$this->element->setAttribute('authority','gnd');
					$this->element->setAttribute('authorityURI','http://d-nb.info/gnd/');
					$this->element->setAttribute('valueURI',$gnd_id[0]);
				}
				break;
			default:
				#var_dump($meta_arr[$j]['value']);
				$this->element->nodeValue = $this->metadata_value;
		}
	}
	
	public function insertGND(){
		switch($this->MapType){
			case 'person':
				if(preg_match('/(?<=\(DE-588\)).{5,9}/',$this->GND,$gnd_id)){
					#var_dump($gnd_id);
					$authorityID = $this->dom->createElement('goobi:authorityID',"gnd");
					$this->element->appendChild($authorityID);
					$authorityURI = $this->dom->createElement('goobi:authorityURI',"http://d-nb.info/gnd/");
					$this->element->appendChild($authorityURI);
					$authorityValue = $this->dom->createElement('goobi:authorityValue',$gnd_id[0]);
					$this->element->appendChild($authorityValue);
				}
				break;
			case 'Classification':
				if(preg_match('/(?<=\(DE-588\)).{5,9}/',$this->GND,$gnd_id)){
					$this->element->setAttribute('authority','gnd');
					$this->element->setAttribute('authorityURI','http://d-nb.info/gnd/');
					$this->element->setAttribute('valueURI',$gnd_id[0]);
				}
				break;
		
		}
	}
	
	function insertMetsStructMap($id,$type){
		#Insert the first StructureNode in meta_anchor.xml
		#$id and $type have to been arrays where "0" -> contains Content for TopStruct, "1" - contains content for the firstchild
		$parent = $this->xpath->query('//mets:mets');
		$metsStructMap = $this->dom->createElement('mets:structMap');
		$metsStructMap->setAttribute('TYPE','LOGICAL');
		$parent->item(0)->appendChild($metsStructMap);
		
		$metsdiv = $this->dom->createElement('mets:div');
		$metsdiv->setAttribute('DMDID',$id[0]);
		$dmdid = preg_match('/(?<=DMD)LOG_[\d]{4}/',$id[0],$match);
		$metsdiv->setAttribute('ID',$match[0]);
		$metsdiv->setAttribute('TYPE',$type[0]);
		$metsStructMap->appendChild($metsdiv);
		
		$metsdiv1 = $this->dom->createElement('mets:div');
		$dmdid = preg_match('/(?<=DMD)LOG_[\d]{4}/',$id[1],$match);
		$metsdiv1->setAttribute('ID',$match[0]);
		$metsdiv1->setAttribute('TYPE',$type[1]);
		$metsdiv->appendChild($metsdiv1);
		
		$metsmptr = $this->dom->createElement('mets:mptr');
		$metsmptr->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink','http://www.w3.org/1999/xlink');
		$metsmptr->setAttribute('LOCTYPE','URL');
		$metsmptr->setAttribute('xlink:href','');
		$metsdiv1->appendChild($metsmptr);
		
		$this->saveGoobiMetaXML();
	}
	
	function insertMetsDmdSecPhy($id,$prozess_id,$vorgang_titel){
		
		$parent = $this->xpath->query('//mets:mets');
		$metsdmdSec = $this->dom->createElement('mets:dmdSec');
		$metsdmdSec->setAttribute('ID',$id);
		$parent->item(0)->appendChild($metsdmdSec);
		
		$mdWrap = $this->dom->createElement('mets:mdWrap');
		$mdWrap->setAttribute('MDTYPE','MODS');
		$metsdmdSec->appendChild($mdWrap);
		
		$xmlData = $this->dom->createElement('mets:xmlData');
		$mdWrap->appendChild($xmlData);
		
		$mods = $this->dom->createElementNS('http://www.loc.gov/mods/v3', 'mods:mods');
		$xmlData->appendChild($mods);
		
		$extension = $this->dom->createElement('mods:extension');
		$mods->appendChild($extension);
		
		$goobi = $this->dom->createElementNS('http://meta.goobi.org/v1.5.1/', 'goobi:goobi');
		$extension->appendChild($goobi);
		
		$goobimetadata = $this->dom->createElement('goobi:metadata','AK Bibliothek Wien für Sozialwissenschaften');
		$goobimetadata->setAttribute('name','PhysicalLocation');
		$goobi->appendChild($goobimetadata);
		
		$goobimetadata = $this->dom->createElement('goobi:metadata',"file:///opt/digiverso/goobi/metadata/".$prozess_id."/images/".$vorgang_titel."_tif");
		$goobimetadata->setAttribute('name','pathimagefiles');
		$goobi->appendChild($goobimetadata);
		
		$this->saveGoobiMetaXML();
	}
	
	function insertMetsStructMap_meta($type){
		$parent = $this->xpath->query('//mets:mets');
		$metsStructMap = $this->dom->createElement('mets:structMap');
		$metsStructMap->setAttribute('TYPE','LOGICAL');
		$parent->item(0)->appendChild($metsStructMap);
		
		$metsdiv = $this->dom->createElement('mets:div');
		$metsdiv->setAttribute('ID',"LOG_0002");
		$metsdiv->setAttribute('TYPE',$type[0]);
		$metsStructMap->appendChild($metsdiv);
		
		$metsdiv1 = $this->dom->createElement('mets:div');
		#$dmdid = preg_match('/(?<=DMD)LOG_[\d]{4}/',$id[1],$match);
		$metsdiv1->setAttribute('ID',"LOG_0003");
		$metsdiv1->setAttribute('DMDID',"DMDLOG_0001");
		$metsdiv1->setAttribute('TYPE',$type[1]);
		$metsdiv->appendChild($metsdiv1);
		
		$metsmptr = $this->dom->createElement('mets:mptr');
		$metsmptr->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink','http://www.w3.org/1999/xlink');
		$metsmptr->setAttribute('LOCTYPE','URL');
		$metsmptr->setAttribute('xlink:href','');
		$metsdiv->appendChild($metsmptr);
		
		$metsStructMap = $this->dom->createElement('mets:structMap');
		$metsStructMap->setAttribute('TYPE','PHYSICAL');
		$parent->item(0)->appendChild($metsStructMap);
		$metsdiv = $this->dom->createElement('mets:div');
		$metsdiv->setAttribute('ID',"PHYS_0000");
		$metsdiv->setAttribute('DMDID',"DMDPHYS_0000");
		$metsdiv->setAttribute('TYPE',"BoundBook");
		$metsStructMap->appendChild($metsdiv);
		
		$this->saveGoobiMetaXML();
	}
	
	function setanchorID($acnr){
		$element = $this->xpath->query('//goobi:goobi');
		$metadata = $this->dom->createElement('goobi:metadata',$acnr);
		$metadata->setAttribute('name','CatalogIDDigital');
		$metadata->setAttribute('anchorId','true');
		$element->item(0)->appendChild($metadata);
		$this->saveGoobiMetaXML();
	}
	
}

?>

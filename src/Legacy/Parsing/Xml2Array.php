<?php

namespace App\Legacy\Parsing;

class Xml2Array {
	private $arrOutput = [];
	private $resParser;
	private $strXmlData;

	public function parse($strInputXML) {
		$this->resParser = xml_parser_create();
		
		xml_set_object($this->resParser, $this);
		xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");
		
		xml_set_character_data_handler($this->resParser, "tagData");
		
		$this->strXmlData = xml_parse($this->resParser, $strInputXML);
		
		if (!$this->strXmlData) {
			die(sprintf("XML error: %s at line %d",
				xml_error_string(xml_get_error_code($this->resParser)),
				xml_get_current_line_number($this->resParser))
			);
		}
					   
		xml_parser_free($this->resParser);
		
		return $this->arrOutput;
	}

	private function tagOpen($parser, $name, $attrs) {
		$this->arrOutput[] = [ "name" => $name, "attrs" => $attrs ];
	}
   
	private function tagData($parser, $tagData) {
		if (trim($tagData)) {
			$last = count($this->arrOutput)-1;
			if (isset($this->arrOutput[$last]['tagData'])) {
				$this->arrOutput[$last]['tagData'] .= $tagData;
			} 
			else {
				$this->arrOutput[$last]['tagData'] = $tagData;
			}
		}
	}
	
	private function tagClosed($parser, $name) {
		$this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];
		array_pop($this->arrOutput);
	}
}

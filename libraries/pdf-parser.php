<?php 

class pdf_parser {

	function __construct($filename, $data_format = NULL) {
		$this->data = $this->parseForm($filename, $data_format);
	}

	function parseForm($file_path, $data_format = '')
	{
		$start = false;
		$content = file_get_contents($file_path);

		/**
		* Split apart the PDF document into sections. We will address each
		* section separately.
		*/
		$a_obj    = $this->getDataArray($content, 'obj', 'endobj');
		$j        = 0;
		$a_chunks = array();
		/**
		* Attempt to extract each part of the PDF document into a 'filter'
		* element and a 'data' element. This can then be used to decode the
		* data.
		*/
		foreach ($a_obj as $k => $obj) {
			
			$a_filter = $this->getDataArray($obj, '<<', '>>');
			if (is_array($a_filter) && isset($a_filter[0])) {
				$a_chunks[$j]['filter'] = $a_filter[0];
				$a_data = $this->getDataArray($obj, 'stream', 'endstream');
				
				if (is_array($a_data) && isset($a_data[0])) {
					$a_chunks[$j]['data'] = trim(substr($a_data[0], strlen('stream'), strlen($a_data[0]) - strlen('stream') - strlen('endstream')));
				}
				$j++;
			}
		}
		$result_data = null;
		// decode the chunks
		foreach ($a_chunks as $key => $chunk) {
		// Look at each chunk decide if we can decode it by looking at the contents of the filter
			if (isset($chunk['data'])) {
				// look at the filter to find out which encoding has been used
				//if($key < 200)	print_r(zlib_decode ($chunk['data']));
				//if($key < 200)	print_r(gzdeflate ($chunk['data']));
				
				if (strpos($chunk['filter'], 'FlateDecode') !== false) {
					// Use gzuncompress but suppress error messages.
					$data =@ gzuncompress($chunk['data']);
				}
				else if (strpos($chunk['filter'], '<>') !== false) {
					$data =@ zlib_decode ($chunk['data']);
				}
				else {
					$data = $chunk['data'];
				}
				if (trim($data) != '') {
					// If we got data then attempt to extract it.
					if (strpos($data, '/CIDInit') === 0) {
						continue;
					}
					$text  = '';
					$lines = explode("\n", $data);
					foreach ($lines as $line) {
						$line = trim($line);
						/* Stream: Our requirement is only XFA form data: START **/
						if(stristr($line, '<xfa:data')!==FALSE && stristr($line, '<xfa:datasets')===FALSE) {
							$start = true;
						}
						if($start){
							$collected[] = $line;
						}
						if(stristr($line, '/xfa:data')!==FALSE && stristr($line, '/xfa:datasets')===FALSE) {
							$start = false;
						}
						/* Stream: Our requirement is only XFA form data: END */
						/* Return from here, because we dont need the more data from the PDF */
					}
					$start = false;
				}
			}
		}
		$tags = implode('', $collected);

		/* Fetch only the needed node data */
		preg_match_all('/<xfa:data>(.*?)<\/xfa:data/', $tags, $match); 
		$xmlstring = $match[1][1];

		/* Sometimes its taking the closing tag from the last element to here */
		if($xmlstring{0}=='>'){ $xmlstring = substr($xmlstring, 1); }
		/* Sometimes its missing the closing tag becuase of the next stream to here */
		if($xmlstring{strlen($xmlstring)-1}!='>'){ $xmlstring .= '>'; }

		/* Remove special characters from the tags <frm:data> */
		$xmlstring = preg_replace(array('/<([a-zA-Z0-9 _-])+[@|:|,|*|!|]/', '/<\/([a-zA-Z0-9 _-])+[@|:|,|*|!|]/'), array('<','</'), $xmlstring);
		/* Load the xml data and get it parsed */
		$xml = simplexml_load_string(utf8_encode((string)$xmlstring), "SimpleXMLElement", LIBXML_NOCDATA);

		if('' == $data_format){
			return $xml;
		}
		$json = json_encode($xml);
		if('json' == $data_format){
			$json = json_encode($xml);
			return $json;
		}
		if('array' == $data_format){
			return json_decode($json, true);
		}

	}
	/**
	* Convert a section of data into an array, separated by the start and end words.
	*
	* @param  string $data       The data.
	* @param  string $start_word The start of each section of data.
	* @param  string $end_word   The end of each section of data.
	* @return array              The array of data.
	*/
	function getDataArray($data, $start_word, $end_word)
	{
		$start     = 0;
		$end       = 0;
		$a_results = array();
		while ($start !== false && $end !== false) {
			$start = strpos($data, $start_word, $end);
			$end   = strpos($data, $end_word, $start);
			if ($end !== false && $start !== false) {
				// data is between start and end
				$a_results[] = substr($data, $start, $end - $start + strlen($end_word));
			}
		}
		return $a_results;
	}
}

/*
$obj = new pdf_parser('/var/www/09-Form CHG-1-05072016.pdf', 'array');
print_r($obj->data);
*/
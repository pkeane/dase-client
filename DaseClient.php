<?php

class DaseClient_Exception extends Exception {}

class DaseClient 
{
	private $coll;
	private $dase_url;
	private $return_php;
	private $username;
	private $password;
	public static $mime_types = array(
		'application/msword',
		'application/pdf',
		'application/xml',
		'application/xslt+xml',
		'audio/mpeg',
		'audio/mpg',
		'image/gif',
		'image/jpeg',
		'image/png',
		'image/tiff',
		'text/css',
		'text/html',
		'text/plain',
		'text/xml',
		'video/mp4',
		'video/quicktime',
	);

	/**
	 * this is a simple class that allows quick-and-easy access to a
	 * DASe collection. It also makes use of the Services_JSON class,
	 * which should be unnecessary w/ PHP 5.2+
	 *
	 * sample:
	 *
	 * include 'DaseClient.php';
	 * 
	 * $client = new DaseClient('keanepj');
	 * $res = $client->search('st*');
	 * 
	 * foreach ($res->items as $item) {
	 * 	if (isset($item->metadata->title)) {
	 * 		print $item->metadata->title[0]."\n";
	 * 	}
	 * }
	 *
	 */

	public function __construct($collection_ascii_id,$return_php=true,$dase_url='http://dasebeta.laits.utexas.edu')
	{
		$this->dase_url = $dase_url;
		$this->coll = $collection_ascii_id;
		$this->return_php = $return_php;
	}

	public function setAuth($username,$password) 
	{
		$this->username = $username;
		$this->password = $password;
	}

	public function search($q,$max=500)
	{
		$q = urlencode($q);
		$search_url = $this->dase_url.'/collection/'.$this->coll.'/search.json?max='.$max.'&q='.$q;
		$res = self::get($search_url);
		if ('200' == $res[0]) {
			if ($this->return_php) {
				return $this->json2Php($res[1]);
			} else {
				return $res[1];
			}
		}
	}

	public function getCollectionAttributes()
	{
		$url = $this->dase_url.'/collection/'.$this->coll.'/attributes.json';
		$res = self::get($url,$this->username,$this->password);
		if ('200' == $res[0]) {
			if ($this->return_php) {
				return $this->json2Php($res[1]);
			} else {
				return $res[1];
			}
		}
	}

	public function getAttributeValues($att)
	{
		$url = $this->dase_url.'/attribute/'.$this->coll.'/'.$att.'.json';
		$res = self::get($url,$this->username,$this->password);
		if ('200' == $res[0]) {
			if ($this->return_php) {
				return $this->json2Php($res[1]);
			} else {
				return $res[1];
			}
		}
	}

	public function postFileToCollection($file_path,$metadata=array(),$check_for_dups=true) 
	{
		if (!$this->username || !$this->password) {
			throw new DaseClient_Exception('must set username and password');
		}
		$mime = $this->getMime($file_path);
		if (!in_array($mime,self::$mime_types)) {
			throw new DaseClient_Exception($mime.' is not an authorized file type');
		}
		if ($check_for_dups) {
			$md5 = md5_file($file_path);
			$check_url = $this->dase_url.'/collection/'.$this->coll.'/items/by/md5/'.$md5.'.txt';
			$res = self::get($check_url);
			if ('200' == $res[0]) {
				return array('n/a','duplicate file');
			}
		}
		$url = $this->dase_url.'/media/'.$this->coll;
		$body = file_get_contents($file_path);
		//resp is an array of code & content
		$resp = self::post($url,$body,$this->username,$this->password,$mime);
		if ('201' == $resp[0]) {
			$json_members = array();
			$metadata_url = self::getLinkHref($resp[1],'http://daseproject.org/relation/edit-metadata'); 
			foreach ($metadata as $att => $val) {
				if ($att && $val) {
					$json_members[] = '"'.$att.'":"'.$val.'"';
				}
			}
			$json = '{'.join(',',$json_members).'}';
			//check response
			$resp = self::post($metadata_url,$json,$this->username,$this->password,'application/json');
		}
		return $resp;
	}

	public function getFilePaths($directory)
	{
		$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
		$files = array();
		foreach ($dir as $file) {
			$file_path = $file->getPathname();
			$mime = self::getMime($file_path);
			if (in_array($mime,self::$mime_types)) {
				$files[] = $file_path;
			}
		}
		return $files;
	}

	private function json2Php($json)
	{
		$ver = explode( '.', PHP_VERSION );
		$version = $ver[0] . $ver[1] . $ver[2];
		if ($version >= 520) {
		//	return json_decode($json);
			//	json_decode not working 
			$js = new Services_JSON();
			return $js->decode($json);
		} else {
			$js = new Services_JSON();
			return $js->decode($json);
		}

	}

	public static function get($url,$user='',$pass='')
	{
		//todo: error handling
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERPWD,$user.':'.$pass);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);  
		// returns status code && response body
		return array($info['http_code'],$result);
	}

	public static function put($url,$body,$user,$pass,$mime_type='')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_USERPWD,$user.':'.$pass);
		if ($mime_type) {
			$headers  = array(
				"Content-Type: $mime_type"
			);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);  
		return array($info['http_code'],$result);
	}

	public static function post($url,$body,$user,$pass,$mime_type='')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_USERPWD,$user.':'.$pass);
		if ($mime_type) {
			$headers  = array(
				"Content-Type: $mime_type"
			);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		//not using error just now
		$error = curl_error($ch);
		curl_close($ch);  
		return array($info['http_code'],$result);
	}

	public static function getMime($file_path) 
	{
		//function is deprecated, so should be replaced
		// at some point
		return mime_content_type($file_path);
	}

	public static function getLinkHref($atom_entry,$rel) 
	{
		$dom = new DOMDocument('1.0','utf-8');
		if (is_file($atom_entry)) {
			$dom->load($atom_entry);
		} else {
			$dom->loadXml($atom_entry);
		}
		$x = new DomXPath($dom);
		$x->registerNamespace('atom','http://www.w3.org/2005/Atom');
		$xpath = "atom:link[@rel='$rel']";
		$nodeList = $x->query($xpath);
		return $nodeList->item(0)->getAttribute('href');
	}
}

/**
 * @package     Services_JSON
 * @author      Michal Migurski <mike-json@teczno.com>
 * @author      Matt Knapp <mdknapp[at]gmail[dot]com>
 * @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
 * @copyright   2005 Michal Migurski
 * @version     CVS: $Id: JSON.php,v 1.31 2006/06/28 05:54:17 migurski Exp $
 * @license     http://www.opensource.org/licenses/bsd-license.php
 * @link        http://pear.php.net/pepr/pepr-proposal-show.php?id=198
 */

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_SLICE',   1);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_STR',  2);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_ARR',  3);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_OBJ',  4);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_CMT', 5);

/**
 * Behavior switch for Services_JSON::decode()
 */
define('SERVICES_JSON_LOOSE_TYPE', 16);

/**
 * Behavior switch for Services_JSON::decode()
 */
define('SERVICES_JSON_SUPPRESS_ERRORS', 32);

class Services_JSON
{
	/**
	 * constructs a new JSON instance
	 *
	 * @param    int     $use    object behavior flags; combine with boolean-OR
	 *
	 possible values:
	 - SERVICES_JSON_LOOSE_TYPE:  loose typing.
	 "{...}" syntax creates associative arrays
	 instead of objects in decode().
	 - SERVICES_JSON_SUPPRESS_ERRORS:  error suppression.
	 Values which can't be encoded (e.g. resources)
	 appear as NULL instead of throwing errors.
	 By default, a deeply-nested resource will
	 bubble up with an error, so all return values
	 from encode() should be checked with isError()
	 */
	function Services_JSON($use = 0)
	{
		$this->use = $use;
	}

    /**
     * convert a string from one UTF-16 char to one UTF-8 char
     *
     * Normally should be handled by mb_convert_encoding, but
     * provides a slower PHP-only method for installations
     * that lack the multibye string extension.
     *
     * @param    string  $utf16  UTF-16 character
     * @return   string  UTF-8 character
     * @access   private
     */
    function utf162utf8($utf16)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        }

        $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

        switch(true) {
        case ((0x7F & $bytes) == $bytes):
            // this case should never be reached, because we are in ASCII range
            // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
            return chr(0x7F & $bytes);

        case (0x07FF & $bytes) == $bytes:
            // return a 2-byte UTF-8 character
            // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
            return chr(0xC0 | (($bytes >> 6) & 0x1F))
                . chr(0x80 | ($bytes & 0x3F));

        case (0xFFFF & $bytes) == $bytes:
            // return a 3-byte UTF-8 character
            // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
            return chr(0xE0 | (($bytes >> 12) & 0x0F))
                . chr(0x80 | (($bytes >> 6) & 0x3F))
                . chr(0x80 | ($bytes & 0x3F));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }


    /**
     * convert a string from one UTF-8 char to one UTF-16 char
     *
     * Normally should be handled by mb_convert_encoding, but
     * provides a slower PHP-only method for installations
     * that lack the multibye string extension.
     *
     * @param    string  $utf8   UTF-8 character
     * @return   string  UTF-16 character
     * @access   private
     */
    function utf82utf16($utf8)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }

        switch(strlen($utf8)) {
        case 1:
            // this case should never be reached, because we are in ASCII range
            // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
            return $utf8;

        case 2:
            // return a UTF-16 character from a 2-byte UTF-8 char
            // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
            return chr(0x07 & (ord($utf8{0}) >> 2))
                . chr((0xC0 & (ord($utf8{0}) << 6))
                | (0x3F & ord($utf8{1})));

        case 3:
            // return a UTF-16 character from a 3-byte UTF-8 char
            // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
            return chr((0xF0 & (ord($utf8{0}) << 4))
                | (0x0F & (ord($utf8{1}) >> 2)))
                    . chr((0xC0 & (ord($utf8{1}) << 6))
                        | (0x7F & ord($utf8{2})));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }


	/**
	 * reduce a string by removing leading and trailing comments and whitespace
	 *
	 * @param    $str    string      string value to strip of comments and whitespace
	 *
	 * @return   string  string value stripped of comments and whitespace
	 * @access   private
	 */
	function reduce_string($str)
	{
		$str = preg_replace(array(

			// eliminate single line comments in '// ...' form
			'#^\s*//(.+)$#m',

			// eliminate multi-line comments in '/* ... */' form, at start of string
			'#^\s*/\*(.+)\*/#Us',

			// eliminate multi-line comments in '/* ... */' form, at end of string
			'#/\*(.+)\*/\s*$#Us'

		), '', $str);

		// eliminate extraneous space
		return trim($str);
	}

	/**
	 * decodes a JSON string into appropriate variable
	 *
	 * @param    string  $str    JSON-formatted string
	 *
	 * @return   mixed   number, boolean, string, array, or object
	 *                   corresponding to given JSON input string.
	 *                   See argument 1 to Services_JSON() above for object-output behavior.
	 *                   Note that decode() always returns strings
	 *                   in ASCII or UTF-8 format!
	 * @access   public
	 */
	function decode($str)
	{
		$str = $this->reduce_string($str);

		switch (strtolower($str)) {
		case 'true':
			return true;

		case 'false':
			return false;

		case 'null':
			return null;

		default:
			$m = array();

			if (is_numeric($str)) {
				// Lookie-loo, it's a number

				// This would work on its own, but I'm trying to be
				// good about returning integers where appropriate:
				// return (float)$str;

				// Return float or int, as appropriate
				return ((float)$str == (integer)$str)
					? (integer)$str
					: (float)$str;

			} elseif (preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2]) {
				// STRINGS RETURNED IN UTF-8 FORMAT
				$delim = substr($str, 0, 1);
				$chrs = substr($str, 1, -1);
				$utf8 = '';
				$strlen_chrs = strlen($chrs);

				for ($c = 0; $c < $strlen_chrs; ++$c) {

					$substr_chrs_c_2 = substr($chrs, $c, 2);
					$ord_chrs_c = ord($chrs{$c});

					switch (true) {
					case $substr_chrs_c_2 == '\b':
						$utf8 .= chr(0x08);
						++$c;
						break;
					case $substr_chrs_c_2 == '\t':
						$utf8 .= chr(0x09);
						++$c;
						break;
					case $substr_chrs_c_2 == '\n':
						$utf8 .= chr(0x0A);
						++$c;
						break;
					case $substr_chrs_c_2 == '\f':
						$utf8 .= chr(0x0C);
						++$c;
						break;
					case $substr_chrs_c_2 == '\r':
						$utf8 .= chr(0x0D);
						++$c;
						break;

					case $substr_chrs_c_2 == '\\"':
					case $substr_chrs_c_2 == '\\\'':
					case $substr_chrs_c_2 == '\\\\':
					case $substr_chrs_c_2 == '\\/':
						if (($delim == '"' && $substr_chrs_c_2 != '\\\'') ||
							($delim == "'" && $substr_chrs_c_2 != '\\"')) {
								$utf8 .= $chrs{++$c};
							}
						break;

					case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $c, 6)):
						// single, escaped unicode character
						$utf16 = chr(hexdec(substr($chrs, ($c + 2), 2)))
							. chr(hexdec(substr($chrs, ($c + 4), 2)));
						$utf8 .= $this->utf162utf8($utf16);
						$c += 5;
						break;

					case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
						$utf8 .= $chrs{$c};
						break;

					case ($ord_chrs_c & 0xE0) == 0xC0:
						// characters U-00000080 - U-000007FF, mask 110XXXXX
						//see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
						$utf8 .= substr($chrs, $c, 2);
						++$c;
						break;

					case ($ord_chrs_c & 0xF0) == 0xE0:
						// characters U-00000800 - U-0000FFFF, mask 1110XXXX
						// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
						$utf8 .= substr($chrs, $c, 3);
						$c += 2;
						break;

					case ($ord_chrs_c & 0xF8) == 0xF0:
						// characters U-00010000 - U-001FFFFF, mask 11110XXX
						// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
						$utf8 .= substr($chrs, $c, 4);
						$c += 3;
						break;

					case ($ord_chrs_c & 0xFC) == 0xF8:
						// characters U-00200000 - U-03FFFFFF, mask 111110XX
						// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
						$utf8 .= substr($chrs, $c, 5);
						$c += 4;
						break;

					case ($ord_chrs_c & 0xFE) == 0xFC:
						// characters U-04000000 - U-7FFFFFFF, mask 1111110X
						// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
						$utf8 .= substr($chrs, $c, 6);
						$c += 5;
						break;

					}

				}

				return $utf8;

			} elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
				// array, or object notation

				if ($str{0} == '[') {
					$stk = array(SERVICES_JSON_IN_ARR);
					$arr = array();
				} else {
					if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
						$stk = array(SERVICES_JSON_IN_OBJ);
						$obj = array();
					} else {
						$stk = array(SERVICES_JSON_IN_OBJ);
						$obj = new stdClass();
					}
				}

				array_push($stk, array('what'  => SERVICES_JSON_SLICE,
					'where' => 0,
					'delim' => false));

				$chrs = substr($str, 1, -1);
				$chrs = $this->reduce_string($chrs);

				if ($chrs == '') {
					if (reset($stk) == SERVICES_JSON_IN_ARR) {
						return $arr;

					} else {
						return $obj;

					}
				}

				//print("\nparsing {$chrs}\n");

				$strlen_chrs = strlen($chrs);

				for ($c = 0; $c <= $strlen_chrs; ++$c) {

					$top = end($stk);
					$substr_chrs_c_2 = substr($chrs, $c, 2);

					if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == SERVICES_JSON_SLICE))) {
						// found a comma that is not inside a string, array, etc.,
						// OR we've reached the end of the character list
						$slice = substr($chrs, $top['where'], ($c - $top['where']));
						array_push($stk, array('what' => SERVICES_JSON_SLICE, 'where' => ($c + 1), 'delim' => false));
						//print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

						if (reset($stk) == SERVICES_JSON_IN_ARR) {
							// we are in an array, so just push an element onto the stack
							array_push($arr, $this->decode($slice));

						} elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
							// we are in an object, so figure
							// out the property name and set an
							// element in an associative array,
							// for now
							$parts = array();

							if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
								// "name":value pair
								$key = $this->decode($parts[1]);
								$val = $this->decode($parts[2]);

								if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
									$obj[$key] = $val;
								} else {
									$obj->$key = $val;
								}
							} elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
								// name:value pair, where name is unquoted
								$key = $parts[1];
								$val = $this->decode($parts[2]);

								if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
									$obj[$key] = $val;
								} else {
									$obj->$key = $val;
								}
							}

						}

					} elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != SERVICES_JSON_IN_STR)) {
						// found a quote, and we are not inside a string
						array_push($stk, array('what' => SERVICES_JSON_IN_STR, 'where' => $c, 'delim' => $chrs{$c}));
						//print("Found start of string at {$c}\n");

					} elseif (($chrs{$c} == $top['delim']) &&
						($top['what'] == SERVICES_JSON_IN_STR) &&
							((strlen(substr($chrs, 0, $c)) - strlen(rtrim(substr($chrs, 0, $c), '\\'))) % 2 != 1)) {
								// found a quote, we're in a string, and it's not escaped
								// we know that it's not escaped becase there is _not_ an
								// odd number of backslashes at the end of the string so far
								array_pop($stk);
								//print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");

							} elseif (($chrs{$c} == '[') &&
								in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
									// found a left-bracket, and we are in an array, object, or slice
									array_push($stk, array('what' => SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false));
									//print("Found start of array at {$c}\n");

								} elseif (($chrs{$c} == ']') && ($top['what'] == SERVICES_JSON_IN_ARR)) {
									// found a right-bracket, and we're in an array
									array_pop($stk);
									//print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

								} elseif (($chrs{$c} == '{') &&
									in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
										// found a left-brace, and we are in an array, object, or slice
										array_push($stk, array('what' => SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false));
										//print("Found start of object at {$c}\n");

									} elseif (($chrs{$c} == '}') && ($top['what'] == SERVICES_JSON_IN_OBJ)) {
										// found a right-brace, and we're in an object
										array_pop($stk);
										//print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

									} elseif (($substr_chrs_c_2 == '/*') &&
										in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
											// found a comment start, and we are in an array, object, or slice
											array_push($stk, array('what' => SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false));
											$c++;
											//print("Found start of comment at {$c}\n");

										} elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == SERVICES_JSON_IN_CMT)) {
											// found a comment end, and we're in one now
											array_pop($stk);
											$c++;

											for ($i = $top['where']; $i <= $c; ++$i)
												$chrs = substr_replace($chrs, ' ', $i, 1);

											//print("Found end of comment at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

										}

				}

				if (reset($stk) == SERVICES_JSON_IN_ARR) {
					return $arr;

				} elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
					return $obj;

				}
			}
		}
	}
}

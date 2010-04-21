<?php

class DaseClient_Exception extends Exception {}

class DaseClient 
{
	private $coll;
	private $dase_url;
	private $return;
	private $username;
	private $password;
	public static $mime_types = array(
		'application/msword',
		'application/ogg',
		'application/pdf',
		'application/xml',
		'application/xslt+xml',
		'audio/mpeg',
		'audio/mpg',
		'audio/ogg',
		'image/gif',
		'image/jpeg',
		'image/png',
		'image/tiff',
		'text/css',
		'text/html',
		'text/plain',
		'text/xml',
		'video/mp4',
		'video/ogg',
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

	public function __construct($collection_ascii_id,$return='json',$dase_url='https://daseupload.laits.utexas.edu')
	{
		$this->dase_url = $dase_url;
		$this->coll = $collection_ascii_id;
		$this->return = $return;
	}

	public function setAuth($username,$password) 
	{
		$this->username = $username;
		$this->password = $password;
	}

	public function setReturnFormat($json_or_php_or_atom) 
	{
		$this->return = $json_or_php_or_atom;
	}

	public function getUsername() 
	{
		return $this->username;
	}

	public function getPassword() 
	{
		return $this->password;
	}

    public function getDaseUrl()
    {
        return $this->dase_url;
    }

	//get all collections that the user is either manager or OR are public
	public function getUserCollections()
	{
		$url = $this->dase_url.'/user/'.$this->username.'/collections.json';
		$res = self::get($url,$this->username,$this->password);
		if ('200' == $res[0]['http_code']) {
			return $res[1];
		} else {
			return $res[0]['http_code'];
		}
	}

	public function searchCollections($q='',$collections=array(),$max=500,$start=0,$sort='')
	{
		//$collections is an array of collection_ascii_ids
		$q = urlencode($q);
		if($this->return == 'atom'){
			$search_url = $this->dase_url.'/search.atom?max='.$max.'&sort='.$sort.'&start='.$start.'&q='.$q;
		}
		else{
			$search_url = $this->dase_url.'/search.json?max='.$max.'&sort='.$sort.'&start='.$start.'&q='.$q;
		}
		$c_params = join('&c=',$collections);
		$search_url .= '&c='.$c_params;
		$res = self::get($search_url);
		if ('200' == $res[0]['http_code']) {
			if ($this->return == 'php') {
				return $this->json2Php($res[1]);
			} else {
				return $res[1];
			}
		}
	}

	public function getBySerialNumber($sernum)
    {
		$url = $this->dase_url.'/item/'.$this->coll.'/'.$sernum.'.json';
		$res = self::get($url,$this->username,$this->password);
		if ('200' == $res[0]['http_code']) {
			if ($this->return == 'php') {
				return $this->json2Php($res[1]);
			} else {
				return $res[1];
			}
		}
    }

	public function search($q='',$max=500,$start=0,$sort='')
	{
		$q = urlencode($q);
		if($this->return == 'atom'){
			$search_url = $this->dase_url.'/collection/'.$this->coll.'/search.atom?max='.$max.'&sort='.$sort.'&start='.$start.'&q='.$q;
		}
		else{
			$search_url = $this->dase_url.'/collection/'.$this->coll.'/search.json?max='.$max.'&sort='.$sort.'&start='.$start.'&q='.$q;
		}
		$res = self::get($search_url);
		if ('200' == $res[0]['http_code']) {
			if ($this->return == 'php') {
				return $this->json2Php($res[1]);
			} else {
				return $res[1];
			}
		}
	}

	public function getCollectionItemUris()
	{
		$url = $this->dase_url.'/collection/'.$this->coll.'/items.uris';
		$res = self::get($url);
		if ('200' == $res[0]['http_code']) {
			if ('php' == $this->return) {
				return $this->json2Php($res[1]);
			} else {
				return $res[1];
			}
		}
	}

	public function getCollectionArchiveUris()
	{
		$url = $this->dase_url.'/collection/'.$this->coll.'/archive.uris';
		$res = self::get($url);
		if ('200' == $res[0]['http_code']) {
			if ('php' == $this->return) {
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
		if ('200' == $res[0]['http_code']) {
			if ('php' == $this->return) {
				return $this->json2Php($res[1]);
			} else {
				return $res[1];
			}
		}
	}

	public function getCollectionAttributesAtom()
	{
		$url = $this->dase_url.'/collection/'.$this->coll.'/attributes.atom';
		$res = self::get($url,$this->username,$this->password);
		if ('200' == $res[0]['http_code']) {
			return $res[1];
		}
	}

	public function getAttributeValues($att)
	{
		$url = $this->dase_url.'/attribute/'.$this->coll.'/'.$att.'.json';
		$res = self::get($url,$this->username,$this->password);
		if ('200' == $res[0]['http_code']) {
			if ('php' == $this->return) {
				return $this->json2Php($res[1]);
			} else {
				return $res[1];
			}
		}
	}

	public function postAttributeToCollection($attribute_atom_entry) 
	{
		if (!$this->username || !$this->password) {
			throw new DaseClient_Exception('must set username and password');
		}
		$url = $this->dase_url.'/collection/'.$this->coll.'/attributes';
		$resp = self::post($url,$attribute_atom_entry,$this->username,$this->password,'application/atom+xml');
		return $resp[0]['http_code'];
	}

	public function postFileToCollection($file_path,$metadata=array(),$check_for_dups=true,$item_type='',$content='') 
	{
		if (!$this->username || !$this->password) {
			throw new DaseClient_Exception('must set username and password');
		}
		$mime = $this->getMime($file_path);
		$mime = array_shift(explode(';',$mime));
		if (!in_array($mime,self::$mime_types)) {
			throw new DaseClient_Exception($mime.' is not an authorized file type');
		}
		if ($check_for_dups) {
			$md5 = md5_file($file_path);
			$check_url = $this->dase_url.'/collection/'.$this->coll.'/items/by/md5/'.$md5.'.txt';
			$res = self::get($check_url);
			if ('200' == $res[0]['http_code']) {
				return array('n/a','duplicate file');
			}
		}
		$url = $this->dase_url.'/media/'.$this->coll;
		$body = file_get_contents($file_path);
		//resp is an array of code & content
		$resp = self::post($url,$body,$this->username,$this->password,$mime);
		$atom_media_entry = $resp[1];
		if ('201' == $resp[0]['http_code']) {
			$json_members = array();
			$metadata_url = self::getLinkByRel($atom_media_entry,'http://daseproject.org/relation/edit-metadata'); 
			foreach ($metadata as $att => $val) {
				if ($att && $val) {
					$json_members[] = '"'.$att.'":"'.$val.'"';
				}
			}
			$json = '{'.join(',',$json_members).'}';
			//check response
			$resp = self::post($metadata_url,$json,$this->username,$this->password,'application/json');
			if ($item_type) {
				$item_url = self::getLinkByRel($atom_media_entry,'up'); 
				$item_resp = self::get($item_url,$this->username,$this->password);
				$item_atom = $item_resp[1];
				$item_type_url = self::getLinkByRel($item_atom,'http://daseproject.org/relation/edit-item_type'); 
				$resp = self::post($item_type_url,$item_type,$this->username,$this->password,'text/plain');
			}
			if ($content) {
				$item_url = self::getLinkByRel($atom_media_entry,'up'); 
				$item_resp = self::get($item_url,$this->username,$this->password);
				$item_atom = $item_resp[1];
				$item_type_url = self::getLinkByRel($item_atom,'http://daseproject.org/relation/edit-content'); 
				$resp = self::post($item_type_url,$content,$this->username,$this->password,'text/plain');
			}
		}
		return $resp;
	}

	public function getFilePaths($directory)
	{
		$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
		$files = array();
		foreach ($dir as $file) {
			$fn = $file->getFilename();
			//skip files beginning w/ '._'
			if ('._' == substr($fn,0,2)) {
				continue;
			}
			$file_path = $file->getPathname();
			$mime = self::getMime($file_path);
			//print $file_path.$mime."\n";
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
			return json_decode($json);
		} else {
			$js = new Services_JSON();
			return $js->decode($json);
		}

	}

	public static function makeAtom($id,$title,$data) {
		$ns = 'http://www.w3.org/2005/Atom';
		$dom = new DOMDocument('1.0','utf-8');
		$root = $dom->appendChild($dom->createElementNS($ns,'entry'));
		$id_elem = $root->appendChild($dom->createElementNS($ns,'id'));
		$id_elem->appendChild($dom->createTextNode($id));
		$title_elem = $root->appendChild($dom->createElementNS($ns,'title'));
		$title_elem->appendChild($dom->createTextNode($title));
		foreach ($data as $k => $v) {
			if ($k && $v) {
				$category = $root->appendChild($dom->createElementNS($ns,'category'));
				$category->appendChild($dom->createTextNode($v));
				$category->setAttribute('scheme','http://daseproject.org/category/metadata');
				$category->setAttribute('term',$k);
			}
		}
		$dom->formatOutput = true;
		return $dom->saveXML();
	}

	public static function get($url,$user='',$pass='')
	{
		//print_r(func_get_args());
		//todo: error handling
		$ch = curl_init();
		if (strpos($url,'?')) {
			$url = $url .'&auth=http';
		} else {
			$url = $url .'?auth=http';
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERPWD,$user.':'.$pass);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$result = curl_exec($ch);
		//status is 'http_code'
		$info = curl_getinfo($ch);
		$error = curl_error($ch);
		curl_close($ch);  
		return array($info,$result,$error);
	}

	public static function put($url,$body,$user,$pass,$mime_type='')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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
		//status is 'http_code'
		$info = curl_getinfo($ch);
		$error = curl_error($ch);
		curl_close($ch);  
		return array($info,$result,$error);
	}

	public static function post($url,$body,$user,$pass,$mime_type='')
	{
		if (strpos($url,'?')) {
			$url = $url .'&auth=http';
		} else {
			$url = $url .'?auth=http';
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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
		//status is 'http_code'
		$info = curl_getinfo($ch);
		$error = curl_error($ch);
		curl_close($ch);  
		return array($info,$result,$error);
	}

	public static function delete($url,$user,$pass)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_USERPWD,$user.':'.$pass);
		$result = curl_exec($ch);
		//status is 'http_code'
		$info = curl_getinfo($ch);
		$error = curl_error($ch);
		curl_close($ch);  
		return array($info,$result,$error);
	}

	public static function getMime($file_path) 
	{
        //from http://forums.digitalpoint.com/showthread.php?t=522166
        $mtype = '';
        if (function_exists('mime_content_type')){
            $mtype = mime_content_type($file_path);
        }
        else if (function_exists('finfo_file')){
            $finfo = finfo_open(FILEINFO_MIME);
            $mtype = finfo_file($finfo, $file_path);
            finfo_close($finfo);  
        }
        if ($mtype == ''){
            $mtype = "application/force-download";
        }
        return $mtype;
    }

	public static function getEntries($atom_feed) 
	{
		$entries = array();
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($atom_feed);
		$atom_ns = 'http://www.w3.org/2005/Atom';
		foreach ($dom->getElementsByTagNameNS($atom_ns,'entry') as $el) {
			$newdoc = new DOMDocument('1.0', 'UTF-8');
			$newdoc->formatOutput = true;
			$new_el = $newdoc->importNode($el,true);
			$newdoc->appendChild($new_el);
			$entries[] = $newdoc->saveXML();
		}
		return $entries;
	}

	public static function getElementValue($atom,$element) 
	{
		//returen first one found
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($atom);
		$x = new DomXPath($dom);
		$x->registerNamespace('atom','http://www.w3.org/2005/Atom');
		$xpath = "//atom:$element";
		$nodeList = $x->query($xpath);
		foreach ($nodeList as $node) {
			return $node->nodeValue;
		}
	}

	public static function getCategoryTermByScheme($atom,$scheme) 
	{
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($atom);
		$x = new DomXPath($dom);
		$x->registerNamespace('atom','http://www.w3.org/2005/Atom');
		$xpath = "//atom:category[@scheme='$scheme']";
		$nodeList = $x->query($xpath);
		foreach ($nodeList as $node) {
			return $node->getAttribute('term');
		}
	}

	public static function getLinksByRel($atom,$rel) 
	{
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($atom);
		$x = new DomXPath($dom);
		$x->registerNamespace('atom','http://www.w3.org/2005/Atom');
		$xpath = "//atom:link[@rel='$rel']";
		$nodeList = $x->query($xpath);
		$links = array();
		foreach ($nodeList as $node) {
			$links[] = $node->getAttribute('href');
		}
		return $links;
	}

	public static function getLinkByRel($atom_entry,$rel) 
	{
		$dom = new DOMDocument('1.0','utf-8');
		//try {
		$dom->loadXml($atom_entry);
		//} catch (Exception $e) {
		//}
		$x = new DomXPath($dom);
		$x->registerNamespace('atom','http://www.w3.org/2005/Atom');
		$xpath = "//atom:link[@rel='$rel']";
		$nodeList = $x->query($xpath);
		$links = array();
		foreach ($nodeList as $node) {
			return $node->getAttribute('href');
		}
	}

	public static function getMediaBySize($atom_entry,$size) 
	{
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($atom_entry);
		$x = new DomXPath($dom);
		$x->registerNamespace('media','http://search.yahoo.com/mrss/');
		$xpath = "//media:category";
		$nodeList = $x->query($xpath);
		$links = array();
		foreach ($nodeList as $node) {
			if($node->nodeValue == $size){
				return $node->parentNode->getAttribute('url');
			}
		}
	}
	public static function getThumbnail($atom_entry) 
	{
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($atom_entry);
		$x = new DomXPath($dom);
		$x->registerNamespace('media','http://search.yahoo.com/mrss/');
		$xpath = "//media:thumbnail";
		$nodeList = $x->query($xpath);
		$links = array();
		foreach ($nodeList as $node) {
			return $node->getAttribute('url');
		}
	}

	public static function getLinkTitleByRel($atom_entry,$rel) 
	{
		$dom = new DOMDocument('1.0','utf-8');
		if (!@ $dom->loadXml($atom_entry)) {
			print $atom_entry; exit;
		}
		$x = new DomXPath($dom);
		$x->registerNamespace('atom','http://www.w3.org/2005/Atom');
		$xpath = "//atom:link[@rel='$rel']";
		$nodeList = $x->query($xpath);
		$links = array();
		foreach ($nodeList as $node) {
			return $node->getAttribute('title');
		}
	}

	function getAdminMetadata($atom_entry,$att = '') 
	{
		$metadata = array();
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($atom_entry);
		$atom_ns = 'http://www.w3.org/2005/Atom';
		$dase_ns = 'http://daseproject.org/ns/1.0';
		foreach ($dom->getElementsByTagNameNS($atom_ns,'category') as $el) {
			if ('http://daseproject.org/category/admin_metadata' == $el->getAttribute('scheme')) {
				$att_ascii_id = $el->getAttribute('term');
				$metadata[$att_ascii_id] = $el->nodeValue;
			}
		}
		if ($att) {
			if (isset($metadata[$att])) {
				return $metadata[$att];
			} else {
				return false;
			}
		}
		return $metadata;
	}

	public static function createCollection($host,$collection_name,$user,$pass) 
	{
		$ascii = self::dirify($collection_name);
		$entry = DaseClient::makeAtom($ascii,$collection_name,array());
		$entry = DaseClient::addCategory($entry,'collection','http://daseproject.org/category/entrytype');
		//could have been retrieved from base AtomPub service doc:
		$url = $host.'/collections';
		$resp = DaseClient::post($url,$entry,$user,$pass,'application/atom+xml');
		return $resp;
	}

	public static function dirify($str)
	{
		$str = strtolower(preg_replace('/[^a-zA-Z0-9_-]/','_',trim($str)));
		return preg_replace('/__*/','_',$str);
	}

    public static function makeSerialNumber($str)
    {
        if ($str) {
            //get just the last segment if it includes directory path
            $str = array_pop(explode('/',$str));
            $str = preg_replace('/[^a-zA-Z0-9_-]/','_',trim($str));
            $str = trim(preg_replace('/__*/','_',$str),'_');
            if (strlen($str) <= 50) {
                return $str;
            }
            return substr($str,0,50);
        } else {
            return null;
        }
    }

	public static function addCategory($atom_entry,$term,$scheme,$label='')
	{
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($atom_entry);
		$atom_ns = 'http://www.w3.org/2005/Atom';
		$cat = $dom->documentElement->appendChild($dom->createElementNS($atom_ns,'category'));
		$cat->setAttribute('term',$term);
		if ($scheme) {
			$cat->setAttribute('scheme',$scheme);
		}
		if ($label) {
			$cat->setAttribute('label',$label);
		}
		$dom->formatOutput = true;
		return $dom->saveXML();
	}

	function getMetadata($atom_entry,$att = '',$include_private_metadata=false) 
	{
		$metadata = array();
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($atom_entry);
		$atom_ns = 'http://www.w3.org/2005/Atom';
		$dase_ns = 'http://daseproject.org/ns/1.0';
		foreach ($dom->getElementsByTagNameNS($atom_ns,'category') as $el) {
			if ('http://daseproject.org/category/metadata' == $el->getAttribute('scheme')) {
				$v = array();
				$att_ascii_id = $el->getAttribute('term');
				$metadata[$att_ascii_id]['attribute_name'] = $el->getAttribute('label');
				$v['edit'] = $el->getAttributeNS($dase_ns,'edit-id');
				$v['id'] = array_pop(explode('/',$v['edit'])); //provides the last segment, i.e. value id
				$v['text'] = $el->nodeValue;
				$v['mod'] = $el->getAttributeNS($dase_ns,'mod');
				$v['modtype'] = $el->getAttributeNS($dase_ns,'modtype');
				$metadata[$att_ascii_id]['values'][] = $v;
				//easy access to first value
				if (1 == count($metadata[$att_ascii_id]['values'])) {
					$metadata[$att_ascii_id]['text'] = $v['text'];
					$metadata[$att_ascii_id]['edit'] = $v['edit'];
					$metadata[$att_ascii_id]['id'] = $v['id'];
				}
			}
			if ($include_private_metadata &&
				'http://daseproject.org/category/private_metadata' == $el->getAttribute('scheme')) {
					$att_ascii_id = $el->getAttribute('term');
					$metadata[$att_ascii_id]['attribute_name'] = $el->getAttribute('label');
					$v['edit'] = $el->getAttributeNS($dase_ns,'edit-id');
					$v['id'] = array_pop(explode('/',$v['edit'])); //provides the last segment, i.e. value id
					$v['text'] = $el->nodeValue;
					$v['mod'] = $el->getAttributeNS($dase_ns,'mod');
					$v['modtype'] = $el->getAttributeNS($dase_ns,'modtype');
					$metadata[$att_ascii_id]['values'][] = $v;
					//easy access to first value
					if (1 == count($metadata[$att_ascii_id]['values'])) {
						$metadata[$att_ascii_id]['text'] = $v['text'];
						$metadata[$att_ascii_id]['edit'] = $v['edit'];
						$metadata[$att_ascii_id]['id'] = $v['id'];
					}
				}
		}
		foreach ($dom->getElementsByTagNameNS($atom_ns,'link') as $el) {
			if(strpos($el->getAttribute('rel'), 'http://daseproject.org/relation/metadata-link/') !== false){
				$att_ascii_id = basename($el->getAttribute('rel'));
				$metadata[$att_ascii_id]['attribute_name'] = $el->getAttributeNS($dase_ns,'attribute');
				$v['edit'] = $el->getAttributeNS($dase_ns,'edit-id');
				$v['id'] = array_pop(explode('/',$v['edit'])); //provides the last segment, i.e. value id

				//matches link attribute
				$v['title'] = $el->getAttribute('title');

				//duplicated for cases when you don't care that it is a link
				// and so will be looking for 'text'
				$v['text'] = $el->getAttribute('title');

				$v['href'] = $el->getAttribute('href');
				$v['mod'] = $el->getAttributeNS($dase_ns,'mod');
				$v['modtype'] = $el->getAttributeNS($dase_ns,'modtype');
				$metadata[$att_ascii_id]['values'][] = $v;
				if (1 == count($metadata[$att_ascii_id]['values'])) {
					$metadata[$att_ascii_id]['title'] = $v['title'];
					$metadata[$att_ascii_id]['text'] = $v['title'];
					$metadata[$att_ascii_id]['href'] = $v['href'];
					$metadata[$att_ascii_id]['edit'] = $v['edit'];
					$metadata[$att_ascii_id]['id'] = $v['id'];
				}
			}
		}
		if ($att) {
			if (isset($metadata[$att])) {
				return $metadata[$att];
			} else {
				return false;
			}
		}
		return $metadata;
	}

	public static function promptForPassword($user)
	{
		print "enter password for user $user:\n";
		system('stty -echo');
		$password = trim(fgets(STDIN));
		system('stty echo');
		return $password; 
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


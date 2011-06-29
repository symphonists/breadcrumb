<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourceBreadcrumb extends Datasource {
		
		public $dsParamROOTELEMENT = 'breadcrumb';
		
		private $_database;
		
		public function about(){
			return array(
					 'name' => 'Breadcrumb',
					 'author' => array(
							'name' => 'Alistair Kearney',
							'website' => 'http://pointybeard.com',
							'email' => 'alistair@symphony21.com'),
					 'version' => '1.1',
					 'release-date' => '2011-06-29');	
		}
		
		public function __construct(&$parent, Array $env = null, $process_params=true){
			parent::__construct($parent, $env, $process_params);

			// Include ASDC, if it exists...
			if(!class_exists('ASDCLoader')) {
				try {
					if((include_once(EXTENSIONS . '/asdc/lib/class.asdc.php')) === FALSE) {
						// if the include did not raise any exception , raise a dummy one
						throw new Exception();
					}
				} catch (Exception $e) {
					throw new SymphonyErrorPage(
						__('Please make sure that the ASDC extension is installed and enabled at %s.', array('<code>' . EXTENSIONS . '/asdc/</code>')) 
						. '<br/><br/>' . 
						__('It\'s available at %s.', array('<a href="https://github.com/pointybeard/asdc/tree">github.com/pointybeard/asdc/tree</a>')), 
						__('ASDC not found')
					);
				}
				
			}
		}

		/**
		 * 
		 * Method called by Symphony in order to build the 
		 * @param $param_pool
		 * @return XMLElement
		 */
		public function grab(&$param_pool){
			
			// save the ext manager pointer
			$ext_man = $this->_Parent->ExtensionManager;						
			
			// detect if multilangual field AND language redirect is enabled
			$isMultiLangual = ($ext_man ->fetchStatus('multilingual_field') == EXTENSION_ENABLED && 
							   $ext_man ->fetchStatus('language_redirect') == EXTENSION_ENABLED);
			
			// add a ref to the Language redirect
			if ($isMultiLangual) {
				require_once (EXTENSIONS . '/language_redirect/lib/class.languageredirect.php');
			}
			
			// get the current page id
			$current_page_id = (int)$this->_env['param']['current-page-id'];
			
			// save the pointer to the db
			$db = ASDCLoader::instance();
			
			// prepare output
			$result = new XMLElement($this->dsParamROOTELEMENT);
			
			// create a pointer to the flat array
			$flat = null;
			
			try{
				// Try to get the flat array
				// i.e. ['my-post-post','member','my-blog'] for the /my-blog/member/my-post-post/ URL
				$flat = $this->getHierarchy($db, $current_page_id, $isMultiLangual);
			}
			catch(Exception $e){
				$this->appendError($result, vsprintf('%d: %s on query "%s"', $db->lastError()));
				// exit now
				return $result;
			}
			
			if (count($flat) > 0) {
				
				// we need to build the correct urls
				$this->buildPath($flat, $isMultiLangual);
				
				//  reverse order to have the parents first
				$flat = array_reverse($flat);
				
				// generate the output
				foreach ($flat as $value) {
					
					// @todo add a setting for that
					if (strlen($value['path']) > 0) {
						$this->appendPage($result, $value);
					}
				}
				
			} else {
				$this->appendError($result, __('No records found'));
			}
			
			// return xml result set
			return $result;
		}
		
		/**
		 * 
		 * Generate a "flat" view of the current page and ancestors
		 * return array of all pages, starting with the current page
		 * @param $db
		 * @param $current_page_id
		 * @param $isMultiLangual
		 */
		private function getHierarchy(&$db, $current_page_id, $isMultiLangual) {
			$flat = array();
			$cid = $current_page_id;
			
			$cols = "id, parent, title, handle";
			
			if ($isMultiLangual) {
				
				// current language
				$lg = LanguageRedirect::instance()->getLanguageCode();
				
				// modify SQL query
				$cols = "id, parent, page_lhandles_t_$lg as title, page_lhandles_h_$lg as handle";
			}
			
			// do it once, and then repeat if needed
			do {
				
				$results = $db->query("SELECT $cols FROM `tbl_pages` WHERE `id` = '{$cid}' LIMIT 1");
				
				if ($results->length() > 0) {
					$current = $results->current();
					
					// sanitize title
					$current->title = General::sanitize($current->title);
					
					// save the result in flat view
					array_push($flat, get_object_vars($current));
					
					// update pointer
					$cid = (int) $current->parent;
					
				} else {
					// exit
					$cid = -1; 
				}
				
				// clean meme
				$results = null;
			
			} while ($cid > 0);
			
			return $flat;
		}
		
		/**
		 * 
		 * Appends a new field 'path' in each array in $flat 
		 * @param array $flat
		 */
		private function buildPath(&$flat, $isMultiLangual) {
			$count = count($flat);
			
			for ($i = 0; $i < $count; $i++) { // for each element
				
				// pointer to the path to be build
				$path = '';
				
				for ($j = $i; $j < $count; $j++) { // iterate foward in order to build the path
					
					// handle to be prepend
					$handle = $flat[$j]['handle'];
					
					// if handle if not empty
					if (strlen($handle) > 0) {
						$path = $handle . '/' . $path;
					}
				}
				
				// assure dash is starting the path
				// @todo add a setting for that
				//$path = '/' . $path;
				
				if ($isMultiLangual && // then path starts with language Code
					strlen($path) > 1) { 
				
					// current language
					$lg = LanguageRedirect::instance()->getLanguageCode();
					
					// prepand $lg
					$path  = "$lg/" . $path;
				
				}
				
				// save path in array
				$flat[$i]['path'] = trim($path, '/');
			}
		}
		
		/**
		 * 
		 * Quickly appends a error xml node to the result
		 * @param XMLElement $result
		 * @param string $msg
		 */
		private function appendError(&$result, $msg) {
			$result->appendChild(new XMLElement('error', General::sanitize($msg)));
		}
		
		/**
		 * 
		 * Quickly appends a error xml node to the result
		 * @param XMLElement $result
		 * @param array $value
		 */
		private function appendPage(&$result, $value) {
			$result->appendChild(new XMLElement('page', 
									$value['title'], // value
									array('path'=>$value['path']) // attributes
								));
		}
		
	}


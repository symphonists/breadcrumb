<?php

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(EXTENSIONS . '/asdc/lib/class.asdc.php');
	
	Class datasourceBreadcrumb extends Datasource{
		
		public $dsParamROOTELEMENT = 'breadcrumb';
		
		private $_database;
		
		public function about(){
			return array(
					 'name' => 'Breadcrumb',
					 'author' => array(
							'name' => 'Alistair Kearney',
							'website' => 'http://pointybeard.com',
							'email' => 'alistair@symphony21.com'),
					 'version' => '1.0',
					 'release-date' => '2009-01-02');	
		}

		public function grab(&$param_pool){
			$result = new XMLElement($this->dsParamROOTELEMENT);
			
			$ext_man = $this->_Parent->ExtensionManager;						
			
			// detect if multilangual field AND language redirect is enabled
			$isMultiLangual = ($ext_man ->fetchStatus('multilingual_field') == EXTENSION_ENABLED && $ext_man ->fetchStatus('language_redirect') == EXTENSION_ENABLED);
			
			// add a ref to the Language redirect
			if ($isMultiLangual) {
				require_once (EXTENSIONS . '/language_redirect/lib/class.languageredirect.php');
			}
			
			$current_page_id = (int)$this->_env['param']['current-page-id'];
			
			$db = ASDCLoader::instance();
			
			try{
				$results = $db->query("SELECT * FROM `tbl_pages` WHERE `id` = '{$current_page_id}' LIMIT 1");
			}
			catch(Exception $e){
				$result->appendChild(new XMLElement('error', General::sanitize(vsprintf('%d: %s on query "%s"', $db->lastError()))));
				return $result;
			}
			
			while($results->length() > 0){
				
				$current = $results->current();
				
				$child = new XMLElement('page', $current->title,  array('path' => trim("{$current->path}/{$current->handle}", '/')));
				
				if ($isMultiLangual) {
					try {
						// current language
						$lg = LanguageRedirect::instance()->getLanguageCode();
						// get object as array
						$c = get_object_vars($current);
						// get the current handle
						$h = $c["page_lhandles_h_$lg"];
						
						// get the parent(s) handles
						$path = '';
						
						$child = new XMLElement('page', $c["page_lhandles_t_$lg"],  array('path' => trim("{$path}/{$h}", '/')));
					} catch (Exception $e) {
						// do nothing, leave the $child as is
					}
				}
				
				$result->prependChild($child);
				
				if(is_null($current->parent)) break;

				$results = $db->query(sprintf("SELECT * FROM `tbl_pages` WHERE `id` = '%d' LIMIT 1", (int)$current->parent));
				
			}

			return $result;
		}
		
		public function getLocalizedParentPath($current, $db, $lg) {
			
			$path = '';
			$results = NULL;
		
			do {
			
				if(is_null($current->parent)) return $path;
			
				$results = $db->query(sprintf("SELECT * FROM `tbl_pages` WHERE `id` = '%d' LIMIT 1", (int)$current->parent));
				
				$current = $results->current();
				
				// get object as array
				$c = get_object_vars($current);
				
				// prepend to path
				$path = '/' . $c["page_lhandles_h_$lg"] . $path;
			
			} while($results->length() > 0) ;
			
			return $path;
		}
	}


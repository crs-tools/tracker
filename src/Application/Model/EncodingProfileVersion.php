<?php
	
	class EncodingProfileVersion extends Model {
		
		const TABLE = 'tbl_encoding_profile_version';
		
		public $belongsTo = [
			'EncodingProfile' => [
				'foreign_key' => ['encoding_profile_id']/*,
				'select' => 'name AS encoding_profile_name'*/
			],
		];
		
		// Shortcuts from encoding profile
		public $hasMany = [
			'Properties' => [
				'class_name' => 'EncodingProfileProperties',
				'foreign_key' => ['encoding_profile_id']
			],
			'Ticket' => [
				'foreign_key' => ['encoding_profile_id']
			]
		];
		
		public $hasAndBelongsToMany = [
			'Project' => []
		];
		
		public function getJobfile(array $properties) {
			libxml_use_internal_errors(true);
			
			$template = new DOMDocument();
			
			// prepare template
			if (!$template->loadXML($this['xml_template'])) {
				throw EncodingProfileTemplateException::fromLibXMLErrors();
			}
			
			// Process templates as XSL
			
			$content = new DOMDocument('1.0', 'UTF-8');

			$parent = $content->createElement('properties');
			
			foreach ($properties as $name => $value) {
				$element = $content->createElement('property');
				$element->setAttribute('name', $name);
				$element->appendChild(new DOMText($value));
				
				$parent->appendChild($element);
			}
			
			$content->appendChild($parent);
			
			$processor = self::_getXSLTProcessor();
			
			if (!$processor->importStylesheet($template)) {
				throw EncodingProfileTemplateException::fromLibXMLErrors();
			}
			
			// pretty print
			$output = $processor->transformToDoc($content);
			$output->insertBefore($output->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="/xsl/jobstyle.xsl"'), $output->firstChild);
			
			if (!$output or !$output instanceOf DOMDocument) {
				throw EncodingProfileTemplateException::fromLibXMLErrors();
			}
			
			$output->formatOutput = true;
			$output->encoding = 'UTF-8';
			
			return $output->saveXml();
		}
		
		// TODO: move to Model validation
		public static function isTemplateValid($string) {
			libxml_use_internal_errors(true);
			
			try {
				$template = new DOMDocument();
			
				// prepare template
				if (!$template->loadXML($string)) {
					throw EncodingProfileTemplateException::fromLibXMLErrors();
				}
			
				$processor = self::_getXSLTProcessor();
			
				if (!$processor->importStylesheet($template)) {
					throw EncodingProfileTemplateException::fromLibXMLErrors();
				}
			} catch (EncodingProfileTemplateException $e) {
				return $e->getMessage();
			}
			
			return true;
		}
		
		private static function _getXSLTProcessor() {
			$processor = new XSLTProcessor();
			
			$processor->setSecurityPrefs(
				XSL_SECPREF_READ_FILE |
				XSL_SECPREF_WRITE_FILE |
				XSL_SECPREF_CREATE_DIRECTORY |
				XSL_SECPREF_READ_NETWORK |
				XSL_SECPREF_WRITE_NETWORK
			);
			
			return $processor;
		}
		
	}
	
	class EncodingProfileTemplateException extends RuntimeException {
		
		public static function fromLibXMLErrors() {
			$errors = libxml_get_errors();
			libxml_clear_errors();
			
			if (empty($errors)) {
				return new static('Unknown error');
			}
			
			$errors = array_map(function($error) {
				return sprintf(
					'%s, %d:%d',
					trim($error->message),
					$error->line,
					$error->column
				);
			}, $errors);
			
			return new static(implode('; ', $errors));
		}
		
	}
	
?>

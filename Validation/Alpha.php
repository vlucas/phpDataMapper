<?php
/**
 * $Id$
 * 
 * "Alpha" Validation Rule
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 * 
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 */
class phpDataMapper_Validation_Alpha implements phpDataMapper_Validation_IRule {
	/**
	 * Run validation rule
	 */
	public function run($value) {
		if(!empty($value) && !ctype_alpha($value)) {
			return false;
		} else {
			return true;
		}
	}
}
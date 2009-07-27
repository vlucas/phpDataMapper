<?php
/**
 * $Id$
 * 
 * "Required" Validation Rule
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 * 
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 */
class phpDataMapper_Validation_AlphaNumeric implements phpDataMapper_Validation_IRule {
	/**
	 * Run validation rule
	 */
	public function run($value) {
		if(!empty($value) && !ctype_alnum($value)) {
			return false;
		} else {
			return true;
		}
	}
}
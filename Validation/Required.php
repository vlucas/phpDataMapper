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
class phpDataMapper_Validation_Required implements phpDataMapper_Validation_IRule {
	/**
	 * Run validation rule
	 */
	public function run($value) {
		if(((is_null($value) || (is_string($value) && rtrim($value) == "")) && $value !== false)) {
			return false;
		} else {
			return true;
		}
	}
}
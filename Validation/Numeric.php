<?php
/**
 * $Id$
 * 
 * "Numeric" Validation Rule
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 * 
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 */
class phpDataMapper_Validation_Numeric implements phpDataMapper_Validation_IRule {
	/**
	 * Run validation rule
	 */
	public function run($value) {
		if(!empty($value) && !is_numeric($value)) {
			return false;
		} else {
			return true;
		}
	}
}
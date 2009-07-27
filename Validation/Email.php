<?php
/**
 * $Id$
 * 
 * "Email" Validation Rule
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 * 
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 */
class phpDataMapper_Validation_Email implements phpDataMapper_Validation_IRule {
	/**
	 * Run validation rule
	 */
	public function run($value) {
		if(!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
			return false;
		} else {
			return true;
		}
	}
}
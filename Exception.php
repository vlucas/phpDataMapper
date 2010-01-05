<?php
/**
 * Handles generic phpDataMapper errors
 *
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 * @link http://github.com/vlucas/phpDataMapper
 */
class phpDataMapper_Exception extends Exception
{
	public function getError()
	{
		// Show friendly error message
		$error = '<div style="font-size:14px; color:#000000;">';
		$error .= '<div style="padding:8px; background:#eee; font:\'Courier New\', Courier, mono; font-weight:bold;">';
		$error .= $this->getMessage();
		$error .= '</div>';
		
		// Show stack trace if in debug mode
		if($debug = true) { // debug always on for now... (assignment)
			$error .= '<div style="font-size:12px; padding:2px 8px; background:#FFFFCC; font:\'Courier New\', Courier, mono"><pre>';
			$error .= "Code: " . $this->getCode() . "\n" . "File: " . $this->getFile() . "\n" . "Line: " . $this->getLine() . " at: \n";
			$error .= $this->getTraceAsString() . "\n";
			$error .= '</pre></div>';
		}
		
		$error .= '</div>';
		
		return $error;
	}
}
<?php
interface phpDataMapper_Validation_IRule
{
	/**
	 * Run validation rule
	 * 
	 * @param string value
	 * @return bool
	 */
	public function run($value);
}
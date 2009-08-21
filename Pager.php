<?php
/**
 * $Id$
 * 
 * Pagination Object for phpDataMapper queries
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 * 
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 */
class phpDataMapper_Pager
{
	protected $mapper;
	protected $currentPage;
	protected $rowsPerPage;
	
	/**
	 *
	 */
	public function __construct(phpDataMapper_Model $mapper, $page = 1, $rowsPerPage = 30)
	{
		$this->mapper = $mapper;
		$this->currentPage = $page;
		$this->rowsPerPage = $rowsPerPage;
		$this->mapper->limit($this->rowsPerPage, $this->getOffset());
	}
	
	
	/**
	 * Set current page
	 * @param $page int
	 */
	public function setPage($page)
	{
		$this->currentPage = $page;
		$this->mapper->limit($this->rowsPerPage, $this->getOffset());
	}
	
	
	/**
	 * Get current page
	 * @return $page int
	 */
	public function getPage()
	{
		return $this->currentPage;
	}
	
	
	/**
	 * Set rows per page limit
	 * @param $rowsPerPage int
	 */
	public function setRowsPerPage($rowsPerPage)
	{
		$this->rowsPerPage = $rowsPerPage;
		$this->mapper->limit($this->rowsPerPage, $this->getOffset());
	}
	
	
	/**
	 * Get row offset for SQL query
	 *
	 * @return int
	 */
	public function getOffset()
	{
		$offset = ($this->currentPage - 1) * $this->rowsPerPage;
		return $offset;
	}
}
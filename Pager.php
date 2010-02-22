<?php
/**
 * Dynamic and automated pagination for phpDataMapper_Query objects
 * 
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 * @link http://github.com/vlucas/phpDataMapper
 */
class phpDataMapper_Pager
{
	protected $mapper;
	protected $currentPage;
	protected $rowsPerPage;
	
	/**
	 *
	 */
	public function __construct(phpDataMapper_Query $query, $page = 1, $rowsPerPage = 30)
	{
		$this->query = $query;
		$this->mapper = $query->mapper();
		$this->currentPage = $page;
		$this->rowsPerPage = $rowsPerPage;
		$this->query->limit($this->rowsPerPage, $this->getOffset());
	}
	
	
	/**
	 * Set current page
	 * @param $page int
	 */
	public function setPage($page)
	{
		$this->currentPage = $page;
		$this->query->limit($this->rowsPerPage, $this->getOffset());
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
	 * Get total number of pages page
	 * @return $total int
	 */
	public function getTotalPages()
	{
		$this->query->limit(null, null);
		$count = $this->query->count();
		$count =  ceil( $count / $this->rowsPerPage );
		$this->setRowsPerPage( $this->rowsPerPage );
		return $count;
	}
	
	
	/**
	 * Set rows per page limit
	 * @param $rowsPerPage int
	 */
	public function setRowsPerPage($rowsPerPage)
	{
		$this->rowsPerPage = $rowsPerPage;
		$this->query->limit($this->rowsPerPage, $this->getOffset());
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
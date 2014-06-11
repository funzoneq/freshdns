<?php
class xmlcreator {
	var $content;

	function __construct ()
	{
		$this->content = "";
	}
	
	function __destruct ()
	{
		unset($this->content);
	}
	
	function addHeader()
	{
		//header('Content-Type: application/xml');
		$this->content .= "<?xml version=\"1.0\"?>\n";
	}
	
	function addTag ($tagName, $tagLevel)
	{
		// ADD THE TABS
		for($i=0; $i<$tagLevel; $i++)
		{
			$this->content .= "\t";
		}
		
		$this->content .= "<".$tagName.">\n";
	}
	
	function addItem ($tagName, $tagContent, $tagLevel)
	{
		// ADD THE TABS
		for($i=0; $i<$tagLevel; $i++)
		{
			$this->content .= "\t";
		}
		
		$this->content .= "<".$tagName.">".$tagContent."</".$tagName.">\n";
	}
	
	function returnContent ()
	{
		return $this->content;
	}
}
?>
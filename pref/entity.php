<?php

//Objects Definition
class Staff {
    private $staffid;
    private $salutation, $name;


	function __construct($staffid, $salutation, $name){
		$this->staffid 			= $staffid;
		$this->salutation		= $salutation;
		$this->name 			= $name;
	}

	function getID() 				{ return $this->staffid; }
	function getSalutation()		{ return $this->salutation; }
	function getName() 				{ return $this->name; }

	function getSalutationStr()
	{
		$salutation = $this->salutation;
		switch($salutation)
		{
		case "Nanyang Assistant Professor":
		case "Assistant Professor": $salutation = "Ast/P";
			break;
		case "Associate Professor": $salutation = "A/P";
			break;
		case "Senior Lecturer":
		case "Lecturer": $salutation = "Lec";
			break;
		case "Professor": $salutation = "Prof";
			break;
		case "Sr Research Scientist":
		case "Research Scientist":
		case "Research Fellow": $salutation = "";
			break;
		case "TBD": $salutation = "";
			break;
		default:	//echo $salutation."<br/>";
			break;
		}

		return $salutation;
	}

	function toString() { return $this->getSalutationStr()." ".$this->name; }
}

class Project {
	private $projectid;
	private $staffid, $examiner, $title, $summary;

	function __construct($projectid, $staffid, $examiner, $title, $summary){
		$this->projectid 	= $projectid;
		$this->staffid		= $staffid;
		$this->examiner 	= $examiner;
		$this->title 		= $title;
    $this->summary  = $summary;
	}

	function setStaff($staffid) 			{ $this->staffid = staffid; }
	function setExaminer($examiner) 		{ $this->examiner = examiner; }
  function setSummary($summary) 		{ $this->summary = summary; }

	function getID() 			{ return $this->projectid; }
	function getStaff() 		{ return $this->staffid; }
	function getExaminer() 		{ return $this->examiner; }
	function getTitle()			{ return $this->title; }
  function getSummary()			{ return $this->summary; }
}

class Area {
	private $areaid, $title;

	function __construct($areaid, $title){
		$this->areaid 		= $areaid;
		$this->title		= $title;
	}

	function getID() 			{ return $this->areaid; }
	function getTitle()			{ return $this->title; }
}
?>

<?php

//Objects Definition
class StaffAllocate {
    private $staffid;
    private $salutation, $name, $mseCount, $exemption, $exemptionS2;
    private $interestarea, $interestproject, $supervisingNo;

    public $assignment_area, $assignment_project;		//Used for project assignment working
    public $assignment_list;							//Used for timeslot assignment working

    //public $timeslot_test = new Timeslot();

    public $timeslotException;

    function __construct($staffid, $salutation, $name, $exemption, $exemptionS2, $mseCount=0){
        $this->staffid 			= $staffid;
        $this->salutation		= $salutation;
        $this->name 			= $name;
        $this->getExemption			= $exemption;
        $this->getExemption2		 = $exemptionS2;
        $this->mseCount			= $mseCount;
        $this->init_mseCount	= $this->mseCount;
        $this->interestarea 	= array();
        $this->interestproject 	= array();
        $this->timeslotException = array();
        $this->supervisingNo =0;
        $this->initTimeslotAssignment();
    }

    function initTimeslotAssignment() {	//Initialize before Timeslot Assignment
        $this->assignment_list = array();
    }

    function getPriority() { return count($this->assignment_list); }

    function initProjectAssignment() {	//Initialize before Project Assignment
        $this->assignment_area = array_merge(array(), $this->interestarea);
        $this->assignment_project = array_merge(array(), $this->interestproject);
    }

    function clearInterest() {
        unset($this->interestarea);
        $this->interestarea = array();

        unset($this->interestproject);
        $this->interestproject = array();
    }

    function addInterestArea($priority, $area) {
        $this->interestarea[$priority] = $area;
    }

    function addInterestProject($priority, $project) {
        $this->interestproject[$priority] = $project;
    }
    public function isAvailable($day, $start, $end) {
        //echo "inside isAvail ";
        foreach ($this->timeslotException as $exception)
        {
            $exceptionDay = $exception->getDay();
            if ($exceptionDay == -1 || $exceptionDay == $day) 	//Not Affected Day Skip
            {
                $exceptionStart_str = $exception->getStartStr();
                $exceptionEnd_str = $exception->getEndStr();

                $start_str = $start->format('H:i:s');
                $end_str = $end->format('H:i:s');

                if ( ($end_str <= $exceptionStart_str) ||
                    ($start_str >= $exceptionEnd_str) ){
                    //Okay
                    //return true; /// added
                }
                else
                {
                    //Collide
                    return false;
                }
            }
        }
        return true;
    }

    function addTimeslotException($day, $start, $end) {
        $this->timeslotException[] = new Timeslot(count($this->timeslotException)+1, $day, -1, $start, $end);
    }

    function getID() 				{ return $this->staffid; }
    function getSalutation()		{ return $this->salutation; }
    function getName() 				{ return $this->name; }
    function getWorkload() 			{ return $this->mseCount; }
    function getExemption()			{ return $this->exemption; }
    function getExemption2()		{ return $this->exemptionS2; }
    function getInitialWorkload()	{ return $this->init_mseCount; }
    function getInterestArea()		{ return $this->interestarea; }
    function getInterestProject()	{ return $this->interestproject; }
    function getSupervisingNo()		{ return $this->supervisingNo; }
    function setWorkload($mseCount) { $this->mseCount = $mseCount; }

    function setInitialWorkload($mseCount) {
        $this->mseCount = $mseCount;
        $this->init_mseCount = $this->mseCount;
    }
    function setSupervisingNo($supervisingNo){
        $this->supervisingNo = $supervisingNo;
    }
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
            case "Research Fellow": $salutation = "Dr";
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
class Staff {
    private $staffid;
    private $salutation, $name, $mseCount, $exemption, $exemptionS2;
    private $interestarea, $interestproject, $supervisingNo;

    public $assignment_area, $assignment_project;		//Used for project assignment working
    public $assignment_list;							//Used for timeslot assignment working

    //public $timeslot_test = new Timeslot();

    public $timeslotException;

    function __construct($staffid, $salutation, $name, $mseCount=0){
        $this->staffid 			= $staffid;
        $this->salutation		= $salutation;
        $this->name 			= $name;
        $this->mseCount			= $mseCount;
        $this->init_mseCount	= $this->mseCount;
        $this->interestarea 	= array();
        $this->interestproject 	= array();
        $this->timeslotException = array();
        $this->supervisingNo =0;
        $this->initTimeslotAssignment();
    }

    function initTimeslotAssignment() {	//Initialize before Timeslot Assignment
        $this->assignment_list = array();
    }

    function getPriority() { return count($this->assignment_list); }

    function initProjectAssignment() {	//Initialize before Project Assignment
        $this->assignment_area = array_merge(array(), $this->interestarea);
        $this->assignment_project = array_merge(array(), $this->interestproject);
    }

    function clearInterest() {
        unset($this->interestarea);
        $this->interestarea = array();

        unset($this->interestproject);
        $this->interestproject = array();
    }

    function addInterestArea($priority, $area) {
        $this->interestarea[$priority] = $area;
    }

    function addInterestProject($priority, $project) {
        $this->interestproject[$priority] = $project;
    }
    public function isAvailable($day, $start, $end) {
        //echo "inside isAvail ";
        foreach ($this->timeslotException as $exception)
        {
            $exceptionDay = $exception->getDay();
            if ($exceptionDay == -1 || $exceptionDay == $day) 	//Not Affected Day Skip
            {
                $exceptionStart_str = $exception->getStartStr();
                $exceptionEnd_str = $exception->getEndStr();

                $start_str = $start->format('H:i:s');
                $end_str = $end->format('H:i:s');

                if ( ($end_str <= $exceptionStart_str) ||
                    ($start_str >= $exceptionEnd_str) ){
                    //Okay
                    //return true; /// added
                }
                else
                {
                    //Collide
                    return false;
                }
            }
        }
        return true;
    }

    function addTimeslotException($day, $start, $end) {
        $this->timeslotException[] = new Timeslot(count($this->timeslotException)+1, $day, -1, $start, $end);
    }

    function getID() 				{ return $this->staffid; }
    function getSalutation()		{ return $this->salutation; }
    function getName() 				{ return $this->name; }
    function getWorkload() 			{ return $this->mseCount; }
    function getExemption()			{ return $this->exemption; }
    function getExemption2()		{ return $this->exemptionS2; }
    function getInitialWorkload()	{ return $this->init_mseCount; }
    function getInterestArea()		{ return $this->interestarea; }
    function getInterestProject()	{ return $this->interestproject; }
    function getSupervisingNo()		{ return $this->supervisingNo; }
    function setWorkload($mseCount) { $this->mseCount = $mseCount; }

    function setInitialWorkload($mseCount) {
        $this->mseCount = $mseCount;
        $this->init_mseCount = $this->mseCount;
    }
    function setSupervisingNo($supervisingNo){
        $this->supervisingNo = $supervisingNo;
    }
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
            case "Research Fellow": $salutation = "Dr";
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
    private $staffid, $examiner, $title;
    private $projectarea;

    private $assignedStaff, $reason;

    public $assignment_priority;						//Used for timeslot assignment working
    private $assigned_day, $assigned_room, $assigned_time, $timeslot_assigned;

    public function __toString() {
        return "project id: {$this->projectid}";
    }
    function __construct($projectid, $staffid, $examiner, $title){
        $this->projectid 	= $projectid;
        $this->staffid		= $staffid;
        $this->examiner 	= $examiner;
        $this->title 		= $title;
        $this->projectarea 	= array();

        $this->assignment_priority = 0;
        $this->initProjectAssignment();
    }

    function setStaff($staffid) 			{ $this->staffid = staffid; }
    function setExaminer($examiner) 		{ $this->examiner = examiner; }

    function initTimeslotAssignment() {	//Initialize before Timeslot Assignment
        $this->assignment_priority = 0;
        $this->assigned_day = -1;
        $this->assigned_room = -1;
        $this->assigned_time = -1;
        $this->timeslot_assigned = false;
    }

    function assignTimeslot($day, $room, $time)
    {
        $this->assigned_day = $day;
        $this->assigned_room = $room;
        $this->assigned_time = $time;
        $this->timeslot_assigned = true;
    }

    function initProjectAssignment()	{
        $this->assignedStaff = null;
        $this->reason = null;
    }

    function assignStaff($staff, $reason)
    {
        $this->assignedStaff = $staff;
        $this->reason = $reason;
    }

    function clearProjectArea() {
        unset($this->projectarea);
        $this->projectarea = array();
    }

    function addProjectArea($projectarea) 	{ $this->projectarea[] = $projectarea; }

    function getID() 			{ return $this->projectid; }
    function getStaff() 		{ return $this->staffid; }
    function getExaminer() 		{ return $this->examiner; }
    function getTitle()			{ return $this->title; }
    function getProjectArea()	{ return $this->projectarea; }

    function isAssignedStaff()	{ return $this->assignedStaff != null; }
    function isAssignedTimeslot() { return $this->timeslot_assigned; }
    function getAssignedStaff() { return $this->assignedStaff; }
    function getAssignedReason() { return $this->reason; }

    function getAssigned_Day() 	{ return $this->assigned_day; }
    function getAssigned_Room() { return $this->assigned_room; }
    function getAssigned_Time() { return $this->assigned_time; }

    function hasValidTimeSlot()
    {
        return ($this->assigned_day !== null && $this->assigned_day != -1 &&
            $this->assigned_room !== null && $this->assigned_room != -1 &&
            $this->assigned_time !== null && $this->assigned_time != -1);
    }
}

class Timeslot {
    public $id, $day, $slot, $startTime, $endTime; 	//Set to public: For use in javascript later

    function __construct($id, $day, $slot, $startTime, $endTime){
        $this->id			= $id;
        $this->day			= $day;
        $this->slot			= $slot;
        $this->startTime 	= clone $startTime;
        $this->endTime		= clone $endTime;
    }

    function getID()		{ return $this->id; }
    function getDay()		{ return $this->day; }
    function getSlot()		{ return $this->slot; }

    function getStartStr() 	{ return $this->startTime->format('H:i:s'); }
    function getEndStr() 	{ return $this->endTime->format('H:i:s'); }

    function getStartTime() { return clone $this->startTime; }
    function getEndTime() 	{ return clone $this->endTime; }

    function toString()			{ return $this->startTime->format("H:i")." - ".$this->endTime->format('H:i'); }
    function toExcelString()	{ return $this->startTime->format("Hi")."-".$this->endTime->format('Hi'); }		//Only used for excel exporting
    public function __toString() {
        return "timeslot start: ". $this->getStartStr(). " timeslot end: ". $this->getEndStr();
    }
}

class Room {
    private $id, $roomName;

    function __construct($id, $roomName){
        $this->id			= $id;
        $this->roomName 	= $roomName;
    }

    function getID()		{ return $this->id; }
    function getRoomName()  { return $this->roomName; }

    function toString()		{ return $this->getRoomName(); }
}

?>
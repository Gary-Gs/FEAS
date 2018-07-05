<?php require_once('../Utility.php'); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>FYP Examiner Allocation System</title>

<?php require_once('../head.php');?>
</head>

<body>
	<div id="bar"></div>
	<div id="wrapper">
		<div id="header"></div>
		
        <div id="left">
            <div id="nav">
               <?php require_once('nav.php'); ?>
            </div>
        </div>	
			<div id="logout">
				<a href="../../logout.php"><img src="../images/logout.jpg" /></a>
			</div>
		<!-- InstanceBeginEditable name="Content" -->
        <div id="content">
		
		   <?php if (isset($_SESSION['success'])) {
			   echo "<p class='success'>[Login] ".$_SESSION['success']."</p>";
			   unset ($_SESSION['success']);
		        }
				if (isset($_SESSION['displayname'])){
					  echo "<p class='success'>Hello, ".$_SESSION['displayname']."</p>";

				} 
			   ?>
            <h1>FYP Examiner Allocation System</h1>
            <p>Welcome!</p>
            <br/>
			 <!--<p>There are three sub-categories under each of the categories (Full Time and Part Time) of the 'Navigation' menu on the left.<br/><br/>-->
			<p>There are three sub-categories under the 'Full Time' category of the 'Navigation' menu on the left.<br/><br/>
			Under the <b>'General'</b> category, there are two links:<br/>
			<b>Project List:</b> List all projects available. Staff may use the search bar provided to search for a particular project or use the select box to filter the projects by year and semester. <br/>
			<b>Faculty List:</b> List all staff project preferences and area preferences. Staff may use the search bar provided to search for a particular staff.
			<br/><br/>
			Under the <b>'Pre-Allocation'</b> category, there are three links:<br/>
			<b>Staff Pref Settings:</b> To set the open period for staff to choose their project or area preferences.<br/>
			<b>Faculty Settings:</b> To view and update staff workload to the Faculty table.<br/>
			<b>Timeslot Exception:</b> To edit the timeslots to exclude from the allocation
			<br/><br/>
			Under the <b>'Allocation'</b> category, there are three links:<br/>
			<b>Allocation Settings:</b> To set the number of days and timings for allocation in the Timeslot Settings and enter actual room names for allocation in the Room Settings.<br/>
			<b>Allocation System:</b> To allocate examiners to projects, allocate timeslots for examiners to examine, view and edit timetable, and clear allocation.<br/>
			<b>View Allocation Plan:</b> To view examiner allocation timetable plan.<br/>
			</p>
		</div>
		<!-- InstanceEndEditable --> 
       
		
		<?php require_once('../footer.php'); ?>
    </div>
</body>

</html>
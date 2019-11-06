<?php require_once('../Utility.php');
require_once('../restriction.php');  ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>FYP Examiner Allocation System</title>

<style>
	table, th, td {
  		border: 1px solid black;
	}
	th{
		color: white;
		background-color: #101010;
		opacity: 0.8;
	}
	td{
		background-color: white;
	}
</style>


</head>

<body>
	<?php require_once('../php_css/headerwnav.php');?>



	<div style="margin-left: -15px;">
		<div class="container-fluid">
            <?php require_once('nav.php'); ?>

                <!-- Page Content Holder -->
                <div class="container-fluid">

                    <br/>
                   	<p style="color: black;">There are three sub-categories under the 'Full Time' category of the 'Navigation' menu on the left.<br/>

                    <table width="100%">
                    	<th colspan="2" style="padding: 7px;">
                    		1. General Category</br>
                    		Under the 'General' category, there are two links:
                    	</th>
                    	<tr>
                    		<td width="20%"><a href ="/fyp/fulltime/gen/project.php">A. Project List</a></td>
                    		<td>List all projects available. Staff may use the search bar provided to search for a particular project or use the select box to filter the projects by year and semester. </td>
                    	</tr>
                    	<tr>
                    		<td width="20%"><a href="/fyp/fulltime/gen/faculty.php">B. Faculty List</a></td>
                    		<td>List all staff project preferences and area preferences. Staff may use the search bar provided to search for a particular staff.</td>
                    	</tr>
                        <tr>
                            <td width="20%"><a href="/fyp/fulltime/gen/research_interest.php">C. Research Interest</a></td>
                            <td>List all research interest of staff. Staff may use the select box to search for a particular staff.</td>
                        </tr>
                        <tr>
                            <td width="20%"><a href="/fyp/fulltime/gen/feedback.php">D. Feedback</a></td>
                            <td>List all feedback received. Staff may use the select box to filter the feedback.</td>
                        </tr>
                    </table>

                	</br>

                    <table width="100%">
                    	<th colspan="2" style="padding: 7px;">
                    		2. Pre-Allocation</br>
                    		Under the 'Pre-Allocation' category, there are three links:<br/>
                    	</th>
                    	<tr>
                    		<td width="20%"><a href="/fyp/fulltime/alloc/staffpref_setting.php">A. Staff Pref Settings</a></td>
                    		<td>To set the open period for staff to choose their project or area preferences. </td>
                    	</tr>
                    	<tr>
                    		<td width="20%"><a href="/fyp/fulltime/alloc/examiner_setting.php">B. Faculty Settings</a></td>
                    		<td>To view and update staff workload to the Faculty table.</td>
                    	</tr>
                    	<tr>
                    		<td width="20%"><a href="/fyp/fulltime/alloc/timeslot_exception.php">C. Timeslot Exception</a></td>
                    		<td>To edit the timeslots to exclude from the allocation</td>
                    	</tr>
                    </table>

               		</br>

                    <table width="100%">
                    	<th colspan="2" style="padding: 7px;">
                    		3. Allocation</br>
                    		Under the 'Allocation' category, there are three links:<br/>
                    	</th>
                    	<tr>
                    		<td width="20%"><a href="/fyp/fulltime/alloc/allocation_setting.php">A. Allocation Settings</a></td>
                    		<td>To set the number of days and timings for allocation in the Timeslot Settings and enter actual room names for allocation in the Room Settings.</td>
                    	</tr>
                    	<tr>
                    		<td width="20%"><a href="/fyp/fulltime/alloc/allocation.php">B. Allocation System</a></td>
                    		<td>To allocate examiners to projects, allocate timeslots for examiners to examine, view and edit timetable, and clear allocation.</td>
                    	</tr>
                    	<tr>
                    		<td width="20%"><a href="/fyp/fulltime/alloc/allocation_timetable.php">C. View Allocation Plan</a></td>
                    		<td>To view examiner allocation timetable plan</td>
                    	</tr>
                        <tr>
                            <td width="20%"><a href="/fyp/fulltime/alloc/result_visualization.php">D. Results Visualization</a></td>
                            <td>To have a visualization of the allocation results</td>
                        </tr>
                        <tr>
                            <td width="20%"><a href="/fyp/fulltime/alloc/staffpref_result.php">E. Staff Preference Result</a></td>
                            <td>To have a visualization of the staff preference results</td>
                        </tr>
                    </table>
                </div>

            <!-- closing navigation div in nav.php -->
            </div>

		</div>

	</div>

		<?php require_once('../footer.php'); ?>
</body>

</html>

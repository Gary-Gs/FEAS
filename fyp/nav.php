<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <title>Navigation Bar</title>
        <!-- Bootstrap CSS CDN -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

        <!-- Our Custom CSS -->
        <?php
            if(strcmp($_SERVER["REQUEST_URI"] , "fyp.php")){
                echo "<link rel='stylesheet' href='../css.navibarstyle.css'>";
            }

            if(strcmp($_SERVER["REQUEST_URI"] , "project.php")){
                echo "<link rel='stylesheet' href='../../../css/navibarstyle.css'>";
                //for floating panel
                echo "<script src='../../../scripts/float-panel.js'></script>";
            }
        ?>

    </head>
    <body>

        <div class="wrapper" style="min-height: 850px;">
            <!-- Sidebar Holder -->
            <nav id="sidebar">
                <!-- can add words here
                <div class="sidebar-header">
                    <h3></h3>
                    <strong></strong>
                </div> -->
                <br/>
                    <div class="container-fluid">

                        <div class="navbar-header">
                            <button type="button" id="sidebarCollapse" class="btn" style="background-color: #4682b4;">
                                <i class="glyphicon glyphicon-align-left"></i>
                                <span><!-- can add some words here --></span>
                            </button>
                        </div>
                    </div>
                <ul class="list-unstyled components">
                    <li class="active">
                        <a href="/fyp/fyp.php">
                            <i class="glyphicon glyphicon-home"></i>
                            Home
                        </a>
                    </li>


                    <li>
                        <a href="#homeSubmenu" data-toggle="collapse" aria-expanded="false">
                            <i class="glyphicon glyphicon-link"></i>
                            General
                        </a>
                        <ul class="collapse list-unstyled" id="homeSubmenu">
                            <li><a href="/fyp/fulltime/gen/project.php">Project List</a></li>
                            <li><a href="/fyp/fulltime/gen/faculty.php">Faculty List</a></li>
                            <li><a href="/fyp/fulltime/gen/research_interest.php">Research Interests</a></li>
                            <li><a href="/fyp/fulltime/gen/feedback.php">Feedback</a></li>
                        </ul>
                    </li>

                    <li>
                        <a href="#preallocSubMent" data-toggle="collapse" aria-expanded="false">
                            <i class="glyphicon glyphicon-duplicate"></i>
                            Pre-Allocation
                        </a>
                        <ul class="collapse list-unstyled" id="preallocSubMent">
                            <li><a href="/fyp/fulltime/alloc/staffpref_setting.php">Staff Pref Settings</a></li>
                            <li><a href="/fyp/fulltime/alloc/examiner_setting.php">Faculty Settings</a></li>
                            <li><a href="/fyp/fulltime/alloc/timeslot_exception.php">Timeslot Exception</a></li>
                        </ul>
                    </li>


                    <li>
                        <a href="#allocationSubMenu" data-toggle="collapse" aria-expanded="false">
                            <i class="glyphicon glyphicon-send"></i>
                            Allocation
                        </a>
                        <ul class="collapse list-unstyled" id="allocationSubMenu">
                            <li><a href="/fyp/fulltime/alloc/allocation_setting.php">Allocation Settings</a></li>
                            <li><a href="/fyp/fulltime/alloc/allocation.php">Allocation System</a></li>
                            <li><a href="/fyp/fulltime/alloc/allocation_timetable.php">View Allocation Plan</a></li>
                            <li><a href="/fyp/fulltime/alloc/result_visualization.php">Results Visualization</a></li>
                            <li><a href="/fyp/fulltime/alloc/staffpref_result.php">Staff Preference Results</a></li>
                        </ul>
                    </li>

                </ul>
            </nav>

        <!-- jQuery CDN -->
         <script src="https://code.jquery.com/jquery-1.12.0.min.js"></script>
         <!-- Bootstrap Js CDN -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

        <!-- for datepicker -->
        <link rel="stylesheet" href="http://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <link rel="stylesheet" href="https://jqueryui.com/resources/demos/style.css">
        <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <!-- end of datepicker -->

         <script type="text/javascript">
             $(document).ready(function () {
                 $('#sidebarCollapse').on('click', function () {
                     $('#sidebar').toggleClass('active');
                 });
             });
         </script>
    </body>
</html>


<!-- <?php

	$open=0;
	$pageURL = explode("/", $_SERVER["REQUEST_URI"]);
	if (count($pageURL) >= 3) {
		$selectedURL = $pageURL[2];
		if( strcmp($selectedURL, "gen") == 0)
			$open = 1;
		else if( strcmp($selectedURL, "alloc") == 0)
			$open = 2;
	}

	//Patched
	?>

    <script type="text/javascript">
    $(document).on("click","dt",function(){
		var dd = $(this).next();

		// If the title is clicked and the dd is not currently animated,
        // start an animation with the slideToggle() method.

        if(!dd.is(':animated')){
        	dd.slideToggle();
         	$(this).toggleClass('opened');
		}
	});

	$('a.button').click(function(){
		// To expand/collapse all of the FAQs simultaneously,
        // just trigger the click event on the DTs

        if($(this).hasClass('collapse')){
			$('dt.opened').click();
        }
        else $('dt1:not(.opened)').click();

        $(this).toggleClass('expand collapse');

        return false;
    });
    </script>
<h1>Navigation</h1>
	<dt><a style="text-decoration:none;" href="/fyp/fyp.php">Home</a></dt>
		<dt class = "<?php if($open=1) echo "opened"; ?>">Full Time</dt>
			<div style = "padding-left:10px ">
				<ul class = "subnav">
					<dt style="<?php if($open=1) echo "opened";?>">General </dt>
						<dd style="<?php if($open==1) echo "display: block;";?>">
							<ul class="subnav">
								<li><a href="/fyp/fulltime/gen/project.php">Project List</a></li>
								<li><a href="/fyp/fulltime/gen/faculty.php">Faculty List</a></li>
							</ul>
							</dd>
					<dt class="<?php if($open==1) echo "opened"; ?>">Pre-Allocation</dt>
						<dd style="<?php if($open==1) echo "display: block;";?>">
							<ul class="subnav">
								 <li><a href="/fyp/fulltime/alloc/staffpref_setting.php">Staff Pref Settings</a></li>
								 <li><a href="/fyp/fulltime/alloc/examiner_setting.php">Faculty Settings</a></li>
								 <li><a href="/fyp/fulltime/alloc/timeslot_exception.php">Timeslot Exception</a></li>
							</ul>
						</dd>

					<dt class="<?php if($open==1) echo "opened"; ?>">Allocation</dt>
						<dd style="<?php if($open==1) echo "display: block;";?>">
							<ul class="subnav">
									<li><a href="/fyp/fulltime/alloc/allocation_setting.php">Allocation Settings</a></li>
									<li><a href="/fyp/fulltime/alloc/allocation.php">Allocation System</a></li>
									<li><a href="/fyp/fulltime/alloc/allocation_timetable.php">View Allocation Plan</a></li>
								</ul>
							</dd>
				</ul>
           </div>
	<!--Part time commented since not using for now-->
   <!--<dt class = "<?php if($open=1) echo "opened"; ?>">Part Time</dt>
	    <div style = "padding-left:15px ">
				<ul class = "subnav">
					<dt style="<?php if($open=1) echo "opened";?> padding-left: 10px ">General </dt>
						<dd style="<?php if($open==1) echo "display: block;";?>">
						<ul class="subnav">
							<li><a href="/fyp/parttime/gen/project.php">Project List</a></li>
							<li><a href="/fyp/parttime/gen/faculty.php">Faculty List</a></li>
						</ul>
						</dd>
					<dt class="<?php if($open==2) echo "opened"; ?>">Pre-Allocation</dt>
						<dd style="<?php if($open==2) echo "display: block;";?>">
							<ul class="subnav">
								<li><a href="/fyp/parttime/alloc/staffpref_setting.php">Staff Pref Settings</a></li>
								<li><a href="/fyp/parttime/alloc/examiner_setting.php">Faculty Settings</a></li>
								<li><a href="/fyp/parttime/alloc/timeslot_exception.php">Timeslot Exception</a></li>
							</ul>
						</dd>

					<dt class="<?php if($open==2) echo "opened"; ?>">Allocation</dt>
						<dd style="<?php if($open==2) echo "display: block;";?>">
							<ul class="subnav">
								<li><a href="/fyp/parttime/alloc/allocation_setting.php">Allocation Settings</a></li>
								<li><a href="/fyp/parttime/alloc/allocation.php">Allocation System</a></li>
								<li><a href="/fyp/parttime/alloc/allocation_timetable.php">View Allocation Plan</a></li>
							</ul>
						</dd>
				</ul>
    </div>	-->

<?php 	
	
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
					<dt class="<?php if($open==2) echo "opened"; ?>">Pre-Allocation</dt>
						<dd style="<?php if($open==2) echo "display: block;";?>">
							<ul class="subnav">
								 <li><a href="/fyp/fulltime/alloc/staffpref_setting.php">Staff Pref Settings</a></li>
								 <li><a href="/fyp/fulltime/alloc/examiner_setting.php">Faculty Settings</a></li>
								 <li><a href="/fyp/fulltime/alloc/timeslot_exception.php">Timeslot Exception</a></li>
							</ul>
						</dd>
				
					<dt class="<?php if($open==2) echo "opened"; ?>">Allocation</dt>
						<dd style="<?php if($open==2) echo "display: block;";?>">
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
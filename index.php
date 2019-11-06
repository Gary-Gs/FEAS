<?php require_once('Utility.php');
require_once('restriction.php');?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Main Page</title>

</head>

<body style="background: url('images/background2.png'); background-size: 100% 100%;">
	<?php require_once('php_css/headerwonav.php');?>

	<div class="container-fluid pt-5">
      <div class="row">

		<div class="col-sm-2 col-md-2 col-lg-2">

		</div>

		<div class="col-sm-10 col-md-10 col-lg-10">
			<div class="row">
				<div class="col-lg-1"></div>

				<div class="col-sm-5 col-md-5 col-lg-4" style="opacity: 0.8;">
					<div class="card">
						<div class="card-header bg-dark">
							<div class="card-title"><h5><u><a href="/fyp/fyp.php" class="text-white">Examiner Allocation Module</a></u></h5></div>
						</div>
						<a href="/fyp/fyp.php"><img class="card-img-top img-fluid" src="images/examiner_mod_bw.png" alt="Examiner Allocation Module"/></a>
						<div class="card-body">
							<div class="card">
								<div class="card-header text-white" style="background-color: #336699;">
									1. General
								</div>
								<div class="card-body">
									<p>
										- Project List<br/>
										- Faculty List<br/>
										- Research Interests<br/>
										- Feedback

									</p>
								</div>
							</div>

							<div class="card">
								<div class="card-header text-white" style="background-color: #336699;">
									2. Pre-Allocation
								</div>
								<div class="card-body">
									<p>
										- Staff Pref Settings<br/>
										- Faculty Settings<br/>
										- Timeslot Exception

									</p>
								</div>
							</div>

							<div class="card">
								<div class="card-header text-white" style="background-color: #336699;">
									3. Allocation
								</div>
								<div class="card-body">
									<p>
										- Allocation Settings<br/>
										- Allocation System<br/>
										- View Allocation Plan<br/>
										- Results Visualization<br/>
										- Staff Preference Results

									</p>
								</div>
							</div>

						</div>
					</div>
				</div>

				<div class="col-sm-5 col-md-5 col-lg-4" style="opacity: 0.8;">
					<div class="card">
						<div class="card-header bg-dark" style="background-color: #999999;">
							<h5 class="card-title"><u><a href="/pref/nav.php" class="text-white">Staff Preference Module</a></u></h5>
						</div>
						<a href="/pref/nav.php"><img  width="800" height="600" class="card-img-top img-fluid" src="images/staff_pref_bw.png" alt="Staff Preference Module"/></a>
						<div class="card-body">
							<div class="card">
								<div class="card-header text-white" style="background-color: #336699;">
									Staff Preference
								</div>
								<div class="card-body">
									<p>
										- Project Preference Selection<br/>
										- Area Preference Selection<br/>
										- View Supervising Projects
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
	<br/><br/><br/>

</div>

<?php require_once('footer.php'); ?>

</body>
</html>

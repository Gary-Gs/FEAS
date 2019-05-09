<?php 
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');


if ($_SERVER['HTTP_REFERER'] != null) {
    $urlString = explode('/', $_SERVER['HTTP_REFERER']);
    $foldername = $urlString[3];
    $entireUrlArr = explode("?", $_SERVER['HTTP_REFERER']);
    $entireUrlString = $entireUrlArr[0];
    $httpheader = $urlString[0];


    // to be used for localhost
   /* if((strcmp($foldername, 'fyp') != 0)
        && strcmp($entireUrlString, 'http://localhost/fyp/fulltime/gen/project.php') != 0) {
        throw new Exception("Invalid referer");
    }

   */
    if((strcmp($foldername, "fyp") != 0) && (strcmp($httpheader, 'https:') == 0)){
        if(strcmp($entireUrlString, 'https://155.69.100.32/fyp/fulltime/gen/project.php') != 0){
            throw new Exception($_SERVER['Invalid referer']);
        }
    }
    elseif((strcmp($foldername, "fyp") != 0) && (strcmp($httpheader,'http:') == 0)){
        if(strcmp($entireUrlString, 'http://155.69.100.32/fyp/fulltime/gen/project.php') != 0){
            throw new Exception($_SERVER['Invalid referer']);
        }
    }



// to be used for school server
  /*  if((strcmp($foldername,"fyp") != 0) ||
        strcmp($entireUrlString, 'http://155.69.100.32/fyp/fulltime/gen/project.php') != 0){
        throw new Exception("Invalid referer");
    }
  */

}
$csrf = new CSRFProtection();

$_REQUEST['csrf'] 	= $csrf->cfmRequest();


global $TABLES;

$cleanedSearch = (isset($_POST['search']) && !empty($_POST['search'])) ?
        preg_replace('[^a-zA-Z0-9\s\-()]', '', $_POST['search']) : '';
$filter_Search 			= "%". $cleanedSearch . "%";

$filter_ProjectYear 	= "%". (isset($_POST['filter_ProjectYear']) && !empty($_POST['filter_ProjectYear']) ?
        preg_replace('/[^0-9]/','',$_POST['filter_ProjectYear']) : '') ."%";
$filter_ProjectSem 		= "%". (isset($_POST['filter_ProjectSem']) && !empty($_POST['filter_ProjectSem']) ?
        preg_replace('/[^0-9]/','',$_POST['filter_ProjectSem']) : '') ."%";
$filter_Supervisor  	= "%". (isset($_POST['filter_Supervisor']) && !empty($_POST['filter_Supervisor']) ?
        preg_replace('/[^a-zA-Z._\s\-]/','',$_POST['filter_Supervisor']) : '') ."%";


$query_rsStaff				= "SELECT * FROM " . $TABLES["staff"];
$query_rsProject 			= "SELECT * FROM " .
$TABLES['fea_projects'] . " as p1 LEFT JOIN " . 
$TABLES['fyp_assign'] 	. " as p2 ON p1.project_id 	= p2.project_id LEFT JOIN "	.
$TABLES['fyp']			. " as p3 ON p2.project_id 	= p3.project_id LEFT JOIN "	.
$TABLES['staff']		. " as p4 ON p2.staff_id 	= p4.id "					.
"WHERE p2.complete = 0 AND (p2.project_id LIKE ? OR p3.title LIKE ?) AND (p2.year LIKE ? AND p2.sem LIKE ? AND p2.staff_id LIKE ?) ORDER BY p2.project_id ASC";

try
{
	// GET ALL STAFF FOR FILTER DROP DOWN CONTROL
	$stmt_0 			= $conn_db_ntu->prepare($query_rsStaff);
	$stmt_0->execute();
	$DBData_rsStaff 	= $stmt_0->fetchAll(PDO::FETCH_ASSOC);
	$AL_Staff			= array();
	foreach ($DBData_rsStaff as $key => $value) {
		$AL_Staff[$value["id"]] = $value["name"];
	}
	asort($AL_Staff);

	// GET Project data
	$stmt 				= $conn_db_ntu->prepare($query_rsProject);
	$stmt->bindParam(1, $filter_Search);				// Search project id 
	$stmt->bindParam(2, $filter_Search);				// Search project title
	$stmt->bindParam(3, $filter_ProjectYear);			// Search project year
	$stmt->bindParam(4, $filter_ProjectSem);			// Search project sem
	$stmt->bindParam(5, $filter_Supervisor);			// Search supervisor
	$stmt->execute();
	$DBData_rsProject   = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$Total_RowCount 	= count($DBData_rsProject);


}
catch (PDOException $e)
{
	die($e->getMessage());
}
$conn_db_ntu = null;
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">


<head>


	<title>Full Time Project List</title>
	<style>
            @media only screen and (max-width: 800px) {
            .floatWrapper {float:none!important;}       
            .float-panel {position:static!important;}
            .main-content {padding:20px;margin-right:0px;}
        }

        
    </style>
	
</head>

<body>
	<?php require_once('../../../php_css/headerwnav.php'); ?> 

    <div id="loadingdiv" class="loadingdiv">
		<img id="loadinggif" src="../../../images/loading.gif"/>
		<p>Uploading projects...</p>
	</div> 
	
<div style="margin-left: -15px;">
	<div class="container-fluid">
		 <?php require_once('../../nav.php'); ?> 

		 <!-- Page Content Holder -->
	    <div class="container-fluid">

	    	<!-- for going back to top --> 
	    	<div id="backtop"></div>
	    	<h3>Full Time Project List</h3>

	    	<?php 
	    		if (isset ($_REQUEST['csrf']) ||isset ($_REQUEST['validate'])) {
					echo "<p class='warn'> CSRF validation failed.</p>";	
				}

				else {
					if (isset ($_GET['error_code'])) {
						$error_code = $_GET['error_code'];
						switch ($error_code) {
						case 1:
						echo "<p class='warn'> Uploaded file has no file name!</p>";
						break;
						case 2:
						echo "<p class='warn'> Uploaded file has an invalid format type. Only excel files (.xlsx .xls .csv) are allowed!</p>";
						break;
						case 3:
						echo "<p class='warn'> Uploaded file is open. Close it and upload again!</p>";
						break;
						case 4:
						echo "<p class='error'> Cannot load excel file. Please contact system admin!</p>";
						break;
						case 5:
						echo "<p class='error'> Incorrect Data Format! Please upload the correct excel!</p>";
						break;
						}

					}
				}

				if (isset ($_REQUEST['import_project'])){
				echo "<p class='success'> Project List uploaded successfully.</p>";	
				}
	    	?>

	    		<?php require_once('../../../upload_head.php'); ?>


	    		<form id="FORM_FileToUpload_ProjectList" enctype="multipart/form-data">
				<table style="text-align: left; width: 100%;">
					<col width="20%">
					<col width="20%">
					<col width="20%">
					<col width="20%">
					<col width="20%">
					<tr>
						<td colspan="4">
							Please select the <b><u>Project List</u></b>:
						</td>
						<td style="text-align: right;">
							<input type="submit" value="Import" name="submit" class="btn btn-xs btn-success" >
						</td>
					</tr>
					
					<tr>
						<td colspan="5">
							<input type="file" id="FileToUpload_ProjectList" name="file" >
						</td>
					</tr>
					<tr>
						<td colspan="5">
							<div id="progressbardiv" class="progress" style="display: none;">
								<div id="progressbar" class="progress-bar progress-bar-success" role="progressbar" style="width:0%; color:black; ">
									<span>0%</span>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="5"><div id="status"></div></td>
					</tr>
				</table>
				<?php $csrf->echoInputField();?>
			</form>
			<br/>
			<form id="filterParams" name="searchbox" action="project.php" method="post" >
				<table id="Table_Filter_ProjectList" width="100%" >
					<colgroup>
						<col width="20%" >
						<col width="20%" >
						<col width="20%" >
						<col width="20%" >
						<col width="20%" >
					</colgroup>
					<tr>
						<td >
							<b> Year</b>
						</td>
						<td colspan="3">
							<select id="filter_ProjectYear" name="filter_ProjectYear" onchange="this.form.submit()">
								<option value="">SELECT</option>
								<?php
								$CurrentYear = sprintf("%02d", substr(date("Y"), -2));
								$LastestYear = sprintf("%02d", substr(date("Y"), -2));
								$EarlistYear = $CurrentYear - 10;

									 // Loops over each int[year] from current year, back to the $earliest_year [1950]
								foreach ( range( $LastestYear, $EarlistYear ) as $i ) {
									$i = sprintf("%02d", substr($i, -2)) . (sprintf("%02d", (substr($i, -2)+1)));

									if(isset($_POST["filter_ProjectYear"]) && $_POST["filter_ProjectYear"] == $i){
										echo "<option selected value='".$i."'>".$i."</option>";
									}else{
										echo "<option value='".$i."'>".$i."</option>";
									}
								}
								?>
							</select>
						</td>
						<td style="float: right;">
							<?php 
							if( $Total_RowCount > 1){
								echo $Total_RowCount . " records";
							}else{
								echo $Total_RowCount . " record";
							}
							?>
						</td>
					</tr>
					<tr>
						<td >
							<b> Sem</b>
						</td>
						<td>
							<select id="filter_ProjectSem" name="filter_ProjectSem" onchange="this.form.submit()">
								<option value="">SELECT</option>
								<?php
								for($index = 1; $index<3; $index++){
									if(isset($_POST["filter_ProjectSem"]) && $_POST["filter_ProjectSem"] == $index){
										echo "<option selected value='".$index."'>".$index."</option>";
									}else{
										echo "<option value='".$index."'>".$index."</option>";
									}
								}
								?>
							</select>
						</td>
						<td colspan="3"></td>
					</tr>
					<tr>
						<td >
							<b>Supervisor</b>
						</td>
						<td colspan="2">
							<select id="filter_Supervisor" name="filter_Supervisor" onchange="this.form.submit()">
								<option value="" selected>SELECT</option>
								<?php
								foreach ($AL_Staff as $key => $value) {
								    if(isset($_POST["filter_Supervisor"])) {
	                                    $StaffID_Filter = preg_replace('/[^a-zA-Z._\s\-]/','',$_POST['filter_Supervisor']);
	                                } else {
	                                    $StaffID_Filter = null;
	                                }
									$StaffID = $key;
									$StaffName = $value;
									if($StaffID_Filter == $StaffID){
										echo "<option value=" . $StaffID . " selected>";
										echo $StaffName;
										echo "</option>";	
									}else{
										echo "<option value=" . $StaffID . ">";
										echo $StaffName;
										echo "</option>";
									}
								}
								?>
							</select>
						</td>
						<td colspan="2" style="text-align:right;">
							<input type="search" id="filter_Search" name="search" value="<?php echo isset($_POST['search']) ?  $cleanedSearch : '' ?>" />
							<input type="submit" value="Search" title="Search for a project" class="bt"/>
						</td>
					</tr>
				</table>
				<?php $csrf->echoInputField();?>
			</form>

			<br/>
			<table id="tables" width="100%" border="1">
				<col width="13%" />
				<col width="6%" />
				<col width="5%" />
				<col width="32%" />
				<col width="30%" />
				<col width="6%" />
				<col width="6%" />

				<tr class="bg-dark text-white text-center">
					<td>Project ID</td>
					<td>Year</td>
					<td>Sem</td>
					<td>Project Title</td>
					<td>Supervisor</td>
					<td>Exam Year</td>
					<td>Exam Sem</td>
				</tr>
				<?php
				foreach ($DBData_rsProject as $key => $value) {
					echo "<tr>";
					echo "<td class='text-center'>" . $value['project_id'] . "</td>";
					echo "<td class='text-center'>" . $value['year'] . "</td>";
					echo "<td class='text-center'>" . $value['sem'] . "</td>";
					echo "<td class='text-center'>" . $value['title'] . "</td>";
					echo "<td class='text-center'>" . $value['Supervisor'] . "</td>";
					echo "<td class='text-center'>" . $value['examine_year'] . "</td>";
					echo "<td class='text-center'>" . $value['examine_sem'] . "</td>";
					echo "</tr>";
				}
				?>
			</table>

			<br/>
		</div>
		
			<script type="text/javascript">
				$("#FORM_FileToUpload_ProjectList").submit(function( event ) {
					// start of xm edits 
					//this for displaying to show that the data format for excel uploaded is wrong, somehow it worked but dont understand why
		            //window.location.href = ("project.php?error_code=5");
		            // end of xm edits 
					uploadFile();
					event.preventDefault();
				});
				function _(el){
					return document.getElementById(el);
				}
				function uploadFile(){
					if(_("FileToUpload_ProjectList").files.length == 0) {
						alert("Please select a file to upload!");
					}
					else {
						var file_data = _("FileToUpload_ProjectList").files[0];
						var csrfToken = _("CSRF_token").value;
						console.log(file_data.name + ", "+ file_data.size +", "+ file_data.type);
						var formData = new FormData();
						
						formData.append("file", file_data);
						formData.append("csrf__",csrfToken );
						_("loadingdiv").style.display  = "block";
						$.ajax({
							url: 'submit_import_projectlist.php',
							data: formData,
							processData: false,
							contentType: false,
							type: 'POST',
							xhr: function () {
		                    	// this part is progress bar
		                    	var xhr = new window.XMLHttpRequest();
		                    	xhr.upload.addEventListener("progress", function (evt) {
		                    		_("progressbardiv").style.display  = "block";
		                    		if (evt.lengthComputable) {
		                    			var percentComplete = evt.loaded / evt.total;
		                    			percentComplete = parseInt(percentComplete * 100);
		                    			$("#progressbar").text(percentComplete + "%");
		                    			$("#progressbar").css('width', percentComplete + "%");

		                    			if(percentComplete == 100){
		                    				_('status').innerHTML = "File uploaded. Waiting for server to respond!";
		                    				_('status').setAttribute("class", "success");
		                    			}
		                    		}
		                    	}, false );
		                    	return xhr;
		                    },
		                    success: function (data) {
		                    	console.log(data);
		                    	/* original codes */
		                    	console.log("File uploaded. Server Responded!");
		                    	_('status').innerHTML = "File uploaded. Server Responded!";
		                    	/* editted by xm */
		                    	/*console.log("Incorrect data format! Please upload the correct excel!");
		                    	_('status').innerHTML = "Incorrect data format! Please upload the correct excel!";
		                    	_('status').setAttribute("class", "error");*/
		                    	/* end of edits */
		                    	_("progressbardiv").style.display  = "none";
		                    	_("loadingdiv").style.display  = "none";
		                    	$("#progressbar").text(0 + "%");
		                    	$("#progressbar").css('width', 0 + "%");
		                    	window.location.href = ("project.php?" + data);

		                    },
		                    error: function(data){
		                    	console.log("File upload failed!");
		                    	_('status').innerHTML = "File upload failed!";
		                    }
		                });

					}
				}

				$('#filterParams').submit(function(event)
                {
                    let year = $('#filter_ProjectYear option:selected');
                    let sem = $('#filter_ProjectSem option:selected');
                    let supervisor = $('#filter_Supervisor option:selected');
                    let search = $('#filter_Search');
                    search.val(search.val().replace(/[^a-zA-Z0-9\s\-()]/gi, ""));
                    supervisor.val(supervisor.val().replace(/[^a-zA-Z._\s\-]/gi, ""));
                    sem.val(sem.val().replace(/[^0-9]/gi, ""));
                    year.val(year.val().replace(/[^0-9]/gi, ""));
                });

			</script>
	    

	    <div class="container col-sm-1 col-md-1 col-lg-1">
	    	<div class="float-panel">
	    		<br/><br/><br/>
            		<a href="#backtop"><img src="../../../images/totop.png" width="40%" height="40%" /></a><br/>
            		<a href="#tobottom"><img src="../../../images/tobottom.png" width="40%" height="40%" /></a><br/>
	    	</div>

	    </div> 
	    
	    
		 <!-- closing navigation div in nav.php -->
	 </div>
	 
	</div>
</div>
	<!-- for going back to bottom --> 
	<div id="tobottom"></div>

<?php require_once('../../../footer.php'); ?>
</body>
<!-- InstanceEnd -->
</html>
<?php
unset($rsProject);
?>
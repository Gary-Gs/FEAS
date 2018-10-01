<?php 
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');?>

<?php
$csrf = new CSRFProtection();


$_REQUEST['csrf'] = $csrf->cfmRequest();


$search = '';
$maxRows_rsStaff = 15;
$pageNum_rsStaff = 0;

if (isset($_GET['pageNum_rsStaff'])) {
	$pageNum_rsStaff = $_GET['pageNum_rsStaff'];
}
$startRow_rsStaff = $pageNum_rsStaff * $maxRows_rsStaff;

if(isset($_REQUEST['search'])) {
	$maxRows_rsStaff = 1000;
	$search = $_REQUEST['search'];
	$searchWildcard = '%'.$search.'%';
	$query_rsStaff = "SELECT id, name, workload , examine  FROM ". $TABLES['staff']." WHERE id LIKE ? OR name LIKE ? ORDER BY name ASC";
	$query_ExaminableStaffCount = "SELECT count(*) FROM ". $TABLES['staff']." WHERE (id LIKE ? OR name LIKE ?)  AND examine = 1";
} else {
	$query_rsStaff = "SELECT id, name, workload , examine FROM ". $TABLES['staff']." ORDER BY name ASC";
	$query_ExaminableStaffCount = "SELECT count(*) FROM ". $TABLES['staff']." WHERE examine = 1";
}



try
{
	// $rsStaff = $conn_db_ntu->query($query_rsStaff)->fetchAll();
	$stmt = $conn_db_ntu->prepare($query_rsStaff);
	$stmt->bindParam(1, $searchWildcard);
	$stmt->bindParam(2, $searchWildcard);
	$stmt->execute();
	$rsStaff = $stmt->fetchAll();
	$stmt = $conn_db_ntu->prepare($query_ExaminableStaffCount);
	$stmt->bindParam(1, $searchWildcard);
	$stmt->bindParam(2, $searchWildcard);
	$stmt->execute();
	$RowCount = $stmt->fetchColumn();
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
	<title>Faculty Settings</title>
	<?php require_once('../../../head.php'); ?>
	<script type="text/javascript">
		
		
		function checkExamine(val)
		{
			$('.chk').prop('checked', val);
		}
	</script>
</head>

<body>
	<div id="loadingdiv" class="loadingdiv">
		<img id="loadinggif" src="../../../images/loading.gif" />
		<p>Waiting for server to respond!</p>
	</div>
	<div id="bar"></div>
	<div id="wrapper">
		<div id="header"></div>
		
		<div id="left">
			<div id="nav">
				<?php require_once('../../nav.php'); ?>
			</div>
		</div>
		
		<div id="logout">
			<a href="../../../logout.php"><img src="../../../images/logout.jpg" /></a>
		</div>
		
		<!-- InstanceBeginEditable name="Content" -->
		<div id="content">
			<h1>Faculty Settings for Full Time Projects</h1>
			<?php 
			
			if(isset($_REQUEST['save'])) echo "<p class='success'> Faculty settings saved.</p>";
			if(isset($_REQUEST['examiner_setting'])) {
				echo "<p class='success'> Faculty settings uploaded successfully.</p>";
			}
			if(isset($_REQUEST['clear'])) echo "<p class='warn'> Faculty changes cleared.</p>";
			
			
			
			if (isset ($_REQUEST['csrf']) || isset ($_REQUEST['validate'])) echo "<p class='warn'> CSRF validation failed. </p>";
			else  {
				?>
				<div id="topcon">
					<?php require_once('../../../upload_head.php'); ?>
					<form id="FORM_FileToUpload_ExaminerSettings" class="form-inline" enctype="multipart/form-data" role="form">
						<table style="text-align: left; width: 100%;"> 
							<col width="20%" />
							<col width="20%" />
							<col width="20%" />
							<col width="20%" />
							<col width="20%" />
							<tr >
								<td colspan="4">
									<label  style="color:Orange;">Please ensure you have the latest staff list uploaded. You can do it 
										<a href="../gen/faculty.php">here</a>
									</label>
								</td>
								<td style="text-align: right;">
									<input type="submit" value="Import" name="submit" class="btn btn-xs btn-success">
								</td>
							</tr>
							<tr>
								<td colspan="5">
									Please select the <b><u>Examinable Staff List</u></b> and <b><u>Workload Staff List</u></b>:
								</td>
							</tr>
							<tr>
								<td colspan="5">File Name format: <b>examinable_staff_list</b> & <b>workload_staff_list</b></td>
							</tr>
							<tr>
								<td colspan="2">
									<input type="file" id="FileToUpload_ExaminerSettings" name="FileToUpload_ExaminerSettings[]" multiple="multiple"/>
								</td>
								<td colspan="3">
									<ul id="FileToUpload_FileList"></ul>
								</td>
							</tr>
							<tr>
								<td colspan="5">
									<div id="progressbardiv"  class="progress" style="display: none;">
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
					<script type="text/javascript">
						IsValidFileUpload = true;
						// To list all the selected files
						$( "#FileToUpload_ExaminerSettings" ).change(function() {
							var FileToUpload_ExaminerSettings 	= _("FileToUpload_ExaminerSettings");
							var FileToUpload_FileList 			= _("FileToUpload_FileList");

							//empty list for now...
							FileToUpload_FileList.innerHTML = "";
							while (FileToUpload_FileList.hasChildNodes()) {
								FileToUpload_FileList.removeChild(FileToUpload_FileList.firstChild);
							}

							if(FileToUpload_ExaminerSettings.files.length != 2){
								FileToUpload_ExaminerSettings.value = "";
								alert("Please select only 2 files for upload!");
							}else{
								
								// display every file...
								for (var FileIndex = 0; FileIndex < FileToUpload_ExaminerSettings.files.length; FileIndex++) {
									//add to list
									var li = document.createElement('li');
									li.innerHTML = 'File ' + (FileIndex + 1) + ':  ' + FileToUpload_ExaminerSettings.files[FileIndex].name;
									Filename =  FileToUpload_ExaminerSettings.files[FileIndex].name.substr(0, FileToUpload_ExaminerSettings.files[FileIndex].name.indexOf('.')); 

									if(	Filename.toLowerCase() == "examinable_staff_list" ){
										li.innerHTML += " (Valid)";
										li.setAttribute("style", "list-style-type: none; color: green;");
									} else if( Filename.toLowerCase() == "workload_staff_list" ){
										li.innerHTML += " (Valid)";
										li.setAttribute("style", "list-style-type: none; color: green;");
									} else{
										li.setAttribute("style", "list-style-type: none; color: red;");
										li.innerHTML += " (Invalid)";
									}
									FileToUpload_FileList.append(li);
								}
								if( (FileToUpload_ExaminerSettings.files[0].name.toLowerCase().includes("examinable_staff_list") || FileToUpload_ExaminerSettings.files[0].name.toLowerCase().includes("workload_staff_list")) && (FileToUpload_ExaminerSettings.files[1].name.toLowerCase().includes("examinable_staff_list") ||FileToUpload_ExaminerSettings.files[1].name.toLowerCase().includes("workload_staff_list"))){
									IsValidFileUpload=true;
								} else{
									IsValidFileUpload=false;
								}
							}
						});

						$( "#FORM_FileToUpload_ExaminerSettings" ).submit(function( event ) {
							if(IsValidFileUpload){
								// alert("UPLOAD ME");
								UploadFile_ExaminerSettings();
							}else{
								alert("Please check that you have the correct file and file name format");
							}
							
							event.preventDefault();
						});
						function _(el){
							return document.getElementById(el);
						}
						function UploadFile_ExaminerSettings(){
							if(_("FileToUpload_ExaminerSettings").files.length != 2) {
								alert("Please ensure you have the two files selected!");
							}
							else {
								var FileToUpload_ExaminerSettings 	= _("FileToUpload_ExaminerSettings");
								var csrfToken = _("CSRF_token").value;
								// console.log(FileToUpload_ExaminerSettings.name + ", "+ FileToUpload_ExaminerSettings.size +", "+ FileToUpload_ExaminerSettings.type);
								var formData = new FormData();

								for (var x = 0; x < FileToUpload_ExaminerSettings.files.length; x++) {
									formData.append("FileToUpload_ExaminerSettings[]", FileToUpload_ExaminerSettings.files[x]);
								}
								formData.append("csrf__",csrfToken );
								_("loadingdiv").style.display  = "block";
								$.ajax({
									url: 'submit_import_examiner_settings.php',
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
				                    			$("#progressbar").text(percentComplete + '%');
				                    			$("#progressbar").css('width', percentComplete + '%');
				                    			if(percentComplete == 100){
				                    				_('status').innerHTML = "File uploaded. Waiting for server to respond!";
				                    			}
				                    		}
				                    	}, false );
				                    	return xhr;
				                    },
				                    success: function (data) {
										console.log(data);
				                    	_("progressbardiv").style.display  = "none";
				                    	_("loadingdiv").style.display  = "none";
				                    	$("#progressbar").text(0 + '%');
				                    	$("#progressbar").css('width', 0 + '%');		
				                    	if(data.includes("error_code"))	{
				                    		error_code_index = data.indexOf("error_code");
				                    		error_code_type = data.substring(error_code_index); 
				                    		error_code_int = error_code_type.substring(error_code_type.length-1);
				                    		_('status').innerHTML = "";
				                    		switch(error_code_int){
				                    			case '1': 
				                    			alert("Uploaded file has no file name. Aborted."); 
				                    			break;
				                    			case '2': 
				                    			alert("Uploaded file has an invalid format type. Only excel files (.xlsx .xls .csv) allowed."); 
				                    			break;
												//by right this error wont show if upload file from own computer
												//this error will show if u choose a file from the server  
				                    			case '3': 
				                    			alert("File is in use. Please try again. Aborted."); 
				                    			break;
				                    		}
				                    	}else{
				                    		console.log("File uploaded. Server Responded!");
				                    		_('status').innerHTML = "File uploaded. Server Responded!";
				                    		window.location.href = ("examiner_setting.php?" + data);
				                    	}

				                    },
				                    error: function(data){
				                    	console.log("File upload failed!");
				                    	_('status').innerHTML = "File upload failed!";
				                    }
				                });
							}
						}
					</script>
					<br/>
					<form name="searchbox" action="examiner_setting.php" method="post">
						<table style="width: 100%;">
							<col width="20%" />
							<col width="20%" />
							<tr>
								<td colspan="2" style="text-align: left;">
									<?php echo "Total staff(s) that can examine : " . $RowCount . "/" .count($rsStaff) ?>
								</td>
								<td colspan="3" style="text-align: right;">
									<input type="search" name="search" value="<?php echo $search; ?>" />
									<input type="submit" value="Search" title="Search for a staff" class="bt"/>
								</td>
							</tr>
						</table>
						<?php $csrf->echoInputField();?>
					</form>
					<br/>
					<form action="submit_savewl.php" method="post">
						<?php $csrf->echoInputField();?>
						<table border="1" cellpadding="0" cellspacing="0" width="700">
							<col width="200" />
							<col width="120" />
							<col width="180" />
							<col width="180" />

							<tr class="heading">
								<td>Staff Name</td>
								<td>Staff ID</td>
								<td>Workload</td>
								<td>Can Examine</td>
							</tr>

							<tr>
								<td></td>
								<td></td>
								<td></td>
								<td><a onclick="javascript:checkExamine(true);">Check All</a> / <a onclick="javascript:checkExamine(false);" >Uncheck All</a></td>
							</tr>

							<?php
							foreach ($rsStaff as $row_rsStaff) { 
								$staffid = str_replace('.', '', $row_rsStaff['id']);
								echo "<tr>";
								echo "<input type='hidden' id='index_". $staffid ."' name='index_". $staffid ."' value='".$row_rsStaff['id']."'/>";
								echo "<td>" . $row_rsStaff['name'] . "</td>";
								echo "<td>" . $row_rsStaff['id'] . "</td>";
								echo "<td>";
								echo ($row_rsStaff['workload'] == null) ? "<input type='number' id='workload_". $staffid ."' name='workload_".$staffid."' min='0' max='100' value='0' />" :
								"<input type='number' id='workload_". $staffid ."' name='workload_".$staffid."' min='0' max='100' value='".$row_rsStaff['workload']."' />";
								echo "</td>";
								echo "<td>";
								echo ($row_rsStaff['examine']) ? "<input type='checkbox' class='chk' id='examine_".$staffid."' name='examine_".$staffid."' checked />" :
								"<input type='checkbox' class='chk' id='examine_".$staffid."' name='examine_".$staffid."' />";
								echo "</td>";
								echo "</tr>";								
							} 
							?>
						</table>

						<div style="float:right; padding-top:25px;">
							<input type="submit" title="Save all changes" value="Save Changes" class="bt" style="font-size:12px !important;"/>
						</div>
					</form>
				</div>
				<?php }?>
		</div>

		<!-- InstanceEndEditable --> 
		<?php require_once('../../../footer.php'); ?>
	</div>
</body>
</html>

<?php
unset($rsStaff);
?>

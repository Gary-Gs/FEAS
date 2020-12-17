
<?php
/* Wee Teck Zong [12.05.2020]
- Whole webpage created by Wee Teck Zong
- Purpose for this webpage is to use ProjectID passed from project.php to edit the specific row of project
- *Important* - Project ID has been disabled from editing to prevent any errors from interlinked tables
*/
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');
require_once('../../../restriction.php');

/* Wee Teck Zong [12.05.2020]
- Check Project ID recevied from project.php and retrieve the following from the specific project
1) Project ID;
2) year;
3) sem;
4) title;
5) Supervisor;
6) examine_year;
7) examine_sem ;

*/
  if(isset($_GET['edit']))
  {
      $id = $_GET['edit'];
      $res= "SELECT * FROM " .
          $TABLES['fea_projects'] . " as p1 LEFT JOIN " .
          $TABLES['fyp_assign'] 	. " as p2 ON p1.project_id 	= p2.project_id LEFT JOIN "	.
          $TABLES['fyp']			. " as p3 ON p2.project_id 	= p3.project_id LEFT JOIN "	.
          $TABLES['staff']		. " as p4 ON p2.staff_id 	= p4.id "					.
          "WHERE p2.project_id ='$id'";
          $stmt_0 			= $conn_db_ntu->prepare($res);
          $stmt_0->execute();
      $row=  $stmt_0->fetchAll(PDO::FETCH_ASSOC);
      foreach ($row as $key => $value) {
        // echo $value['title'];
      }
  }

  /* Wee Teck Zong [12.05.2020]
  - Check if Sem has been edited
  - If edited, Update the changes in database
  - *Important* - Due to both 'fyp' & 'fyp_assign' table having the sem of proj, both tables has to be updated as shown below
  */
  if( isset($_POST['newsem']) )
  {
    $newSem = $_POST['newsem'];
    $id   = $_POST['id'];
    $assigntablename = $TABLES['fyp_assign'];
    $fyptablename = $TABLES['fyp'];
    $res = "UPDATE $assigntablename SET sem=? WHERE project_id=?";
    $stmt_1= $conn_db_ntu->prepare($res);
    $stmt_1->execute([$newSem, $id]);

    $res1 = "UPDATE $fyptablename SET sem=? WHERE project_id=?";
    $stmt_2= $conn_db_ntu->prepare($res1);
    $stmt_2->execute([$newSem, $id]);
  }

  /* Wee Teck Zong [12.05.2020]
  - Check if year has been edited
  - If edited, Update the changes in database
  */
  if( isset($_POST['newyear']) )
  {
    $newYear = $_POST['newyear'];
    $id   = $_POST['id'];
    $assigntablename = $TABLES['fyp_assign'];

    $res = "UPDATE $assigntablename SET year=? WHERE project_id=?";
    $stmt_1= $conn_db_ntu->prepare($res);
    $stmt_1->execute([$newYear, $id]);
  }

  /* Wee Teck Zong [12.05.2020]
  - Check if Title has been edited
  - If edited, Update the changes in database
  */
  if( isset($_POST['newtitle']) )
  {
    $newTitle = $_POST['newtitle'];
    $id   = $_POST['id'];
    $fyptablename = $TABLES['fyp'];
    $res = "UPDATE $fyptablename SET title=? WHERE project_id=?";
    $stmt_1= $conn_db_ntu->prepare($res);
    $stmt_1->execute([$newTitle, $id]);
  }

  /* Wee Teck Zong [12.05.2020]
  - Check if Supervisor has been edited
  - If edited, Update the changes in database
  */
  if( isset($_POST['newsupervisor']) )
  {
    $newSupervisor = $_POST['newsupervisor'];
    $id   = $_POST['id'];
    $fyptablename = $TABLES['fyp'];
    $res = "UPDATE $fyptablename SET Supervisor=? WHERE project_id=?";
    $stmt_1= $conn_db_ntu->prepare($res);
    $stmt_1->execute([$newSupervisor, $id]);
  }

  /* Wee Teck Zong [12.05.2020]
  - Check if examine_year has been edited
  - If edited, Update the changes in database
  */
  if( isset($_POST['newexamyear']) )
  {
    $newExamYear = $_POST['newexamyear'];
    $id   = $_POST['id'];
    $fyptablename = $TABLES['fea_projects'];
    $res = "UPDATE $fyptablename SET examine_year=? WHERE project_id=?";
    $stmt_1= $conn_db_ntu->prepare($res);
    $stmt_1->execute([$newExamYear, $id]);
  }

  /* Wee Teck Zong [12.05.2020]
  - Check if examine_sem has been edited
  - If edited, Update the changes in database
  */
  if( isset($_POST['newexamsem']) )
  {
    $newExamSem = $_POST['newexamsem'];
    $id   = $_POST['id'];
    $fyptablename = $TABLES['fea_projects'];
    $res = "UPDATE $fyptablename SET examine_sem=? WHERE project_id=?";
    $stmt_1= $conn_db_ntu->prepare($res);
    $stmt_1->execute([$newExamSem, $id]);
  }

  /* Wee Teck Zong [12.05.2020]
  - Check if any fields has been edited.
  - refresh the page to update and redirect to project.php
  */
  if( isset($_POST['newyear']) || isset($_POST['newsem']) || isset($_POST['newtitle']) || isset($_POST['newsupervisor']) || isset($_POST['newexamyear']) )
  {
      echo "<meta http-equiv='refresh' content='0; url=project.php'>";
  }
  ?>
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
  	<?php require_once('../../../php_css/headerwnav.php');?>



  	<div style="margin-left: -15px;">
  		<div class="container-fluid">
              <?php require_once('../../nav.php'); ?>
<?php require_once('../../../upload_head.php'); ?>
                  <!-- Page Content Holder -->
                  <div class="container-fluid">
                    <form action="edit.php" method="POST">
                      <br/>
                     	<h1 style="color: black;">Edit Project List<br/></h1>
                      <div class="table-responsive">
                          <table id="tables" width="100%" border="1">
                              <col width="13%" />
                              <col width="6%" />
                              <col width="5%" />
                              <col width="32%" />
                              <col width="30%" />
                              <col width="6%" />
                              <col width="6%" />

                              <tr class="bg-dark text-black text-center">
                                  <td>Project ID</td>
                                  <td>Year</td>
                                  <td>Sem</td>
                                  <td>Project Title</td>
                                  <td>Supervisor</td>
                                  <td>Exam Year</td>
                                  <td>Exam Sem</td>
                              </tr>

                              <tr class="bg-dark text-black text-center">
                                  <td><?php echo $value['project_id'] ?> </td>
                                  <td><input type="text" name="newyear" value='<?php echo $value['year'] ?> '></td>
                                  <td><input type="text" name="newsem" value='<?php echo $value['sem'] ?> '></td>
                                  <td><input type="text" name="newtitle" value=' <?php echo htmlspecialchars($value['title'])  ?>' > </input></td>
                                  <td><input type="text" name="newsupervisor" value='<?php echo $value['Supervisor'] ?> '></td>
                                  <td><input type="text" name="newexamyear" value='<?php echo $value['examine_year'] ?>' ></td>
                                  <td><input type="text" name="newexamsem" value='<?php echo $value['examine_sem'] ?>' ></td>
                              </tr>
                            </table>
                            <input type="hidden" name="id" value="<?php echo $value['project_id'] ?>">
                            <input type="submit" value=" Update "/>
                            </div>
                  </div>
                  </form>
              <!-- closing navigation div in nav.php -->
              </div>

  		</div>

  	</div>

  		<?php require_once('../footer.php'); ?>
  </body>

  </html>

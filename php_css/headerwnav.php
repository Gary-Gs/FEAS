<!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1, shrink-to-fit=yes">


    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
    
    <link rel="stylesheet" href="/css/new_main.css" type="text/css">

    <div class="container-fluid bg-dark text-white" style="background-color: white; opacity: 0.9; filter: alpha(opacity=90);">
			<h4 class="float-left" style="margin-top:12px">SCSE | FYP Examiner Allocation System</h4>
			<div class="text-white float-right" style="margin-top:7px">
	                <?php if (isset($_SESSION['success'])) {
	                    //echo "<p class='success'>[Login] ".$_SESSION['success']."</p>";
	                    unset ($_SESSION['success']);
	                    }
	                        if (isset($_SESSION['displayname'])){
	                            $displayname = trim($_SESSION['displayname'], '#');
	                            echo "<p class='credentials' style='color: white;'>Welcome, ".$displayname. " <a href='/logout.php' title='Logout'>
	                            <img src='/images/logout.png' width='25px' height='25px' alt='Logout'/></a></p>";

	                            } 
	                ?>         
	        </div>
			
	</div>
    

 <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
 <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js" integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T" crossorigin="anonymous"></script>
 
 

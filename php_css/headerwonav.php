<!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1, shrink-to-fit=yes">


    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">

    <link rel="stylesheet" href="/css/new_main.css" type="text/css">

    <div class="container-fluid bg-dark text-white" style="background-color: white; opacity: 0.9; filter: alpha(opacity=90);">
        <?php // users who are able to access all modules
        $verifiedUsers=["asfli", "sguo005", "audr0012", "jwong063", "lees0169", "ngxu0008", "c170155", "c170178", "SNKoh", "c170098", "teew0007", "hoang009", "weet0011"];

        // only verified users navigate to all modules
        if (in_array($_SESSION['id'], $verifiedUsers)) {
            echo "<a href='/index.php'><h4 class='float-left' style='font-family: Poppins, sans-serif;font-size: 1.2em; color:white;
		font-weight: 300; line-height: 1.7em;padding:5px'>SCSE | FYP Examiner Allocation System</h4></a>";
        }
        else {
            echo "<a href='/pref/nav.php'><h4 class='float-left' style='font-family: Poppins, sans-serif;font-size: 1.2em; color:white;
		font-weight: 300; line-height: 1.7em;padding:5px'>SCSE | FYP Examiner Allocation System</h4></a>";
        }

        if (isset($_SESSION['success'])) {
            //echo "<p class='success'>[Login] ".$_SESSION['success']."</p>";
            unset ($_SESSION['success']);
        }
        if (isset($_SESSION['displayname'])){
            $displayname = trim($_SESSION['displayname'], '#');
            echo "<p class='credentials' style='color: white; text-align:right;padding:5px'>Welcome, ".$displayname. " <a href='/logout.php' title='Logout'>
                                    <img src='/images/logout.png' width='25px' height='25px' alt='Logout'/></a></p>";

        }
        ?>
    </div>

<!-- Optional JavaScript -->
 <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
 <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js" integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T" crossorigin="anonymous"></script>

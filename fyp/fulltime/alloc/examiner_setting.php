<?php
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php'); ?>

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


if (isset($_REQUEST['search'])) {
    $maxRows_rsStaff = 1000;
    $search = $_REQUEST['search'];
    $searchWildcard = '%' . $search . '%';
    $query_rsStaff = "SELECT id, email, name, name2, exemption , examine  FROM " . $TABLES['staff'] . " WHERE id LIKE ? OR name LIKE ? OR name2 LIKE ? ORDER BY name ASC";
    $query_ExaminableStaffCount = "SELECT count(*) FROM " . $TABLES['staff'] . " WHERE (id LIKE ? OR name LIKE ? OR name2 LIKE ?)  AND examine = 1";
} else {
    $query_rsStaff = "SELECT id, email, name, name2, exemption , examine FROM " . $TABLES['staff'] . " ORDER BY name ASC";
    $query_ExaminableStaffCount = "SELECT count(*) FROM " . $TABLES['staff'] . " WHERE examine = 1";
}

// retrieve sem 2 exemption value
if (isset($_REQUEST['filter_Sem']) && $_REQUEST["filter_Sem"] == 2) {
    $query_rsStaff = "SELECT id, email, name, name2, exemptionS2 , examine FROM " . $TABLES['staff'] . " ORDER BY name ASC";
    $query_ExaminableStaffCount = "SELECT count(*) FROM " . $TABLES['staff'] . " WHERE examine = 1";
}


try {
    // $rsStaff = $conn_db_ntu->query($query_rsStaff)->fetchAll();
    $stmt = $conn_db_ntu->prepare($query_rsStaff);
    $stmt->bindParam(1, $searchWildcard);
    $stmt->bindParam(2, $searchWildcard);
    $stmt->bindParam(3, $searchWildcard);
    $stmt->execute();
    $rsStaff = $stmt->fetchAll();
    $stmt = $conn_db_ntu->prepare($query_ExaminableStaffCount);
    $stmt->bindParam(1, $searchWildcard);
    $stmt->bindParam(2, $searchWildcard);
    $stmt->bindParam(3, $searchWildcard);
    $stmt->execute();
    $RowCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    die($e->getMessage());
}

$conn_db_ntu = null;
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Faculty Settings</title>

    <script type="text/javascript">
        function checkExamine(val) {
            $('.chk').prop('checked', val);
        }
    </script>
</head>

<body>
<?php require_once('../../../php_css/headerwnav.php'); ?>

<div id="loadingdiv" class="loadingdiv">
    <img id="loadinggif" src="../../../images/loading.gif"/><br/>
    <p>Waiting for server to respond!</p>
</div>

<div style="margin-left: -15px;">
    <div class="container-fluid">
        <?php require_once('../../nav.php'); ?>
        <div class="container-fluid">
            <!-- for going back to top -->
            <div id="backtop"></div>
            <h3>Faculty Settings for Full Time Projects</h3>
            <?php
            if (isset($_REQUEST['save'])) echo "<p class='success'> Faculty settings saved.</p>";
            if (isset($_REQUEST['examiner_setting'])) {
                echo "<p class='success'> Faculty settings uploaded successfully.</p>";
            }
            if (isset($_REQUEST['clear'])) echo "<p class='warn'> Faculty changes cleared.</p>";


            if (isset ($_REQUEST['csrf']) || isset ($_REQUEST['validate'])) echo "<p class='warn'> CSRF validation failed. </p>";
            else {
                ?>

                <?php require_once('../../../upload_head.php'); ?>
                <form id="FORM_FileToUpload_ExaminerSettings" class="form-inline" enctype="multipart/form-data"
                      role="form">
                    <table style="text-align: left; width: 100%;">
                        <col width="20%"/>
                        <col width="20%"/>
                        <col width="20%"/>
                        <col width="20%"/>
                        <col width="20%"/>
                        <tr>
                            <td style="text-align: left; color:Orange;" colspan="4">
                                Please ensure you have the latest staff list uploaded. You can do it <u><a
                                            href="../gen/faculty.php"> here</a></u>

                            </td>
                            <td style="text-align: right;">
                                <input type="submit" value="Import" name="submit" class="btn btn-xs btn-success">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                Please select the <b><u>examiner list</u></b> and <b><u>exemption</u></b>:
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5">File Name format: <b>fyp examiner list_for dr li fang_30aug2018</b> & <b>exemption</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="file" id="FileToUpload_ExaminerSettings"
                                       name="FileToUpload_ExaminerSettings[]" multiple="multiple"/>
                            </td>
                            <td colspan="3">
                                <ul id="FileToUpload_FileList"></ul>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <div id="progressbardiv" class="progress" style="display: none;">
                                    <div id="progressbar" class="progress-bar progress-bar-success" role="progressbar"
                                         style="width:0%; color:black; ">
                                        <span>0%</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <div id="status"></div>
                            </td>
                        </tr>
                    </table>
                    <?php $csrf->echoInputField(); ?>
                </form>
                <script type="text/javascript">
                    var IsValidFileUpload = true;
                    // To list all the selected files
                    $("#FileToUpload_ExaminerSettings").change(function () {
                        var FileToUpload_ExaminerSettings = _("FileToUpload_ExaminerSettings");
                        var FileToUpload_FileList = _("FileToUpload_FileList");

                        //empty list for now...
                        FileToUpload_FileList.innerHTML = "";
                        while (FileToUpload_FileList.hasChildNodes()) {
                            FileToUpload_FileList.removeChild(FileToUpload_FileList.firstChild);
                        }

                        /*if(FileToUpload_ExaminerSettings.files.length != 2){
                            FileToUpload_ExaminerSettings.value = "";
                            alert("Please select only 2 files for upload!");
                        }*/
                        if (FileToUpload_ExaminerSettings.files.length == 0 || FileToUpload_ExaminerSettings.files.length > 3) {
                            alert("Please select the correct number of file(s) for upload!");
                        } else {

                            var selected = $('#filter_Sem :selected').text();

                            // display every file...
                            for (var FileIndex = 0; FileIndex < FileToUpload_ExaminerSettings.files.length; FileIndex++) {
                                //add to list
                                var li = document.createElement('li');
                                li.innerHTML = 'File ' + (FileIndex + 1) + ':  ' + FileToUpload_ExaminerSettings.files[FileIndex].name;
                                Filename = FileToUpload_ExaminerSettings.files[FileIndex].name.substr(0, FileToUpload_ExaminerSettings.files[FileIndex].name.indexOf('.'));

                                if (Filename.toLowerCase() == "examiner_list") {
                                    li.innerHTML += " (Valid)";
                                    li.setAttribute("style", "list-style-type: none; color: green;");
                                } else if ((selected == "2" && Filename.toLowerCase() == "exemption") || (selected =="2" && Filename.toLowerCase() == "master")) {
                                    li.innerHTML += " (Valid)";
                                    li.setAttribute("style", "list-style-type: none; color: green;");
                                } else {
                                    li.setAttribute("style", "list-style-type: none; color: red;");
                                    li.innerHTML += " (Invalid)";
                                }
                                FileToUpload_FileList.append(li);
                            }

                            if (selected == "1" && FileToUpload_ExaminerSettings.files[0].name.toLowerCase().includes("examiner_list") && FileToUpload_ExaminerSettings.files.length < 2) {
                                IsValidFileUpload = true;
                            }
                            else if (selected == "2" && FileToUpload_ExaminerSettings.files.length == 3 && ((FileToUpload_ExaminerSettings.files[0].name.toLowerCase().includes("examiner_list") || FileToUpload_ExaminerSettings.files[0].name.toLowerCase().includes("master") || FileToUpload_ExaminerSettings.files[0].name.toLowerCase().includes("exemption"))
                                && (FileToUpload_ExaminerSettings.files[1].name.toLowerCase().includes("examiner_list") || FileToUpload_ExaminerSettings.files[1].name.toLowerCase().includes("master") || FileToUpload_ExaminerSettings.files[1].name.toLowerCase().includes("exemption"))
                                && (FileToUpload_ExaminerSettings.files[2].name.toLowerCase().includes("examiner_list") || FileToUpload_ExaminerSettings.files[2].name.toLowerCase().includes("master") || FileToUpload_ExaminerSettings.files[2].name.toLowerCase().includes("exemption")))) {
                                IsValidFileUpload = true;
                            }
                            /*if((FileToUpload_ExaminerSettings.files[0].name.toLowerCase().includes("examinable_staff_list")) || ((FileToUpload_ExaminerSettings.files[0].name.toLowerCase().includes("examinable_staff_list") || FileToUpload_ExaminerSettings.files[0].name.toLowerCase().includes("workload_staff_list"))
                                && (FileToUpload_ExaminerSettings.files[1].name.toLowerCase().includes("examinable_staff_list") ||FileToUpload_ExaminerSettings.files[1].name.toLowerCase().includes("workload_staff_list") ))){
                                IsValidFileUpload=true;

                            }*/ else {
                                IsValidFileUpload = false;
                            }
                        }
                    });


                    $("#FORM_FileToUpload_ExaminerSettings").submit(function (event) {

                        if (IsValidFileUpload) {
                            //alert("UPLOAD ME");
                            UploadFile_ExaminerSettings();
                        } else {
                            alert("Please check that you have the correct files and file name format");
                        }

                        event.preventDefault();
                    });

                    function _(el) {
                        return document.getElementById(el);
                    }

                    function UploadFile_ExaminerSettings() {
                        if (_("FileToUpload_ExaminerSettings").files.length == 0 || _("FileToUpload_ExaminerSettings").files.length > 3) {
                            alert("Please ensure you have the correct number of file(s) selected!");
                        } else {
                            var FileToUpload_ExaminerSettings = _("FileToUpload_ExaminerSettings");
                            var csrfToken = _("CSRF_token").value;

                            // console.log(FileToUpload_ExaminerSettings.name + ", "+ FileToUpload_ExaminerSettings.size +", "+ FileToUpload_ExaminerSettings.type);
                            var formData = new FormData();

                            for (var x = 0; x < FileToUpload_ExaminerSettings.files.length; x++) {
                                formData.append("FileToUpload_ExaminerSettings[]", FileToUpload_ExaminerSettings.files[x]);

                            }

                            //console.log(formData.get("FileToUpload_ExaminerSettings")[1].name);
                            formData.append("filter_Sem", $('#filter_Sem :selected').text());
                            formData.append("filter_Year", $('#filter_Year :selected').text());
                            // i change this from csrf__ to validate but not sure why is it csrf__ in the first place.
                            formData.append("validate", csrfToken);
                            _("loadingdiv").style.display = "block";
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
                                        _("progressbardiv").style.display = "block";
                                        if (evt.lengthComputable) {
                                            var percentComplete = evt.loaded / evt.total;
                                            percentComplete = parseInt(percentComplete * 100);
                                            $("#progressbar").text(percentComplete + '%');
                                            $("#progressbar").css('width', percentComplete + '%');
                                            if (percentComplete == 100) {
                                                _('status').innerHTML = "File uploaded. Waiting for server to respond!";
                                            }
                                        }
                                    }, false);
                                    return xhr;
                                },
                                success: function (data) {
                                    console.log(data);
                                    _("progressbardiv").style.display = "none";
                                    _("loadingdiv").style.display = "none";
                                    $("#progressbar").text(0 + '%');
                                    $("#progressbar").css('width', 0 + '%');
                                    if (data.includes("error_code")) {
                                        error_code_index = data.indexOf("error_code");
                                        error_code_type = data.substring(error_code_index);
                                        error_code_int = error_code_type.substring(error_code_type.length - 1);
                                        _('status').innerHTML = "";
                                        switch (error_code_int) {
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
                                    } else {
                                        console.log("File uploaded. Server Responded!");
                                        _('status').innerHTML = "File uploaded. Server Responded!";
                                        window.location.href = ("examiner_setting.php?" + data);
                                    }

                                },
                                error: function (data) {
                                    console.log("File upload failed!");
                                    _('status').innerHTML = "File upload failed!";
                                }
                            });
                        }
                    }
                </script>
                <br/>
                <form name="searchbox" action="#" method="post">
                    <table style="width: 100%;">
                        <colgroup>
                            <col width="20%"/>
                            <col width="20%"/>
                            <col width="20%"/>
                        </colgroup>
                        <tr>
                            <td>
                                <b> Exam AC Year</b>
                                <select id="filter_Year" name="filter_Year" onchange="this.form.submit()">
                                    <?php
                                    $currentYear = sprintf("%04d", substr(date("Y"), 0));
                                    $earliestYear = $currentYear - 10;

                                    // Loops over each int[year] from current year, back to the $earliest_year [1950]
                                    foreach (range($currentYear, $earliestYear) as $i) {


                                        if (isset($_REQUEST["filter_Year"]) && $_REQUEST["filter_Year"] == $i) {
                                            echo "<option selected value='" . $i . "'>" . $i . "</option>";
                                        } else {
                                            echo "<option value='" . $i . "'>" . $i . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>

                            <td>
                                <b>Sem</b>

                                <select id="filter_Sem" name="filter_Sem" onchange="this.form.submit()">
                                    <!--<option value="">SELECT</option>-->
                                    <?php
                                    for ($index = 1; $index < 3; $index++) {
                                        if (isset($_REQUEST["filter_Sem"]) && $_REQUEST["filter_Sem"] == $index) {
                                            echo "<option selected value='" . $index . "'>" . $index . "</option>";
                                        } else {
                                            echo "<option value='" . $index . "'>" . $index . "</option>";
                                        }
                                    }
                                    ?>
                                </select>

                        </tr>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: left;">
                                <?php echo "Total staff(s) that can examine : " . $RowCount . "/" . count($rsStaff) ?>
                            </td>
                            <td colspan="3" style="text-align: right;">
                                <input type="search" name="search" value="<?php echo $search; ?>"/>
                                <input type="submit" value="Search" title="Search for a staff" class="bt"/>
                            </td>
                        </tr>
                    </table>
                    <?php $csrf->echoInputField(); ?>
                </form>
                <br/>

                <form action="submit_savewl.php" method="post">
                    <?php $csrf->echoInputField(); ?>
                    <table id="staffTable" border="1" cellpadding="0" cellspacing="0" width="100%">
                        <col width="25%"/>
                        <col width="25%"/>
                        <col width="25%"/>
                        <col width="15%"/>
                        <col width="10%"/>

                        <tr class="bg-dark text-white text-center">
                            <td>Staff Name</td>
                            <td>Staff Name2</td>
                            <td>Staff Email</td>
                            <td>Exemption</td>
                            <td>Can Examine</td>
                        </tr>

                        <tr class="text-center">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><a onclick="javascript:checkExamine(true);">Check All</a> / <a
                                        onclick="javascript:checkExamine(false);">Uncheck All</a></td>
                        </tr>

                        <?php
                        foreach ($rsStaff as $row_rsStaff) {
                            $staffid = str_replace('.', '', $row_rsStaff['id']);
                            echo "<tr class='text-center'>";
                            echo "<input type='hidden' id='index_" . $staffid . "' name='index_" . $staffid . "' value='" . $row_rsStaff['id'] . "'/>";
                            echo "<td>";
                            echo ($row_rsStaff['name'] != null) ? "<input type='text' name='name_" . $staffid . "'  value='" . $row_rsStaff['name'] . "' required />" :
                                "<input type='text'  name='name_" . $staffid . "'  required />";
                            echo "</td>";
                            echo "<td>";
                            echo ($row_rsStaff['name2'] != null) ? "<input type='text' id='name2_" . $staffid . "' name='name2_" . $staffid . "'  value='" . $row_rsStaff['name2'] . "' required />" :
                                "<input type='text' id='name2_" . $staffid . "' name='name2_" . $staffid . "' required />";
                            echo "</td>";
                            echo "<td>";
                            echo ($row_rsStaff['email'] != null) ? "<input type='email' id='email_" . $staffid . "' name='email_" . $staffid . "'  value='" . $row_rsStaff['email'] . "' required />" :
                                "<input type='text' id='email_" . $staffid . "' name='email_" . $staffid . "' required />";
                            echo "</td>";

                            echo "<td>";

                            // display sem 2 staffs' exemptions.
                            if (isset($_REQUEST['filter_Sem']) && $_REQUEST["filter_Sem"] == 2) {
                                echo ($row_rsStaff['exemptionS2'] != null) ? "<input type='number' id='exemptionS2_" . $staffid . "' name='exemptionS2_" . $staffid . "' min='0' max='100' value='" . $row_rsStaff['exemptionS2'] . "' />" :
                                    "<input type='number' id='exemptionS2_" . $staffid . "' name='exemptionS2_" . $staffid . "' min='0' max='100' value='0' />";
                            } // display sem 1 staffs' exemptions.
                            else {
                                echo ($row_rsStaff['exemption'] != null) ? "<input type='number' id='exemption_" . $staffid . "' name='exemption_" . $staffid . "' min='0' max='100' value='" . $row_rsStaff['exemption'] . "' />" :
                                    "<input type='number' id='exemption_" . $staffid . "' name='exemption_" . $staffid . "' min='0' max='100' value='0' />";
                            }
                            echo "</td>";
                            echo "<td>";
                            echo ($row_rsStaff['examine']) ? "<input type='checkbox' class='chk' id='examine_" . $staffid . "' name='examine_" . $staffid . "' checked />" :
                                "<input type='checkbox' class='chk' id='examine_" . $staffid . "' name='examine_" . $staffid . "' />";
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </table>


                    <div style="float:right">
                        <input type="button" id="addEntry" onclick="addRow()" title="Add new entry"
                               value="Add new entry" class="btn btn-primary text-white text-center"
                               style="font-size:12px"/>
                        <input type="button" id="deleteEntry" title="Delete entry"
                               value="Delete selected" class="btn btn-primary text-white text-center"
                               style="font-size:12px"/>
                    </div>
                        <br/><br/>
                        <br/><br/>

                        <div style="float:right">
                        <input type="submit" id="saveChanges" title="Save all changes" value="Save Changes"
                               class="btn bg-dark text-white text-center" style="font-size:12px !important;"/>
                        <br/><br/>
                    </div>
                </form>
                <script>
                    function addRow() {
                        let table = document.getElementById("staffTable");
                        let tr = table.insertRow(-1);
                        tr.className = "text-center";
                        let name = tr.insertCell(0);
                        let name2 = tr.insertCell(1);
                        let email = tr.insertCell(2);
                        let exemption = tr.insertCell(3);
                        let examine = tr.insertCell(4);

                        name.innerHTML = "<input type='text' name='newName[]' required />";
                        name2.innerHTML = "<input type='text' name='newName2[]' required />";
                        email.innerHTML = "<input type='email' name='newEmail[]' required />";
                        exemption.innerHTML = "<input type='number' name='newExemption[]' min='0' max='100' value='0' />";
                        examine.innerHTML = "<input type='checkbox' name='newExamine[]' class='chk'/>"

                    }

                    $(document).ready(function () {
                        $("#staffTable").click(function highlight_row() {
                            var table = document.getElementById("staffTable");
                            var cells = table.getElementsByTagName("td");

                            for (var i = 0; i < cells.length; i++) {
                                // Take each cell
                                var cell = cells[i];
                                // do something on onclick event for cell
                                cell.onclick = function () {

                                    // Get the row id where the cell exists


                                    var rowID = this.parentNode.rowIndex;
                                    console.log("selected" + rowID);
                                    var rowClicked = table.getElementsByTagName("tr")[rowID];
                                    if (rowClicked.style.backgroundColor != "yellow" && !rowClicked.classList.contains("selected")) {
                                        rowClicked.style.backgroundColor = "yellow";
                                        rowClicked.className += " selected";
                                    } else {
                                        rowClicked.style.backgroundColor = "";
                                        rowClicked.classList.remove("selected");
                                    }

                                }
                            }
                        })
                    });

                    function getHighlightedRows() {
                        var table = document.getElementById("staffTable");
                        var rows = table.getElementsByTagName("tr");
                        var selectedRowsIndex = [];
                        for (var i = 0; i < rows.length; i++) {
                            var row = rows[i];
                            if (row.style.backgroundColor == "yellow" && row.classList.contains("selected")) {
                                selectedRowsIndex.push(i);

                            }
                        }
                        return selectedRowsIndex;
                    }
                    $(document).ready(function () {
                        $("#deleteEntry").click(function deleteRow() {
                            var sTable = document.getElementById("staffTable");
                            var selectedRowsIndex = getHighlightedRows().reverse();
                            var r = confirm("Delete the selected staff entries?");
                            if (r == true){
                                for (var i = 0; i < selectedRowsIndex.length; i++) {
                                        sTable.deleteRow(selectedRowsIndex[i]);
                                }

                            }
                        })
                    });


                    function setElementIDs() {
                        var table = document.getElementById("staffTable");
                        var inputs = table.getElementsByTagName("input");
                        var counter = 1;
                        for (let i = 0; i < inputs.length - 4; i++) {
                            if (inputs[i].id == null || inputs[i].id == "" || inputs[i].id == undefined) {
                                if (inputs[i].type.toLowerCase() == 'text') {
                                    inputs[i].id = "newName_" + (counter.toString());
                                    inputs[i + 1].id = "newName2_" + (counter.toString());
                                    inputs[i + 2].id = "newEmail_" + (counter.toString());
                                    inputs[i + 3].id = "newExemption_" + (counter.toString());
                                    inputs[i + 4].id = "newExamine_" + (counter.toString());
                                    counter++;
                                }
                            }
                        }
                    }

                </script>


            <?php } ?>


        </div>
        <br/><br/>
        <div class="container col-sm-1 col-md-1 col-lg-1">
            <div class="float-panel">
                <br/><br/><br/><br/>
                <a href="#backtop"><img src="../../../images/totop.png" width="40%" height="40%"/></a><br/>
                <a href="#tobottom"><img src="../../../images/tobottom.png" width="40%" height="40%"/></a><br/>
            </div>
        </div>


        <!-- closing navigation div in nav.php -->
    </div>

</div>

<!-- Modal
<div class="modal fade" id="exampleModalLong" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">New staff details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>
-->
</div>

<!-- for going back to bottom -->
<div id="tobottom"></div>

<?php require_once('../../../footer.php'); ?>

</body>
</html>

<?php
unset($rsStaff);
?>

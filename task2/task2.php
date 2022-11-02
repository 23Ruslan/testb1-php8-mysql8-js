<?php
require_once "header.php";
use Shuchkin\SimpleXLS;             // SimpleXLS is external library, only for converting .xls file to php array. https://github.com/shuchkin/simplexls
class Task2{
    function xlsToArr($file = 'uploads/table.xls'){
        require_once "SimpleXLS.php";
        $file = 'uploads/' . $_FILES["filename"]["name"];
        if ( $xls = SimpleXLS::parseFile($file) ) {
            // print_r( $xls->rows() ); // for testing and debugging
            // echo $xls->toHTML();	    // for testing and debugging
        } else
            echo SimpleXLS::parseError();
        $arr=$xls->rows();
        array_splice($arr, 0, 7);  // we do not consider the first 7 lines of the file, since it definitely does not contain information about operations
        $arrGroups = [[]];         // names of excel classes (groups)
        $length = count($arr);
        for ($i = 0; $i < $length; ++$i)                    // single row check
            if (!preg_match('/\d\d\d\d/', $arr[$i][0])) { 
                if (mb_stripos($arr[$i][0], 'КЛАСС ') !== false) 
                    array_push($arrGroups[0], $arr[$i][0]); // add a new class name
                array_splice($arr, $i, 1);                  // we delete the row in which a match is found, i.e. in which there are not 4 digits
                --$length;
                --$i;
            } else
                array_splice($arr[$i], 7);                  // remove the remaining unnecessary empty columns from the table file, but leave the row
        return array_merge($arr, $arrGroups);
    }
    function arrToDb($arr, $importedRowsNumber = 0){
        $mysqli = new mysqli('localhost','root','','shopee'); // at first, clear space from old data at database
        $deleteQuery = $mysqli->prepare // in the database we delete only one table, the second one will be deleted automatically because a foreign key was created for each row                 
        (" DELETE FROM osv_class_names;");                          
        $deleteQuery->execute();                                 
        $insertQuery = $mysqli->prepare                 
        (" INSERT `osv_class_names`
            VALUES (?, ?); "
        );
        $i = 0;
        foreach(array_pop($arr) as $name) {                 // extract the last element of the array (names) and add the names to the table
            ++$i;
            $insertQuery->bind_param('is', $i, $name);      // basic SQL injection protection
            $insertQuery->execute();
        }
        $length = count($arr);
        $insertQuery = $mysqli->prepare                     // basic SQL injection protection
            (" INSERT `osv` (`account`, `start_assets`, `start_liabilities`, `current_assets`, `current_liabilities`, `final_assets`, `final_liabilities`)
                VALUES (
                    ?, ?, ?, ?, ?, ?, ?
                ); "
            );
        foreach($arr as &$row) {
            foreach($row as &$item)
                $item = str_replace('.', '', $item);
            unset($item);
            $insertQuery->bind_param('sssssss', $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]);
            $insertQuery->execute();
            //echo 'Imported: ', ++$importedRowsNumber, ', left: ', $length - $importedRowsNumber, " rows.\n"; // for testing and debugging
        }
        unset($row);
    }
    function uploadFileToServer(){
        if ($_FILES && $_FILES["filename"]["error"] == UPLOAD_ERR_OK)
            move_uploaded_file($_FILES["filename"]["tmp_name"], 'uploads/' . $_FILES["filename"]["name"]);
        else
            echo "File upload error\n";
        $_SESSION["filename"] = 'uploads/' . $_FILES["filename"]["name"];
    }
    function showUploadedFilesList($path = 'uploads'){
        echo '<br>List of uploaded files:<br>';
        if (count(scandir($path)) <= 2)     // there is Array ( [0] => . [1] => .. ) by default
            echo 'No files have been uploaded yet...<br>';
        else
            foreach(scandir($path) as $filename)
                if($filename !== '.' && $filename !== '..')
                    echo $filename, ($filename === $_FILES["filename"]["name"] ? ' — current file' : ''), '<br>'; // mark the current file in the list of all files in the directory
    }
    function showSqlSelectInHtml(){
        $connection = mysqli_connect("localhost","root","","shopee");
        if (mysqli_connect_errno())       // check connection status
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        $result = mysqli_query($connection, 
        "   SELECT * FROM summary_osv;
        ");
        echo '<table border = "3">', '
                <tr>
                    <th rowspan="2">Б/сч</th>
                    <th colspan="2">ВХОДЯЩЕЕ САЛЬДО</th>
                    <th colspan="2">ОБОРОТЫ</th>
                    <th colspan="2">ИСХОДЯЩЕЕ САЛЬДО</th>
                </tr>
                <tr>
                    <th>Актив</th>
                    <th>Пассив</th>
                    <th>Дебет</th>
                    <th>Кредит</th>
                    <th>Актив</th>
                    <th>Пассив</th>
                </tr>';
        while($row = mysqli_fetch_array($result)) // enter the result of a SELECT query in HTML tags, that is, on the browser page:
            if (mb_stripos($row['start_assets'], 'КЛАСС ') !== false)
                echo '<tr><td colspan="7" id="names">', $row['start_assets'], '</td></tr>';                   
            else
                echo '<tr><td>', $row['account'], '</td><td>', $this->drawNumber($row['start_assets']), '</td><td>', $this->drawNumber($row['start_liabilities']), '</td><td>', $this->drawNumber($row['current_assets']),
                '</td><td>', $this->drawNumber($row['current_liabilities']), '</td><td>', $this->drawNumber($row['final_assets']), '</td><td>', $this->drawNumber($row['final_liabilities']), '</td></tr>';        
        echo '</table>';
        mysqli_close($connection);
    }
    function drawNumber($number){               // just "draw" numbers with commas and thousands separators as we need
        return number_format(floatval($number)/100, 2, ',', ' ');
    }
    function writeArrayToFile($array, $file) {  
        file_put_contents($file, '');           // clear file before writing
        foreach ($array as $fields)             // write the array to a .txt file and store it on the server
            file_put_contents($file, implode(';', $fields) . "\n", FILE_APPEND); // and download the file using js by clicking on a direct link in the browser window (header.php)
    }
}


// creating and using instances of the class that was described above:
$obj = new Task2;
$obj->uploadFileToServer();
$arr = $obj->xlsToArr();
//print_r($arr); // for testing and debugging
$obj->writeArrayToFile($arr, 'table.txt');
$obj->arrToDb($arr);
$obj->showUploadedFilesList();
$obj->showSqlSelectInHtml();
?>
</main>
</body>
<?php
class Task1{
    static private $start_date;     // convert to timetamps
    static private $end_date;
    static $deletedRowsNumber = 0;  // total number of columns removed
    static $numberOfRows = 100_000;
    static $numberOfFiles = 100;
    static $bigFile = 'file.txt';
    static $files = 'res/*.txt';    // location of a large number of generated files
    function randomDate() {         // a random date between $start_date and $end_date timetamps                               
        return date('d.m.Y', rand(self::$start_date, self::$end_date)); // generate random number, сonvert back to desired date format
    }
    function randomLat($length = 10, $str = '') {                            // a random string   
        for ($i = 0; $i < $length; ++$i)
            $str .= substr('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', rand(0, 51), 1);
        return $str;
    }
    function randomRus($length = 10, $str = '') {                             // a random string
        for ($i = 0; $i < $length; ++$i)
            $str .= mb_substr('АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдеёжзийклмнопрстуфхцчшщъыьэюя', rand(0, 65), 1);
        return $str;
    }
    function randomEvenNumber($min = 1, $max = 100_000_000) {                 // a random even number
        $int  = rand($min, $max);
        return $int % 2 === 0 ? $int : $this->randomEvenNumber($min, $max);
    }
    function randomFractionalNumber($min = 100_000_000, $max = 2000_000_000) {// a random number between 1 and 20 with 8 decimal places
        return rand($min, $max) / 100_000_000;
    }
    function writeArrayToFile($array, $file) {
        file_put_contents($file, '');
        foreach ($array as $fields)
            file_put_contents($file, implode(';', $fields) . "\n", FILE_APPEND);
    }
    function joinAndCheck($bigFile = 'file.txt', $files = 'res/*.txt', $str1 = 'гн'){; // conformity check
        $flag = 0;                                // check if you need to overwrite the original file in order to remove some entries
        file_put_contents($bigFile, '');
        foreach (glob($files) as $file) {
            $contents = file_get_contents($file); // took the file as a string
            $pieces = explode("\n", $contents);   // table entries as array elements
            $length = count($pieces);
            for ($i = 0; $i < $length; ++$i)      // single row check
                if (strpos($pieces[$i], $str1) !== false) {
                    array_splice($pieces, $i, 1); // delete the entry in which a match is found
                    --$length;
                    --$i;
                    ++$flag;
                }
            if($flag !== 0){
                $contents = implode("\n", $pieces);
                file_put_contents($file, $contents);
                self::$deletedRowsNumber += $flag;
                $flag = 0;
            }
            file_put_contents($bigFile, $contents, FILE_APPEND);
        }
        echo 'Удалено ', self::$deletedRowsNumber, " записей.\n";
    }
    function fileToArray($file){
        $list = explode("\n", file_get_contents($file)); // file --> array of strings (rows)
        array_pop($list);                                // remove blank line at the end
        $arr = [];
        foreach($list as $data)
            array_push($arr, explode(';', $data));
        return $arr;
    }
    function generate($numberOfFiles = 100, $numberOfRows = 50, $files = 'res*.txt', $bigFile = 'file.txt') {
        if(file_exists($bigFile))
            unlink($bigFile);
        foreach (glob($files) as $file) // cleaning up extra files in case more files were generated than $numberOfFiles earlier
            if(file_exists($file))
                unlink($file);
        for ($i = 0; $i < $numberOfFiles; ++$i) {
            $fp = fopen('res/' . $i . '.txt', 'w');
            for ($j = 0; $j < $numberOfRows; ++$j)
                fwrite($fp,  $this->randomDate() . ';' . $this->randomLat() . ';' . $this->randomRus() . ';' . 
                $this->randomEvenNumber() . ';' . $this->randomFractionalNumber() . ";\n");
            fclose($fp);
        }
    }
    function fileToDB($file = 'file.txt', $deletedRowsNumber = 0, $numberOfRows = 0, $numberOfFiles = 0, $importedRowsNumber = 0) {
        $temporary = $numberOfRows * $numberOfFiles - $deletedRowsNumber;
        $list = explode("\n", file_get_contents($file));    // file --> array of strings (rows)
        array_pop($list);                                   // remove blank line at the end
        $mysqli = new mysqli('localhost','root','','shopee');
        $deleteQuery = $mysqli->prepare                     // clear the table first
        ("TRUNCATE TABLE `from_file`; ");
        $deleteQuery->execute();
        $insertQuery = $mysqli->prepare                     // basic SQL injection protection
            (" INSERT `from_file` 
                VALUES 
                (
                    NULL, /*AUTO_INCREMENT will work*/ 
                    STR_TO_DATE(?, '%d.%m.%Y'), 
                    ?, ?, ?, ?
                ); "
            );
        foreach($list as $stringData) {
            $arrayData = explode(';', $stringData); // single row data
            $insertQuery->bind_param('sssss', $arrayData[0], $arrayData[1], $arrayData[2], $arrayData[3], $arrayData[4]);
            $insertQuery->execute();
            echo 'Импортировано ', ++$importedRowsNumber, ', осталось ', $temporary - $importedRowsNumber, " записей. $deletedRowsNumber удалено.\n";
        }
    }
    function setSomeStartValues(){
        self::$start_date = strtotime('-5 year');   // convert to timetamps
        self::$end_date = strtotime('now');         // so that each time the function is called, do not recalculate the same string into a date stamp
    }
}


// creating and using instances of the class that was described above:
$obj = new Task1;
$obj->setSomeStartValues();

/* $list = [                     // for testing and debugging
    ['цщ', 'bbb', 'мн', 'dddd'],
    ['123', 'епрстщццв', 'екл'],
    ['aaa', 'bbb']
];
writeArrayToFile($list, '1.txt');
$list[1][0] = 'епататпаГцщщпатпатпатпат24';
writeArrayToFile($list, '2.txt'); */

$obj->generate($obj::$numberOfFiles, $obj::$numberOfRows, $obj::$files, $obj::$bigFile);
$obj->joinAndCheck('file.txt', $obj::$files, 'гн');
$obj->fileToDB('file.txt', $obj::$deletedRowsNumber, $obj::$numberOfRows, $obj::$numberOfFiles);
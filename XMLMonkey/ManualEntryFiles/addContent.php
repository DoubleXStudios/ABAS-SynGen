<?php

$category = $_POST["category"];
$preProcessChanges = $_POST["changes"];


$entries = explode(",", $preProcessChanges);

$servername = "localhost";
$username = "root";
$password = "Password";
$dbname = "xmlMonkeyDB";
$conn = new mysqli($servername, $username, $password, $dbname);
//if (count($words) != count($values)) {
//echo "There seems to be something wrong with the values you entered....";
//} else {
populateDBFromXml($entries, $category, $conn);
$conn->close();

//}

function populateDBFromXml($entries, $table, $conn) {
    foreach ($entries as $entry) {
        if(!contains($conn,$entry,$table)){
        $query = "INSERT INTO " . $table . " (word, val) VALUES (' " . preg_replace("/[^A-Za-z ]/", '', $entry) . "', '" . preg_replace("/[^0-9 ]/", '', $entry) . "')";
        if ($conn->query($query) === TRUE) {
            echo "Entry: [ " . preg_replace("/[^A-Za-z ]/", '', $entry) . "] with the value: " . preg_replace("/[^0-9]/", '', $entry) . " was successfully inserted into table: " . $table . "<br />";
        } else {
            echo "Error: " . $query . "<br /><br />" . $conn->error;
        }
        } else {
            echo preg_replace("/[^A-Za-z ]/", '', $entry). " already is in the table<br />";
        }
    }

    echo "--------------------------------------------- <br /><br />";
    //include_once "generateXML.php";
}

function contains($conn, $entry, $table) {
    $sql = "SELECT word FROM " . $table;
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // output data of each row
        while ($row = $result->fetch_assoc()) {
            if(preg_replace("/[^A-Za-z]/", '', $row["word"]) === preg_replace("/[^A-Za-z]/", '', $entry)){
                return true;
            }
        }
    }
    return false;
}

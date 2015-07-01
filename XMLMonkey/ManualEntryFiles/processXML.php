<?php
echo $_POST["category"];
echo $_POST["value"];
exit();
/*
  $xmlSource=simplexml_load_file('Keywords.xml') or die("Error: Cannot create object");
  $xml=simplexml_load_string($xmlSource);
  $dom = new DOMDocument('1.0');
  $dom->preserveWhiteSpace = true;
  $dom->formatOutput = true;
  $dom->loadXML($xmlSource->asXML());
  $xml = new SimpleXMLElement($xmlSource);
  echo $xml->asXML();
  $dom->saveXML();
  // but vardump (or print_r) not!
  var_dump($xml->keywords->sad);
  // so cast the SimpleXML Element to 'string' solve this issue
  var_dump((string) $xml->keywords->sad); 
*/

function createDB($dbname) {
    $servername = "localhost";
    $username = "root";
    $password = "Password";
    createDatabase($servername, $username, $password, $dbname);
}

function xmlRequest() {
    $servername = "localhost";
    $username = "root";
    $password = "Password";
    $dbname = "xmlMonkeyDB";
    $conn = new mysqli($servername, $username, $password, $dbname);
    $xmlDoc = generateXML($servername, $username, $password, $dbname, $conn);
    echo $xmlDoc->saveXML();
    $xmlDoc->save("test.xml");

    $conn->close();
}

function createDatabase($servername, $username, $password, $dbname) {
// Create connection
    $conn = new mysqli($servername, $username, $password);
    if ($conn->connect_error) {
        echo("Connection failed: " . $conn->connect_error);
        $conn->close();
        return false;
    }
    echo "Connected successfully <br />";
    if (generateDatabase($dbname, $conn)) {
        populateDBFromXml("Keywords.xml", $servername, $username, $password, $dbname);
        $conn->close();
        return true;
    }
}

function generateDatabase($dbName, $conn) {
    // Check connection

    $sql = "CREATE DATABASE " . $dbName;
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully";
        return true;
    } else {
        echo "Error creating database: " . $conn->error . "<br />";
        return false;
    }
}

/**
 * Just prints an understandble version of an xml file whose name is 
 * passed in.
 * @param type $fileName name of the file to be read
 */
function printXMLFileContents($xml) {
    echo"<br /><br />";
    foreach ($xml->children() as $catagory) {
        echo"~~~~~~~~~~~~~~~~~~~~~~~~~~";
        echo strtoupper($catagory->getName()) . ": <br /> ";
        echo"~~~~~~~~~~~~~~~~~~~~~~~~~~";
        foreach ($catagory->children() as $word) {

            echo "Word: " . $word . ", Value: " . $word->attributes();
            echo"<br />";
        }
        echo" <br />";
        echo"~~~~~~~~~~~~~~~~~~~~~~~~~~";
        echo" <br /><br />";
    }
}

/**
 * This Function takes in db information and a xml filename, and populates 
 * the db with the xml file contents
 * @param type $fName name of the file
 * @param type $server server hosting the mysql
 * @param type $userName username of the person logging into the server
 * @param type $password password of the person logging into the server
 * @param type $db name of the database being used.
 */
function populateDBFromXml($fName, $server, $userName, $password, $db) {
    $xml = simplexml_load_file($fName) or die("File doesn't exist");
    $conn = new mysqli($server, $userName, $password, $db);
    foreach ($xml->children() as $catagory) {
        $tName = $catagory->getName();
        $createTable = "CREATE TABLE " . $tName . " (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,word VARCHAR(30) NOT NULL,val INT(3) UNSIGNED NOT NULL)";
        if ($conn->query($createTable) === TRUE) {
            echo "Table [" . $tName . "]  created successfully <br />---------------------------------------------<br />";
            foreach ($catagory->children() as $word) {
                $fWord = preg_replace("/[^A-Za-z0-9 ]/", '', $word);
                $entry = "INSERT INTO " . $tName . " (word, val) VALUES ('" . $fWord . "', '" . $word->attributes() . "')";
                if ($conn->query($entry) === TRUE) {
                    echo "Entry: [" . $fWord . "] with the value: " . $word->attributes() . " was successfully inserted into table: " . $tName . "<br />";
                } else {
                    echo "Error: " . $entry . "<br /><br />" . $conn->error;
                }
            }
        } else {
            echo "Error creating table: " . $conn->error . "<br /><br />";
        }
        echo "--------------------------------------------- <br /><br />";
    }$conn->close();
}

function getTableNames($server, $userName, $password, $db) {
    $tablesArr = array();
    if (!mysql_connect($server, $userName, $password, $db)) {
        echo "Could not connect";
    }

    $sqlShow = "SHOW TABLES FROM $db";
    $returned = mysql_query($sqlShow);

    if ($returned) {
        // output data of each row
        while ($table = mysql_fetch_row($returned)) {
            array_push($tablesArr, $table[0]);
        }
    } else {
        echo "0 results";
    }
    return $tablesArr;
}

function generateXML($servername, $username, $password, $dbname, $conn) {
    $tables = getTableNames($servername, $username, $password, $dbname);
    header("Content-Type: text/plain");
//create the xml document
    $xmlDoc = new DOMDocument("1.0", "UTF-8");
//create the root element

    $root = $xmlDoc->appendChild($xmlDoc->createElement("Keywords"));
    $xmlDoc->formatOutput = true;
    foreach ($tables as $catagory) {
        $sqlWord = "SELECT word, val FROM $catagory";
        $result = $conn->query($sqlWord);
        $part = $root->appendChild($xmlDoc->createElement($catagory));

        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                $word = $part->appendChild($xmlDoc->createElement("word", $row["word"]));
                $word->appendChild($xmlDoc->createAttribute("value"))->appendChild($xmlDoc->createTextNode($row["val"]));
            }
        } else {
            echo "0 results";
        }
    }

    return $xmlDoc;
}
?> 
?>
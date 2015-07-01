<?php

$xml = new XMLRequest();

class XMLRequest {

    private $servername = "localhost";
    private $username = "root";
    private $password = "Password";
    private $xmlDBName = "xmlMonkeyDB";
    private $mysqliXML;
    private $xmlTables;

    public function __construct() {
        $this->xmlTables = Array();
        $this->open();
        $this->populateTableNames();
        $xmlDoc = $this->generateXML();
        //echo $xmlDoc->saveXML();
        $xmlDoc->save("test.xml");
        $this->mysqliXML->close();
        header("location:test.xml");
    }

    public function populateTableNames() {
        $sql = "show tables";
        $xmlTablesResult = $this->mysqliXML->query($sql);
        if ($xmlTablesResult->num_rows > 0) {
            while ($r = $xmlTablesResult->fetch_array()) {
                $table = $r[0];
                array_push($this->xmlTables, $table);
            }
        }
    }

    public function generateXML() {
//create the xml document
        $xmlDoc = new DOMDocument("1.0", "UTF-8");
//create the root element

        $root = $xmlDoc->appendChild($xmlDoc->createElement("Keywords"));
        $xmlDoc->formatOutput = true;
        foreach ($this->xmlTables as $catagory) {
            $sqlWord = "SELECT word, val FROM $catagory";
            $result = $this->mysqliXML->query($sqlWord); //problem
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

    private function open() {
        $this->mysqliXML = new mysqli($this->servername, $this->username, $this->password, $this->xmlDBName);
        if ($this->mysqliXML->connect_error) {
            echo("Connection failed: " . $this->mysqliXML->connect_error);
            $this->mysqliXML->close();
            return false;
        }

        return true;
    }

}

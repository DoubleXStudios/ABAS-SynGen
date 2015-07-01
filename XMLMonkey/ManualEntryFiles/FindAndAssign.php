<?php

/* $syns = $test->getSynonyms("sad");
  echo("Synonyms: <br />");
  if (count($syns) > 1) {
  foreach ($syns as $syn) {
  echo ("   :   " . $syn . "<br />");
  }
  }
 * */
$newWord = $_POST["newWord"];
ini_set('max_execution_time', 3000);
echo ("---------------------------<br />");
echo("Assigning Word \"$newWord\".....<br />");
$test = new Word($newWord);
echo("----------------------------<br />");

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Word {

    private $word;
    private $value;
    private $catagory;
    private $servername = "localhost";
    private $username = "root";
    private $password = "Password";
    private $xmlDBName = "xmlMonkeyDB";
    private $thesaurusDBName = "moby_thesaurus";
    private $mysqliXML;
    private $mysqliThes;
    private $synonyms;
    private $knownEntries; //saves a minimum of about 3-4%execution time per generation
    private $xmlTables;

    public function __construct($newWord) {
        $this->word = $newWord;
        if ($this->init()) {
            if (!$this->exists($this->word)) {
                $this->findPlace($this->word);
            }
        }
    }

    public function findPlace($word) {
        $start_time = microtime(true);
        $this->tryOpen();
        $largestVal = -1;
        $bestCat = "";

        foreach ($this->xmlTables as $table) {
            $temp = round($this->estimateLocalValue($word, 2, $table));
            if ($temp > $largestVal) {
                $largestVal = $temp;
                $bestCat = $table;
            }
        }
        
        echo("Best catagory for " . $word . " is: " . $bestCat . " with the value of : " . $largestVal . "<br />");
        if ($largestVal > 0) {
            if ($largestVal > 4) {
                $largestVal = 4;
            }
            $this->addToDB($word, $largestVal, $bestCat);
        }

        $this->close();
        echo "Processing Time: " . (microtime(true) - $start_time) . "<br>";
    }

    function addToDB($entry, $val, $table) {
        if (!($this->contains($entry, $table))) {
            $query = "INSERT INTO " . $table . " (word, val) VALUES (' " . preg_replace("/[^A-Za-z ]/", '', $entry) . "', '" . preg_replace("/[^0-9 ]/", '', $val) . "')";
            if ($this->mysqliXML->query($query) === TRUE) {
                echo "Entry: [ " . preg_replace("/[^A-Za-z ]/", '', $entry) . "] with the value: " . preg_replace("/[^0-9]/", '', $val) . " was successfully inserted into table: " . $table . "<br />";
            } else {
                echo "Error: " . $query . "<br /><br />" . $conn->error;
            }
        } else {
            echo preg_replace("/[^A-Za-z ]/", '', $entry) . " already is in the table<br />";
        }


        echo "--------------------------------------------- <br /><br />";
        //include_once "generateXML.php";
    }

    function contains($entry, $table) {
        $sql = "SELECT word FROM " . $table;
        $result = $this->mysqliXML->query($sql);

        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                if (preg_replace("/[^A-Za-z]/", '', $row["word"]) === preg_replace("/[^A-Za-z]/", '', $entry)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getSynonyms($word) {
        $this->tryOpen();
//$start_time = microtime(true);

        $sql = "SELECT synonyms.* FROM words LEFT JOIN synonyms ON synonyms.word_id = words.word_id WHERE word = \"$word\" ";
        $result = $this->mysqliThes->query($sql);
        $currentSynonyms = Array();
        if ($result->num_rows > 0) {
            while ($thisOne = $result->fetch_array()) {
                $synonym = trim($thisOne["synonym"]);
                array_push($currentSynonyms, $synonym);
            }
            return $currentSynonyms;
        }
        return false;
//echo "Processing Time: " . (microtime(true) - $start_time) . "<br>";
    }

    public function estimateLocalValue($entry, $generation, $table) {

        $val = $this->getVal($entry, $table);

        if ($val === -1) {

            $total = 0;
            if ($generation > 0) {
                $currentSyns;
                if (array_key_exists($entry, $this->knownEntries)) {
                    $currentSyns = $this->knownEntries[$entry];
                } else {
                    $currentSyns = $this->getSynonyms($entry);
                }

                if ($currentSyns) {
                    $this->knownEntries [$entry] = $currentSyns;
                    foreach ($currentSyns as $syn) {
                        $syn = trim($syn);
                        if (!array_key_exists($syn, $this->synonyms)) {
                            $this->synonyms[$syn] = true;
                            $total+= ($generation * $this->estimateLocalValue($syn, ($generation - 1), $table));
                        }
                    }
                    $total = $total / (count($this->synonyms));
                    foreach ($currentSyns as $toRemove) {
                        unset($this->synonyms[trim($toRemove)]);
                    }
                    return($total);
                }
            } else {

                return $total;
            }
        } else {
//echo("Word: " . $entry . " found with value: ". $val . " at generation: ". $generation. "<br />");
            return $val;
        }
    }

    public function getVal($entry, $table) {
        if (in_array($table, $this->xmlTables)) {
            return $this->findVal($entry, $table);
        } else {
            return -1;
        }
    }

    private function findVal($entry, $table) {
        if ($this->tryOpen()) {
            $sql = "SELECT * FROM " . $table;
            $result = $this->mysqliXML->query($sql);

            if ($result->num_rows > 0) {
// output data of each row
                while ($row = $result->fetch_assoc()) {
                    if (preg_replace("/[^A-Za-z]/", '', $row["word"]) === preg_replace("/[^A-Za-z]/", '', $entry)) {
                        return $row["val"];
                    }
                }
            }
        }
        return -1;
    }

    public function exists($entry) {
        foreach ($this->xmlTables as $table) {
            $temp = $this->findVal($entry, $table);
            if ($temp !== -1) {
                return true;
            }
        }

        return false;
    }

    public function init() {
        $this->synonyms = Array();
        $this->knownEntries = Array();
        $this->xmlTables = Array();
        $this->thesTables = Array();

        $this->open();

        $sql = "show tables";
        $xmlTablesResult = $this->mysqliXML->query($sql);
        if ($xmlTablesResult->num_rows > 0) {
            while ($r = $xmlTablesResult->fetch_array()) {
                $table = $r[0];
                array_push($this->xmlTables, $table);
            }
        }
        $xmlTablesResult->close();


        return true;
    }

    public function tryOpen() {
        if (!isset($this->mysqliThes, $this->mysqliXML)) {
            return($this->open());
        }
        return true;
    }

    private function open() {
        $this->mysqliXML = new mysqli($this->servername, $this->username, $this->password, $this->xmlDBName);
        if ($this->mysqliXML->connect_error) {
            echo("Connection failed: " . $this->mysqliXML->connect_error);
            $this->mysqliXML->close();
            return false;
        }
        $this->mysqliThes = new mysqli($this->servername, $this->username, $this->password, $this->thesaurusDBName);
        if ($this->mysqliThes->connect_error) {
            echo("Connection failed: " . $this->mysqliThes->connect_error);
            $this->mysqliThes->close();
            return false;
        }
        echo "Databases Opened! <br />";
        return true;
    }

    public function close() {
        $this->mysqliThes->close();
        unset($this->mysqliThes);
        $this->mysqliXML->close();
        unset($this->mysqliXML);
        echo "Databases Closed! <br />";
    }

    public function getTable($value) {
        foreach ($this->xmlTables as $table) {
            $temp = $this->findVal($value, $table);
            if ($temp !== -1) {
                return $table;
            }
        }
        return false;
    }

}

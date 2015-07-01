<?php

use mysqli;

//$apikey = "ab8wyeaxHi1WKdEgVdBU"; // NOTE: replace test_only with your own key

$start_time = microtime(true);
echo("<br />------------------------<br />");
$daWord = new Word("crazy", 3);
echo("<br />------------------------<br />");
echo("<br />------------------------<br />");
$daWord = new Word("stupid", 3);
echo("<br />------------------------<br />");
echo("<br />------------------------<br />");
$daWord = new Word("fucking", 3);
echo("<br />------------------------<br />");
echo("<br />------------------------<br />");
$daWord = new Word("love", 3);
echo("<br />------------------------<br />");
echo("<br />---------------------------<br />");
        echo "Processing Time: " . (microtime(true) - $start_time) . "<br>";
        echo("---------------------------<br />");
//echo ("Synonyms: <br />");
//$daWord->printSynonyms();
//echo ("<br />");
//echo ("Value: " . $daWord->getValue(). "<br />");

/**
 * 
 */
class Word {

    private $word;
    private $value;
    private $synonyms;
    private $catagory;
    private $servername = "localhost";
    private $username = "root";
    private $password = "Password";
    private $xmlDBName = "xmlMonkeyDB";
    private $thesaurusDBName = "moby_thesaurus";
    private $mysqli;
    private $accessed;
    /**
     * 
     * @param type $newWord
     */
    public function __construct($newWord, $val) {
        $this->word = $newWord;
        $this->synonyms = Array();
        $this->accessed = Array();
        $this->synonyms = $this->findSynonyms($this->word, $val);
    }

    public function assignValue($value, $search) {
        if ($value === 0) {
            return 0;
        }
        $mysqli = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
        $sql = "show tables";
        $rs = $mysqli->query($sql);
        if ($rs->num_rows > 0) {
            while ($r = $rs->fetch_array()) {
                $table = $r[0];
                //Walk through each table
                $sql_search = "select * from " . $table . " where "; //our final queery to find all the similiar occurances of our word
                $sql_search_fields = Array();
                $sql2 = "SHOW COLUMNS FROM " . $table;
                $rs2 = $mysqli->query($sql2);
                if ($rs2->num_rows > 0) {
                    while ($r2 = $rs2->fetch_array()) {
                        $colum = $r2[0];
                        //walk through each column of this table 
                        $sql_search_fields[] = $colum . " like('%" . $search . "%')"; //add this to our check
                    }
                    $rs2->close();
                }
                $sql_search .= implode(" OR ", $sql_search_fields);
                $rs3 = $mysqli->query($sql_search); //look for similiar words
                if ($rs3->num_rows > 0) {
                    while ($r3 = $rs3->fetch_assoc()) {
                        //walk through each column of this table 
                        $foundWord = trim($r3["word"]);
                        $foundValue = intval(trim($r3["val"]));
                        if (strcmp($foundWord, $search) === 0) {
                            //echo("<br /><br />WOOT, WE FOUND : " . $foundWord . " ");
                            return $foundValue;
                        }
                    }
                    $rs3->close();
                }
            }
            $rs->close();
        }
        $mysqli->close();
        $i = 0;
        $synonyms = $this->findSynonyms($search);
        foreach ($synonyms as $synonym) {
            $x = $this->assignValue(($value - 1), $synonym);
            if ($x > $i) {
                $i = $x;
            }
        }
        return $i;
    }

    public function findSynonyms($word, $bigCount) {

        $start_time = microtime(true);
        echo("<br />---------------------------<br />");
        echo("Finding synonyms for: $word <br />");
        echo("---------------------------<br />");
        $mysqli = new mysqli($this->servername, $this->username, $this->password, $this->thesaurusDBName);
        $sql = "SELECT synonyms.* FROM words LEFT JOIN synonyms ON synonyms.word_id = words.word_id WHERE word = \"$word\" ";
        $result = $mysqli->query($sql);
        if ($result->num_rows > 0) {
            $count = 0;
            while ($thisOne = $result->fetch_array()) {
                $synonym = trim($thisOne["synonym"]);
                if (!in_array($synonym, $this->accessed)) {
                    if ($count === 0) {
                        echo ("synonym: ");
                    }

                    echo($synonym . ",");
                    array_push($this->accessed, $synonym);
                    $i = $bigCount - 1;
                    if ($bigCount > 1) {
                        $this->findSynonyms($synonym, $i);
                    }

                    if ($count === 4) {
                        $count = 0;
                        echo("<br />");
                    } else {
                        $count++;
                    }
                }
            }
        }
        echo("<br />---------------------------<br />");
        echo "Processing Time: " . (microtime(true) - $start_time) . "<br>";
        echo("---------------------------<br />");
    }
    
    
    /**
     * 
     * @param type $val
     */
    public function setValue($val) {
        $this->value = $val;
    }

    /**
     * 
     * @param type $newWord
     */
    public function setWord($newWord) {
        $this->word = $newWord;
    }

    /**
     * 
     * @param type $newKey
     */
    public function setKey($newKey) {
        $this->apikey = $newKey;
    }

    /**
     * 
     * @param type $newKey
     */
    public function getKey() {
        return $this->apikey;
    }

    public function getSynonyms() {
        return $this->synonyms;
    }

    /**
     * 
     * @return type
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * 
     * @return type
     */
    public function getWord() {
        return $this->word;
    }

    public function printSynonyms() {
        foreach ($this->synonyms as $synonym) {
            echo("__" . $synonym . "<br />");
        }
    }


}

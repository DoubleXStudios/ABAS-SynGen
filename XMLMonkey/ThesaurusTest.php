<?php

//$apikey = "ab8wyeaxHi1WKdEgVdBU"; // NOTE: replace test_only with your own key



$daWord = new Word("disagreeable");
echo ("Synonyms: <br />");
$daWord->printSynonyms();
echo ("<br />");
echo ("Value: " . $daWord->getValue(). "<br />");


/**
 * 
 */
class Word {

    private $word;
    private $value;
    private $synonyms;
    private $apikey = "ab8wyeaxHi1WKdEgVdBU";
    private $catagory;
    private $servername = "localhost";
    private $username = "root";
    private $password = "Password";
    private $dbname = "xmlMonkeyDB";
    private $mysqli;

//    public function __construct0($newWord, $val) {
//        
//        $this->word = $newWord;
//        $this->value = $val;
//        $this->synonyms = Array();
//        $this->findSynonyms();
//    }

    /**
     * 
     * @param type $newWord
     */
    public function __construct($newWord) {
        $this->word = $newWord;
        $this->synonyms = Array();
        $this->synonyms = $this->findSynonyms($this->word);
        $this->value = $this->assignValue(3, $this->word);
    }

    public function assignValue($value, $search) {
        if ($value === 0){
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
        $mysqli ->close();
        $i = 0;
        $synonyms = $this->findSynonyms($search);
        foreach($synonyms as $synonym){
            $x = $this->assignValue(($value-1), $synonym);
            if ($x > $i){
                $i = $x;
            }
        }
        return $i;
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

    public function findSynonyms($word) {
        $synonymList = Array();
        $language = "en_US"; // you can use: en_US, es_ES, de_DE, fr_FR, it_IT
        $endpoint = "http://thesaurus.altervista.org/thesaurus/v1";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$endpoint?word=" . urlencode($word) . "&language=$language&key=$this->apikey&output=json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($info['http_code'] == 200) {
            $result = json_decode($data, true);
            foreach ($result["response"] as $value) {
                $tempArray = explode("|", $value["list"]["synonyms"]);

                $end = count($tempArray);
                for ($j = 0; $j < $end; $j++) {
                    preg_replace("/[^A-Za-z0-9 ]/", '', $tempArray[$j]);
                    if (strpos($tempArray[$j], '(antonym)') !== false) {
                       // echo "<~~>$tempArray[$j] <~~> <br />";
                        unset($tempArray[$j]);
                    } else {
                        if (strpos($tempArray[$j], '(') !== false) {
                            $tempWord = substr($tempArray[$j], 0, strpos($tempArray[$j], "("));
                           // echo "<+> $tempWord<+><br />";
                            array_push($synonymList, trim($tempWord));
                        } else {
                            $tempWord = $tempArray[$j];
                           // echo "<+> $tempWord<+><br />";
                            array_push($synonymList, trim($tempWord));
                        }
                    }
                }
            }
        } else {
            echo "Http Error: " . $info['http_code'];
        }
        array_filter($synonymList);
        return $synonymList;
    }

}

<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        echo "Client IP " . $_SERVER['REMOTE_ADDR'] . "<br /><br />";
        echo "-------------------------------------------<br />";
        echo "----Welcome to XML Monkey------<br />";
        echo "-------------------------------------------<br />";
        //include 'processXML.php';
        ?>
        <button class="btn" onclick="createDatabase();">Add</button>
        <div class="submission">
            
            <form action="processXML.php" method="post" enctype="multipart/form-data">
                Select an XML file  to upload:
                <input type="file" name="fileToUpload" id="fileToUpload">
                <br />
                <br />
                <input type="submit" value="Upload XML" name="submit">
            </form> 
            
        </div>

        <div class="editor">
            <form action="generateXML.php" method="post">
                <p>Produce XML</p>
                <input type="submit" name="submit" value="Submit Entry" />
                </p>

            </form>
            <br />
            <form action="FindAndAssign.php" method="post">
                <p>Input a word You want automatically assigned to a category </p>
                <p>Word:
                    <input type="text" name="newWord" size="20" value="" />
                </p>
                </p>
                <input type="submit" name="submit" value="Submit Entry" />
                </p>
            </form>
            <br />
            <form action="addContent.php" method="post">
                <p>Input the category where you want to add a word </p>
                <p>Category:
                    <input type="text" name="category" size="20" value="" />
                </p>
                <p>Enter words followed by numeric values seperated by commas <br />
                    to be added to the database E.G. sublime 3, awesome 4, joyful 2</p>
                <p>Input:
                    <input type="text" name="changes" size="100" value="" />
                </p>
                <input type="submit" name="submit" value="Submit Entry" />
                </p>
            </form>
        </div>
    </body>
</html>

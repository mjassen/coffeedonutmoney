<?php
/* 你好. Hello.
Coffee Donut Money. 咖啡甜甜圈錢.
2014-02-22
MIT license.
MIT 授權規定.

******
The MIT License (MIT)

Copyright (c) 2014 Morgan Jassen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
******


*/
?>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
  <title></title>
  <style type="text/css">	
  #wrapper {
    max-width: 600px;
  }
  </style>

</head>
<body>
<div id="wrapper">
<?php
// Begin logic to insert into the database
//initialize variables
$message_text = '';
$post_text_text1 = '';



//start check if the post value is set before trying to use it
if (isset($_POST['text_text1'])) 
{

//test if any fields were posted back
if ($_POST['text_text1'] ) {



//read in the post values and prep them for insert


$post_text_text1 = date('Y') . date('m') . date('d') . ' ' . mzzprepareinput($_POST['text_text1']);



$sql_insert = "INSERT INTO table1 (text1) VALUES('$post_text_text1')";

$message_text .= $sql_insert;

$message_text .= " 插入了 1 行. Inserted 1 row.";


} //End test if any fields were posted back

}else{ //endcheck if the post value is set before trying to use it
$message_text .= ' 插入新資料. Insert a new record.';
}



//start of database string cleaning functions
// cleans a string to make it safe to input into database.
  function mzzprepareinput($string) {
    if (is_string($string)) {
      return trim(mzzcleanstring(stripslashes($string)));
    } elseif (is_array($string)) {
      reset($string);
      while (list($key, $value) = each($string)) {
        $string[$key] = mzzprepareinput($value);
      }
      return $string;
    } else {
      return $string;
    }
  }
    
  function mzzcleanstring($string) {
    $patterns = array ('/ +/');
    $replace = array (' ', '_');
    return preg_replace($patterns, $replace, trim($string));
  }
   

//end of database string cleaning functions


		


// end logic to insert into the database
?>

<form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>" enctype="multipart/form-data">

<?php 
if($message_text != ''){
echo "訊息 Message: ". $message_text ;
?>
<br />
<?php
}
 ?>

<textarea name="text_text1" rows="4" cols="30"></textarea>
<br/>
<input type="submit" name="submit" value=" 執行 Go → "/>

</form>

</div>
</body>

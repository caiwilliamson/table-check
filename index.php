<!DOCTYPE html>
<html>
<head>
  <title>TableCheck</title>
</head>
<body>

<style type="text/css">
table, td {
  border: 3px solid;
  border-collapse: collapse;
}
table {
  width: 100%;
}
</style>

<?php

// Makes DOM object manipulation a lot less stressful
include_once('simple_html_dom.php');

// Connection to the database
$servername = "localhost";
$username = "root";
$password = "";
$database = "tablecheck";

$mysqli = new mysqli($servername, $username, $password, $database);

function tables_to_array() {
  // Create a new html_simple_dom object
  $html = new simple_html_dom();
  // The URL to create the dom_object from
  $url = "http://www.leicester.gov.uk/your-council-services/lc/sports-services/centre-details/evington-leisure-centre/";
  $html->load_file($url);
  // Find all tables in the dom_object
  $tables = $html->find('table');
  // Return table of choice
  return $tables[5];
}

function compare_tables($table_old, $table_new) {
  global $mysqli;
  if ($table_old !== $table_new) {
    echo "<h1>Change detected! New table!</h1>";
    // Clear the old table from the database
    $sql = "DELETE FROM data";
    $mysqli->query($sql);
    // Insert the new, changed table
    $sql = "INSERT INTO data (html_table) VALUES ('$table_new')";
    $mysqli->query($sql);
  } else {
    echo "<h1>No change!</h1>";
  }
}

// Decode and print out the table
function print_table ($table) {
  $decoded_table = htmlspecialchars_decode($table);
  echo $decoded_table;
}

// Get the returned table
$table = tables_to_array();
// Serialise and encode the table for storage
$table_serialized = serialize(htmlspecialchars($table));

// Create a table called "data" with columns "id" and "html_table"
$sql = "CREATE TABLE data (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
html_table TEXT
)";
$mysqli->query($sql);

// Query "data" to find out the number of rows
$result = $mysqli->query("SELECT * FROM data");
$num_rows = $result->num_rows;

if ($num_rows === 0) {
  // No table entry exists for comparison so create one
  $sql = "INSERT INTO data (html_table) VALUES ('$table_serialized')";
  $mysqli->query($sql);
  echo "<h1>Table Added</h1>";
  print_table($table);
} else {
  // Pull the table
  $sql = "SELECT html_table FROM data";
  $ret = $mysqli->query($sql);
  $result = $ret->fetch_array(MYSQLI_NUM);
  // Assign the table to a variable
  $table_old = $result[0];
  // Compare the old table with the new table for changes
  compare_tables($table_old, $table_serialized);
  // Print the newly scraped table
  print_table($table);
}

?>

</body>
</html>
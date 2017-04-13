# RESTImport
A simple script to import REST API JSON data into your local MariaDB/MySQL database

Provides two functions for simply grabbing REST API data and then adding/updating your local database.

updateTable($table, $data)
$table = The name of the table you wish to insert data into
$data = An array('name' => value, ...) of data you wish to insert or update into $table

getRequest($url, $params)
$url = The REST API url you're grabbing JSON data from without query strings.
$params = An array array('name' => value, ...) of query strings you'd like to run your request on.

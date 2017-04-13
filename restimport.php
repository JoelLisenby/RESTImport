<?php
/***
RESTImport class by Joel Lisenby

A simple PHP class to import REST API JSON data into your local MariaDB/MySQL database

RESTImport is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
RESTImport is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with RESTImport.  If not, see <http://www.gnu.org/licenses/>.

***/


class RESTImport {
	private $pdo;
	private $api_base_uri;
	private $time_start;
	private $time_end;

	public function __construct() {
		$this->api_base_uri = 'https://your-rest-api-url.com/secret-key-goes-here' // your rest api's base url;
		$dsn = "mysql:dbname=yourdbname;host=localhost";
		
		try {
			$this->pdo = new PDO($dsn,'yourdbusername','yourdbpassword');
		} catch(PDOException $e) {
			echo 'db connection failure: '.$e->getMessage();
		}

		$this->updateEndpointA();
	}

	/*** updateEndpointA()
	An example function to update a specific endpoint. Create as many as you need or use some other fancy method
	to update as many endpoints as you want using the provided getRequest and updateTable functions :)
	***/
	private function updateEndpointA() {
		$this->time_start = microtime(true); // let's measure how long the request takes.
		$json = $this->getRequest('/your-endpoint.json'); // enter the endpoint of the REST API source you wish to import here.
		if($json !== false) {
			$array = json_decode($json, true);
			$this->updateTable('your-db-table-name', $array['data']);
		}
	}

	/*** updateTable($table, $data)
	$table = the table you wish to insert data into
	$data = the data you wish to insert or update into $table
	***/
	private function updateTable($table, $data) {
		$stmt = $this->pdo->prepare('DESCRIBE '. $table);
		$stmt->execute();
		$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
		$cols_backticked = array();
		$placeholders_insert = array();
		$placeholders_update = array();
		$total_cnt = 0;
		$success_cnt = 0;

		foreach($columns as $col) {
			$cols_backticked[] = '`'.$col.'`';
			$placeholders_insert[] = ':'. $col;
			$placeholders_update[] = $col .'=:'. $col;
		}

		$sql = 'INSERT INTO '. $table .' ('. implode(', ', $cols_backticked) .')';
		$sql .= ' VALUES ('. implode(', ', $placeholders_insert) .')';
		$sql .= ' ON DUPLICATE KEY UPDATE '. implode(', ', $placeholders_update) .';';
		$stmt = $this->pdo->prepare($sql);
		
		$this->pdo->beginTransaction();

		foreach($data as $row) {
			$total_cnt++;
			$input = array();

			foreach($columns as $col) {
				$input[$col] = $row[$col];
			}
			
			if($stmt->execute($input)) {
				$success_cnt++;
			} else {
				echo $table .' row '. $input['id'] .' failed'."\n";
				/*** use these to debug your sql errors
				var_dump($stmt->errorInfo());
				echo $stmt->queryString;
				print_r($input);
				die();
				***/
			}
		}

		$this->pdo->commit();

		$this->time_end = microtime(true); // measuring execution time
		$time = ($this->time_end - $this->time_start) * 1000;
		echo $success_cnt .' of '. $total_cnt .' '. $table .' added/updated in '. round($time) .'ms'."\n";
	}

	/*** getRequest($url, $params)
	$url = the full REST API url you're grabbing JSON data from without query strings.
	$params = an array of the query strings you'd like to run your request on.
	***/
	private function getRequest($url, $params = array()) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $this->api_base_uri . $url .(!empty($params) ? '?'. http_build_query($params) 
: ''));
		$json = curl_exec($ch);
		curl_close($ch);
		return $json;
	}

}

new RESTImport();

?>

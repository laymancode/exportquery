<?php
/**
 * Description: This library can be used in export sql query to csv and xls file
 *
 * @author Jai Kumawat
 */
class ExportQuery {

    private $con;
    private $out;
    public $filename;
    public $sqlquery;
    private $resultcount;
    public $batchcount;
    private $limit;
    private $offset;
    public $colheadings = array();
	private $table_alias;
	public $export_to;
	public $enable_debug = false;

    function __construct() {
        $this->table_alias = "export_query_". time();
    }

    // create and get database connection if not set
    private function getDBConnection() {
		
		if(empty($this->con)){
			$host = "localhost";
			$port = 3306;
			$socket = "";
			$user = "user";
			$password = "pass";
			$dbname = "db_name";
			$con = new mysqli($host, $user, $password, $dbname, $port, $socket) or die('Could not connect to the database server' . mysqli_connect_error());
			$this->con = $con;	
		}
    }

	// start export
    public function export() {
		
		if (empty($this->filename)) {
			if($this->enable_debug){
				die('error : filename blank');
			}
			else{
				die('error in exporting data');
			}
        }
		else{
			$this->filename = trim($this->filename);
			
		}
        if (empty($this->sqlquery)) {
            if($this->enable_debug){
				die('error : sqlquery blank');
			}
			else{
				die('error in exporting data');
			}
        }
		else{
			$this->sqlquery = trim($this->sqlquery);
		}
		$count = strlen($this->sqlquery);
		if(substr($this->sqlquery,($count-1)) == ";"){
			$this->sqlquery = substr($this->sqlquery,0,($count-1));
		}
		if(empty($this->export_to)){
			$this->export_to = 'csv';
			$this->out = fopen("php://output", 'w');
		}
		
		set_time_limit(300);
        $this->getDBConnection();
        $this->getContentType();
        $this->resultcount = $this->getResultCount();
        $fields = $this->getFields();
        $this->add_to_output([$fields]);
        if ($this->batchcount) {
            $total_loop = ceil($this->resultcount / $this->batchcount);
            for ($i = 0; $i < $total_loop; $i++) {
                $this->limit = $i * $this->batchcount;
                $result = $this->getResultBatch();
                $this->add_to_output($result);
            }
        } else {
            $this->getResult();
        }
		
    }

    // put array to file
    private function putcsv($result) {
        foreach ($result as $row) {
            array_walk($row, array($this, 'cleanData'));
            fputcsv($this->out, array_values($row), ',', '"');
        }
    }

    // echo array with tabs
    private function echo_xls($result) {
        foreach ($result as $row) {
            array_walk($row, array($this, 'cleanData'));
			echo implode("\t", array_values($row)) . "\r\n";
        }
    }
	
	// add to output
    private function add_to_output($result) {
        if($this->export_to == 'csv'){
			$this->putcsv($result);
		}
		elseif($this->export_to == 'xls'){
			$this->echo_xls($result);
		}
		else{
			$this->putcsv($result);
		}
    }

    // get query result count
    private function getResultCount() {
        $query = "select count(*) from(" . $this->sqlquery .") as ".$this->table_alias . ";";
        $result = mysqli_query($this->con, $query) or die('Query failed!');
        $result = mysqli_fetch_row($result);
        return $result[0];
    }
	
	// set databse connection property
    public function setDBCon($con) {
		$this->con = $con;
    }

    // get query result array
    private function getResult() {
        $data_arr = array();
        $query = $this->sqlquery .";";
        $result = mysqli_query($this->con, $query) or die('Query failed!');
        while ($row = mysqli_fetch_assoc($result)) {
			$this->add_to_output([$row]);
        }
    }

    // get batch query result array
    private function getResultBatch() {
        $data_arr = array();
        if(stripos($this->sqlquery, ' limit ')===false){
			$query = $this->sqlquery . " limit " . $this->limit . "," . $this->batchcount . ";";
		}
		else{
			$query = "select * from (" . $this->sqlquery . ") as ". $this->table_alias . " limit " . $this->limit . "," . $this->batchcount . ";";
		}
        $result = mysqli_query($this->con, $query) or die('Query failed!');
        while ($row = mysqli_fetch_assoc($result)) {
            $data_arr[] = $row;
        }
        return $data_arr;
    }

    // get field names
    private function getFields() {

		// check if column heading already set
		if(count($this->colheadings)>0){
			return $this->colheadings;
		}
		
		// fetch heading from query

		if(stripos($this->sqlquery, ' limit ')===false){
			$query = $this->sqlquery . " limit 1;";
		}
		else{
			$query = "select * from (" . $this->sqlquery . ") as ". $this->table_alias . " limit 1";
		}
		
        $result = mysqli_query($this->con, $query) or die('Query failed!');
        $fields = array();
        $row = mysqli_fetch_fields($result);
        foreach ($row as $r) {
            $fields[] = $r->name;
        }
        return $fields;
    }

    // clean data
    private function cleanData(&$str) {
        if ($str == 't')
            $str = 'TRUE';
        if ($str == 'f')
            $str = 'FALSE';
        if (preg_match("/^0/", $str) || preg_match("/^\+?\d{8,}$/", $str) || preg_match("/^\d{4}.\d{1,2}.\d{1,2}/", $str)) {
            $str = $str;
        }
        if (strstr($str, '"'))
            $str = '"' . str_replace('"', '""', $str) . '"';
    }

    // get content type csv
    private function getContentType() {
		if($this->export_to == 'csv'){
			$filename = $this->filename . ".csv";
			header("Content-Disposition: attachment; filename=\"$filename\"");
			header("Content-Type: text/csv");
		}
		elseif($this->export_to == 'xls'){
			$filename = $this->filename . ".xls";
			header("Content-Type: application/vnd.ms-excel");
			header("Content-Disposition: attachment; filename=\"$filename\"");
			header("Pragma: no-cache");
			header("Expires: 0");
		}
		else{
			$filename = $this->filename . ".csv";
			header("Content-Disposition: attachment; filename=\"$filename\"");
			header("Content-Type: text/csv");
		}
    }

    function __destruct() {
		if($this->export_to == 'csv'){
			if (!empty($this->out)) {
				fclose($this->out);
			}
		}
    }

}

?>

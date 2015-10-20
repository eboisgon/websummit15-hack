<?php
define('DEBUG', false);

/**
  Database access class
   
  Usages :
	$SQL = new sqlQueries('localhost', 'user', 'pass', 'db');

	// queries with escape

	$SQL->query("DELETE FROM `table` WHERE `name` = '".$SQL->e($name)."' ");

	// queries with with directly readable result

	echo "Now is : ".$SQL->query2cell("SELECT NOW()");

	foreach( $SQL->query2list("SELECT `id`,`name` FROM `table`", true) as $id=>$name) echo "<p>($id) $name</p>";

	foreach( $SQL->query2assoc("SELECT `id`,`name` FROM `table`") as $r) echo "<p>($r[id]) $r[name]</p>";

 */
class sqlQueries {

	///// Configuration, change them between   $SQL = new sqlQueries();   and   $SQL->connect(...);

	public $throw_on_error = false; //!< By default, we continue to work when there is an error. Set this at true to throw an exception instead.
	public $my_log = null; //!< Set a function here that will be called on an error. Doesn't work if $throw_on_error is used.

	public $PDO_connect_options = array( //!< write here connections options you want to use.
			//PDO::ATTR_PERSISTENT => true, // http://stackoverflow.com/questions/3332074/what-are-the-disadvantages-of-using-persistent-connection-in-pdo
			);

	///// Public informations for reading

	public $rowCount; //!< affected rows count after a insert/update/delete
	public $affected_rows; //<! See $rowCount

	///// Internal vars

	protected $tr_status = false; //!< to true while in a transaction.
	protected $tr_erreurs = 0; //!< count the number of errors while in a transaction.

	protected $connected = false; //!< Check for bad devs.
	protected $dbh = null; //!< PDO object after connexion.
	protected $PDOStatement = false; //!< PDO statement after a prepare()
	protected $query; //!< Save a copy of the last query
	private $credentials; //!< We'll store connection infos in there.

	function __construct() {
		// if we give arguments, it means we want to connect
		if ($args = func_get_args()) {
			$this->connect($args[0], $args[1], $args[2], $args[3]);
		}
	}


	//! connexion shall be in utf8 now. Many params to call, put here
	public function connect($db_host=null, $db_database=null, $db_username=null, $db_password=null) {

		if ($db_host!==null) {
			$this->credentials = array($db_host, $db_username, $db_password, $db_database);
		}

		// if function was called with no params
		if ($db_host===null && $this->credentials) {
			list($db_host, $db_username, $db_password, $db_database) = $this->credentials;
		}

		try {
			$this->dbh = new PDO("mysql:host=$db_host;dbname=$db_database", $db_username, $db_password, $this->PDO_connect_options);
		}
		catch(PDOException $e) {
			$msg = "<p>sqlQueries: Can't connect to server.\n";
			$msg .= $e->getMessage()."\n";
			throw new Exception($msg);
		}

		// we're connected now
		$this->connected = true;

		$this->query("SET names utf8,
				session character_set_server=utf8,
				session character_set_database=utf8,
				session character_set_connection=utf8,
				session character_set_results=utf8,
				session character_set_client=utf8");
	}

	//! Outputs errors
	/** Creat your own reporting like this :
	  $SQL->my_log = function($sql, $err) {
	  	echo "Err mysql !\nSQL query: $sql\nError: $err\n";
	  };
	 */
	private function loggue($sql, $err=null) {
		$this->tr_erreurs++;

		if ($err===null) $err = $this->dbh->errorInfo();

		if ($this->throw_on_error) throw new Exception("(($sql))\n".$err[2]);
		elseif ($this->my_log) $this->my_log->__invoke($sql, $err[2]);
		else {
			// my report code

			if (DEBUG!==true) return false;

			if (PHP_SAPI=='cli')
				echo "($sql)\n".$err[2]."\n";
			else
				echo "<p><code>$sql</code></p>".$err[2]."\n";
		}
		return false;
	}

	//! All queries should go thru this first. @return (bool) Answer to should I continue?
	private function query_reader($q, $selects_only=false) {
		// it that a writing query?
		$this->write = !preg_match('/^\s*(SET|SELECT|SHOW)\s/is',$q);

		$this->query = $q;

		if ($selects_only && $this->write) {
			throw new Exception("<p>sqlQueries: Developer error. Should use a SELECT only.\n");
		}
	
		if (!$this->connected) throw new Exception("<p>sqlQueries: Developer error. He didn't call connect(). Stupid man.\n");
		return true;
	}

	//! Turns $this->PDOStatement into the array of your choice
	/**
	  Reads the $this->PDOStatement, return the content in array, and close the cursor.

	  @param $mode ('assoc|assoc_multi|row|list|cell') One of the words we use in * for function query2*() and exec2*()
	  @param $indexfield (string|bool)
	  @return (array|scalar)
	  */
	private function fetchAll($mode, $indexfield=null) {
		if ($this->PDOStatement===false) return false;

		$out = array();
		switch($mode) {
			case 'assoc':
				if ($indexfield===null) 
					$out = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
				else
					foreach($this->PDOStatement->fetchAll(PDO::FETCH_ASSOC) as $r) $out[$r[$indexfield]] = $r;
				break;

			case 'assoc_multi':
				// no $indexfield allowed here
				do {
					$out[] = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
				} while($this->PDOStatement->nextRowset());
				break;

			case 'row':
				$out = $this->PDOStatement->fetch( $indexfield? PDO::FETCH_ASSOC : PDO::FETCH_NUM );
				break;

			case 'list':
				if ($indexfield)
					foreach($this->PDOStatement->fetchAll(PDO::FETCH_NUM) as $r) $out[$r[0]] = $r[1];
				else
					$out = $this->PDOStatement->fetchAll(PDO::FETCH_COLUMN, 0);
				break;

			case 'cell':
				$r = $this->PDOStatement->fetch(PDO::FETCH_NUM);
				if (!$r) return false;
				$out = $r[0];
				break;
		}
		$this->PDOStatement->closeCursor();
		return $out;
	}


	//! Execute a query the simplest way. Return a PODStatement, fetched or not, or a boolean
	/**
	  @param $q (string) The mysql query
	  @return (bool) Return a true if request was successful.
	  */
	function query($q) {
		$continue = $this->query_reader($q, false );
		if (!$continue) return true;

		if ($this->write) {
			// save number of modified rows
			$this->affected_rows = $this->rowCount = $this->dbh->exec($q);
			if ($this->rowCount===false) {
				$this->loggue($q);
				return false;
			}
			return true;
		}
		else {
			$this->PDOStatement = $this->dbh->query($q);
			if ($this->PDOStatement===false) return $this->loggue($q);
			return true;
		}
	}

	//! Get a query result as an array of assoc arrays
	/** Retourne un tableau au lieu d'un result.
	  Si donne $indexfield le tableau sera indexé par ce dernier

	  @param $q (string) sql query string
	  @param $indexfield (null | string) if given, the returned array will be indexed by the content of this field (needs to be in the SELECT)
	  @return (assoc or numeric 1-level array of (1-level assoc array)s )
	 */
	function query2assoc($q, $indexfield=null) {
		$this->query($q);
		return $this->fetchAll('assoc', $indexfield);
	}

	//! query2assoc() 's first row only
	/** La premiere ligne d'un result
	  @param $q (string) sql query string
	  @param $assoc (bool) Tells if you want assoc keys of numeric keys
	  */
	function query2row($q, $assoc=true) {
		$this->query($q);
		return $this->fetchAll('row', $assoc);
	}

	//! Get a query result with only one field in SELECT.
	/** Choppe un resultat de query avec un seul champ dans le SELECT.
	  Si $index est vrai, on choppe 2 champs et le 1er est l'index.

	  @param $q (string) sql query string
	  @param $index (bool) if true, we take two fields and the 1st one is the index of the array
	  @return (array | assoc array )
	  */
	function query2list($q, $index=false) {
		$this->query($q);
		return $this->fetchAll('list', $index);
	}


	//! Get just one value, directly
	/** Choppe un resultat unique de query, une ligne, une colone
	  */
	function query2cell($q) {
		$this->query($q);
		return $this->fetchAll('cell');
	}



	//! Insert and get inserted id. Use carefully.
	/**
	  If you want to use a function like NOW() instead of a value, add "LITERAL" at the begining of value.
		Exemple : $SQL->insert('countries', array('code'=>'FR', 'name'=>'France', 'population'=>60000, 'somekindofid'=>"LITERAL MD5('{$theid}')"));

		@param $table (string)
		@param $fields (string|1-level assoc array) See array2sets()
		@param $action (INSERT | INSERT IGNORE | REPLACE)
		@return (int) inserted id
	 */
	function insert($table, $fields, $action='INSERT') {
		if (!$fields) {
			$success = $this->query("$action INTO $table VALUES()");
		}
		else {
			$fields = $this->array2sets($fields);
			print("$action INTO $table SET $fields");
			$success = $this->query("$action INTO $table SET $fields");
		}
		return $success? $this->insert_id() : false;
	}

	/**
	  Create sets or conditions with field=value. Escapes the values.

	  You can use a special value if begins by "LITERAL" to write function or another field. Exemple : 'thedate'=>"LITERAL NOW()" will produce  "`thedate`=NOW()
	  	=> Potential security hole !!

	  @param fields: (1-level assoc array)
	  @param $implode_with (string) ", " or "AND" for creating conditions or "OR"
	  @return (string)
	  */
	function array2sets($fields, $implode_with=',') {
		if (is_string($fields)) return $fields;

		$data = array();
		foreach($fields as $k=>$v) {
			if (0===strpos($v, 'LITERAL')) {
				$data[] = "`$k`=".substr($v,7);
			}
			else {
				$data[] = "`$k`='".$this->e($v)."'";
			}
		}
		return implode(' '.$implode_with.' ', $data);
	}

  	//! Escape your vars with that. This does not quote. It escapes. It means you still need to put ' around it. (Legacy from mysql times)
	function e($a) {
		// I'm not using $this->dbh->quote() because it add quotes around the result and sqlQueries->e() doens't usually returns that.
		return addslashes($a);
	}
	
	///// copy paste of good old mysql_* functions

	function affected_rows() {
		return $this->affected_rows;
	}
	function errno() {
		$err = $this->dbh->errorInfo();
		return $err[1];
	}
	function error() {
		$err = $this->dbh->errorInfo();
		return $err[2];
	}
	function insert_id() {
		return $this->dbh->lastInsertId();
	}
	function num_rows($res=null) {
		return is_object($res) ? $res->rowCount() : $this->affected_rows;
	}
	function ping() {
		try {
			$this->dbh->query('SELECT 1');
		} catch (PDOException $e) {
			$this->connect();
		}
		return true;
	}
	function select_db($db_database) {
		if (!$this->credentials) return false;
		$this->credentials[3] = $db_database;
		$this->connect();
		return true;
	}
	//! To launch after a query with SQL_CALC_FOUND_ROWS and a LIMIT
	function found_rows() {
		return $this->query2cell("SELECT FOUND_ROWS()");
	}
}


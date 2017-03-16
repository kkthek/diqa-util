<?php
namespace DIQA\Util;

class DBHelper {

	/**
	 * Make sure the table of the given name has the given fields, provided
	 * as an array with entries fieldname => typeparams. typeparams should be
	 * in a normalised form and order to match to existing values.
	 *
	 * The function returns an array that includes all columns that have been
	 * changed. For each such column, the array contains an entry
	 * columnname => action, where action is one of 'up', 'new', or 'del'
	 * If the table was already fine or was created completely anew, an empty
	 * array is returned (assuming that both cases require no action).
	 *
	 * NOTE: the function partly ignores the order in which fields are set up.
	 * Only if the type of some field changes will its order be adjusted explicitly.
	 *
	 * @param string $primaryKeys
	 *      This optional string specifies the primary keys if there is more
	 *      than one. This is a comma separated list of column names. The primary
	 *      keys are not altered, if the table already exists.
	 */
	public static function setupTable($table, $fields, $db, $verbose, $primaryKeys = "") {
		global $wgDBname;
		self::reportProgress("Setting up table $table ...\n",$verbose);
		if ($db->tableExists($table) === false) { // create new table
			$sql = 'CREATE TABLE ' . $wgDBname . '.' . $table . ' (';
			$first = true;
			foreach ($fields as $name => $type) {
				if ($first) {
					$first = false;
				} else {
					$sql .= ',';
				}
				$sql .= $name . '  ' . $type;
			}
			if (!empty($primaryKeys)) {
				$sql .= ", PRIMARY KEY(".$primaryKeys.")";
			}
			$sql .= ') ENGINE=MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin';
			$db->query( $sql, 'self::setupTable' );
			self::reportProgress("   ... new table created\n",$verbose);
			return array();
		} else { // check table signature
			self::reportProgress("   ... table exists already, checking structure ...\n",$verbose);
			$res = $db->query( 'DESCRIBE ' . $table, 'self::setupTable' );
			$curfields = array();
			$result = array();
			while ($row = $db->fetchObject($res)) {
				$type = strtoupper($row->Type);
				if ($row->Null != 'YES') {
					$type .= ' NOT NULL';
				}
				$curfields[$row->Field] = $type;
			}
			$position = 'FIRST';
			foreach ($fields as $name => $type) {
				if ( !array_key_exists($name,$curfields) ) {
					self::reportProgress("   ... creating column $name ... ",$verbose);
					$db->query("ALTER TABLE $table ADD `$name` $type $position", 'self::setupTable');
					$result[$name] = 'new';
					self::reportProgress("done \n",$verbose);
				} elseif ($curfields[$name] != $type && stripos($type, "primary key") === false) {
					// Changing primary keys throws an error
					self::reportProgress("   ... changing type of column $name from '$curfields[$name]' to '$type' ... ",$verbose);
					$db->query("ALTER TABLE $table CHANGE `$name` `$name` $type $position", 'self::setupTable');
					$result[$name] = 'up';
					$curfields[$name] = false;
					self::reportProgress("done.\n",$verbose);
				} else {
					self::reportProgress("   ... column $name is fine\n",$verbose);
					$curfields[$name] = false;
				}
				$position = "AFTER $name";
			}
			foreach ($curfields as $name => $value) {
				if ($value !== false) { // not encountered yet --> delete
					self::reportProgress("   ... deleting obsolete column $name ... ",$verbose);
					$db->query("ALTER TABLE $table DROP COLUMN `$name`", 'self::setupTable');
					$result[$name] = 'del';
					self::reportProgress("done.\n",$verbose);
				}
			}
			self::reportProgress("   ... table $table set up successfully.\n",$verbose);
			return $result;
		}
	}

	/**
	 * Print some output to indicate progress. The output message is given by
	 * $msg, while $verbose indicates whether or not output is desired at all.
	 */
	public static function reportProgress($msg, $verbose) {
		if (!$verbose) {
			return;
		}
		if (ob_get_level() == 0) { // be sure to have some buffer, otherwise some PHPs complain
			ob_start();
		}
		print $msg;
		ob_flush();
		flush();
	}
}
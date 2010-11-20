<?
	class Mysql {
		//this function receives a number of parameters and uses them to execute a sql command
		//against the database and return the results as an array. the paramaters are:
		//	table: the table to query
		//	field: the name of the field to be used in a single WHERE condition
		//	value: the value of the above field for the single WHERE condition
		//	condition: if this is null, the passed-in field and value will be used to create
		//	a single WHERE condition, if it's anything but null, this is used as the WHERE condition
		//to select an entire table, pass in null as the field, value and condition. if the
		//query encounters an error while executing, this function returns false
		public function selectRecord($table, $field, $value, $condition) {
			//start building the select sql command
			$sql = "SELECT * FROM $table";

			//if the passed-in condition is null
			if ($condition == null) {
				//if both the field and value aren't null
				if (($field != null) && ($value != null)) {
					//complete the sql command using the passed-in field and value
					$sql .= " WHERE $field='$value'";
				}
			}
			else {
				//complete the sql command using the passed-in condition
				$sql .= " WHERE " . $condition;
			}

			$sql .= ";";

			//run the sql command and store the result
			$result = mysql_query($sql);

			//if there was an error running the sql command
			if ($result == false) {
				return false;
			}
			else {
				//the sql command executed successfully, the results are now in the $result resource
				//format the resource into array form
				for($i = 0; $resultArray[$i] = mysql_fetch_assoc($result); $i++);

				//remove the last (empty) row
				array_pop($resultArray);

				return $resultArray;
			}
		}

		//takes in the name of a table in the database, and an array of data, and then
		//inserts into that table any value in the data array with a key that matches a
		//column in that table
		//returns the id of the new record, or false if an error occured
		public function insertRecord($table, $data) {
			$id = 0;

			//this sql statement will be progressively crafted and then executed later on
			$sql = "INSERT INTO $table (";

			//get an array of all the fields in the passed-in table
			$fields = $this->getFieldsInTable($table);

			//if we did not successfully retrieve the fields of this table, then
			//return false back to the calling function
			if ($fields[0] == null) {
				return false;
			}

			//loop through each piece of data (including ones that hold values that we
			//don't want inserted into the table)
			foreach ($data as $field => $value) {
				//if there is a field in this table with the same name as the data key
				if (in_array($field, $fields)) {
					//add this field and value to the array to be inserted into the table
					$insertData["$field"] = $value;
				}
			}

			//now create a list of the fields and values to be inserted into the
			//sql statement, formatting as we go (no commas in front of the first ones)
			$i = 0;
			foreach ($insertData as $field => $value) {
				if ($i != 0) {
					$fieldList .= ", ";
					$valueList .= ", ";
				}

				$fieldList .= $field;
				$valueList .= "\"$value\"";
				$i++;
			}

			//and insert the field and value lists into the sql statement
			$sql .= "$fieldList) VALUES ($valueList);";

			//and now, attempt to run the sql statement and make the insertion into the table
			if (mysql_query($sql)) {
				//the insertion completed successfully. return the id of the new record
				return mysql_insert_id();
			}
			else {
				//an error occured, the sql failed. return false immediately
				return false;
			}
		}

		//this function will update one or more records in a table with the data in the
		//passed-in data array, for each record that matches the passed-in WHERE condition.
		//	table: the table to be updated
		//	data: an array of fields to update, and their new values
		//	whereField: the field name that makes up the field of the single WHERE condition
		//	whereValue: the value of the field that makes up the single WHERE condition
		//	condition: the condition that selects what records will be updated with the new data
		//to update the entire table, pass in null for the condition, whereField and whereValue.
		//the function returns a boolean to indicate whether the function executed successfully
		public function updateRecord($table, $data, $whereField, $whereValue, $condition) {
			//begin building the update sql command
			$sql = "UPDATE $table SET ";

			//get an array of all the fields in the selected table
			$fields = $this->getFieldsInTable($table);

			//if the inputted table doesn't exist in the database
			if ($fields[0] == null) {
				return false;
			}

			//loop through each row in the passed-in data array
			foreach ($data as $field => $value) {
				//if the key of this row matches the name of a field in the table
				if (in_array($field, $fields)) {
					//add this field and value to the array of data to update
					$updateData["$field"] = $value;
				}
			}

			//for each field that we're going to update
			$i = 0;
			foreach ($updateData as $field => $value) {
				//if this is not the first field
				if ($i != 0) {
					//prepend a comma and space to the next field in the sql command
					$sql .= ", ";
				}

				//now add this field and value to the sql command
				$sql .= $field . "='" . $value . "'";
				$i++;
			}

			//if there is no passed-in condition
			if ($condition == null) {
				//and if both the where field and value are not null
				if (($whereField != null) && ($whereValue != null)) {
					//use the where field and value as the where condition
					$sql .= " WHERE " . $whereField . "='" . $whereValue . "'";
				}
			}
			else {
				//if a condition was passed into this function, use it as the where condition
				$sql .= " WHERE " . $condition;
			}

			$sql .= ";";

			//execute the sql command
			if (mysql_query($sql)) {
				//and if it runs successfully, return true
				return true;
			}
			else {
				//and return false if an error occurs while executing the command
				return false;
			}
		}

		//this function takes in the name of a database table, and deletes the record(s) in
		//that table with the passed-in id as the value of the id field. returns true if
		//the sql executes successfully (regardless of whether any records were deleted),
		//and false if an error occurs (ie, incorrect table name)
		public function deleteRecord($table, $id) {
			//build a sql statement to delete the record with the passed-in ID from the
			//passed-in table
			$sql = "DELETE FROM $table WHERE id='$id';";

			//attempt to execute the sql statement
			if (mysql_query($sql)) {
				//the deletion completed successfully. return true
				return true;
			}
			else {
				//the record couldn't be deleted
				return false;
			}
		}

		//this function will return an array of the names of all the fields in the
		//passed-in table. if the first value of the fields array is null, then
		//an error occured while executing
		private function getFieldsInTable($table) {
			//default the first value of the array of fields to null
			$fields[0] = null;

			//retrieve all the fields in the table
			$fieldNames = mysql_query("SHOW COLUMNS FROM $table");

			//if the above query went wrong (invalid table name), return null
			if ($fieldNames == null) {
				return $fields;
			}

			//count the number of fields found in the table
			$numFields = mysql_num_rows($fieldNames);

			//loop through each field, adding it to the fields array
			$i = 0;
			while ($i < $numFields) {
				$thisField = mysql_fetch_row($fieldNames);
				$fields[$i] = $thisField[0];
				$i++;
			}

			return $fields;
		}
	}
?>
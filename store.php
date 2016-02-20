<?php
/**
 * PillBox
 * Copyright (C) 2016 Sindastra <sindastra@gmail.com>
 * Copyright (C) 2016 Artox <privacy@not.given>
 *
 * The above copyright notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Accepts JSON only!
 * 
 * use POST data in field called "json"
 * 
 * Format:
 * { type = "log_medication", parameters ..... }
 * 
 * Valid types are:
 * MEDICATION_ADD: add new medication information
 * parameters:
 * name
 * active_agent
 * dosage_package
 * dosage_package_unit
 * dosage_to_take
 * dosage_to_take_unit
 * colour
 * shape
 * food_instructions
 * indication
 * minimum_spacing
 * minimum_spacing_unit
 * maximum_dosage
 * maximum_dosage_unit
 * note
 * 
 * returns medication id
 * 
 * MEDICATION_LOG: log taking or not taking medication
 * parameters:
 * medication_id
 * quantity
 * timestamp (when taken)
 * status
 * note
 * 
 * returns: log entry id
 * 
 * MEASUREMENT_ADD: add new measurement description/category
 * 
 * parameters:
 * name
 * unit
 * 
 * MEASUREMENT_LOG: log a measurements values
 * 
 * parameters:
 * measurement_id
 * value
 * timestamp
 * 
 * There will be a reply with an ID and an error string
 * non-zero ID means success, and error will be empty
 * { id = 0, error = "abcdef" }
 */

// define constants
abstract class StoreType {
	const MEDICATION_ADD = 1;
	const MEDICATION_LOG = 2;
	const MEASUREMENT_ADD = 3;
	const MEASUREMENT_LOG = 4;
}

abstract class LogEntryStatus {
	const AUTO = 0;
	const SKIPPED = 1;
}

include "functions.php";

init_session();

// include functions
include "include/cmdline_to_postandget_hack.inc";
include "include/mysql_open_database.inc";

// initialize a dummy failed result
$result = array('id' => 0, 'error' => '');

// check if user is logged in
if(!is_logged_in()) {
	$result['error'] = 'Please log in first!';
	echo json_encode($result);
	exit(0);
}

// check if data was passed
if(!array_key_exists('json', $_POST)) {
	$result['error'] = 'Please append request data!';
	echo json_encode($result);
	exit(0);
}

// try to decode
$data = json_decode($_POST['json']);
if($data == NULL) {
	$result['error'] = 'Failed to decode json!';
	echo json_encode($result);
	exit(0);
}

// execute
switch($data->type) {
	case StoreType::MEDICATION_ADD:
		/*
		 * expected fields: name, active_agent, dosage_package, dosage_package_unit, dosage_to_take, dosage_to_take_unit, colour
		 * shape, food_instructions, indication, minimum_spacing, minimum_spacing_unit, maximum_dosage, maximum_dosage_unit, note
		 * TODO: schedule, schedule_id
		 */
		if( !isset($data->name) || !isset($data->active_agent) || !isset($data->dosage_package)
		    || !isset($data->dosage_package_unit) || !isset($data->dosage_to_take) || !isset($data->dosage_to_take_unit)
		    || !isset($data->colour) || !isset($data->shape) || !isset($data->food_instructions)
		    || !isset($data->indication) || !isset($data->minimum_spacing) || !isset($data->minimum_spacing_unit)
		    || !isset($data->maximum_dosage) || !isset($data->maximum_dosage_unit) || !isset($data->note) ) {
			$result['error'] = 'Missing data!';
			break;
		}
		// TODO: check for valid data

		// store
		$query = sprintf('INSERT INTO `medications` (`user_id`, `name`, `active_agent`, `dosage_package`, `dosage_package_unit`, `dosage_to_take`, `dosage_to_take_unit`, `colour`, `shape`, `food_instructions`, `indication`, `minimum_spacing`, `minimum_spacing_unit`, `maximum_dosage`, `maximum_dosage_unit`, `note`, `time`) VALUES (%u, "%s", "%s", %F, "%s", %F, "%s", %u, %u, %u, "%s", %F, "%s", %F, "%s", "%s", NOW())',
		    $_SESSION['userid'], $data->name, $data->active_agent, $data->dosage_package,
		    $data->dosage_package_unit, $data->dosage_to_take, $data->dosage_to_take_unit,
		    $data->colour, $data->shape, $data->food_instructions,
		    $data->indication, $data->minimum_spacing, $data->minimum_spacing_unit,
		    $data->maximum_dosage, $data->maximum_dosage_unit, $data->note);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// get ID of new medication entry
		$id = mysql_insert_id($mysql);
		if($id == FALSE || $id == 0) {
			$result['error'] = "An unexpected error occured!";
			break;
		}

		// return ID as proof of success
		$result['id'] = $id;
		break;
	case StoreType::MEDICATION_LOG:
		// expected fields: medication_id, quantity, timestamp, status, note (may be empty)
		if(!isset($data->medication_id) || !isset($data->quantity) || !isset($data->timestamp) || !isset($data->status) || !isset($data->note)) {
			$result['error'] = 'Missing data!';
			break;
		}

		/*
		 * medication_id should be numeric TODO: and exist
		 * quantity
		 * timestamp should be numeric
		 * status should be numeric
		 * note can be any string
		 */
		if(!is_numeric($data->medication_id) || !is_numeric($data->quantity) || !is_numeric($data->timestamp) || !is_numeric($data->status)) {
			$result['error'] = 'Invalid data!';
			break;
		}

		// store
		$query = sprintf('INSERT INTO `medication_log` (`medication_id`, `quantity`, `time_taken`, `status`, `note`, `time`) VALUES (%u, %u, FROM_UNIXTIME(%u), %u, "%s", NOW())', $data->medication_id, $data->quantity, $data->timestamp, $data->status, $data->note);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// get ID of new Log entry
		$id = mysql_insert_id($mysql);
		if($id == FALSE || $id == 0) {
			$result['error'] = "An unexpected error occured!";
			break;
		}

		// return ID as proof of success
		$result['id'] = $id;
		break;
	case StoreType::MEASUREMENT_ADD:
		// expected fields: name, unit

		// name should not be empty
		// unit should not be empty
		if(empty($data->name) || empty($data->unit)) {
			$result['error'] = 'Invalid data!';
			break;
		}

		// store
		$query = sprintf('INSERT INTO `measurements` (`user_id`, `name`, `unit`, `time`) VALUES (%u, "%s", "%s", NOW())', $_SESSION['userid'], $data->name, $data->unit);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// get ID of new measurement entry
		$id = mysql_insert_id($mysql);
		if($id == FALSE || $id == 0) {
			$result['error'] = "An unexpected error occured!";
			break;
		}

		// return ID as proof of success
		$result['id'] = $id;
		break;
	case StoreType::MEASUREMENT_LOG:
		// expected fields: measurement_id, value, timestamp

		// measurement_id should be numeric
		// value should be numeric
		// timestamp should be numeric
		if(!is_numeric($data->measurement_id) || !is_numeric($data->value) || !is_numeric($data->timestamp)) {
			$result['error'] = 'Invalid data!';
			break;
		}

		// store
		$query = sprintf('INSERT INTO `measurement_log` (`measurement_id`, `measurement`, `time_measured`, `time`) VALUES (%u, %F, FROM_UNIXTIME(%u), NOW())', $data->measurement_id, $data->value, $data->timestamp);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// get ID of new measurement entry
		$id = mysql_insert_id($mysql);
		if($id == FALSE || $id == 0) {
			$result['error'] = "An unexpected error occured!";
			break;
		}

		// return ID as proof of success
		$result['id'] = $id;
		break;
	default:
		$result['error'] = 'Invalid type!';
		break;
}

// return the result
echo json_encode($result);

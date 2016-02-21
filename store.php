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
 * { _type = "log_medication", parameters ..... }
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
 * scheduled (when supposed to be taken)
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
 * SCHEDULE_ADD: add another schedule for an existing medication
 * 
 * medication_id
 * type
 * start
 * times
 * end
 * interval
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
	const SCHEDULE_ADD = 5;

	public static function fromString($str) {
		switch($str) {
			case 'MEDICATION_ADD': return StoreType::MEDICATION_ADD;
			case 'MEDICATION_LOG': return StoreType::MEDICATION_LOG;
			case 'MEASUREMENT_ADD': return StoreType::MEASUREMENT_ADD;
			case 'MEASUREMENT_LOG': return StoreType::MEASUREMENT_LOG;
			case 'SCHEDULE_ADD': return StoreType::SCHEDULE_ADD;
			default: return -1;
		}
	}
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

// convert from string if required
if(!is_numeric($data->_type)) {
	$data->_type = StoreType::fromString($data->_type);
}

// execute
switch($data->_type) {
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
		if(!isset($data->medication_id) || !isset($data->quantity) || !isset($data->timestamp) || !isset($data->status) || !isset($data->note) || !isset($data->scheduled)) {
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
		if(!is_numeric($data->medication_id) || !is_numeric($data->quantity) || !is_numeric($data->timestamp) || !is_numeric($data->status) || !is_numeric($data->scheduled)) {
			$result['error'] = 'Invalid data!';
			break;
		}

		// store
		$query = sprintf('INSERT INTO `medication_log` (`medication_id`, `quantity`, `time_taken`, `status`, `note`, `time_scheduled`, `time`) VALUES (%u, %u, FROM_UNIXTIME(%u), %u, "%s", FROM_UNIXTIME(%u), NOW())', $data->medication_id, $data->quantity, $data->timestamp, $data->status, $data->note, $data->scheduled);
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
	case StoreType::SCHEDULE_ADD:
		// expected input data: medication_id, type, start, times, end, interval
		// first check data
		if( !is_numeric($data->medication_id) || !is_numeric($data->type) || !is_numeric($data->start) || !is_numeric($data->times) || !is_numeric($data->end) || !is_numeric($data->interval) ) {
			$result['error'] = 'Invalid data!';
			break;
		}

		// storing is non-trivial because there might already be another schedule for this medication already
		// look for such one(s) first
		$query = sprintf('SELECT `schedule`=1, `schedule_id` FROM `medications` WHERE `id`=%u', $data->medication_id);
		$r = mysql_query($query, $mysql);
		if($r == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// see if there was any result
		$count = mysql_num_rows($r);
		if($count == FALSE) {
			$result['error'] = "An unexpected error occured!";
			break;
		}
		if($count == 0) {
			$result['error'] = "An unexpected error occured!";
			break;
		}

		// fetch result
		$r = mysql_fetch_row($r);
		if($r == FALSE) {
			$result['error'] = "An unexpected error occured!";
			break;
		}
		list($schedule_id) = $r;

		// is there a schedule linked to this medication?
		if(is_numeric($schedule_id) && !empty($schedule_id)) {
			// This is the complicated case
			// Again there are 2 possibilities:
			// a) There is exactly 1 schedule for this medication, and it does not yet have a group ID
			// b) There are multiple schedules for this medication linked by a common group ID

			// load first schedules group ID
			$query = sprintf('SELECT `group_id` FROM `schedule` WHERE `id`=%u', $schedule_id);
			$r = mysql_query($query, $mysql);
			if($r == FALSE) {
				$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
				break;
			}
			$r = mysql_fetch_row($r);
			if($r == FALSE) {
				$result['error'] = "An unexpected error occured!";
				break;
			}
			list($group_id) = $r;

			// is this a valid group_id? (!=0, !=NULL)
			if(is_numeric($group_id) && !empty($group_id)) {
				// This is rather easy, a group id exists already
				// so add another schedule with same group ID
				$query = sprintf('INSERT INTO `schedule` (`group_id`, `type`, `schedule_start`, `times`, `schedule_end`, `interval`,`time`) VALUES (%u, %u, FROM_UNIXTIME(%u), %u, FROM_UNIXTIME(%u), %u, NOW())', $group_id, $data->type, $data->start, $data->times, $data->end, $data->interval);
				if(mysql_query($query, $mysql) == FALSE) {
					$result['error'] = "An unexpected error occured!";
					break;
				}
				$id = mysql_insert_id($mysql);
				if($id == FALSE || $id == 0) {
					$result['error'] = "An unexpected error occured!";
					break;
				}

				// return ID as proof of success
				$result['id'] = $id;
			} else {
				// This is the hardest part:
				// First, create a group ID
				// for ease of use, take first schedules ID
				$group_id = $schedule_id;

				// set group_id on existing schedule
				$query = sprintf('UPDATE `schedule` SET `group_id`=%u WHERE `id`=%u', $group_id, $schedule_id);
				if(mysql_query($query, $mysql) == FALSE) {
					$result['error'] = "An unexpected error occured!";
					break;
				}

				// add new schedule
				$query = sprintf('INSERT INTO `schedule` (`group_id`, `type`, `schedule_start`, `times`, `schedule_end`, `interval`,`time`) VALUES (%u, %u, FROM_UNIXTIME(%u), %u, FROM_UNIXTIME(%u), %u, NOW())', $group_id, $data->type, $data->start, $data->times, $data->end, $data->interval);
				if(mysql_query($query, $mysql) == FALSE) {
					$result['error'] = "An unexpected error occured!";
					break;
				}
				$id = mysql_insert_id($mysql);
				if($id == FALSE || $id == 0) {
					$result['error'] = "An unexpected error occured!";
					break;
				}

				// return ID as proof of success
				$result['id'] = $id;
			}
			
		} else {
			// This is the easy case!
			// just insert a new schedule, and link it.
			$query = sprintf('INSERT INTO `schedule` (`group_id`, `type`, `schedule_start`, `times`, `schedule_end`, `interval`,`time`) VALUES (%u, %u, FROM_UNIXTIME(%u), %u, FROM_UNIXTIME(%u), %u, NOW())', 0, $data->type, $data->start, $data->times, $data->end, $data->interval);
			if(mysql_query($query, $mysql) == FALSE) {
				$result['error'] = "An unexpected error occured!";
				break;
			}
			$id = mysql_insert_id($mysql);
			if($id == FALSE || $id == 0) {
				$result['error'] = "An unexpected error occured!";
				break;
			}

			// link the new schedule to the medication
			$query = sprintf('UPDATE `medications` SET `schedule_id`=%u, `schedule`=1 WHERE `id`=%u', $id, $data->medication_id);
			if(mysql_query($query, $mysql) == FALSE) {
				$result['error'] = "An unexpected error occured!";
				break;
			}

			// return ID as proof of success
			$result['id'] = $id;
		}
		break;
	default:
		$result['error'] = 'Invalid type!';
		break;
}

// return the result
echo json_encode($result);

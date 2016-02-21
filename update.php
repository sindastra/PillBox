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
 * Accepts JSON only
 * 
 * use POST data in field called "json"
 * 
 * Format:
 * { _type = TYPE, id = ID, arguments .... } <----- IMPORTANT: always pass the objects ID here!
 * valid types are:
 * 
 * MEDICATION: add new medication information
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
 * MEDICATION_LOG: log taking or not taking medication
 * parameters:
 * medication_id
 * quantity
 * timestamp (when taken)
 * status
 * note
 * scheduled (when supposed to be taken)
 * 
 * MEASUREMENT: add new measurement description/category
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
 * SCHEDULE: add another schedule for an existing medication
 * 
 * parameters:
 * medication_id
 * type
 * start
 * times
 * end
 * interval
 * 
 * returns a pair of { status = STATUS, error = MESSAGE }, encoded in json
 */

abstract class UpdateType {
	const MEASUREMENT = 0;
	const MEASUREMENT_LOG = 1;
	const MEDICATION = 2;
	const MEDICATION_LOG = 3;
	const SCHEDULE = 4;
}

abstract class UpdateResult {
	const SUCCESS = 0;
	const FAILURE = 1;
}

include "functions.php";

init_session();

include "include/cmdline_to_postandget_hack.inc";
include "include/mysql_open_database.inc";

// initialize a dummy failed result
$result = array('status' => DeleteResult::FAILURE, 'error' => '');

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

// check if valid ID was given
if(!is_numeric($data->id) || $data->id <= 0) {
	$result['error'] = 'Invalid ID!';
	echo json_encode($result);
	exit(0);
}

// execute
switch($data->_type) {
	case UpdateType::MEASUREMENT:
		// expected fields: name, unit

		// name should not be empty
		// unit should not be empty
		if(empty($data->name) || empty($data->unit)) {
			$result['error'] = 'Invalid data!';
			break;
		}

		// update
		$query = sprintf('UPDATE `measurements` SET `name`="%s", `unit`="%s", `time`=NOW() WHERE `id`=%u)', $data->name, $data->unit, $data->id);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// Done
		$result['status'] = UpdateResult::SUCCESS;
		break;
	case UpdateType::MEASUREMENT_LOG:
		// expected fields: measurement_id, value, timestamp

		// measurement_id should be numeric
		// value should be numeric
		// timestamp should be numeric
		if(!is_numeric($data->measurement_id) || !is_numeric($data->value) || !is_numeric($data->timestamp)) {
			$result['error'] = 'Invalid data!';
			break;
		}

		// update
		$query = sprintf('UPDATE `measurement_log` SET `measurement_id`=%u, `measurement`=%u, `time_measured`=FROM_UNIXTIME(%u), `time`=NOW() WHERE `id`=%u', $data->measurement_id, $data->value, $data->timestamp, $data->id);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// Done
		$result['status'] = UpdateResult::SUCCESS;
		break;
	case UpdateType::MEDICATION:
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

		// update
		$query = sprintf('UPDATE `medications` SET `name`="%s", `active_agent`"%s", `dosage_package`=%F, `dosage_package_unit`="%s", `dosage_to_take`=%F, `dosage_to_take_unit`="%s", `colour`=%u, `shape`=%u, `food_instructions`=%u, `indication`="%s", `minimum_spacing`=%F, `minimum_spacing_unit`="%s", `maximum_dosage`=%F, `maximum_dosage_unit`="%s", `note`="%s", `time`=NOW() WHERE `id`=%u',
		    $data->name, $data->active_agent, $data->dosage_package,
		    $data->dosage_package_unit, $data->dosage_to_take, $data->dosage_to_take_unit,
		    $data->colour, $data->shape, $data->food_instructions,
		    $data->indication, $data->minimum_spacing, $data->minimum_spacing_unit,
		    $data->maximum_dosage, $data->maximum_dosage_unit, $data->note, $data->id);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// Done
		$result['status'] = UpdateResult::SUCCESS;
		break;
	case UpdateType::MEDICATION_LOG:
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

		// update
		$query = sprintf('UPDATE `medication_log` SET `medication_id`%u, `quantity`=%u, `time_taken`=FROM_UNIXTIME(%u), `status`=%u, `note`="%s", `time_scheduled`=FROM_UNIXTIME(%u), `time`=NOW() WHERE `id`=%u', $data->medication_id, $data->quantity, $data->timestamp, $data->status, $data->note, $data->scheduled, $data->id);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// Done
		$result['status'] = UpdateResult::SUCCESS;
		break;
	case UpdateType::SCHEDULE:
		// expected fields: medication_id, type, start, times, end, interval
		if( !is_numeric($data->medication_id) || !is_numeric($data->type) || !is_numeric($data->start) || !is_numeric($data->times) || !is_numeric($data->end) || !is_numeric($data->interval) ) {
			$result['error'] = 'Invalid data!';
			break;
		}

		// update
		$query = sprintf('UPDATE `schedule` SET `type`=%u, `schedule_start`=FROM_UNIXTIME(%u), `times`=%u, `schedule_end`=FROM_UNIXTIME(%u), `interval`=%u,`time`=NOW() WHERE `id`=%u', $data->type, $data->start, $data->times, $data->end, $data->interval, $data->id);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// Done
		$result['status'] = UpdateResult::SUCCESS;
		break;
	default:
		$result['error'] = 'Invalid type!';
		break;
}

// return the result
echo json_encode($result);

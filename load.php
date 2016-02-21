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
 * { _type = TYPE, arguments .... }
 * valid types are:
 * 
 * MEDICATIONS_GET: get all medications of current user
 * takes no parameters
 * 
 * MEDICATION_LOG_GET: gets the medication log for all, or a given medication; in selected timespan
 * 
 * parameters:
 * medication_id : if zero, match *all* medications
 * start : if zero, matches from beginning of time, else UNIXTIME
 * end : if zero, matches till end of time, else UNIXTIME
 * 
 * MEASUREMENTS_GET: get all measurement definitions of current user
 * takes no parameters
 * 
 * MEASUREMENT_LOG_GET: get the measurement log for all, or given measurement definition; in selected timespan
 * 
 * parameters:
 * measurement_id : if zero, match all measurements
 * start : if zero, matches from beginning of time, else UNIXTIME
 * end : if zero, matches till end of time, else UNIXTIME
 * 
 * SCHEDULE_GET: get all schedules for given medication, or for all medications; wich are active in given timespan
 * 
 * parameters:
 * medication_id : if zero, matches all medications
 * start : if zero, matches from beginning of time, else UNIXTIME
 * end : if zero, matches till end of time, else UNIXTIME
 * 
 * returns 2 fields: data [array] and error [string] as json
 */

abstract class RequestType {
	const MEDICATIONS_GET = 0;
	const MEDICATION_LOG_GET = 1;
	const MEASUREMENTS_GET = 2;
	const MEASUREMENT_LOG_GET = 3;
	const SCHEDULE_GET = 4;

	public static function fromString($str) {
		switch($str) {
			case 'MEDICATIONS_GET': return RequestType::MEDICATIONS_GET;
			case 'MEDICATION_LOG_GET': return RequestType::MEDICATION_LOG_GET;
			case 'MEASUREMENTS_GET': return RequestType::MEASUREMENTS_GET;
			case 'MEASUREMENT_LOG_GET': return RequestType::MEASUREMENT_LOG_GET;
			case 'SCHEDULE_GET': return RequestType::SCHEDULE_GET;
			default: return -1;
		}
	}
}

include "functions.php";

init_session();

include "include/cmdline_to_postandget_hack.inc";
include "include/mysql_open_database.inc";

// initialize a dummy failed result
$result = array('data' => array(), 'error' => '');

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

// check if _type was passed
if(!isset($data->_type)) {
	$result['error'] = 'Please specify load type!';
	echo json_encode($result);
	exit(0);
}

// convert from string if required
if(!is_numeric($data->_type)) {
	$data->_type = RequestType::fromString($data->_type);
}

// execute
switch($data->_type) {
	case RequestType::MEDICATIONS_GET:
		// load all medication entries
		$query = sprintf('SELECT `id`, `name`, `active_agent`, `dosage_package`, `dosage_package_unit`, `dosage_to_take`, `dosage_to_take_unit`, `colour`, `shape`, `food_instructions`, `indication`, `minimum_spacing`, `minimum_spacing_unit`, `maximum_dosage`, `maximum_dosage_unit`, `schedule`, `note`, UNIX_TIMESTAMP(`time`) AS `time` FROM `medications` WHERE `user_id`=%u', $_SESSION['userid']);
		$r = mysql_query($query, $mysql);
		if($r == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// return array of schedules
		$count = mysql_num_rows($r);
		for($i = 0; $i < $count; $i++) {
			$row = mysql_fetch_assoc($r);
			$result['data'][] = $row;
		}

		// Done
		break;
	case RequestType::MEDICATION_LOG_GET:
		// expected input data: medication_id, start, end
		if(!is_numeric($data->medication_id) || !is_numeric($data->start) || !is_numeric($data->end)) {
			$result['error'] = 'Invalid or missing data!';
			break;
		}

		// This is a complicated query, so make 4 of them!
		// is medication ID given?
		if($data->medication_id > 0) {
			// is end-time given?
			if($data->end > 0) {
				$query = sprintf('SELECT `id`, `medication_id`, `quantity`, UNIX_TIMESTAMP(`time_taken`) AS `timestamp`, `status`, `note`, UNIX_TIMESTAMP(`time_scheduled`) AS scheduled FROM `medication_log` WHERE `medication_id`=%u AND `time_taken` > FROM_UNIXTIME(%u) AND `time_taken` < FROM_UNIXTIME(%u)', $data->medication_id, $data->start, $data->end);
			} else {
				$query = sprintf('SELECT `id`, ´medication_id`, `quantity`, UNIX_TIMESTAMP(`time_taken`) AS `timestamp`, `status`, `note`, UNIX_TIMESTAMP(`time_scheduled`) AS scheduled FROM `medication_log` WHERE `medication_id`=%u AND `time_taken` > FROM_UNIXTIME(%u)', $data->medication_id, $data->start);
			}
		} else {
			// is end-time given?
			if($data->end > 0) {
				$query = sprintf('SELECT l.`id`, l.`medication_id`, l.`quantity`, UNIX_TIMESTAMP(l.`time_taken`) AS `timestamp`, l.`status`, l.`note`, UNIX_TIMESTAMP(l.`time_scheduled`) AS scheduled FROM `medication_log` l LEFT JOIN `medications` m ON (l.`medication_id` = m.`id`) WHERE m.`user_id`=%u AND `time_taken` > FROM_UNIXTIME(%u) AND `time_taken` < FROM_UNIXTIME(%u)', $_SESSION['userid'], $data->medication_id, $data->start, $data->end);
			} else {
				$query = sprintf('SELECT l.`id`, l.`medication_id`, l.`quantity`, UNIX_TIMESTAMP(l.`time_taken`) AS `timestamp`, l.`status`, l.`note`, UNIX_TIMESTAMP(l.`time_scheduled`) AS scheduled FROM `medication_log` l LEFT JOIN `medications` m ON (l.`medication_id` = m.`id`) WHERE m.`user_id`=%u AND `time_taken` > FROM_UNIXTIME(%u)', $_SESSION['userid'], $data->start);
			}
		}
		// fetch data
		$r = mysql_query($query, $mysql);
		if($r == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// return all data as is
		$count = mysql_num_rows($r);
		for($i = 0; $i < $count; $i++) {
			$row = mysql_fetch_assoc($r);
			$result['data'][] = $row;
		}

		// Done
		break;
	case RequestType::MEASUREMENTS_GET:
		$query = sprintf('SELECT `id`, `name`, `unit` FROM `measurements` WHERE `user_id`=%u', $_SESSION['userid']);
		$r = mysql_query($query, $mysql);
		if($r == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// return all data as is
		$count = mysql_num_rows($r);
		for($i = 0; $i < $count; $i++) {
			$row = mysql_fetch_assoc($r);
			$result['data'][] = $row;
		}

		// Done
		break;
	case RequestType::MEASUREMENT_LOG_GET:
		// expected input data: measurement_id, start, end
		if(!is_numeric($data->measurement_id) || !is_numeric($data->start) || !is_numeric($data->end)) {
			$result['error'] = 'Invalid or missing data!';
			break;
		}

		// This is a complicated query, so make 4 of them!
		// is measurement ID given?
		if($data->measurement_id > 0)
			$query = sprintf('SELECT `id`, `measurement_id`, `measurement` AS `value`, UNIX_TIMESTAMP(`time_measured`) AS `timestamp` FROM `measurement_log` WHERE `measurement_id` = %u', $data->measurement_id);
		else
			$query = sprintf('SELECT l.`id`, l.`measurement_id`, l.`measurement` AS `value`, UNIX_TIMESTAMP(l.`time_measured`) AS `timestamp` FROM `measurement_log` l LEFT JOIN `measurements` m ON (l.`measurement_id` = m.`id`) WHERE m.`user_id`=%u', $_SESSION['userid']);
		// fetch data
		$r = mysql_query($query, $mysql);
		if($r == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// go through each result
		$count = mysql_num_rows($r);
		for($i = 0; $i < $count; $i++) {
			$row = mysql_fetch_assoc($r);

			// filter by given timespan
			if($row['timestamp'] > $data->start && ($row['timestamp'] < $data->end || $data->end == 0))
				$result['data'][] = $row;
		}

		// Done
		break;
	case RequestType::SCHEDULE_GET:
		// expected input data: medication_id, start, end
		if(!is_numeric($data->medication_id) || !is_numeric($data->start) || !is_numeric($data->end)) {
			$result['error'] = 'Invalid or missing data!';
			break;
		}

		// make it 2 steps:
		// 1) get all schedules directly linked to a medication
		// 2) get any that are in a group with either of those

		// 1)
		if($data->medication_id > 0)
			$query = sprintf('SELECT s.`id`, s.`group_id`, s.`type`, UNIX_TIMESTAMP(s.`schedule_start`) AS start, s.`times`, UNIX_TIMESTAMP(s.`schedule_end`) AS end, s.`interval`, m.`id` AS medication_id FROM `schedule` s LEFT JOIN `medications` m ON (s.`id` = m.`schedule_id`) WHERE m.`id`=%u', $data->medication_id);
		else
			$query = sprintf('SELECT s.`id`, s.`group_id`, s.`type`, UNIX_TIMESTAMP(s.`schedule_start`) AS start, s.`times`, UNIX_TIMESTAMP(s.`schedule_end`) AS end, s.`interval`, m.`id` AS medication_id FROM `schedule` s LEFT JOIN `medications` m ON (s.`id` = m.`schedule_id`) WHERE m.`user_id`=%u', $_SESSION['userid']);
		$r = mysql_query($query, $mysql);
		if($r == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}
		$count = mysql_num_rows($r);
		for($i = 0; $i < $count; $i++) {
			$row = mysql_fetch_assoc($r);
			$group_id = $row['group_id'];
			unset($row['group_id']);

			// filter by given timespan
			if($row['start'] > $data->start && ($row['end'] < $data->end || $data->end == 0 || $row['end'] == 0))
				$result['data'][] = $row;

			// 2)
			if(is_numeric($group_id) && !empty($group_id)) {
				// get the other group members
				if($data->end > 0)
					$query = sprintf('SELECT s.`id`, s.`type`, UNIX_TIMESTAMP(s.`schedule_start`) AS `schedule_start`, s.`times`, UNIX_TIMESTAMP(s.`schedule_end`) AS `schedule_end`, s.`interval`, m.`id` AS medication_id FROM `schedule` s LEFT JOIN `medications` m ON (s.`id` = m.`schedule_id`) WHERE s.`group_id`=%u AND s.`id`!=%u AND s.`schedule_start` > FROM_UNIXTIME(%u) AND (s.`schedule_end` < FROM_UNIXTIME(%u) || UNIX_TIMESTAMP(s.`schedule_end`)=0)', $group_id, $row['id'], $data->start, $data->end);
				else
					$query = sprintf('SELECT s.`id`, s.`type`, UNIX_TIMESTAMP(s.`schedule_start`) AS `schedule_start`, s.`times`, UNIX_TIMESTAMP(s.`schedule_end`) AS `schedule_end`, s.`interval`, m.`id` AS medication_id FROM `schedule` s LEFT JOIN `medications` m ON (s.`id` = m.`schedule_id`) WHERE s.`group_id`=%u AND s.`id`!=%u AND s.`schedule_start` > FROM_UNIXTIME(%u)', $group_id, $row['id'], $data->start);
				$r2 = mysql_query($query, $mysql);
				if($r2 == FALSE) {
					$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
					break;
				}
				$count = mysql_num_rows($r);
				for($i = 0; $i < $count; $i++) {
					$row = mysql_fetch_assoc($r);

					// filter by given timespan
					if($row['start'] > $data->start && ($row['end'] < $data->end || $data->end == 0 || $row['end'] == 0))
						$result['data'][] = $row;
				}
			}
		}

		// Done
		break;
	default:
		$result['error'] = 'Invalid type!';
		break;
}

// return the result
echo json_encode($result);

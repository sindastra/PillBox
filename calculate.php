<?php
/**
 * PillBox
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
 * { _type = TYPE, parameters ..... }
 * 
 * Valid types are:
 * 
 * SCHEDULE: produces a list of all scheduled intakes of medication in given timespan; including their status
 * 
 * parameters:
 * medication_id [ID] : 0 means match any
 * start [UNIXTIME] : if zero, matches from beginning of time
 * end [UNIXTIME] : if zero, matches till end of time
 * 
 * returns a list of:
 * medication_id
 * schedule_id
 * time
 * TODO: status
 */

abstract class CalculateTypes {
	const SCHEDULE = 0;
}

abstract class CalculateResult {
	const SUCCESS = 0;
	const FAILURE = 1;
}

abstract class ScheduleType {
	const UNKNOWN = 0;
	const INTERVALS = 1;
	const TIMES = 2;
	const DAILY_FIXED_TIME = 3;
}

include "functions.php";

init_session();

include "include/cmdline_to_postandget_hack.inc";
include "include/mysql_open_database.inc";

// initialize a dummy failed result
$result = array('status' => CalculateResult::FAILURE, 'error' => '', 'data' => array());

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
	case CalculateTypes::SCHEDULE:
		// expected fields: medication_id, start, end
		if(!is_numeric($data->medication_id) || !is_numeric($data->start) || !is_numeric($data->end)) {
			$result['error'] = 'Missing or illegal data!';
			break;
		}

		// collect all schedule definitions that are active in given timespan
		$schedules = array();
		{
			// first, find the schedule that is directly linked to the given medication, and get its group_id
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

				// check if group id is valid
				$group_id = $row['group_id'];
				if(is_numeric($group_id) && !empty($group_id)) {
					// get all schedules in this group
					$query = sprintf('SELECT s.`id`, s.`group_id`, s.`type`, UNIX_TIMESTAMP(s.`schedule_start`) AS start, s.`times`, UNIX_TIMESTAMP(s.`schedule_end`) AS end, s.`interval`, m.`id` AS medication_id FROM `schedule` s LEFT JOIN `medications` m ON (s.`id` = m.`schedule_id`) WHERE s.`group_id`=%u', $group_id);
					$r2 = mysql_query($query, $mysql);
					if($r2 == FALSE) {
						$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
						break;
					}
					$count = mysql_num_rows($r2);
					for($i = 0; $i < $count; $i++) {
						$row = mysql_fetch_assoc($r2);
						// check if it is still active
						// if no end time is given, assume end of time
						if($row['start'] > $data->start && ($row['end'] < $data->end || $data->end == 0 || $row['end'] == 0)) {
							unset($row['group_id']);
							$schedules[] = $row;
						} else {
							// not active now, ignore
						}
					}
				} else {
					// This is the only schedule for given medication.
					// check if it is still active
					// if no end time is given, assume end of time
					if($row['start'] > $data->start && ($row['end'] < $data->end || $data->end == 0 || $row['end'] == 0)) {
						unset($row['group_id']);
						$schedules[] = $row;
					} else {
						// not active now, ignore
					}
				}
			}
		}

		// evaluate each schedule
		foreach($schedules as $schedule) {
			switch($schedule['type']) {
				case ScheduleType::UNKNOWN:
					// TODO: What do I do?
					// I'll just claim everything is alright
					break;
				case ScheduleType::INTERVALS:
					
					break;
				case ScheduleType::TIMES:
					
					break;
				case ScheduleType::DAILY_FIXED_TIME:
					// This is relatively:
					// intake is supposed to happen today, at same time specified as start
					// So go from start_time and add as many days as necessary to reach specified timeframe
					// TODO: write better algorithm
					$time = $schedule['start'];
					while($time < $data->start)
						$time += 24*60*60; // + 1 day
					// now go on until either specified timespan, or the schedule ends
					while($time < $data->end && $time < $schedule['end']) {
						// push this one to result
						$result['data'][] = array(
							'medication_id' => $schedule['medication_id'],
							'time' => $time,
							'schedule_id' => $chedule['id']
						);
						$time += 24*60*60; // + 1 day
					}
					break;
				default:
					$result['error'] = 'Encountered a schedule with unknown type!';
					break 2;
			}
		}

		// Done
		$result['status'] = CalculateResult::SUCCESS;
		break;
	default:
		$result['error'] = 'Invalid type!';
		break;
}

// return the result
echo json_encode($result);

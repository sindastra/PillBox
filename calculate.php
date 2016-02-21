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
 * returns an array with these fields:
 * medication_id
 * schedule_id
 * time
 * status
 */

abstract class CalculateTypes {
	const SCHEDULE = 0;

	public static function fromString($str) {
		switch($str) {
			case 'SCHEDULE': return CalculateTypes::SCHEDULE;
			default: return -1;
		}
	}
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

abstract class ScheduleStatus {
	const UNKNOWN = 3;
	const TAKEN = 0;
	const SKIPPED = 1;
	const MISSED = 2;
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

// convert from string if required
if(!is_numeric($data->_type)) {
	$data->_type = CalculateTypes::fromString($data->_type);
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
					// every N seconds
					// find first time in given timespan
					$time = $schedule['start'];
					$step = $schedule['interval'];
					while($time < $data->start)
						$time += $step;

					// now procede till end of given timespan, or schedule
					while($time < $data->end && $time < $schedule['end']) {
						// add to result
						$result['data'][] = array(
							'medication_id' => $schedule['medication_id'],
							'time' => $time,
							'schedule_id' => $chedule['id']
						);
						$time += $step;
					}
					break;
				case ScheduleType::TIMES:
					// distribute number of intakes evenly across 24 hours
					$time = $schedule['start'];
					$times = $schedule['times'];

					// find first valid day
					while($time < $data->start)
						$time += 24*60*60; // + 1 day

					// now progress day by day, till end of schedule, or given timespan
					while($time < $data->end && $time < $schedule['end']) {
						// now deploy N times evenly across 24 hours
						$step = (24*60*60)/$times;

						// N times
						for($i = 0; $i < $times; $i++) {
							$result['data'][] = array(
								'medication_id' => $schedule['medication_id'],
								'time' => ($time + $i * $step),
								'schedule_id' => $chedule['id']
							);
						}
						$time += 24*60*60; // + 1 day
					}
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
					$result['error'] = 'Encountered a schedule with unhandled type!';
					break 2;
			}
		}

		// go through all of them once more and derive the status from the log
		foreach($result['data'] as &$schedule) {
			// set default status
			$schedule['status'] = ScheduleStatus::UNKNOWN;

			// see if there is a log entry for this medications scheduled time
			$query = sprintf('SELECT `time_taken`, `status`, `note` FROM `medication_log` WHERE `medication_id`=%u AND `time_scheduled`=FROM_UNIXTIME(%u)', $schedule['medication_id'], $schedule['time']);
			$r = mysql_query($query, $mysql);
			if($r == FALSE) {
				$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
				break;
			}
			$count = mysql_num_rows($r);
			if($count == 0) {
				// So nothing. That means it must have been missed
				$schedule['status'] = ScheduleStatus::MISSED;
			} else {
				$row = mysql_fetch_assoc($r);
				if($row == FALSE) {
					$result['error'] = "An unexpected error occured!";
					break 2;
				}
				list($time_taken, $status, $note) = $row;
				if($status == 0)
					$schedule['status'] = ScheduleStatus::TAKEN;
				else if($status == 1)
					$schedule['status'] = ScheduleStatus::SKIPPED;
			}
			
		} unset($schedule);

		// Done
		$result['status'] = CalculateResult::SUCCESS;
		break;
	default:
		$result['error'] = 'Invalid type!';
		break;
}

// return the result
echo json_encode($result);

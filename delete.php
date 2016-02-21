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
 * Accepts JSON only
 * 
 * use POST data in field called "json"
 * 
 * Format:
 * { _type = TYPE, id = ID }
 * valid types are defined below
 * 
 * returned is a pair of { status = STATUS, error = MESSAGE }, encoded in json
 */

abstract class DeleteType {
	const MEASUREMENT = 0;
	const MEASUREMENT_LOG = 1;
	const MEDICATION = 2;
	const MEDICATION_LOG = 3;
	const SCHEDULE = 4;
}

abstract class DeleteResult {
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
	case MEASUREMENT:
		// delete entry
		$query = sprintf('DELETE FROM `measurements` WHERE `id`=%u', $data->id);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// TODO: clear the log too?

		// Done
		$result['status'] = DeleteResult::SUCCESS;
		break;
	case MEASUREMENT_LOG:
		// delete entry
		$query = sprintf('DELETE FROM `measurement_log` WHERE `id`=%u', $data->id);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// Done
		$result['status'] = DeleteResult::SUCCESS;
		break;
	case MEDICATION:
		// delete entry
		$query = sprintf('DELETE FROM `medications` WHERE `id`=%u', $data->id);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// TODO: clear the log too?

		// Done
		break;
	case MEDICATION_LOG:
		// delete entry
		$query = sprintf('DELETE FROM `medication_log` WHERE `id`=%u', $data->id);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}

		// Done
		$result['status'] = DeleteResult::SUCCESS;
		break;
	case SCHEDULE:
		// Be careful! This might destroy the link in medications table
		// So first, read out the group_id of this schedule
		$query = sprintf('SELECT `group_id` FROM `schedule` WHERE `id`=%u', $data->id);
		$r = mysql_query($query, $mysql);
		if($r == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break;
		}
		$row = mysql_fetch_array($r);
		if($row == FALSE) {
			$result['error'] = "An unexpected error occured!";
			break;
		}
		$group_id = $row[0];

		// If group_id is invalid, there is no group and this is the only schedule for a medication
		if(!is_numeric($group_id) || empty($group_id)) {
			// update medication
			$query = sprintf('UPDATE `medications` SET `schedule`=0, `schedule_id`=0 WHERE `schedule_id`=%u', $data->id);
			if(mysql_query($query, $mysql) == FALSE) {
				$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
				break;
			}

			// delete schedule entry
			$query = sprintf('DELETE FROM `schedule` WHERE `id`=%u', $data->id);
			if(mysql_query($query, $mysql) == FALSE) {
				$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
				break;
			}
		} else {
			// find another schedule in this group, if any; and update the linked medication
			$query = sprintf('SELECT `id` FROM `schedule` WHERE `group_id`=%u LIMIT 1', $group_id);
			$r = mysql_query($query, $mysql);
			if($r == FALSE) {
				$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
				break;
			}
			$count = mysql_num_rows($r);
			if($count == 1) {
				$row = mysql_fetch_array($r);
				if($row == FALSE) {
					$result['error'] = "An unexpected error occured!";
					break;
				}
				$otherid = $row[0];

				// link medication to this schedule instead
				$query = sprintf('UPDATE `medications` SET `schedule_id`=%u WHERE `schedule_id`=%u', $otherid, $data->id);
				if(mysql_query($query, $mysql) == FALSE) {
					$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
					break;
				}
			} else {
				// no other schedule exists
				// update medication
				$query = sprintf('UPDATE `medications` SET `schedule`=0, `schedule_id`=0 WHERE `schedule_id`=%u', $data->id);
				if(mysql_query($query, $mysql) == FALSE) {
					$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
					break;
				}
			}

			// delete schedule entry
			$query = sprintf('DELETE FROM `schedule` WHERE `id`=%u', $data->id);
			if(mysql_query($query, $mysql) == FALSE) {
				$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
				break;
			}
		}
		$query = sprintf('DELETE FROM `schedule` WHERE `id`=%u', $data->id);
		$result['status'] = DeleteResult::SUCCESS;
		break;
	default:
		$result['error'] = 'Invalid type!';
		break;
}

// return the result
echo json_encode($result);

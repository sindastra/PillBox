<?php
/**
 * PillBox
 * Copyright (C) 2016 Sindastra <sindastra@gmail.com>
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
 * { type = "log_medication", gid = [GID], timestamp = [UNIXTIME], status = [INT] }
 * { type = "add_medication", TODO }
 * { type = "add_schedule????", TODO }
 * TODO
 * There will be a reply with an ID and an error string
 * non-zero ID means success, and error will be empty
 * 
 */

// define constants
abstract class StoreType {
	const LOG = 1;
	const MED = 2;
}

abstract class LogEntryStatus {
	const AUTO = 0;
	const TAKEN = 1;
	const SKIPPED = 2;
}

// start new, or restore existing session
//session_start();

// include functions
include "include/cmdline_to_postandget_hack.inc";
include "include/mysql_open_database.inc";

include "functions.php";

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
	$result['error'] = 'Please append data!';
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
	case StoreType::LOG:
		// expected fields: gid, timestamp, status
		if(empty($data->gid) || empty($data->timestamp) || empty($data->status)) {
			
			$result['error'] = 'Missing data!';
			break 2;
		}

		// TODO: check validity

		// store
		$query = sprintf('INSERT INTO `log` (`medication_id`, `timestamp`, `status`) VALUES (%u, FROM_UNIXTIME(%u), %u)', $data->gid, $data->timestamp, $data->status);
		if(mysql_query($query, $mysql) == FALSE) {
			$result['error'] = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
			break 2;
		}

		// get ID of new log Log entry
		$id = mysql_insert_id($mysql);
		if($id == FALSE || $id == 0) {
			$result['error'] = "An unexpected error occured!";
			break 2;
		}

		// return ID as proof of success
		$result['id'] = $id;
		break;
	case StoreType::MED:
		break;
	default;
		$result['error'] = 'Invalid type!';
		break;
}

// return the result
echo json_encode($result);

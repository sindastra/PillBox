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

// check if user is logged in
if(!is_logged_in()) {
	die("Please log in first!");
}

// check if data was passed
if(!array_key_exists('json', $_POST)) {
	die("Please append data!");
}

// try to decode
$data = json_decode($_POST['json']);
if($data == NULL)
	die("Failed to decode json!");

// execute
switch($data->type) {
	case StoreType::LOG:
		// expected fields: gid, timestamp, status
		if(empty($data->gid) || empty($data->timestamp) || empty($data->status))
			die("Missing data!");

		// TODO: check validity

		// store
		$query = sprintf('INSERT INTO `log` (`medication_id`, `timestamp`, `status`) VALUES (%u, FROM_UNIXTIME(%u), %u)', $data->gid, $data->timestamp, $data->status);
		if(mysql_query($query, $mysql) == FALSE) {
			die($query . ': ' . mysql_error($mysql));
		}

		// get ID of new log Log entry
		$id = mysql_insert_id($mysql);
		if($id == FALSE || $id == 0) {
			die("An unexpected error occured!");
		}

		// return ID as proof of success
		$result = array('id' => $id);
		$result = json_encode($result);
		break;
	case StoreType::MED:
		break;
	default;
		die("Invalid type!");
}

// return the result
echo $result;

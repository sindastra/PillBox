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
 * { type = TYPE, arguments .... }
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

// execute
switch($data->type) {
	default:
		$result['error'] = 'Invalid type!';
		break;
}

// return the result
echo json_encode($result);
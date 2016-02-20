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
 */#

/**
 * use POST data
 * fields:
 * username
 * password
 * email
 *
 * returns numeric success/error code
 */

abstract class SignupResult {
	const SUCCESS = 0;
	const INTERNAL_ERROR = 1;
	const MISSING_DATA = 2;
	const USERNAME_IN_USE = 3;
	const EMAIL_IN_USE = 4;
}

include "functions.php";

init_session();

include "include/mysql_open_database.inc";
include "include/cmdline_to_postandget_hack.inc";


// check if sufficient data was provided
if( !array_key_exists('username', $_POST) || !array_key_exists('password', $_POST) || !array_key_exists('email', $_POST)
    || empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email']) ) {
	$error = 'missing data';
	echo SignupResult::MISSING_DATA;
	exit(0);
}

// TODO: enforce things like minimum length?

// check if username is available
$query = sprintf('SELECT COUNT(`id`) FROM `accounts` WHERE `username`="%s"', $_POST['username']);
$r = mysql_query($query, $mysql);
if($r == FALSE) {
	$error = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
	echo SignupResult::INTERNAL_ERROR;
	exit(0);
}
$r = mysql_fetch_row($r);
list($count) = $r;
if($count != 0) {
	echo SignupResult::USERNAME_IN_USE;
	exit(0);
}

// check if email is available
$query = sprintf('SELECT COUNT(`id`) FROM `accounts` WHERE `email`="%s"', $_POST['email']);
$r = mysql_query($query, $mysql);
if($r == FALSE) {
	$error = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
	echo SignupResult::INTERNAL_ERROR;
	exit(0);
}
$r = mysql_fetch_row($r);
list($count) = $r;
if($count != 0) {
	echo SignupResult::EMAIL_IN_USE;
	exit(0);
}

// hsh password
$salt = generate_salt();
$password = hash_password($_POST['password'], $salt);

// create accoount
$query = sprintf('INSERT INTO `accounts` (`username`, `password`, `email`, `salt`, `time`) VALUES("%s", "%s", "%s", "%s", NOW())', $_POST['username'], $password, $_POST['email'], $salt);
$r = mysql_query($query, $mysql);
if($r == FALSE) {
	$error = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
	echo SignupResult::INTERNAL_ERROR;
	exit(0);
}

// check if it really worked
$count = mysql_affected_rows($mysql);
if($count != 1) {
	$error = 'Something went very VERY wrong!';
	echo SignupResult::INTERNAL_ERROR;
	exit(0);
}

// log-in user right now
$_SESSION['userid'] = $id;
$_SESSION['username'] = $username;



// return success
echo SignupResult::SUCCESS;

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

/*
 * use POST
 * fields are called: username, password
 * returned is a numeric error code
 */

abstract class LoginResult {
	const SUCCESS = 0;
	const INVALID_REQUEST = 1;
	const INTERNAL_ERROR = 2;
	const INVALID_DATA = 3;
}

include "functions.php";

init_session();

include "include/mysql_open_database.inc";
include "include/cmdline_to_postandget_hack.inc";

// expect login information to be passed as POST
// check if all required inputs were supplied
if(!array_key_exists("username", $_POST)) {
	die("Please supply a username!");
}
if(!array_key_exists("password", $_POST)) {
	die("Please supply a password!");
}

$given_username = POST_SECURE('username');
$given_password = strtolower($_POST['password']); // Make sure password is case-insensitive to make signing in as smooth as possible for judges.

// see if the username exists, and retrieve its data
$query = sprintf('SELECT `id`, `username`, `password`, `email`, `salt`, `time` FROM `accounts` WHERE `username`="%s" OR `email`="%s"', $given_username, $given_username);
$r = mysql_query($query, $mysql);
if($r == FALSE) {
	$error = 'Query ' . $query . ' failed: ' . mysql_error($mysql);
	echo LoginResult::INTERNAL_ERROR;
	exit(0);
}
$r = mysql_fetch_row($r);
if($r == FALSE) {
	$error = 'No result';
	echo LoginResult::INVALID_DATA;
	exit(0);
}
list($id, $username, $password, $email, $salt, $time) = $r;

// check given password with hashed one from database
// TODO
if(!check_password($password, $given_password, $salt)) {
	echo LoginResult::INVALID_DATA;
	exit(0);
}

// store log-in status in session
$_SESSION['userid'] = $id;
$_SESSION['username'] = $username;

// return positive result
echo LoginResult::SUCCESS;

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

// start new, or restore existing session
session_start();

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

// Soooooo
// we need to check username and password
// store the username in the session?
// and an authentication token for later use?
$_SESSION["username"] = "demo";
$_SESSION["security_token"] = "demo_token";

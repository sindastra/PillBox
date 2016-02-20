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
 * Login-related functions
 */

init_session() {
	// start new, or restore existing session
	session_start();

	// check fi all variables are set
	if(!array_key_exists("username", $_SESSION)
	   || empty($_SESSION["username"]) ) {
		// if anything is missing here, clear the session, just to be safe.
		session_destroy();
		unset $_SESSION;
		session_start();
	}
}

function is_logged_in() {
	// TODO make this much better
	// for now, just check if a username is set
	return array_key_exists("username", $_SESSION) && !empty($_SESSION['username']);
}

/**
 * Password related functions.
 */

function sha512($data)
{
	return hash('sha512', $data);
}

function generate_salt()
{
	return sha512(openssl_random_pseudo_bytes(10240));
}

function hash_password($password, $salt)
{
	return sha512($password.$salt);
}

function check_password($password_hashed, $password_text, $salt)
{
	if($password_hashed === hash_password($password_text, $salt))
		return true;
	else
		return false;
}

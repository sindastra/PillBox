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

document.getElementById("submitsignup").addEventListener("click", function(){
    var username = document.getElementById("username").value;
    var password = document.getElementById("password").value;
    var email = document.getElementById("email").value;
    var form = document.getElementById("signup");
    var postURL = form.getAttribute("action");
    $.post(postURL, {username:username, email:email, password:password}, function(data){
        if(data == 0)
            window.location = "main.html";
        if(data == 1)
            alert("Internal error, please try again later.");
        if(data == 3)
            alert("Username taken.");
        if(data == 4)
            alert("E-mail already in use.");
    }, "text");
});
document.getElementById("submitsignin").addEventListener("click", function(){
    var username = document.getElementById("signinusername").value;
    var password = document.getElementById("signinpassword").value;
    var form = document.getElementById("signinform");
    var postURL = form.getAttribute("action");
    $.post(postURL, {username:username, password:password}, function(data){
        if(data == 0)
            window.location = "main.html";
        if(data == 1 || data == 2)
            alert("Internal error, please try again later.")
        if(data == 3)
            alert("Wrong login data. Typo?");
    }, "text");
});
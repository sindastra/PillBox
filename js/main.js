var requestURL = "request.php";
var storeURL   = "store.php";

var clock = document.getElementById("clock");
var date  = document.getElementById("date");

var months = [
    "Jan", "Feb", "Mar", "Apr",
    "May", "Jun", "Jul", "Aug",
    "Sep", "Oct", "Nov", "Dec"
];

function add_leading_zero(number)
{
    if(number < 10)
        return "0" + number;
    else
        return number;
}

function get_all_medications()
{
    $.post(requestURL,{json:'{"type":"MEDICATIONS_GET"}'}, function(data){
        data["data"].forEach(function(entry){
            console.log(entry["name"]);
        });
    }, "json");
}

function create_medication(name,dosage_package,dosage_package_unit,
                            active_agent,dosage_to_take,dosage_to_take_unit,
                           colour,shape,food_instructions,indication,
                           minimum_spacing,minimum_spacing_unit,
                            maximum_dosage,maximum_dosage_unit,note)
{
    var medication_data = {
        "_type":"1",
        "name":name,
        "dosage_package":dosage_package,
        "dosage_package_unit":dosage_package_unit,
        "active_agent":active_agent,
        "dosage_to_take":dosage_to_take,
        "dosage_to_take_unit":dosage_to_take_unit,
        "colour":colour,
        "shape":shape,
        "food_instructions":food_instructions,
        "indication":indication,
        "minimum_spacing":minimum_spacing,
        "minimum_spacing_unit":minimum_spacing_unit,
        "maximum_dosage":maximum_dosage,
        "maximum_dosage_unit":maximum_dosage_unit,
        "note":note
    }
    
    $.post(storeURL, {json:JSON.stringify(medication_data)}, function(data){
        console.log(data);
    },"json");
}

function updateClock()
{
    var now = new Date();
    clock.innerHTML = add_leading_zero(now.getHours()) +":"+ add_leading_zero(now.getMinutes()) +":"+ add_leading_zero(now.getSeconds());
}

function updateDate()
{
    var now = new Date();
    date.innerHTML = months[now.getMonth()] +" "+ now.getDate();
}

setInterval(function(){
    updateClock();
    updateDate();
}, 100);
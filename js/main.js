var requestURL = "load.php";
var storeURL   = "store.php";
var deleteURL  = "delete.php";
var updateURL  = "update.php";

var medications;
var medicationsLoadSuccess = false;
var medicationsLoadErrorCount = 0;
var medicationsLoadErrorThreshhold = 15;

var clock = document.getElementById("clock");
var date  = document.getElementById("date");

var medicationTable = document.getElementById("medicationTable");

function generate_alertbox_error(text)
{
    var alertBox = document.createElement("div");
    alertBox.className += "alert alert-danger";
    alertBox.innerHTML = text;
    
    return alertBox;
}

function generate_add_medication_select()
{
    var select = document.createElement("select");
    select.id = "add_medication_select";
    medications.forEach(function(entry){
        var option = document.createElement("option");
        option.value = entry["id"];
        option.innerHTML = entry["name"];
        select.appendChild(option);
    });
    return select;
}

function clear_medications_table()
{
    medicationTable.innerHTML = "";
}

function load_medications_into_table(data)
{
    data.forEach(function(entry){
        var tr = document.createElement("tr");
        var td = document.createElement("td");
        
        td.innerHTML = entry["name"] +" "+ entry["dosage_package"] +" "+ entry["dosage_package_unit"];
        
        tr.appendChild(td);
        medicationTable.appendChild(tr);
    });
}

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
    $.post(requestURL,{json:'{"_type":"MEDICATIONS_GET"}'}, function(data){
        data["data"].forEach(function(entry){
            console.log(entry["name"]);
        });
        medications = data["data"];
    }, "json").done(function(){
        medicationsLoadSuccess = true;
        medicationsLoadErrorCount = 0;
        console.log("Medication load success.");
    }).fail(function(){
        medicationsLoadSuccess = false;
        medicationsLoadErrorCount++;
        console.log("Medication load failed.");
        console.log("Error count: "+medicationsLoadErrorCount);
    });
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

function create_medication_log(medication_id, quantity, timestamp, status, note)
{
    var medication_log_data = {
        "_type":"2",
        "medication_id":medication_id,
        "quantity":quantity,
        "status":status,
        "note":note,
        "timestamp":(Date.now()/1000) // Time is running... Ha. Deja vu.
    }

    $.post(storeURL, {json:JSON.stringify(medication_log_data)}, function(data){
        console.log(data);
    },"json");
}

function create_measurement_type(name, unit)
{
    var measurement_type_data = {
        "_type":"3",
        "name":name,
        "unit":unit
    }

    $.post(storeURL, {json:JSON.stringify(measurement_type_data)}, function(data){
        console.log(data);
    },"json");
}

function create_measurement_taken(id, value)
{
    var measurement_taken_data = {
        "_type":"4",
        "measurement_id":id,
        "value":value,
        "timestamp":(Date.now()/1000) // Time is running... Ha.
    }

    $.post(storeURL, {json:JSON.stringify(measurement_taken_data)}, function(data){
        console.log(data);
    },"json");
}

function create_schedule(medication_id, type, start, times, end, interval)
{
    var schedule_data = {
        "_type":"5",
        "medication_id":medication_id,
        "type":type,
        "start":start,
        "times":times,
        "end":end,
        "interval":interval,
        "timestamp":(Date.now()/1000) // Time is running... Ha. Deja vu.
    }

    $.post(storeURL, {json:JSON.stringify(schedule_data)}, function(data){
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

setInterval(function(){
    get_all_medications();
    
    if( medicationsLoadSuccess )
    {
        clear_medications_table();
        load_medications_into_table(medications);
    }
    else
    {
        if(medicationsLoadErrorCount > medicationsLoadErrorThreshhold)
        {
            console.log("Error count above threshold.");
            console.log("Outputting error to user.")
            medicationTable.innerHTML = "We are currenlty having issues to fetch the medications... retrying...";
        }
    } 
}, 3000);

document.getElementById("addmedbutton").addEventListener("click", function(){
    var selplace = document.getElementById("addmedselhere");
    selplace.innerHTML = "";
    selplace.appendChild(generate_add_medication_select());
});
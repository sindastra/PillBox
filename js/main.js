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
    $.post(requestURL,{json:'{"type":"MEDICATIONS_GET"}'}, function(data){
        data["data"].forEach(function(entry){
            console.log(entry["name"]);
        });
        medications = data["data"];
    }, "json").done(function(){
        medicationsLoadSuccess = true;
        medicationsLoadErrorCount = 0;
        console.log("Medication load success.")
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
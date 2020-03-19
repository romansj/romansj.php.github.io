<?php

/**
 * This is the main file which receives and analyzes data,
 * generates response data and finally calls the template.
 */

// show all warnings and errors on the screen
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cities = array("Cēsis" => "Latvia/Cesu/Cēsis",
    "Daugavpils" => "Latvia/Daugavpils/Daugavpils",
    "Jēkabpils" => "Latvia/Jekabpils/Jēkabpils",
    "Jelgava" => "Latvia/Jelgava/Jelgava",
    "Jūrmala" => "Latvia/Jurmala~/Jūrmala",
    "Liepāja" => "Latvia/Liepaja/Liepāja",
    "Ogre" => "Latvia/Ogres/Ogre",
    "Rēzekne" => "Latvia/Rezekne/Rēzekne",
    "Riga" => "Latvia/Riga/Riga",
    "Salaspils" => "Latvia/Salaspil/Salaspils",
    "Tukums" => "Latvia/Tukuma/Tukums",
    "Valmiera" => "Latvia/Valmieras/Valmiera",
    "Ventspils" => "Latvia/Ventspils/Ventspils");

// DO NOT EDIT BEFORE THIS LINE


/* Functions and classes You might want to use (you have to study function descriptions and examples)
 * Note: You can easily solve this task without using any regular expressions
file_get_contents() http://lv1.php.net/file_get_contents
file_put_contents() http://lv1.php.net/file_put_contents
file_exists() http://lv1.php.net/file_exists
SimpleXMLElement http://php.net/manual/en/simplexml.examples-basic.php http://php.net/manual/en/class.simplexmlelement.php 
date() http://lv1.php.net/manual/en/function.date.php or Date http://lv1.php.net/manual/en/class.datetime.php
Multiple string functions (choose by studying descriptions) http://lv1.php.net/manual/en/ref.strings.php
Multiple variable handling functions (choose by studying descriptions) http://lv1.php.net/manual/en/ref.var.php
Optionally you can use some array functions (with $_GET, $cities) http://lv1.php.net/manual/en/ref.array.php
*/

// Your code goes here
date_default_timezone_set('Europe/Helsinki');


$result = ""; //valid values: empty string, "OK", "ERROR"
$error_message = "";
$date = "";
$city = "";
$forecast = [];




function saveFile($xml, $fileName): void {
    if ($xml->asXML($fileName)) {
        echo 'Saved!<br>';
    } else {
        echo 'Unable to save to file :(<br>';
    }
}


function goodFileExists($fileName, &$xml): bool {
    $exists = file_exists($fileName);

    if ($exists) {
        echo 'Loaded saved file!<br>';
        $xml = simplexml_load_file($fileName);

        $updateTime = $xml->meta->lastupdate;
        $updateDate = date('Y-m-d', strtotime($updateTime));

        $curDate = date("Y-m-d");
        return $curDate == $updateDate;
    }

    return false;
}

function getWeatherData($dateGet, ?SimpleXMLElement $xml): array {
    if ($xml == null) {
        return null;
    }


    $dateTimeValue = strtotime($dateGet);

    $theTime = null;
    foreach ($xml->forecast->tabular->children() as $time) {
        $from = strtotime($time['from']);
        $to = strtotime($time['to']);


        if ($dateTimeValue >= $from && $dateTimeValue <= $to) {
            $theTime = $time;
            break;
        }
    }

    //type (cloudy, sunny etc)
    $type = $theTime->symbol;
    $typeVal = $type['name'];

    //precipitation
    $precipitation = $theTime->precipitation;
    $precipitationVal = $precipitation['value'];

    //wind
    $windDir = $theTime->windDirection;
    $windDirVal = $windDir['name'];

    $windSpeed = $theTime->windSpeed;
    $windSpeedVal = $windSpeed['mps'];

    //temperature
    $temp = $theTime->temperature;
    $tempVal = $temp['value'];

    //pressure
    $pressure = $theTime->pressure;
    $pressureVal = $pressure['value'];
    $pressureUnit = $pressure['unit'];


    return array(
        'Type' => $typeVal,
        'Precipitation' => $precipitationVal . '%',
        'Wind_direction' => $windDirVal,
        'Wind_speed' => $windSpeedVal . ' mps',
        'Temperature' => $tempVal . '°C',
        'Pressure' => $pressureVal . ' ' . $pressureUnit);


}


function printWeather($city, $dateGet, array $forecast): void {
    echo 'Weather in ' . $city . ' on ' . $dateGet . '<br>';


    foreach ($forecast as $key => $value) {
        echo "$key: $value<br>";
    }
}


//check validity

$isValid = false;

$validEntry = isset($_GET['city']) && !empty($_GET['city']) && isset($_GET['date']) && !empty($_GET['date']);

if ($validEntry) {
    $cityGet = $_GET['city'];
    $dateGet = $_GET['date'];

    #need to check if entered DATE is not in the past
    #need to check if entered DATE & TIME are not in the future
    $curTime = time();


    $dateTimeValue = strtotime($dateGet);
    $timeOK = $dateTimeValue <= $curTime;

    $isYesterday = date('Ymd', $dateTimeValue) == date('Ymd', strtotime('yesterday'));

    //echo 'entered ' . $dateTimeValue . ' ' . $dateGet . '<br>current ' . $curTime . '<br>isYesterday ' . ($isYesterday == 1 ? 'true' : 'false') . ' not in the future ' . ($timeOK == 1 ? 'true' : 'false');

    if (!$isYesterday && $timeOK) {
        $isValid = true;
    }
}


if ($isValid) {


    //todo 19.03!!! DUE 20.03
    //check that not only are city and date set, but that they are filled correctly (is it even possible to fill incorrectly - firefox :)?)

    //$ check if file exists in /xml directory (".../xml/city.xml")
    //$ need to check that last file's date is today -> if it is, then reuse the file **
    //$ if it isn't --> fetch a new file
    //network down - show message (or website not accessible)
    //need to match date input format to user's format (18.03.2020, 23:38)

    //firefox allows the user to enter any characters he/she wants. all the other browsers only provide a standart US-date-format form.


    //** checking date == today
    // https://stackoverflow.com/questions/25622370/php-how-to-check-if-a-date-is-today-yesterday-or-tomorrow

    //also read https://stackoverflow.com/questions/10057671/how-does-php-foreach-actually-work?rq=1

    //$t=date();
    //echo($t . "<br>"); //this returns 'long' value.


    $city = array_search($cityGet, $cities);
    $fileName = 'xml/' . $city . '.xml';

    $xml = null;
    if (goodFileExists($fileName, $xml)) {
        //xml tika modificets funkcija
        //datums tika parbaudits
        //tagad janolasa faila saturs

    } else {
        $urlAddress = 'https://www.yr.no/place/' . $cityGet . '/forecast.xml'; //because $cityGet gives the address part for the city
        $xml = simplexml_load_file($urlAddress) or die("Error: Cannot create object");

        saveFile($xml, $fileName);
    }

    $forecast = getWeatherData($dateGet, $xml);
    printWeather($city, $dateGet, $forecast);

} else {
    echo '<br>' . 'Please select a city and enter a valid date and time.' . '<br>If you are using Firefox, please switch to another browser to see the required datetime format.';
}


// DO NOT EDIT AFTER THIS LINE

require("view.php");
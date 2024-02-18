<?php

$apiKey = 'LVkT40AAbKcEoypKafIDefhIuIiqGt9g';
$cityKey = '28143';

$url = "http://dataservice.accuweather.com/currentconditions/v1/{$cityKey}?apikey={$apiKey}";
$weatherData = json_decode(file_get_contents($url) , true);

if ($weatherData)
{
    $temperature = $weatherData[0]['Temperature']['Imperial']['Value'];
    $weatherText = $weatherData[0]['WeatherText'];

   
    echo "<p>Temperature: {$temperature}&deg;F</p><p>Weather: {$weatherText}</p>";
}
else
{
    
    echo "<p>Error fetching weather data</p>";
}
?>
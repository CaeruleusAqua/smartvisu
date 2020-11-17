<?php
/**
 * -----------------------------------------------------------------------------
 * @package     smartVISU
 * @author      Stefan Widmer
 * @copyright   2018
 * @license     GPL [http://www.gnu.de]
 * -----------------------------------------------------------------------------
 */


require_once '../../../lib/includes.php';
require_once const_path_system.'weather/weather.php';
require_once const_path_system.'class_cache.php';


/**
 * This class generates a weather
 */
class weather_darksky extends weather
{

	/**
	 * retrieve the content
	 */
	public function run()
	{
		// api call
		$cache = new class_cache('darksky_' . $this->location . '.json');

		if ($cache->hit($this->cache_duration_minutes)) {
			$content = $cache->read();
		} else {
			$url = 'https://api.darksky.net/forecast/' . config_weather_key . '/' . $this->location . '?exclude=minutely,hourly,alerts&units=auto&lang=' . trans('darksky', 'lang');
			$content = file_get_contents($url);
			$cache->write($content);
		}

		$parsed_json = json_decode($content);
		if ($parsed_json->{'daily'}) {
			$this->debug($parsed_json);

			// today
			$this->data['current']['temp'] = transunit('temp', (float)$parsed_json->{'currently'}->{'temperature'});

			$this->data['current']['conditions'] = (string)$parsed_json->{'currently'}->{'summary'};
			$this->data['current']['icon'] = $this->icon((string)$parsed_json->{'currently'}->{'icon'}, $this->icon_sm);

			$wind_speed = transunit('speed', (float)$parsed_json->{'currently'}->{'windSpeed'});
			$wind_gust = transunit('speed', (float)$parsed_json->{'currently'}->{'windGust'});
			$wind_direction = $this->getDirection((int)$parsed_json->{'currently'}->{'windBearing'});

			$this->data['current']['wind'] = translate('wind', 'darksky') . " " . $wind_speed;
			// when there is no wind, direction is blank
			if ($parsed_json->{'currently'}->{'windSpeed'} != 0)
				$this->data['current']['wind'] .= " " . translate('from', 'darksky') . " " . $wind_direction;
			if ($wind_gust > 0)
				$this->data['current']['wind'] .= ", " . translate('wind_gust', 'darksky') . " " . $wind_gust;

			$this->data['current']['more'] = translate('humidity', 'darksky') . " " . transunit('%', 100 * (float)$parsed_json->{'currently'}->{'humidity'});

			// forecast
			$i = 0;
			foreach ($parsed_json->{'daily'}->{'data'} as $day) {
				if ((int)$day->{'time'} < mktime(0, 0, 0) || (int)$day->{'time'} > time() + 3 * 24 * 60 * 60) // next 4 days only
					continue;

				$this->data['forecast'][$i]['date'] = date('Y-m-d', (int)$day->{'time'});
				$this->data['forecast'][$i]['conditions'] = (string)$day->{'summary'};
				$this->data['forecast'][$i]['icon'] = $this->icon((string)$day->{'icon'});

				$this->data['forecast'][$i]['temp'] = round((float)$day->{'temperatureHigh'}, 0) . '&deg;/' . round((float)$day->{'temperatureLow'}, 0) . '&deg;';

				$i++;
			}
		} else {
			$add = $parsed_json->{'flags'}->{'darksky-unavailable'};
			$this->error('Weather: darksky.net', 'Read request failed' . ($add ? ': ' . $add : '') . '!');
		}
	}

	/*
	* Icon-Mapper
	*/
	function icon($name, $sm = 'sun_')
	{
		$ret = '';

		$icon['clear-day'] = $sm . '1';
		$icon['clear-night'] = $sm . '1';
		$icon['partly-cloudy-day'] = $sm . '4';
		$icon['partly-cloudy-night'] = $sm . '4';
		$icon['fog'] = $sm . '6';
		$icon['rain'] = 'cloud_8';
		$icon['wind'] = $sm . '10';
		$icon['snow'] = $sm . '12';

		$icon['cloudy'] = 'cloud_4';
		$icon['sleet'] = 'cloud_11';

		$ret = $icon[$name];

		return $ret;
	}

	function getDirection($degree)
	{
		$direction = '';

		if ($degree > 348 or $degree <= 11) {
			$direction = translate('dir_n', 'darksky');
		} elseif ($degree > 11 and $degree <= 34) {
			$direction = translate('dir_nne', 'darksky');
		} elseif ($degree > 34 and $degree <= 56) {
			$direction = translate('dir_ne', 'darksky');
		} elseif ($degree > 56 and $degree <= 79) {
			$direction = translate('dir_ene', 'darksky');
		} elseif ($degree > 79 and $degree <= 101) {
			$direction = translate('dir_e', 'darksky');
		} elseif ($degree > 101 and $degree <= 123) {
			$direction = translate('dir_ese', 'darksky');
		} elseif ($degree > 123 and $degree <= 146) {
			$direction = translate('dir_se', 'darksky');
		} elseif ($degree > 146 and $degree <= 169) {
			$direction = translate('dir_sse', 'darksky');
		} elseif ($degree > 169 and $degree <= 191) {
			$direction = translate('dir_s', 'darksky');
		} elseif ($degree > 191 and $degree <= 214) {
			$direction = translate('dir_ssw', 'darksky');
		} elseif ($degree > 214 and $degree <= 236) {
			$direction = translate('dir_sw', 'darksky');
		} elseif ($degree > 236 and $degree <= 259) {
			$direction = translate('dir_wsw', 'darksky');
		} elseif ($degree > 259 and $degree <= 281) {
			$direction = translate('dir_w', 'darksky');
		} elseif ($degree > 281 and $degree <= 304) {
			$direction = translate('dir_wnw', 'darksky');
		} elseif ($degree > 304 and $degree <= 326) {
			$direction = translate('dir_nw', 'darksky');
		} elseif ($degree > 326 and $degree <= 348) {

			$direction = translate('dir_nnw', 'darksky');
		}

		return $direction;
	}
}


// -----------------------------------------------------------------------------
// call the service
// -----------------------------------------------------------------------------

$service = new weather_darksky(array_merge($_GET, $_POST));
echo $service->json();

?>

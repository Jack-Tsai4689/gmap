<?php
namespace Jack;
class Gmap
{
	private $gmap_key;
	private $EARTH_RADIUS = 6371;//地球半徑，平均半徑爲6371km

	public function key($key)
	{
		$this->gmap_key = $key;
	}
	/**
	 * 計算某個經緯度的周圍某段距離的正方形的四個點
	 * @param $lng  經度
	 * @param $lat  緯度
	 * @param float $distance 該點所在圓的半徑，該圓與此正方形內切，默認值爲0.5km
	 * @return array
	 */
	public function squarePoint($lng, $lat, $distance=0.5)
	{
		$dlng = 2 * asin(sin($distance / (2 * $this->EARTH_RADIUS) / cos(deg2rad($lat))));
		$dlng = rad2deg($dlng);
		$dlat = $distance / $this->EARTH_RADIUS;
		$dlat = rad2deg($dlat);
		return [
			['lat' => $lat + $dlat, 'lng' => $lng + $dlng],//東北
			['lat' => $lat + $dlat, 'lng' => $lng - $dlng],//西北
			['lat' => $lat - $dlat, 'lng' => $lng - $dlng],//西南
			['lat' => $lat - $dlat, 'lng' => $lng + $dlng],//東南
		];
	}
	// 用地址找經緯度
	public function address_geo($address)
	{	
		$address = urlencode($address);
		$url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false&key='.$this->gmap_key;
		$curl = curl_init();
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0
		);
		curl_setopt_array($curl, $options);
		$fileContents = curl_exec($curl);
		curl_close($curl);
		$rs = json_decode($fileContents, true);
		if ($rs['status'] === 'OK') {
			$location = $rs['results'][0]['geometry']['location'];
			return $location;
			// $lat = $location['lat'];
			// // $lng = $location['lng'];
		}
	}
	/**
	 * [distance 起終點計算距離與時間]
	 * @param  [type] $start [description]
	 * @param  [type] $end   [description]
	 * @return [type]        [description]
	 */
	public function distance($start, $end)
	{
		/*
		https://maps.googleapis.com/maps/api/distancematrix/outputFormat?parameters
		units=metric 公里&里 算距離
		origins=起點 經緯度(不能有空格)
		destinations=終點 經緯度(不能有空格)
		 */
		$get = array();
		$get[] = 'units=metric';
		$get[] = 'origins='.$start['lat'].','.$start['lng'];
		$get[] = 'destinations='.$end['lat'].','.$end['lng'];
		$get[] = 'key='.$this->gmap_key;
		
		$url = 'https://maps.googleapis.com/maps/api/distancematrix/json?'.implode('&', $get);
		$curl = curl_init();
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HEADER => 0
		);
		curl_setopt_array($curl, $options);
		$tmpInfo = curl_exec($curl);
		curl_close($curl);
		$rs = json_decode($tmpInfo, true);
		return array(
			'distance' => $rs['rows'][0]['elements'][0]['distance']['value'],
			'duration' => $rs['rows'][0]['elements'][0]['duration']['value']
		);
	}
}
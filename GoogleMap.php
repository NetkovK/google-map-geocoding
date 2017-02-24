<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 24.02.2017
 * Time: 9:25
 */

namespace Netkovk\GoogleMapGeocoding;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class GoogleMap
{

    const STATUS_OK = 'OK';
    const STATUS_ZERO_RESULTS = 'ZERO_RESULTS';
    const STATUS_OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT';
    const STATUS_REQUEST_DENIED = 'REQUEST_DENIED';
    const STATUS_INVALID_REQUEST = 'INVALID_REQUEST';
    const STATUS_UNKNOWN_ERROR = 'UNKNOWN_ERROR';


    private $baseUri = 'https://maps.googleapis.com/maps/api/geocode/json';
    private $client;
    private $params;

    public function __construct($config)
    {
        $this->config = $config;

        $this->params = [
            'key'=>$this->config['key'],
            'language'=>$this->config['language']
        ];
        $this->client =  new Client();
    }

    public function geocoding($params){
        if(isset($params['address'])){
            $response = $this->getAddress($params['address']);
        } elseif(isset($params['latitude']) && isset($params['longitude'])){
            $response = $this->getCoordinates($params['latitude'], $params['longitude']);
        } else {
            abort('422', '');
        }

        return $response;
    }

    public function getAddress($address){
        $this->params['address'] = $address;
        if($cache = $this->getCache($this->params['address'])) return $cache;
        $response = $this->sendRequest();
        return $this->parseResponse($response, 'parseLocation', $this->params['address']);
    }
    public function getCoordinates($lat, $lng){
        $this->params['latlng'] = $lat .',' . $lng;
        if($cache = $this->getCache($this->params['latlng'])) return $cache;
        $response = $this->sendRequest();
        return $this->parseResponse($response, 'parseAddress', $this->params['latlng']);
    }

    private function sendRequest(){
        $response = $this->client->request('GET', $this->baseUri, [
            'query' => $this->params
        ]);

        return json_decode($response->getBody());
    }

    private function parseResponse($json, $method, $key){
        $response = [
            'status' => $json->status,
            'result' => $this->{$method}($json)
        ];
        $this->setCache($key, $response);
        return $response;
    }
    private function parseLocation($json){
        $location = [];
        foreach ($json->results as $result){
            $location[] = [
                'lat' => $result->geometry->location->lat,
                'lng' => $result->geometry->location->lng,
            ];
        }
        return $location;
    }
    private function parseAddress($json){
        $addresses = [];
        foreach ($json->results as $result){
            $addresses[] = $result->formatted_address;
        }
        return $addresses;
    }

    private function getCache($str){
        $key = md5($str);
        if(!Cache::has($key)) return false;
        return unserialize(Cache::get($key));
    }

    private function setCache($key, $value){
        $key = md5($key);
        Cache::add($key, serialize($value), 525600);
    }

}
<?php

namespace Boyo\LinkMobility;

use Boyo\LinkMobility\Exceptions\CouldNotSendMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LinkMobilitySender
{
    private $service_id;

    private $api_key;

    private $api_secret;

	private $log = true;
	
	private $log_channel = 'stack';
	
	private $send = false;
	
	private $url_production = 'https://api.msghub.cloud/send';

	private $url_testing = 'https://api-test.msghub.cloud/send';
	
	private $headers = [
		'Accept' => 'application/json',
		'Content-Type' => 'application/json',
        'Expect' => '',
	];
	
	// construct
	public function __construct() {
		
		// settings
        $this->api_key = config('services.link-mobility.api_key');
        $this->api_secret = config('services.link-mobility.api_secret');
        $this->service_id = config('services.link-mobility.service_id');
		$this->log = config('services.link-mobility.log');
		$this->send = config('services.link-mobility.send');
		$this->log_channel = config('services.link-mobility.log_channel');
	}
	
	public function forceSend(LinkMobilityMessage $message) {
		
		$this->send = true;
		
		$this->send($message);
		
		return $this;
		
	}
	
	// send email
	public function send(LinkMobilityMessage $message) {
		
		try {
			//prepare http client
			$request = $message->getMessage();
			$request['service_id'] = $this->service_id;
            $signature = hash_hmac('sha512', json_encode($request), $this->api_secret);
            $this->headers['x-api-key'] = $this->api_key;
            $this->headers['x-api-sign'] = $signature;

            $call_id = Str::random(10);
			if($this->log) {
                Log::channel($this->log_channel)->info("LinkMobility message with call_id {$call_id}",$request);
			}
			
			if($this->send) {

                // setup Http client
                $apiUrl = app()->env == 'production' ? $this->url_production : $this->url_testing;
                $response = Http::withHeaders($this->headers)->post($apiUrl, $request)->json();

                if($this->log) {
                    Log::channel($this->log_channel)->info("LinkMobility message with call_id {$call_id}",$response ?? []);
                }

                if (!isset($response['meta']) || $response['meta']['code'] != 200) {
                    throw CouldNotSendMessage::responseCodesErrors($call_id);
                }
			}
			
		} catch(CouldNotSendMessage $e) {

			throw $e;

		} catch(\Exception $e) {

            Log::channel($this->log_channel)->info('Could not send LinkMobility message ('.$e->getMessage().')');

            throw CouldNotSendMessage::unknownError();

        }

		
	}
	
	
}

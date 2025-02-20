<?php

namespace Boyo\LinkMobility;

use Illuminate\Notifications\Notification;
use Boyo\LinkMobility\Exceptions\CouldNotSendMessage;
use Bulglish;

class LinkMobilityMessage
{
	/**
     * The phone number to send the message to
     *
     * @var string
     */
    public $to = '';
    
    /**
     * The delivery channel - default is sms
     *
     * @var string
     */
    public $channel = 'sms';
    
    /**
     * The delivery channels possible - 'sms', 'viber' or 'viber-sms'. Default is 'sms'
     *
     * @var string
     */
    private $allowedChannels = ['sms','viber','viber-sms'];
    
    /**
     * The message content for SMS.
     *
     * @var string
     */
    public $messageSMS = '';
    
    /**
     * The message content for Viber.
     *
     * @var string
     */
    public $messageViber = '';
    
    /**
     * The image content for Viber.
     *
     * @var string
     */
    public $imageViber = '';
    
    /**
     * The button url for Viber.
     *
     * @var string
     */
    public $buttonViber = '';
    
    /**
     * The button text for Viber.
     *
     * @var string
     */
    public $buttonTextViber = '';
    
    /**
     * The message unique id
     *
     * @var string
     */
    public $id = '';
    
    /**
     * The prefix - overwrites global setting
     *
     * @var string
     */
    public $prefix = false;

    /**
     * Limit length to 1 sms
     *
     * @var string
     */
    public $limitLength = false;

    /**
     * Bulglish config
     *
     * @var string
     */
    public $bulglish = false;
    
    /**
     * @param  string $id
     */
    public function __construct($id='')
    {
        $this->id = $id;
    }
    
    /**
     * Use this method to build the json request
     *
     *
     * @return $this
     */
    public function getMessage() {
	    
	    $this->bulglish = !empty(config('services.link-mobility.bulglish'));

		$this->limitLength = empty(config('services.link-mobility.allow_multiple'));
		
		if ($this->prefix===false) {
			$this->prefix = config('services.link-mobility.prefix');
		}
	    	    
		if (empty($this->to)) { 
            throw CouldNotSendMessage::telNotProvided();				
		}
		
		$json = [
			'msisdn' => $this->to,
		];

		if ($this->limitLength) {
		    $json['max_sms_size'] = 1;
        }

	    switch($this->channel) {
		    case 'sms':
				
				$sms_message_processed = $this->getSmsText();

				$json['sc'] = config('services.link-mobility.sc_sms');
				$json['text'] = $sms_message_processed;
		    	
				break;
			case 'viber':
			case 'viber-sms':			
				
				if (empty($this->messageViber)) {
				    throw CouldNotSendMessage::contentNotProvided();				
				}

                $json['sc'] = config('services.link-mobility.sc_viber');
                $json['text'] = $this->messageViber;
		    	
			    if ($this->channel == 'viber-sms') {
				    
				    $sms_message_processed = $this->getSmsText();
				    
				    $json['fallback']['sms'] = $sms_message_processed;
				    
			    }
                
                if (!empty($this->imageViber)) {
                    $json['ImageUrl'] = $this->imageViber;
                }
                
                if (!empty($this->buttonViber)) {
                    $json['ButtonUrl'] = $this->buttonViber;
                    $json['ButtonName'] = $this->buttonTextViber;
                }
                
				break;
	    }
	    
	    
	    return $json;
	    
    }
    
    /**
     * Use this method to set custom content in SMS messages
     *
     *
     * @return $this
     */
    public function sms(string $text = '') {
	    
	    $this->messageSMS = $text;
	    
	    return $this;
	    
    }
    
    /**
     * Use this method to set custom content in Viber messages
     *
     *
     * @return $this
     */
    public function viber(string $text = '', ?string $image = null, ?string $button = null, ?string $button_text = '') {
	    
	    $this->messageViber = $text;
        
        if ($image) {
            $this->imageViber = $image;
        }
        
        if ($button) {
            $this->buttonViber = $button;
            $this->buttonTextViber = $button_text;
        }
	    
	    return $this;
	    
    }
    
    /**
     * Set the phone number of the recipient
     *
     * @param  string $to
     *
     * @return $this
     */
    public function to(string $to) {
	    
	    $this->to = $to;
	    
	    return $this;
	    
    }
    
    /**
     * Set the delivery channel 
     *
     * @param  string $channel
     *
     * @return $this
     */
    public function channel(string $channel) {
    	
    	if (!in_array($channel, $this->allowedChannels)) {
		    throw CouldNotSendMessage::unknownChannel();
	    }
	    
        $this->channel = $channel;
        
        return $this;
        
    }
    
    /**
     * Get the processed SMS text
     *
     *
     * @return string $text
     */
    public function getSmsText() {
	    	    		    
    	if (empty($this->messageSMS)) { 
		    throw CouldNotSendMessage::contentNotProvided();				
		}
	    
	    $sms_message_processed = ( $this->prefix ?: '' ) . $this->messageSMS;
		    	
    	if ($this->bulglish) {
			$sms_message_processed = Bulglish::toLatin( $sms_message_processed );
		}
		
		if ($this->limitLength) {
			$sms_message_processed = $this->cutText( $sms_message_processed );
		}

		return $sms_message_processed;
		
    }
    
    /**
     * Cut text to limit of 160 characters 
     *
     * @param  string $text
     *
     * @return $text
     */
	private function cutText($text) {
	        
		if (mb_strlen($text) > 160) {
		
			$text = mb_substr($text, 0, 156);
			$text .= '...';    
			
		}
		
        return $text;
	}
}
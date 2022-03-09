<?php

namespace Boyo\LinkMobility\Channels;

use Illuminate\Notifications\Notification;
use Boyo\LinkMobility\LinkMobilitySender;
use Boyo\LinkMobility\LinkMobilityMessage;

class LinkMobilityViberSmsChannel
{
	
    protected $client;
    
    public function __construct()
    {
        
    }
    
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification) : void
    {
        
        $message = $notification->toSms($notifiable);
        
        if (!$message instanceof LinkMobilityMessage) {
	        throw new \Exception('No message provided');
	    }
	    
	    // run the build functions
	    $message->build();
	    
        // force viber-SMS sending on this channel 
        $message->channel('viber-sms');
        
        $client = new LinkMobilitySender();
        
        $client->send($message);
        
    }
    
    
}
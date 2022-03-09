<?php

namespace Boyo\LinkMobility\Channels;

use Illuminate\Notifications\Notification;
use Boyo\LinkMobility\LinkMobilitySender;
use Boyo\LinkMobility\LinkMobilityMessage;

class LinkMobilitySmsChannel
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
	    
        // force SMS sending on this channel 
        $message->channel('sms');
        
        $client = new LinkMobilitySender();
        
        $client->send($message);
        
    }
    
    
}
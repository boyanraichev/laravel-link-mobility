<?php

namespace Boyo\LinkMobility;

use Boyo\LinkMobility\Exceptions\CouldNotSendMessage;
use Bulglish;

class LinkMobilityMessage
{
    // The phone number to send the message to
    public $to = '';

    // The delivery channel - 'sms', 'viber' or 'viber-sms'. Default is 'sms'
    public $channel = 'sms';

    private $allowedChannels = ['sms', 'viber', 'viber-sms'];

    // The message content for SMS
    public $messageSMS = '';

    // The message content for Viber (free-form)
    public $messageViber = '';

    // The image url for Viber
    public $imageViber = '';

    // The button url for Viber
    public $buttonViber = '';

    // The button text for Viber
    public $buttonTextViber = '';

    // The Viber template ID for transactional template sending
    public ?string $viberTemplateId = null;

    // The language code for the Viber template
    public string $viberTemplateLang = 'bg';

    // The variable values to substitute in the Viber template
    public array $viberTemplateParams = [];

    // The message unique id
    public $id = '';

    // The prefix - overwrites global setting
    public $prefix = false;

    // Limit length to 1 sms
    public $limitLength = false;
    
    // convert cyrillic to latin
    public $bulglish = false;

    // Set message priority
    public $priority = null;

    public function __construct($id = '')
    {
        $this->id = $id;
    }

    // Build and return the json request payload
    public function getMessage()
    {
        $this->bulglish = !empty(config('services.link-mobility.bulglish'));
        $this->limitLength = empty(config('services.link-mobility.allow_multiple'));

        if ($this->prefix === false) {
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

        if ($this->priority) {
            $json['priority'] = $this->priority;
        }

        switch ($this->channel) {
            case 'sms':
                $json['sc'] = config('services.link-mobility.sc_sms');
                $json['text'] = $this->getSmsText();
                break;

            case 'viber':
            case 'viber-sms':
                $json['sc'] = config('services.link-mobility.sc_viber');

                if ($this->viberTemplateId) {
                    $json['templateId'] = $this->viberTemplateId;
                    $json['templateLang'] = $this->viberTemplateLang;
                    if (!empty($this->viberTemplateParams)) {
                        $json['templateParams'] = $this->viberTemplateParams;
                    }
                } else {
                    if (empty($this->messageViber)) {
                        throw CouldNotSendMessage::contentNotProvided();
                    }
                    $json['text'] = $this->messageViber;

                    if (!empty($this->imageViber)) {
                        $json['ImageUrl'] = $this->imageViber;
                    }

                    if (!empty($this->buttonViber)) {
                        $json['ButtonUrl'] = $this->buttonViber;
                        $json['ButtonName'] = $this->buttonTextViber;
                    }
                }

                if ($this->channel == 'viber-sms') {
                    $json['fallback']['sms'] = $this->getSmsText();
                }

                break;
        }

        return $json;
    }

    // Set custom content in SMS messages
    public function sms(string $text = '')
    {
        $this->messageSMS = $text;

        return $this;
    }

    // Set custom content in Viber free-form messages
    public function viber(string $text = '', ?string $image = null, ?string $button = null, ?string $button_text = '')
    {
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

    // Set the Viber template ID and its variable values for transactional template sending
    public function viberTemplate(string $id, string $lang = 'bg', array $params = [])
    {
        $this->viberTemplateId = $id;
        $this->viberTemplateLang = $lang;
        $this->viberTemplateParams = $params;

        return $this;
    }

    // Set the phone number of the recipient
    public function to(string $to)
    {
        $this->to = $to;

        return $this;
    }

    // Set the delivery channel
    public function channel(string $channel)
    {
        if (!in_array($channel, $this->allowedChannels)) {
            throw CouldNotSendMessage::unknownChannel();
        }

        $this->channel = $channel;

        return $this;
    }

    public function priority(string $priority)
    {
        $this->priority = $priority;
    }

    // Get the processed SMS text (applies prefix, Bulglish conversion, length limit)
    public function getSmsText()
    {
        if (empty($this->messageSMS)) {
            throw CouldNotSendMessage::contentNotProvided();
        }

        $sms_message_processed = ($this->prefix ?: '') . $this->messageSMS;

        if ($this->bulglish) {
            $sms_message_processed = Bulglish::toLatin($sms_message_processed);
        }

        if ($this->limitLength) {
            $sms_message_processed = $this->cutText($sms_message_processed);
        }

        return $sms_message_processed;
    }

    private function cutText($text)
    {
        if (mb_strlen($text) > 160) {
            $text = mb_substr($text, 0, 156);
            $text .= '...';
        }

        return $text;
    }
}

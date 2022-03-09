<?php
namespace Boyo\LinkMobility\Commands;

use Illuminate\Console\Command;
use Boyo\LinkMobility\LinkMobilitySender;
use Boyo\LinkMobility\LinkMobilityMessage;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'link-mobility:test {phone : Phone to send to} {--channel=sms} {--message=test} {--promo}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test message';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
	    
	    try {
		    
		    $phone = $this->argument('phone');
		    
		    $channel = $this->option('channel');
		    
		    $content = $this->option('message');
            
            $promo = $this->option('promo');
		    
		    $message = new LinkMobilityMessage(time());
		    $message->to($phone)->sms($content);
            
            if ($promo) {
                $message->viber($content, 'https://upload.wikimedia.org/wikipedia/en/9/95/Test_image.jpg', 'https://www.wikipedia.org', 'Wikipedia');   
            } else {
                $message->viber($content);
            }
		    
		    if (!empty($channel)) {
			    $message->channel($channel);
		    }

		    $client = new LinkMobilitySender();
		    
		    $client->forceSend($message);
		    
	        $this->info('Message send');
			
		} catch(\Exception $e) {
			
			$this->error($e->getMessage());
			
		}
    }
}
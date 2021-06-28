<?php
namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class RegistrasiEmail extends Mailable{
 
    use Queueable, SerializesModels;
    
    public $full_name;
    public $link_activation;

    public function __construct($full_name, $link_activation)
    {
        $this->full_name = $full_name;
        $this->link_activation = $link_activation;
    }
 
    //build the message.
    public function build() {
        return $this->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'))
                ->subject('Registration')
                ->view('emails.registrasi');
    }
}
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $password;


    /**
     * Create a new message instance.
     */
    public function __construct($name, $email, $password)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Get the message envelope.
     */
    
    public function build()
    {
        return $this->view('emails.welcome')
                    ->with([
                        'name' => $this->name,
                        'email' => $this->email,
                        'password' => $this->password,
                    ])
                    ->subject('Bem vindo');
    }

}

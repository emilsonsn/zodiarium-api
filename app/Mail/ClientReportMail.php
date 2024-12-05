<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $customerName;
    public $reports;
    public function __construct($customerName, $reports)
    {
        $this->customerName = $customerName;
        $this->reports = $reports;
    }
    /**
     * Get the message envelope.
     */
    
     public function build()
     {
         return $this->view('emails.client-reports')
                     ->with([
                         'customerName' => $this->customerName,
                         'reports' => $this->reports,
                     ])
                     ->subject('Seus relatórios ficaram prontos!');
     }
 
}

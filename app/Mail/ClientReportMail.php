<?php

namespace App\Mail;

use App\Models\Setting;
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
        $setting = Setting::first();
        $logoUrl = $setting->logo ?? 'https://zodiarium.com:3001/storage/settings/I8lGS5qrRdPP2QnKpQFy5PyYI717MElMh9NijQhA.png';
         return $this->view('emails.client-reports')
                     ->with([
                         'customerName' => $this->customerName,
                         'reports' => $this->reports,
                         'logoUrl' => $logoUrl
                     ])
                     ->subject('Seus relatórios ficaram prontos!');
     }
 
}

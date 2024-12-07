<?php

namespace App\Traits;

use App\Helpers\GlobalSettingsHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Brevo\Client\Configuration;

trait BrevoTrait
{
    protected $brevoApiKey;

    public function prepareBrevoApiCredencials()
    {
        $this->brevoApiKey = GlobalSettingsHelper::get('brevo_api_key');
    }

    public function addContactInList($listId, $contact)
    {
        try {
            $this->prepareBrevoApiCredencials();

            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->brevoApiKey);
            $contactsApi = new \Brevo\Client\Api\ContactsApi(null, $config);

            $fullName = explode(' ', $contact['name']);
            $createContact = new \Brevo\Client\Model\CreateContact([
                'email' => $contact['email'],
                'listIds' => [$listId],
                'attributes' => [
                    'FIRSTNAME' => $fullName[0],
                    'LASTNAME' => $fullName[1] ?? '',
                    'SMS' => $contact['phone']
                ]
            ]);

            $contactsApi->createContact($createContact);
        } catch (Exception $error) {
            Log::error($error->getMessage());
        }
    }
}

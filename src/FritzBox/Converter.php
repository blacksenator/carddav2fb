<?php

namespace Andig\FritzBox;

/**
 * The class provides the functions to convert vCards into contacts according to
 * FRITZ!Box XML phonebook format
 *
 * @see https://avm.de/fileadmin/user_upload/Global/Service/Schnittstellen/x_contactSCPD.pdf#page=19
 *
 * @author Andreas Götz
 * @author Volker Püschel <knuffy@anasco.de>
 * @license MIT
**/

use Andig;
use \SimpleXMLElement;

class Converter
{
    const INTERNAL_PREFIX = '**';           // indicates an internal phone numer
    const PHONE_TYPE = 'other';             // appears as 'sonstige' in German

    private $config;

    /** @var SimpleXMLElement */
    private $contact;

    private $phoneSort = [];
    private $numberConversion = false;
    private $vipCategories;
    private $emailTypes = [];

    public function __construct(array $config)
    {
        $this->config = $config['conversions'];
        $this->phoneSort = $this->getPhoneTypesSortOrder();
        !$this->config['phoneReplaceCharacters'] ?: $this->numberConversion = true;
        $this->vipCategories = $this->config['vip'] ?? [];
        $this->emailTypes = $this->config['emailTypes'] ?? [];
    }

    /**
     * Convert a vCard into a FritzBox XML contact. All conversion steps operate
     * on $this->contact. If the vCard contains more than nine valid telephone
     * numbers, the contact will be divided up, since the FRITZ!Box only allows
     * a maximum of nine telephone numbers per contact. vCards without a phone
     * number will be ignored
     *
     * @param mixed $card
     * @return SimpleXMLElement[]
     */
    public function convert($card): array
    {
        $contactNumbers  = $this->getPhoneNumbers($card);   // get array of prequalified phone numbers
        $adresses = $this->getEmailAdresses($card);     // get array of prequalified email adresses
        $uid = (string)$card->UID;

        $contacts = [];
        if (count($contactNumbers) > 9) {
            error_log(sprintf('Contact (UID %s) with >9 phone numbers will be splited', $uid));
        } elseif (count($contactNumbers) == 0) {
            error_log(sprintf('Contact (UID %s) without phone numbers will be skipped', $uid));
        }

        foreach (array_chunk($contactNumbers, 9) as $numbers) {
            $this->contact = new SimpleXMLElement('<contact />');
            $this->contact->addChild('carddav_uid', $uid);  // reference for image upload

            $this->addVip($card);
            $this->addPhone($numbers);

            // add eMail
            !$adresses ?: $this->addEmail($adresses);

            // add Person
            $person = $this->contact->addChild('person');
            $realName = htmlspecialchars($this->getProperty($card, 'realName'));
            $person->addChild('realName', $realName);

            // add photo
            if (isset($card->PHOTO) && isset($card->IMAGEURL)) {
                $person->addChild('imageURL', (string)$card->IMAGEURL);
            }

            $contacts[] = $this->contact;
        }

        return $contacts;
    }

    /**
     * convert a phone number if conversions (phoneReplaceCharacters) are set
     * SIP or internal number are skiped to avoid unwanted conversions
     *
     * @param string $number
     * @return string $number
     */
    public function convertPhoneNumber($number)
    {
        if ($this->isSipNumber($number) || $this->isInternalNumber($number)) {
            return $number;
        }
        $number = str_replace("\xc2\xa0", "\x20", $number);
        $number = strtr($number, $this->config['phoneReplaceCharacters']);
        $number = trim(preg_replace('/\s+/', ' ', $number));

        return $number;
    }

    /**
     * returns if phone number is a SIP number
     *
     * @param string $number
     * @return bool
     */
    private function isSipNumber(string $number)
    {
        return !filter_var($number, FILTER_VALIDATE_EMAIL) ? false : true;
    }

    /**
     * returns if phone number is an internal number
     *
     * @param string $number
     * @return bool
     */
    private function isInternalNumber(string $number)
    {
        return substr($number, 0, 2) == self::INTERNAL_PREFIX ? true : false;
    }

    /**
     * Return a simple array depending on the order of phonetype conversions
     * whose order should determine the sorting of the telephone numbers
     *
     * @return array
     */
    private function getPhoneTypesSortOrder(): array
    {
        $seqArr = array_values(array_map('strtolower', $this->config['phoneTypes']));
        $seqArr[] = self::PHONE_TYPE;   // ensures that the default value is included last
        return array_unique($seqArr);                       // deletes duplicates
    }

    /**
     * add VIP node
     *
     * @param mixed $card
     * @return void
     */
    private function addVip($card)
    {
        if (Andig\filtersMatch($card, $this->vipCategories)) {
            $this->contact->addChild('category', '1');
        }
    }

    /**
     * add phone nodes
     *
     * @param array $numbers
     * @return void
     */
    private function addPhone(array $numbers)
    {
        $telephony = $this->contact->addChild('telephony');

        foreach ($numbers as $idx => $number) {
            $phone = $telephony->addChild('number', $number['number']);
            $phone->addAttribute('id', (string)$idx);

            foreach (['type', 'quickdial', 'vanity'] as $attribute) {
                if (isset($number[$attribute])) {
                    $phone->addAttribute($attribute, $number[$attribute]);
                }
            }
        }
    }

    /**
     * add emails nodes
     *
     * @param array $addresses
     * @return void
     */
    private function addEmail(array $addresses)
    {
        $services = $this->contact->addChild('services');

        foreach ($addresses as $idx => $address) {
            $email = $services->addChild('email', htmlspecialchars($address['email']));
            $email->addAttribute('id', (string)$idx);

            if (isset($address['classifier'])) {
                $email->addAttribute('classifier', $address['classifier']);
            }
        }
    }

    /**
     * Returns an array of prequalified phone numbers. This is neccesseary to
     * handle the maximum of nine phone numbers per FRITZ!Box phonebook contacts
     *
     * @param mixed $card
     * @return array
     */
    private function getPhoneNumbers($card): array
    {
        if (!isset($card->TEL)) {
            return [];
        }
        $phoneNumbers = [];
        $phoneTypes = $this->config['phoneTypes'] ?? [];
        foreach ($card->TEL as $key => $number) {
            // format number
            if ($this->numberConversion) {
                $number = $this->convertPhoneNumber($number);
            }
            // get types
            $telTypes = strtoupper($card->TEL[$key]['TYPE'] ?? '');
            $type = self::PHONE_TYPE;                           // set default
            foreach ($phoneTypes as $phoneType => $value) {
                if (strpos($telTypes, strtoupper($phoneType)) !== false) {
                    $type = strtolower((string)$value);
                    break;
                }
            }
            if (strpos($telTypes, 'FAX') !== false) {
                $type = 'fax_work';
            }
            $phoneNumbers[] = [
                'type'   => $type,
                'number' => (string)$number,
            ];
        }
        // sort phone numbers
        if (count($phoneNumbers) > 1) {
            $phoneNumbers = $this->sortPhoneNumbers($phoneNumbers);
        }

        return $phoneNumbers;
    }

    /**
     * Sorting of the phone numbers depending on the order of the conversion
     * table
     *
     * @param array $phonenumbers
     * @return array $phonenumbers
     */
    private function sortPhoneNumbers(array $phoneNumbers)
    {
        usort($phoneNumbers, function ($a, $b) {
            $idx1 = array_search($a['type'], $this->phoneSort, true);
            $idx2 = array_search($b['type'], $this->phoneSort, true);
            if ($idx1 == $idx2) {
                return ($a['number'] > $b['number']) ? 1 : -1;
            } else {
                return ($idx1 > $idx2) ? 1 : -1;
            }
        });

        return $phoneNumbers;
    }

    /**
     * Return an array of prequalified email adresses. There is no limitation
     * for the amount of email adresses in FRITZ!Box phonebook contacts.
     *
     * @param mixed $card
     * @return array
     */
    private function getEmailAdresses($card): array
    {
        if (!isset($card->EMAIL)) {
            return [];
        }
        $mailAdresses = [];
        foreach ($card->EMAIL as $key => $address) {
            $mailAddress = [
                'id'    => count($mailAdresses),
                'email' => (string)$address,
            ];
            $vCardMailTypes = strtoupper($card->EMAIL[$key]->parameters['TYPE'] ?? '');
            foreach ($this->emailTypes as $emailType => $value) {
                if (strpos($vCardMailTypes, strtoupper($emailType)) !== false) {
                    $mailAddress['classifier'] = strtolower($value);
                    break;
                }
            }
            $mailAdresses[] = $mailAddress;
        }

        return $mailAdresses;
    }

    /**
     * Return class property with applied conversion rules
     *
     * @param mixed $card
     * @param string $property
     * @return string
     */
    private function getProperty($card, string $property): string
    {
        if (null === ($rules = @$this->config[$property])) {
            throw new \Exception("Missing conversion definition in config for [$property]");
        }

        foreach ($rules as $rule) {
            // parse rule into tokens
            $token_format = '/{([^}]+)}/';
            preg_match_all($token_format, $rule, $tokens);

            if (!$tokens) {
                throw new \Exception("Invalid conversion definition for `$property`");
            }

            $replacements = [];

            // check card for tokens
            foreach ($tokens[1] as $idx => $token) {
                $param = strtoupper($token);
                if (isset($card->$param) && $card->$param) {
                    $replacements[$token] = (string)$card->$param;
                }
            }

            // check if all tokens found
            if (count($replacements) !== count($tokens[0])) {
                continue;
            }

            // replace
            return preg_replace_callback($token_format, function ($match) use ($replacements) {
                $token = $match[1];
                return $replacements[$token];
            }, $rule);
        }

        error_log("No data for conversion `$property`");
        return '';
    }
}

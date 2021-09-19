<?php

namespace blacksenator\jFritz;

/**
 * This class provides functionalities to convert
 * FRITZ!Box phonebook into the jFritz format:
 *
 *   <?xml version="1.0" encoding="utf-8"?>
 *   <phonebook>
 *       <comment>Phonebook for JFritz v0.7.6</comment>
 *       <entry private="false">
 *           <name>
 *               <firstname/>
 *               <lastname/>
 *           </name>
 *           <company>AMCE Inc.</company>
 *           <phonenumbers>
 *               <number type="business">number</number>
 *               <number type="fax">number</number>
 *           </phonenumbers>
 *       </entry>
 *       <entry private="false">
 *           <name>
 *               <firstname>name</firstname>
 *               <lastname>name</lastname>
 *           </name>
 *           <company/>
 *           <phonenumbers>
 *               <number type="home">number</number>
 *               <number type="mobile">number</number>
 *           </phonenumbers>
 *       </entry>
 *   <phonebook/>
 *
 * @see https://jfritz.org
 * Copyright (c) 2021 Volker Püschel
 * @license MIT
 */

use blacksenator\ConvertTrait;
use \SimpleXMLElement;

class jfritz
{
    use ConvertTrait;

    const VERSION = 'v0.7.6',
        TYPES = [
            'work'     => 'business',
            'fax_work' => 'fax',
            'home'     => 'home',
            'mobile'   => 'mobile',
        ];

    /**
     * converts phone types from FRITZ!Box
     * to jFritz types
     *
     * @param string $phoneType
     * @return string
     */
    private function getPhoneType($phoneType)
    {
        return strtr($phoneType, self::TYPES);
    }

    /**
     * delivers a jFritz phonebook
     *
     * @param SimpleXMLElement $xmlPhonebook FRITZ!Box phonebook
     * @return SimpleXMLElement $phonebook jFritz phonebook
     */
    public function getjFritzPhonebook(SimpleXMLElement $xmlPhonebook)
    {
        $phonebook = new simpleXMLElement('<?xml version="1.0" encoding="utf-8"?><phonebook />');
        $phonebook->addChild('comment', 'Phonebook for JFritz ' . self::VERSION);
        foreach ($xmlPhonebook->phonebook->contact as $contact) {
            $entry = $phonebook->addChild('entry');
            $entry->addAttribute('private', 'false');

            $name = $entry->addChild('name');
            $parts = $this->getNameParts($contact->person->realName);
            $name->addChild('firstname', $parts['firstname']);
            $name->addChild('lastname', $parts['lastname']);
            $entry->addChild('company', $parts['company']);

            $phonenumbers = $entry->addChild('phonenumbers');
            foreach ($contact->telephony->number as $number) {
                $numbers = $phonenumbers->addChild('number', (string)$number);
                $type = $this->getPhoneType((string)$number['type']);
                $numbers->addAttribute('type', $type);
            }
        }

        return $phonebook;
    }
}

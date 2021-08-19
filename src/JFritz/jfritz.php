<?php

namespace blacksenator\jFritz;

/**
 * This class provides functionalities to convert
 * FRITZ!Box phonebook to jFritz format
 *
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
     *
     */
    private function getPhoneType($phoneType)
    {
        return strtr($phoneType, self::TYPES);
    }

    /**
     * delivers a jFritz phonebook
     *
     * @param SimpleXMLElement $xmlPhonebook phonebook in FRITZ!Box format
     * @return SimpleXMLElement $phonebook phonebook in jFritz format
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

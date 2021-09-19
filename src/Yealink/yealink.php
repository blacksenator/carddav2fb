<?php

namespace blacksenator\yealink;

/**
 * This class provides functionalities to convert
 * FRITZ!Box phonebook into the simple Yealink format:
 *
 *   <?xml version="1.0" encoding="utf-8"?>
 *   <YealinkIPPhoneBook>
 *       <DirectoryEntry>
 *           <Name>contact name</Name>
 *           <Telephone>number</Telephone>
 *       </DirectoryEntry>
 *   </YealinkIPPhoneBook>
 *
 * @see https://support.yeastar.com/hc/en-us/articles/216881758-How-to-Configure-Remote-Phone-Book
 * Copyright (c) 2021 Volker Püschel
 * @license MIT
 */

use \SimpleXMLElement;

class yealink
{
    /**
     * delivers a Yealink phonebook
     *
     * @param SimpleXMLElement $xmlPhonebook phonebook in FRITZ!Box format
     * @return SimpleXMLElement $phonebook phonebook in jFritz format
     */
    public function getYealinkPhonebook(SimpleXMLElement $xmlPhonebook)
    {
        $phonebook = new simpleXMLElement('<?xml version="1.0" encoding="utf-8"?><YealinkIPPhoneBook />');
        foreach ($xmlPhonebook->phonebook->contact as $contact) {
            $entry = $phonebook->addChild('DirectoryEntry');
            $entry->addChild('Name', htmlspecialchars($contact->person->realName));
            foreach ($contact->telephony->number as $number) {
                $entry->addChild('Telephone', (string)$number);
            }
        }

        return $phonebook;
    }
}

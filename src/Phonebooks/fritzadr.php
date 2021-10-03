<?php

namespace blacksenator\FritzAdr;

/**
 * This class provides a functionality to extract fax numbers
 * and provide them in a simple array with 19 or 21 fields
 * - to pass them to FritzAdr compliant dBASE file (fritzadr.dbf).
 * The DB analysis of several FritzAdr.dbf files has surprisingly
 * shown both variants. Ultimately, the 21er works for me.
 *
 * Copyright (c) 2021 Volker Püschel
 * @license MIT
 */

use blacksenator\ConvertTrait;
use blacksenator\fritzdbf\fritzdbf;
use \SimpleXMLElement;

class fritzadr
{
    use ConvertTrait;
    /**
     * delivers an structured adress array of fax numbers from a designated phone book
     *
     * @param SimpleXMLElement $xmlPhonebook phonebook in FRITZ!Box format
     * @return array fax numbers, names
     */
    public function getFAXcontacts(SimpleXMLElement $xmlPhonebook) : array
    {
        $i = -1;
        $adrRecords = [];
        foreach ($xmlPhonebook->phonebook->contact as $contact) {
            foreach ($contact->telephony->number as $number) {
                if ((string)$number['type'] == "fax_work") {
                    $i++;
                    $name = $contact->person->realName;
                    $faxnumber = (string)$number;
                    // dBase uses the DOS charset (Codepage 850); htmlspecialchars makes a '&amp;' from '&' must be reset here
                    $name = str_replace('&amp;', '&', iconv('UTF-8', 'CP850//TRANSLIT', $name));
                    $adrRecords[$i]['BEZCHNG'] = $name;                 // fullName in field 1
                    $parts = $this->getNameParts($name);
                    $adrRecords[$i]['FIRMA'] = $parts['company'];       // fullName in field 2
                    $adrRecords[$i]['NAME'] = $parts['lastname'];       // lastname in field 3
                    $adrRecords[$i]['VORNAME'] = $parts['firstname'];   // firstnme in field 4
                    $adrRecords[$i]['TELEFAX'] = $faxnumber;            // FAX number in field 10/11
                }
            }
        }

        return $adrRecords;
    }

    /**
     * get contact data array in dBase III format
     *
     * @param array $contacts
     * @return string
     */
    public function getdBaseData(array $contacts)
    {
        $fritzAdr = new fritzdbf();
        foreach ($contacts as $contact) {
            $fritzAdr->addRecord($contact);
        }

        return $fritzAdr->getDatabase();
    }
}

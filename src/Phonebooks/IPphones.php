<?php

namespace blacksenator\IPphones;

/**
 * This class provides generic functionalities to convert
 * FRITZ!Box phonebook into different IP phone XML formats
 * based on XSL Transformation (XSLT)
 *
 * You need to enable libxslt library!
 * php.ini: extension=php_xsl.dll
 *
 * @copyright(c) 2021 Volker Püschel
 * @license MIT
 */

use \SimpleXMLElement;
use \XSLTProcessor;

class ipphones
{
    private $xslFile;
    private $xslTransformer;
    private $validationErrors;

    const INTACT = <<<ORIGIN
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" indent="yes" encoding="utf-8" />
    <xsl:template match="*">
        <xsl:copy-of select="." />
        <xsl:apply-templates/>
    </xsl:template>
</xsl:stylesheet>
ORIGIN;

    public function __construct()
    {
        $this->xslTransformer = new XSLTProcessor();
    }

    /**
     * Validating the designated XSL file
     * If an error occurs, the default xsl is loaded
     * in order to then output the phone book unchanged
     *
     * @return array $xslErrors
     */

    private function validateXSL()
    {
        $xslErrors = [];
        libxml_use_internal_errors(true);
        $result = $this->xslTransformer->importStyleSheet($this->xslFile);
        if (!$result) {
            foreach (libxml_get_errors() as $error) {
                $xslErrors[$error->message];
            }
            $this->xslFile = simplexml_load_string(self::INTACT);
            $this->xslTransformer->importStyleSheet($this->xslFile);
        }
        libxml_use_internal_errors(false);

        return $xslErrors;
    }

    /**
     * get the array with errors
     *
     * @return array $this->validationErrors
     */
    public function getValidationResult()
    {
        return $this->validationErrors;
    }

    /**
     * setting the XSL file
     *
     * @param string $xslFile
     * @return void
     */
    public function setXSL($xslFile)
    {
        $this->xslFile = simplexml_load_file(dirname(__DIR__, 2) . '/lib/' . $xslFile);
        $this->validationErrors = $this->validateXSL();
    }

    /**
     * converting Fritz!Box XML phonebook
     * to IP phone XML phonebook
     *
     * @param SimpleXMLElement $fritzBoxXML
     * @return string|false XML
     */
    public function getIPPhonebook(SimpleXMLElement $fritzBoxXML)
    {
        return $this->xslTransformer->transformToXML($fritzBoxXML);
    }
}

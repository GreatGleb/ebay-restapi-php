<?php

namespace App\Helpers;

use App\Http\Controllers\API\ApiEbayController as Ebay;
use App\Imports\EbayImport;

class EbayData extends Ebay
{
    private function getAllCompatibilities() {
        $dirPath = storage_path('app/private');
        $file = $dirPath . '/ebay_models.xlsx';

        $compatibilities = (new EbayImport)->toArray($file)[0];
        return $compatibilities;
    }

    public static function addToXMLVariables($xml, $variables, $item, Ebay $ebay = null) {
        foreach ($variables as $key=>$variable) {
            if(!is_array($variable)) {
                $name = '$' . $key . '->' . $variable;
                $xml = str_replace($name, $$key->{$variable}, $xml);
            } else {
                foreach ($variable as $let) {
                    $name = '$' . $key . '->' . $let;
                    if(isset($$key->{$let})) {
                        $xml = str_replace($name, $$key->{$let}, $xml);
                    } else {
                        $xml = str_replace($name, '', $xml);
                    }
                }
            }
        }

        return $xml;
    }

    public static function arrayToXmlContent(array $data, \SimpleXMLElement $xml = null): string
    {
        if ($xml === null) {
            $xml = new \SimpleXMLElement('<root/>');
        }

        self::appendArrayToXml($data, $xml);

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = false;

        $innerXml = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $innerXml .= $dom->saveXML($child);
        }

        return $innerXml;
    }

    private static function appendArrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            self::appendValueToXml($key, $value, $xml);
        }
    }

    private static function appendValueToXml(string $key, $value, \SimpleXMLElement $xml): void
    {
        $type = self::detectType($value);

        if($type == 'primitive')
            self::handlePrimitive($key, $value, $xml);
        else if($type == 'assoc')
            self::handleAssoc($key, $value, $xml);
        else if($type == 'listOfAssoc')
            self::handleListOfAssoc($key, $value, $xml);
        else if($type == 'listOfPrimitive')
            self::handleListOfPrimitive($key, $value, $xml);
    }

    private static function detectType($value): string
    {
        if (!is_array($value)) return 'primitive';

        if (self::isAssoc($value)) return 'assoc';

        foreach ($value as $item) {
            if (is_array($item)) return 'listOfAssoc';
        }

        return 'listOfPrimitive';
    }

    private static function isAssoc(array $arr): bool {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private static function handlePrimitive(string $key, $value, \SimpleXMLElement $xml): void
    {
        $xml->addChild($key, self::sanitizeValue($value));
    }

    private static function handleAssoc(string $key, array $value, \SimpleXMLElement $xml): void
    {
        $child = $xml->addChild($key);
        self::appendArrayToXml($value, $child);
    }

    private static function handleListOfAssoc(string $key, array $value, \SimpleXMLElement $xml): void
    {
        if($key == 'CompatibilityList') {
            $child = $xml->addChild($key);
            foreach ($value as $item) {
                self::appendArrayToXml($item, $child);
            }
        } else {
            foreach ($value as $item) {
                $child = $xml->addChild($key);
                self::appendArrayToXml($item, $child);
            }
        }
    }

    private static function handleListOfPrimitive(string $key, array $value, \SimpleXMLElement $xml): void
    {
        foreach ($value as $item) {
            $xml->addChild($key, self::sanitizeValue($item));
        }
    }

    private static function sanitizeValue($value): string
    {
        return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public static function setCompatibiliesToXML($compatibilitiesIds)
    {
        $compatibilityList = [];

        $thisClass = new EbayData();
        $compatibilities = $thisClass->getAllCompatibilities();

        foreach ($compatibilities as $compatibility) {
            if (in_array($compatibility[0], $compatibilitiesIds)) {
                $compatibilityList[] = [
                    'Compatibility' => [
                        'NameValueList' => [
                            [
                                'Name' => 'Year',
                                'Value' => $compatibility[5]
                            ],
                            [
                                'Name' => 'Make',
                                'Value' => $compatibility[1]
                            ],
                            [
                                'Name' => 'Model',
                                'Value' => $compatibility[2]
                            ],
                            [
                                'Name' => 'Platform',
                                'Value' => $compatibility[4]
                            ],
                            [
                                'Name' => 'Trim',
                                'Value' => $compatibility[3]
                            ],
                            [
                                'Name' => 'CCM',
                                'Value' => $compatibility[6]
                            ]
                        ]
                    ]
                ];
            }
        }

        return $compatibilityList;
    }

    public static function truncateWordsByLimit(string $str, int $limit = 65): string
    {
        $words = explode(', ', $str);
        $result = '';

        foreach ($words as $i => $word) {
            $newPart = $i === 0 ? $word : ', ' . $word;

            if (mb_strlen($result . $newPart) > $limit) {
                break;
            }

            $result .= $newPart;
        }

        return $result;
    }
}

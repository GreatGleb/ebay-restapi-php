<?php

namespace App\Helpers;

use App\Http\Controllers\API\ApiEbayController as Ebay;
use App\Imports\EbayImport;

class EbayData extends Ebay
{
    protected static function renderImportForm($routeName) {
        $html = '<form data-action="' . route('import.' . $routeName) . '" method="post" enctype="multipart/form-data"  onsubmit="return false">
                            <div>
                                <h3>Importuoti ' . $routeName. '</h3>
                                <div class="row">
                                    <div>
                                        <input type="file" id="import" name="import">
                                    </div>';
        if($routeName == 'update') {
            $html .= '<div class="update_details">
                        <input type="checkbox" checked="1" name="all">
                        <label for="all">Update all</label>
                        <input type="checkbox" checked="1" name="title">
                        <label for="title">title</label>
                        <input type="checkbox" checked="1" name="newTitle">
                        <label for="newTitle">new title</label>
                        <input type="checkbox" checked="1" name="description">
                        <label for="description">description</label>
                        <input type="checkbox" checked="1" name="pictures">
                        <label for="pictures">images</label>
                        <input type="checkbox" checked="1" name="categoryId">
                        <label for="categoryId">category</label>
                        <input type="checkbox" checked="1" name="quantity">
                        <label for="quantity">quantity</label>
                        <input type="checkbox" checked="1" name="price">
                        <label for="price">price</label>
                        <input type="checkbox" checked="1" name="compatibility">
                        <label for="compatibility">compatibilities</label>
                        <input type="checkbox" checked="1" name="deliveryMethod">
                        <label for="deliveryMethod">delivery method</label></br>
                        <input type="checkbox" checked="1" name="specifications">
                        <label for="specifications">specifications:</label>
                        <input type="checkbox" checked="1" name="hersteller">
                        <label for="hersteller">hersteller</label>,
                        <input type="checkbox" checked="1" name="produkttyp">
                        <label for="produkttyp">producttyp</label>,
                        <input type="checkbox" checked="1" name="garantie">
                        <label for="garantie">herstellergarantie</label>,
                        <input type="checkbox" checked="1" name="oe">
                        <label for="oe">oe referenznummer(n)</label>,
                        <input type="checkbox" checked="1" name="nummer">
                        <label for="nummer">herstellernummer</label>,
                        <input type="checkbox" checked="1" name="ean">
                        <label for="ean">EAN</label>,
                        <input type="checkbox" checked="1" name="country">
                        <label for="country">country</label>,
                        <input type="checkbox" checked="1" name="length">
                        <label for="length">length</label>,
                        <input type="checkbox" checked="1" name="position">
                        <label for="position">position</label>,
                        <input type="checkbox" checked="1" name="keywords">
                        <label for="keywords">keywords</label></br>
                    </div>';
        }
        $html .= '<button class="btn btn-sm btn-primary" type="submit">Importuoti</button>
                                </div>
                            </div>
                        </form>';

        if($routeName == 'update') {
            $html .= '<script src="' . \URL::to('/') . '/js/ebayUpdateImport.js' .'"></script>';
        }

        return $html;
    }

    private function getAllCompatibilities() {
        $file = public_path() . '\ebay_models.xlsx';

        $compatibilities = (new EbayImport)->toArray($file)[0];

        return $compatibilities;
    }

    protected static function getXMLProperty($values, $type)
    {
        $XMLText = '';
        switch($type) {
            case 'title':
                $values = str_replace('&', '&amp;', $values);
                $values = str_replace('<->', '-', $values);
                $XMLText = '<Title>' . $values . '</Title>';
                break;
            case 'category':
                $XMLText = '<PrimaryCategory><CategoryID>' . $values[0] . '</CategoryID>';
                $XMLText .= '<CategoryName>' . str_replace('&', '&amp;', $values[1]) . '</CategoryName></PrimaryCategory>';
                break;
            case 'categoryId':
                $XMLText = '<PrimaryCategory><CategoryID>' . $values . '</CategoryID></PrimaryCategory>';
                break;
            case 'categoryName':
                $XMLText = '<CategoryName>' . $values . '</CategoryName>';
                break;
            case 'sku':
                $XMLText = '<SKU>' . $values . '</SKU>';
                break;
            case 'site':
                $XMLText = '<Site>' . $values . '</Site>';
                break;
            case 'price':
                $XMLText = '<StartPrice>' . $values . '</StartPrice>';
                break;
            case 'quantity':
                $XMLText = '<Quantity>' . $values . '</Quantity>';
                break;
            case 'currency':
                $XMLText = '<Currency>' . $values . '</Currency>';
                break;
            case 'PostalCode':
                $XMLText = '<PostalCode>' . $values . '</PostalCode>';
                break;
            case 'pictures':
                $XMLText = '<PictureDetails>';
                $pictures = explode(",", $values);
                $bathPicturePath = '';
                if(strpos($pictures[0], 'https://domain/storage/products/') !== False) {
                    $sleshPos = strpos(substr($pictures[0], 41), '/');
                    if($sleshPos !== False) {
                        $bathPicturePath = substr($pictures[0], 0, $sleshPos+42);
                    }
                }

                foreach ($pictures as $picture) {
                    if($picture[0] == ' ') {
                        $picture = substr($picture, 1);
                    }

                    if(substr($picture, 0, 4) !== 'http') {
                        $picture = $bathPicturePath . $picture;
                    }

                    $XMLText .= '<PictureURL>' . $picture . '</PictureURL>';
                }
                $XMLText .= '</PictureDetails>';
                break;
            case 'description':
                $values = htmlspecialchars($values, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $XMLText = '<Description>' . $values . '</Description>';
                break;
            case 'descriptionReplace':
                $values = htmlspecialchars($values, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $XMLText = '<Description>' . $values . '</Description>';
                $XMLText .= '<DescriptionReviseMode>Replace</DescriptionReviseMode>';
                break;
            case 'specifications':
                if(isset($values->oe)) {
                    $oe_codes = $values->oe;
                    if (strlen($oe_codes) > 65) {
                        if(strpos($oe_codes, ', ') === false) {
                            $oe_codes2 = $oe_codes;
                            $oe_codes2 = str_replace("\n", ', ', $oe_codes2);
                            $brands = ['AUDI', 'SEAT', 'SKODA', 'MERCEDES-BENZ', 'VAG', 'VW', 'LAND ROVER', 'SUZUKI', 'DACIA', 'RENAULT', 'CORTECO', 'Dr.Motor Automotive', 'ELRING', 'ELWIS ROYAL', 'GLASER', 'GUARNITAUTO', 'VICTOR REINZ'];
                            foreach ($brands as $brand) {
                                $oe_codes2 = str_replace(' ' . $brand . ' ', ', ', $oe_codes2);
                                $oe_codes2 = str_replace($brand . ' ', ', ', $oe_codes2);
                            }
                            $oe_codes2 = str_replace(",,", ',', $oe_codes2);
                            if(substr($oe_codes2, 0, 2) == ', ') {
                                $oe_codes2 = substr($oe_codes2, 2);
                            }
                            $oe_codes = $oe_codes2;
                        }
                        $oe_codes2 = explode(", ", $oe_codes);
                        foreach ($oe_codes2 as $code) {
                            if(strlen($code) > 65) {
                                $oe_codes = str_replace("\n", ', ', $oe_codes);
                                $brands = ['AUDI', 'SEAT', 'SKODA', 'MERCEDES-BENZ', 'VAG', 'VW', 'LAND ROVER', 'SUZUKI', 'DACIA', 'RENAULT', 'CORTECO', 'Dr.Motor Automotive', 'ELRING', 'ELWIS ROYAL', 'GLASER', 'GUARNITAUTO', 'VICTOR REINZ'];
                                foreach ($brands as $brand) {
                                    $oe_codes = str_replace(' ' . $brand . ' ', ', ', $oe_codes);
                                    $oe_codes = str_replace($brand . ' ', ', ', $oe_codes);
                                }
                                $oe_codes = str_replace(",,", ',', $oe_codes);
                                if(substr($oe_codes, 0, 2) == ', ') {
                                    $oe_codes = substr($oe_codes, 2);
                                }
                            }
                        }
                        if(strpos($oe_codes, ', ') === false) {
                            dd($oe_codes, $oe_codes2);
                        }
                        $oe_codes2 = explode(", ", $oe_codes);
                        $с = 0;
                        foreach ($oe_codes2 as $code) {
                            if(strlen($code) > 65) {
                                $oe_codes2[$с] = str_replace(",", ', ', $code);
                            }
                            $с++;
                        }
                        $oe_codes2 = implode(', ', $oe_codes2);
                        $oe_codes2 = explode(", ", $oe_codes2);
                        foreach ($oe_codes2 as $code) {
                            if(strlen($code) > 65) {
                                dd("бля", $oe_codes2, $oe_codes);
                            }
                        }
                        $oe_codes = [];
                        $o = 0;
                        while (count($oe_codes2) > 0) {
//                            var_dump(count($oe_codes2));
                            $oe_codes[$o][] = array_shift($oe_codes2);
                            $oe_codes[$o] = implode(', ', $oe_codes[$o]);
                            if (strlen($oe_codes[$o]) > 65) {
                                $oe_codes[$o] = explode(", ", $oe_codes[$o]);
                                if(count($oe_codes[$o]) > 1) {
                                    array_unshift($oe_codes2, array_pop($oe_codes[$o]));
                                }
                                $oe_codes[$o] = implode(', ', $oe_codes[$o]);
                                $o++;
//                                break;
                            } else {
                                $oe_codes[$o] = explode(", ", $oe_codes[$o]);
                            }
                        }
                        if (isset($oe_codes[$o])) {
                            if (is_array($oe_codes[$o])) {
                                $oe_codes[$o] = implode(', ', $oe_codes[$o]);
                            }
                        } else {
                            $oe_codes = substr($oe_codes[0], 65);
                        }
                    }
                    $values->oe = $oe_codes;

                    if (!is_array($values->oe)) {
                        if(strlen($values->oe) > 65) {
                            $values->oe = null;
                        }
                    }
                }

//                dd($values->oe);

                $XMLText = EbayData::getXMLSpecifications($values);
                break;
            case 'compatibility':
                $values = explode(", ", $values);

                $thisClass = new EbayData();
                $XMLText = '<ItemCompatibilityList>';
                $compatibilities = $thisClass->getAllCompatibilities();
                foreach ($compatibilities as $compatibility) {
                    if (in_array($compatibility[0], $values)) {
                        $XMLText .= <<< EOD
                <Compatibility>
                 <NameValueList/>
                 <NameValueList>
                   <Name>DEM_Year</Name>
                   <Value>$compatibility[5]</Value>
                 </NameValueList>
                 <NameValueList>
                   <Name>DEM_Make</Name>
                   <Value>$compatibility[1]</Value>
                 </NameValueList>
                 <NameValueList>
                   <Name>DEM_Model</Name>
                   <Value>$compatibility[2]</Value>
                 </NameValueList>
                 <NameValueList>
                   <Name>DEM_Platform</Name>
                   <Value>$compatibility[4]</Value>
                 </NameValueList>
                 <NameValueList>
                   <Name>DEM_Trim</Name>
                   <Value>$compatibility[3]</Value>
                 </NameValueList>
                 <NameValueList>
                   <Name>DEM_CCM</Name>
                   <Value>$compatibility[6]</Value>
                 </NameValueList>
               </Compatibility>
        EOD;
                    }
                }
                $XMLText .= '</ItemCompatibilityList>';
                break;
            case 'deliveryMethod':
                $XMLText = "<SellerShippingProfile><ShippingProfileID>" . $values['id'];
                $XMLText .= "</ShippingProfileID><ShippingProfileName>" . $values['name'] . "</ShippingProfileName></SellerShippingProfile>";
                break;
        }

        return $XMLText;
    }

    protected static function getXMLSpecifications($specifications) {
        if(!isset($specifications->oe)) {
            $specificationsText = file_get_contents(public_path() . '\xml\specifications.xml');
            $variables = ['item' => []];

            foreach ($specifications as $key=>$spec) {
                $variables['item'][] = $key;
            }
        } else {
            if (!is_array($specifications->oe)) {
                $specificationsText = file_get_contents(public_path() . '\xml\specificationsWithOe.xml');
                $variables = ['item' => []];
                foreach ($specifications as $key=>$spec) {
                    $variables['item'][] = $key;
                }
            } else {
                $specificationsText = file_get_contents(public_path() . '\xml\specifications.xml');
                $variables = ['item' => []];
                foreach ($specifications as $key=>$spec) {
                    if($key !== "oe") {
                        $variables['item'][] = $key;
                    }
                }
            }
        }

        $specificationsText = EbayData::addToXMLVariables($specificationsText, $variables, $specifications);
//        dd($specificationsText);

        $specificationObject = json_decode(json_encode(simplexml_load_string($specificationsText, "SimpleXMLElement", LIBXML_NOCDATA)), true);
        foreach ($specificationObject['NameValueList'] as $key=>$spec) {
            if(is_array($spec['Value']) or strpos($spec['Value'], '$item->') !== false) {
                unset($specificationObject['NameValueList'][$key]);
            }
        }
        $specificationsText2 = '<ItemSpecifics>';
        foreach ($specificationObject as $key=>$spec) {
            foreach ($specificationObject[$key] as $key2=>$spec2) {
                $specificationsText2 .= '<' . $key . '>';
                if(is_string($key2)) {
                    $specificationsText2 .= '<' . $key2 . '>';
                }

                if(is_array($spec2)) {
                    foreach ($specificationObject[$key][$key2] as $key3=>$spec3) {
                        if(is_string($key3)) {
                            $specificationsText2 .= '<' . $key3 . '>';
                        }

                        if(is_array($spec3)) {

                        } else {
                            $specificationsText2 .= $spec3;
                        }

                        if(is_string($key3)) {
                            $specificationsText2 .= '</' . $key3 . '>';
                        }
                    }
                } else {
                    $specificationsText2 .= $spec2;
                }

                if(is_string($key2)) {
                    $specificationsText2 .= '</' . $key2 . '>';
                }
                $specificationsText2 .= '</' . $key . '>';
            }
        }
        if(isset($specifications->oe)) {
            if(is_array($specifications->oe)) {
                for($o=1; $o < sizeof($specifications->oe); $o++) {
                    $specificationsText2 .= '<NameValueList><Name>';
                    $specificationsText2 .= 'OE/OEM Referenznummer(n)';
                    if($o > 1) {
                        $specificationsText2 .= $o;
                    }
                    $specificationsText2 .= '</Name><Value>';
                    $specificationsText2 .= $specifications->oe[$o] . '</Value></NameValueList>';
                }
            }
        }
        $specificationsText2 .= '</ItemSpecifics>';

        return $specificationsText2;
    }

    public static function addToXMLVariables($xml, $variables, $item, Ebay $ebay = null) {
        foreach ($variables as $key=>$variable) {
            if(!is_array($variable)) {
                $name = '$' . $key . '->' . $variable;
                $xml = str_replace($name, $$key->{$variable}, $xml);
            } else {
                foreach ($variable as $let) {
                    $name = '$' . $key . '->' . $let;
//                    var_dump([$name, $$key->{$let}]);
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

    public static function addDescriptionToHTML($description, $isBigOe) {
        $descriptionText = file_get_contents(public_path() . '\xml\description.html');
        $descriptionText = EbayData::prepareXML($descriptionText);
        if($description === null) { // and !$isBigOe
            $description = $descriptionText;
        } else {
            if($description === null) {
                $newContainer = '<div class="container" style="border-top: 1px solid #CBD4C2; padding: 40px 0 40px 0">
                                <div class="col-12 col-md-12">
                                    <h2 style="line-height: 45px"><font face="Arial" style="color: #353535">Kurze Beschreibung</font></h2>
                                    <ul class="desc" style="color: #353535; line-height: 25px; font-size: 16px; margin-top: 30px"></ul>
                                </div>
                            </div>';
            } else {
                $description = str_replace('&', '&amp;', $description);
                $elems = explode("\n", trim($description));
                $elemsText = '';
                if(sizeof($elems) > 0) {
                    foreach ($elems as $elem) {
                        $elemsText .= '<li>' . $elem . '</li>';
                    }
                }

                $newContainer = '<div class="container" style="border-top: 1px solid #CBD4C2; padding: 40px 0 40px 0">
                                <div class="col-12 col-md-12">
                                    <h2 style="line-height: 45px"><font face="Arial" style="color: #353535">Kurze Beschreibung</font></h2>
                                    <ul class="desc" style="color: #353535; line-height: 25px; font-size: 16px; margin-top: 30px">' . $elemsText . '</ul>
                                </div>
                            </div>';
            }

            $newContainer = EbayData::prepareXML($newContainer);
            $insert = new \SimpleXMLElement($newContainer);

            $xml = simplexml_load_string($descriptionText, "SimpleXMLElement", LIBXML_NOCDATA);

            $containers = $xml->xpath('.//body/div');
            $container = $containers[2];
            $description = EbayData::simplexml_insertBefore($insert, $container)->ownerDocument->saveXML();
        }

        return $description;
    }

    public static function prepareXML($xml)
    {
        $errorTags = [['&Auml;', 'Ä'], ['&auml;', 'ä'], ['&szlig;', 'ß'], ['&Uuml;', 'Ü'], ['&uuml;', 'ü'], ['&nbsp;', '&#160;'], ['&ouml;', 'ö'], ['&copy;', '©'],
            ['<!doctype html>
<html lang="zxx">
<head>
</head>
<body><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"></body>
</html>
<title>Free eBay listing template designed by dewiso.com</title>
<link href="https://dewiso.com/css/bootstrap.min.css" rel="stylesheet" />
', '<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
        <title>eBay listing template</title>
        <link href="https://dewiso.com/css/bootstrap.min.css" rel="stylesheet" />
    </head>
    <body>']
        ];
        foreach ($errorTags as $errorTag) {
            $xml = str_replace($errorTag[0], $errorTag[1], $xml);
        }

        return $xml;
    }

    public static function addToDescriptionOes($description, $oes) {
        $description = EbayData::prepareXML($description);
        $oes = str_replace('&', '&amp;', $oes);

//        $description .= '</body></html>';

        $xml = simplexml_load_string($description, "SimpleXMLElement", LIBXML_NOCDATA);

        $newLiWithOe = '<li>OE/OEM Referenznummer(n): ' . $oes . '</li>';

        $insert = new \SimpleXMLElement($newLiWithOe);
        $containers = $xml->xpath('.//*[contains(concat(" ",normalize-space(@class)," ")," container ")]/div/ul[contains(concat(" ",normalize-space(@class)," ")," desc ")]');
        $container = $containers[0];

        // Append the new element
        $result = EbayData::simplexml_appendChild($insert, $container)->ownerDocument->saveXML();

        return $result;
    }

    public static function simplexml_appendChild(\SimpleXMLElement $insert, \SimpleXMLElement $target)
    {
        $target_dom = dom_import_simplexml($target);
        $insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml($insert), true);
        return $target_dom->appendChild($insert_dom);
    }

    public static function simplexml_insertBefore(\SimpleXMLElement $insert, \SimpleXMLElement $target)
    {
        $target_dom = dom_import_simplexml($target);
        $insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml($insert), true);
        return $target_dom->parentNode->insertBefore($insert_dom, $target_dom);
    }

    public static function getImportResults($emptyStatus, $fileNameForChecking, $fileNameForResult, $fileNameForLogs) {
        $path = realpath(__DIR__ . '\..\\Http\Controllers\API');
        $result = [];

        $dataForChecking = @file_get_contents($path . '\\' . $fileNameForChecking . '.json');
        if($dataForChecking) {
            unlink($path . '\\' . $fileNameForChecking . '.json');
        }

        $dataForResult = @file_get_contents($path . '\\' . $fileNameForResult . '.json');
        if($dataForResult) {
            $dataForResult = json_decode($dataForResult, true);
            $result['result'] = $dataForResult;

//            unlink($path . '\\' . $fileNameForResult . '.json');
        }

        $dataForLogs = @file_get_contents($path . '\\' . $fileNameForLogs . '.json');
        if($dataForLogs) {
            $dataForLogs = json_decode($dataForLogs, true);
            foreach ($dataForLogs as $log) {
                if($log['Ack'] == $emptyStatus) {
                    $result[] = $log;
                } else {
                    $newLog = json_decode('{}');
                    $newLog->{'Ack'} = $log['Ack'];
                    $newLog->{'SKU'} = $log['SKU'];
                    $newLog->{'Title'} = $log['Title'];
                    $newLog->{'numberInFile'} = $log['numberInFile'];
                    if(isset($log['Errors'])) {
                        $newLog->{'Errors'} = [];

                        if (isset($log['Errors'][0])) {
                            foreach ($log['Errors'] as $err) {
                                $error = [];
                                $error['code'] = $err['SeverityCode'];
                                $error['name'] = $err['LongMessage'];
                                $newLog->{'Errors'}[] = $error;
                            }
                        } else {
                            $error = [];
                            $error['code'] = $log['Errors']['SeverityCode'];
                            $error['name'] = $log['Errors']['LongMessage'];
                            $newLog->{'Errors'}[] = $error;
                        }
                    }

                    $result[] = $newLog;
                }
            }

//            unlink($path . '\\' . $fileNameForLogs . '.json');
        }

        return json_encode($result);
    }

    public static function setImportCheckResult($fileNameForChecking, $sizeOfFile) {
        $path = realpath(__DIR__ . '\..\\Http\Controllers\API');
        $file = @file_get_contents($path . '\\' . $fileNameForChecking . '.json');
        if($file) {
            $newCount = json_decode($file)[1] + 1;
            $dataForChecking = json_encode([$sizeOfFile, $newCount]);
            file_put_contents($path . '\\' . $fileNameForChecking . '.json', $dataForChecking);
        }
    }

    public static function setImportResult($fileNameForResult, $ack) {
        $path = realpath(__DIR__ . '\..\\Http\Controllers\API');
        $dataForResult = @file_get_contents($path . '\\' . $fileNameForResult . '.json');
        if($dataForResult) {
            $dataForResult = json_decode($dataForResult, TRUE);
            if(isset($dataForResult[$ack])) {
                $dataForResult[$ack] = $dataForResult[$ack] + 1;
            } else {
                $dataForResult[$ack] = 1;
            }
        } else {
            $dataForResult = [];
            $dataForResult[$ack] = 1;
        }

        $dataForResult = json_encode($dataForResult);
        file_put_contents($path . '\\' . $fileNameForResult . '.json', $dataForResult);
    }

    public static function setImportLogResult($fileNameForLogs, $log) {
        $path = realpath(__DIR__ . '\..\\Http\Controllers\API');
        $dataForLogs = @file_get_contents($path . '\\' . $fileNameForLogs . '.json');
        if($dataForLogs) {
            $dataForLogs = json_decode($dataForLogs, TRUE);
        } else {
            $dataForLogs = [];
        }
        $dataForLogs[] = $log;

        $dataForLogs = json_encode($dataForLogs);
        file_put_contents($path . '\\' . $fileNameForLogs . '.json', $dataForLogs);
    }

    public static function setImportResults($fileNameForChecking, $sizeOfFile, $sku, $title, $logs, $n, $fileNameForResult, $fileNameForLogs) {
        EbayData::setImportCheckResult($fileNameForChecking, $sizeOfFile);

        $logsIndex = array_key_last($logs);
        if(isset($logs[$logsIndex]->Ack)) {
            $log = json_decode(json_encode($logs[$logsIndex]), TRUE);
            $log['SKU'] = $sku;
            $log['Title'] = $title;
            $log['numberInFile'] = $n + 1;
            $ack = $log['Ack'];
            EbayData::setImportResult($fileNameForResult, $ack);

            EbayData::setImportLogResult($fileNameForLogs, $log);
        }
    }
}

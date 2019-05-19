<?php

class Files {
    public static function XmlToCvs () {
        $filexml='aplanamiento.xml';

        if (file_exists($filexml))  {
            $xml = simplexml_load_file($filexml);
            $f = fopen('aplanamiento.csv', 'w');
            $this->createCsv($xml, $f);
            fclose($f);
        }

        function createCsv($xml,$f)
        {
            foreach ($xml->children() as $item) {
                $hasChild = (count($item->children()) > 0)?true:false;
                if( ! $hasChild) {
                    $put_arr = array($item->getName(),$item); 
                    fputcsv($f, $put_arr ,',','"');
                }
                else
                {
                createCsv($item, $f);
                }
            }
        }
    }
}
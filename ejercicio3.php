<?php

class Files {
    public static combineFiles () {
        $this->joinFiles(array('file1.csv', 'file2.csv'), 'combined.csv');
    }

    private function joinFiles( $files, $result) {
        if(!is_array($files)) {
            throw new Exception('`$files` must be an array');
        }

        $wH = fopen($result, "w+");

        foreach($files as $file) {
            $fh = fopen($file, "r");
            while(!feof($fh)) {
                fwrite($wH, fgets($fh));
            }
            fclose($fh);
            unset($fh);
            fwrite($wH, "\n");
        }
    }
}

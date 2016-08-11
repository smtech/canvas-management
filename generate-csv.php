<?php

require_once('common.inc.php');

use Battis\SimpleCache;

$cache = new SimpleCache($sql);

// FIXME this is a gaping security hole, allowing anything in the cache to be dumped to CSV -- needs a nonce or something

if (!empty($_REQUEST['data'])) {
    $data = $cache->getCache($_REQUEST['data']);
    if (is_array($data)) {
        
        $filename = (empty($_REQUEST['filename']) ? date('Y-m-d_H-i-s') : $_REQUEST['filename']);
        if (!preg_match('/.*\.csv$/i', $filename)) {
            $filename .= '.csv';
        }
        
        /* http://code.stephenmorley.org/php/creating-downloadable-csv-files/ */

        /* output headers so that the file is downloaded rather than displayed */
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=$filename");
        
        /* create a file pointer connected to the output stream */
        $output = fopen('php://output', 'w');

        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }
}
    
?>
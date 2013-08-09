<?php
/**
 * Pest is a REST client for PHP.
 *
 * PestCanvas adds Canvas-specific functionality to Pest, stripping numeric
 * indices out of array parameters and sending non-JSON-encoded requests.
 * 
 * If you don't want to have exceptions thrown when there are errors encoding or
 * decoding JSON set the `throwEncodingExceptions` property to FALSE.
 *
 * See http://github.com/educoder/pest for details.
 *
 * This code is licensed for use, modification, and distribution
 * under the terms of the MIT License (see http://en.wikipedia.org/wiki/MIT_License)
 */

require_once 'PestJSON.php';

class PestCanvas extends PestJSON
{
    /**
     * Perform an HTTP POST
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    public function post($url, $data, $headers = array())
    {
        return Pest::post($url, $data, $headers);
    }

    /**
     * Perform HTTP PUT
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    public function put($url, $data, $headers = array())
    {
        return Pest::put($url, $data, $headers);
    }

    /**
     * Prepare data
     * @param array $data
     * @return array|string
     */
	public function prepdata($data) {
        if (is_array($data)) {
            $multipart = false;

            foreach ($data as $item) {
                if (is_string($item) && strncmp($item, "@", 1) == 0 && is_file(substr($item, 1))) {
                    $multipart = true;
                    break;
                }
            }
            
            if ($multipart) {
	            return $data;
            } else {
            	/* Canvas prefers not to receive numeric indices for array parameters */
	            return preg_replace('|%5B\d+%5D=|', '%5B%5D=', http_build_query($data));
	        }
	        
        } else {
            return $data;
        }
	}
}
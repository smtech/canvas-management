<?php

namespace smtech\CanvasManagement;

use Battis\BootstrapSmarty\NotificationMessage;
use Battis\HierarchicalSimpleCache;

use smtech\LTI\Configuration\Option;

class Toolbox extends \smtech\StMarksReflexiveCanvasLTI\Toolbox
{
    /**
     * Configure course and account navigation placements
     *
     * @return Generator
     */
    public function getGenerator()
    {
        parent::getGenerator();

        $this->generator->setOptionProperty(
            Option::ACCOUNT_NAVIGATION(),
            'visibility',
            'admins'
        );

        return $this->generator;
    }

    /**
     * Explode a string
     *
     * Explode into comma- and newline-delineated parts, and trim those parts.
     *
     * @param string $str
     *
     * @return string[]
     **/
    public function explodeCommaAndNewlines($str)
    {
        $list = array();
        $lines = explode("\n", $str);
        foreach ($lines as $line) {
            $items = explode(',', $line);
            foreach ($items as $item) {
                $trimmed = trim($item);
                if (!empty($trimmed)) {
                    $list[] = $trimmed;
                }
            }
        }
        return $list;
    }

    /**
     * Explode a string
     *
     * Explode into trimmed lines
     *
     * @param string $str
     *
     * @return string[]
     **/
    public function explodeNewLines($str)
    {
        $list = array();
        $lines = explode("\n", $str);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                $list[] = $trimmed;
            }
        }
        return $list;
    }

    /**
     * Get a listing of all accounts organized for presentation in a select picker
     *
     * @return array
     **/
    public function getAccountList()
    {
        $cache = new HierarchicalSimpleCache($this->getMySQL(), __CLASS__);

        $accounts = $cache->getCache('accounts');
        if ($accounts === false) {
            $accountsResponse = $this->api_get('accounts/1/sub_accounts', [
                'recursive' => 'true'
            ]);
            $accounts = array();
            foreach ($accountsResponse as $account) {
                $accounts[$account['id']] = $account;
            }
            $cache->setCache('accounts', $accounts, 7 * 24 * 60 * 60);
        }
        return $accounts;
    }

    /**
     * Get a listing of all terms organized for presentation in a select picker
     *
     * @return array
     **/
    public function getTermList()
    {
        $cache = new HierarchicalSimpleCache($this->getMySQL(), __CLASS__);

        $terms = $cache->getCache('terms');
        if ($terms === false) {
            $_terms = $this->api_get('accounts/1/terms', [
                'workflow_state' => 'active'
            ]);
            $termsResponse = $_terms['enrollment_terms'];
            $terms = array();
            foreach ($termsResponse as $term) {
                $terms[$term['id']] = $term;
            }
            $cache->setCache('terms', $terms, 7 * 24 * 60 * 60);
        }
        return $terms;
    }

    /**
     * A standard format for an error message due to an exception
     *
     * @param \Exception $e
     *
     * @return void
     **/
    public function exceptionErrorMessage($e)
    {
        $this->smarty_addMessage(
            'Error ' . $e->getCode(),
            '<p>Last API Request</p><pre>' .
                print_r($this->getAPI()->last_request, true) .
                '</pre><p>Last Headers</p><pre>' .
                print_r($this->getAPI()->last_headers, true) .
                '</pre><p>Error Message</p><pre>' . $e->getMessage() .'</pre>',
            NotificationMessage::ERROR
        );
    }
}

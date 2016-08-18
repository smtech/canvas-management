<?php

require_once 'common.inc.php';

use smtech\CanvasManagement\Toolbox;
use smtech\ReflexiveCanvasLTI\LTI\ToolProvider;
use smtech\ReflexiveCanvasLTI\Exception\ConfigurationException;

/* store any requested actions for future handling */
$action = (empty($_REQUEST['action']) ?
    ACTION_UNSPECIFIED :
    strtolower($_REQUEST['action'])
);

/* action requests only come from outside the LTI! */
if ($action) {
    unset($_SESSION[ToolProvider::class]);
}

/* authenticate LTI launch request, if present */
if ($toolbox->lti_isLaunching()) {
    /* http://stackoverflow.com/a/14329752 */
    // session_start(); // already called in common.inc.php
    @session_destroy(); // TODO I don't feel good about suppressing errors
    @session_unset();
    @session_start();
    @session_regenerate_id(true);
    $_SESSION[Toolbox::class] =& $toolbox;
    session_write_close();
    $toolbox->lti_authenticate();
    exit;
}

/* if authenticated LTI launch, redirect to appropriate placement view */
if (!empty($_SESSION[ToolProvider::class]['canvas']['account_id'])) {
    $toolbox->smarty_display('home.tpl');
    exit;

/* if not authenticated, default to showing credentials */
} else {
    $action = (empty($action) ?
        ACTION_CONFIG :
        $action
    );
}

/* process any actions */
switch ($action) {
    /* reset cached install data from config file */
    case ACTION_INSTALL:
        $_SESSION['toolbox'] = Toolbox::fromConfiguration(CONFIG_FILE, true);
        $toolbox =& $_SESSION['toolbox'];

        /* test to see if we can connect to the API */
        try {
            $toolbox->getAPI();
        } catch (ConfigurationException $e) {
            /* if there isn't an API token in config.xml, are there OAuth credentials? */
            if ($e->getCode() === ConfigurationException::CANVAS_API_INCORRECT) {
                $toolbox->interactiveGetAccessToken('This tool requires access to the Canvas APIs by an administrative user. This API access is used to make (sometimes dramatic) updates to your course and user data via administrative scripts. Please enter the URL of your Canvas instance below (e.g. <code>https://canvas.instructure.com</code> -- the URL that you would enter to log in to Canvas). If you are not already logged in, you will be asked to log in. After logging in, you will be asked to authorize this tool.</p><p>If you are already logged, but <em>not</em> logged in as an administrative user, please log out now, so that you may log in as administrative user to authorize this tool.');
                exit;
            } else { /* no (understandable) API credentials available -- doh! */
                throw $e;
            }
        }

        /* finish by opening consumers control panel */
        header('Location: consumers.php');
        exit;

    /* show LTI configuration XML file */
    case ACTION_CONFIG:
        header('Content-type: application/xml');
        echo $toolbox->saveConfigurationXML();
        exit;
}

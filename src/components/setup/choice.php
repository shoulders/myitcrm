<?php
/**
 * @package   QWcrm
 * @author    Jon Brown https://quantumwarp.com/
 * @copyright Copyright (C) 2016 - 2017 Jon Brown, All rights reserved.
 * @license   GNU/GPLv3 or later; https://www.gnu.org/licenses/gpl.html
 */

defined('_QWEXEC') or die;

// Prevent direct access to this page
if(!$this->app->system->security->checkPageAccessedViaQwcrm('setup', 'choice', 'setup')) {
    header('HTTP/1.1 403 Forbidden');
    die(_gettext("No Direct Access Allowed."));
}

// Define the setup type for smarty - currently only used for 'upgrade'
$this->app->smarty->assign('setup_type', \CMSApplication::$VAR['setup_type'] ?? null);

// Create a Setup Object
$qsetup = new Setup(\CMSApplication::$VAR);

// Get Compatibility Results
$this->app->smarty->assign('compatibility_results', $qsetup->checkServerEnviromentCompatibility());
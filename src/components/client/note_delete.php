<?php
/**
 * @package   QWcrm
 * @author    Jon Brown https://quantumwarp.com/
 * @copyright Copyright (C) 2016 - 2017 Jon Brown, All rights reserved.
 * @license   GNU/GPLv3 or later; https://www.gnu.org/licenses/gpl.html
 */

defined('_QWEXEC') or die;

// check if we have a client_note_id
if(!isset(\CMSApplication::$VAR['client_note_id']) || !\CMSApplication::$VAR['client_note_id']) {
    $this->app->system->variables->systemMessagesWrite('danger', _gettext("No Client Note ID supplied."));
    $this->app->system->general->force_page('client', 'search');
}

// Get the client_id before we delete the record
\CMSApplication::$VAR['client_id'] = $this->app->components->client->get_client_note_details(\CMSApplication::$VAR['client_note_id'], 'client_id');

// Delete the client note
$this->app->system->variables->systemMessagesWrite('success', _gettext("The client note has been deleted."));
$this->app->components->client->delete_client_note(\CMSApplication::$VAR['client_note_id']);

// Reload the clients details page
$this->app->system->general->force_page('client', 'details&client_id='.\CMSApplication::$VAR['client_id']);
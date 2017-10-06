<?php
// joomla\libraries\joomla\session\storage\database.php
/**
 * @package     Joomla.Platform
 * @subpackage  Session
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_QWEXEC') or die;

/**
 * Database session storage handler for PHP
 *
 * @see         https://secure.php.net/manual/en/function.session-set-save-handler.php
 * @since       11.1
 * @deprecated  4.0  The CMS' Session classes will be replaced with the `joomla/session` package
 */
class JSessionStorageDatabase extends JSessionStorage
{
    /**
     * Read the data for a particular session identifier from the SessionHandler backend.
     *
     * @param   string  $id  The session identifier.
     *
     * @return  string  The session data.
     *
     * @since   11.1
     */
    public function read($id)
    {
        // Get the database connection object and verify its connected.
        $db = QFactory::getDbo();

        try {
            // Get the session data from the database table.
            /*$query = $db->getQuery(true)
                ->select($db->quoteName('data'))
            ->from($db->quoteName('#__session'))
            ->where($db->quoteName('session_id') . ' = ' . $db->quote($id));

            $db->setQuery($query);*/
            
            $sql = "SELECT data FROM ".PRFX."session WHERE session_id = ".$db->qstr($id);

            $rs = $db->Execute($sql);

            $result = (string) $rs->fields['data'];

            $result = str_replace('\0\0\0', chr(0) . '*' . chr(0), $result);

            return $result;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    /**
     * Write session data to the SessionHandler backend.
     *
     * @param   string  $id    The session identifier.
     * @param   string  $data  The session data.
     *
     * @return  boolean  True on success, false otherwise.
     *
     * @since   11.1
     */
    public function write($id, $data)
    {
        // Get the database connection object and verify its connected.
        $db = QFactory::getDbo();

        $data = str_replace(chr(0) . '*' . chr(0), '\0\0\0', $data);

        try {
            /*$query = $db->getQuery(true)
                ->update($db->quoteName('#__session'))
                ->set($db->quoteName('data') . ' = ' . $db->quote($data))
                ->set($db->quoteName('time') . ' = ' . $db->quote((int) time()))
                ->where($db->quoteName('session_id') . ' = ' . $db->quote($id));

            // Try to update the session data in the database table.
            $db->setQuery($query);
            $db->execute();*/
            
            $sql = "UPDATE ".PRFX."session SET
                    data = ". $db->qstr($data).",
                    time = ". $db->qstr(time())."
                    WHERE session_id = ".$db->qstr($id);
            
            $db->Execute($sql);

            /*
             * Since $db->execute did not throw an exception, so the query was successful.
             * Either the data changed, or the data was identical.
             * In either case we are done.
             */
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    /**
     * Destroy the data for a particular session identifier in the SessionHandler backend.
     *
     * @param   string  $id  The session identifier.
     *
     * @return  boolean  True on success, false otherwise.
     *
     * @since   11.1
     */
    public function destroy($id)
    {
        // Get the database connection object and verify its connected.
        $db = QFactory::getDbo();

        try {
            /*$query = $db->getQuery(true)
                ->delete($db->quoteName('#__session'))
                ->where($db->quoteName('session_id') . ' = ' . $db->quote($id));

            // Remove a session from the database.
            $db->setQuery($query);

            return (boolean) $db->execute();*/
            
            // Remove a session from the database.
            $sql = "DELETE FROM ".PRFX."session WHERE session_id = ".$db->qstr($id);
            return (boolean) $db->Execute($sql);
        } catch (RuntimeException $e) {
            return false;
        }
    }

    /**
     * Garbage collect stale sessions from the SessionHandler backend.
     *
     * @param   integer  $lifetime  The maximum age of a session.
     *
     * @return  boolean  True on success, false otherwise.
     *
     * @since   11.1
     */
    public function gc($lifetime = 1440)
    {
        // Get the database connection object and verify its connected.
        $db = QFactory::getDbo();

        // Determine the timestamp threshold with which to purge old sessions.
        $past = time() - $lifetime;

        try {
            /*$query = $db->getQuery(true)
                ->delete($db->quoteName('#__session'))
                ->where($db->quoteName('time') . ' < ' . $db->quote((int) $past));

            // Remove expired sessions from the database.
            $db->setQuery($query);

            return (boolean) $db->execute();*/
            
            // Remove expired sessions from the database.
            $sql = "DELETE FROM ".PRFX."session WHERE time = ".$db->qstr((int) $past);
            return (boolean) $db->Execute($sql);
        } catch (RuntimeException $e) {
            return false;
        }
    }
}

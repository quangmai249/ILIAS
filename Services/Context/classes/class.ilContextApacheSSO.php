<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Context/interfaces/interface.ilContextTemplate.php";

/**
 * Service context for cron
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 *
 * @ingroup ServicesContext
 */
class ilContextApacheSSO implements ilContextTemplate
{
    /**
     * Are redirects supported?
     *
     * @return bool
     */
    public static function supportsRedirects()
    {
        return true;
    }
    
    /**
     * Based on user authentication?
     *
     * @return bool
     */
    public static function hasUser()
    {
        return true;
    }
    
    /**
     * Uses HTTP aka browser
     *
     * @return bool
     */
    public static function usesHTTP()
    {
        return true;
    }
    
    /**
     * Has HTML output
     *
     * @return bool
     */
    public static function hasHTML()
    {
        return true;
    }
    
    /**
     * Uses template engine
     *
     * @return bool
     */
    public static function usesTemplate()
    {
        return true;
    }
    
    /**
     * Init client
     *
     * @return bool
     */
    public static function initClient()
    {
        return true;
    }
    
    /**
     * Try authentication
     *
     * @return bool
     */
    public static function doAuthentication()
    {
        return true;
    }
    
    /**
     * Check if persistent session handling is supported
     * @return boolean
     */
    public static function supportsPersistentSessions()
    {
        return true;
    }

    /**
     * Supports push messages
     *
     * @return bool
     */
    public static function supportsPushMessages()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function isSessionMainContext()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function modifyHttpPath(string $httpPath) : string
    {
        return dirname($httpPath);
    }
}

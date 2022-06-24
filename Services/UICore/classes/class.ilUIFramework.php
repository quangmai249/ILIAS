<?php

/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * UI framework utility class
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 * @ingroup ServicesUICore
 */
class ilUIFramework
{
    const BOWER_BOOTSTRAP_JS = "node_modules/bootstrap/dist/js/bootstrap.min.js";


    /**
     * Get javascript files
     *
     * @return array array of files
     */
    public static function getJSFiles()
    {
        return array( "./" . self::BOWER_BOOTSTRAP_JS );
    }

    /**
     * Get javascript files
     *
     * @return array array of files
     */
    public static function getCssFiles()
    {
        return [];
    }


    /**
     * Init
     *
     * @param ilGlobalTemplateInterface $a_tpl template object
     */
    public static function init(ilGlobalTemplateInterface $a_tpl = null)
    {
        global $DIC;

        if ($a_tpl == null) {
            $a_tpl = $DIC["tpl"];
        }

        foreach (ilUIFramework::getJSFiles() as $f) {
            $a_tpl->addJavaScript($f, true, 0);
        }
        foreach (ilUIFramework::getCssFiles() as $f) {
            $a_tpl->addCss($f);
        }
    }
}

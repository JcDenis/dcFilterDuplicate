<?php
/**
 * @brief dcFilterDuplicate, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis, Pierre Van Glabeke
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcFilterDuplicate;

use dcCore;
use dcNsProcess;
use Exception;

class Install extends dcNsProcess
{
    # -- Module specs --
    private static $mod_conf = [[
        'dcfilterduplicate_minlen',
        'Minimum lenght of comment to filter',
        30,
        'integer',
    ]];

    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->newVersion(My::id(), dcCore::app()->plugins->moduleInfo(My::id(), 'version'));

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            # Set module settings
            foreach (self::$mod_conf as $v) {
                dcCore::app()->blog->settings->get(My::id())->put(
                    $v[0],
                    $v[2],
                    $v[3],
                    $v[1],
                    false,
                    true
                );
            }

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return false;
        }
    }
}

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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

# -- Module specs --
$mod_id   = 'dcFilterDuplicate';
$mod_conf = [[
    'dcfilterduplicate_minlen',
    'Minimum lenght of comment to filter',
    30,
    'integer',
]];

# -- Nothing to change below --
try {
    # Check module version
    if (version_compare(
        dcCore::app()->getVersion($mod_id),
        dcCore::app()->plugins->moduleInfo($mod_id, 'version'),
        '>='
    )) {
        return null;
    }
    # Check Dotclear version
    $dc_min = $this->modules[$mod_id]['requires'][0][1];
    if (!method_exists('dcUtils', 'versionsCompare')
        || dcUtils::versionsCompare(DC_VERSION, $dc_min, '<', false)
    ) {
        throw new Exception(sprintf(
            '%s requires Dotclear %s',
            $mod_id,
            $dc_min
        ));
    }
    # Set module settings
    dcCore::app()->blog->settings->addNamespace($mod_id);
    foreach ($mod_conf as $v) {
        dcCore::app()->blog->settings->{$mod_id}->put(
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

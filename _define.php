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

if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    "Duplicate filter",
    "Antispam for duplicate comments on multiblog",
    "Jean-Christian Denis, Pierre Van Glabeke",
    '0.8',
    [
        'requires' => [['core', '2.19']],
        'permissions' => 'admin',
        'priority' => 200,
        'type' => 'plugin',
        'support' => 'http://forum.dotclear.org/viewtopic.php?pid=332947#p332947',
        'details' => 'http://plugins.dotaddict.org/dc2/details/dcFilterDuplicate',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/dcFilterDuplicate/master/dcstore.xml'
    ]
);
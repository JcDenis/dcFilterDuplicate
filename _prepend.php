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

$__autoload['dcFilterDuplicate'] = 
    dirname(__FILE__) . '/inc/class.filter.duplicate.php';

$core->spamfilters[] = 'dcFilterDuplicate';
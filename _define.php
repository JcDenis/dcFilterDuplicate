<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of dcFilterDuplicate, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2015 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {

	return null;
}
 
$this->registerModule(
	/* Name */
	"dcFilterDuplicate",
	/* Description*/
	"Antispam for duplicate comments on multiblog",
	/* Author */
	"Jean-Christian Denis",
	/* Version */
	'0.6',
	/* Properies */
	array(
		'permissions' => 'admin',
		'priority' => 200,
		'type' => 'plugin',
		'dc_min' => '2.6',
		'support' => 'http://forum.dotclear.org/viewtopic.php?pid=332947#p332947',
		'details' => 'http://plugins.dotaddict.org/dc2/details/dcFilterDuplicate'
	)
);
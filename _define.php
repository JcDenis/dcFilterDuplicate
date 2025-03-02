<?php
/**
 * @file
 * @brief       The plugin dcFilterDuplicate definition
 * @ingroup     dcFilterDuplicate
 *
 * @defgroup    dcFilterDuplicate Plugin dcFilterDuplicate.
 *
 * Antispam for duplicate comments on multiblog.
 *
 * @author      Tomtom (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Duplicate filter',
    'Antispam for duplicate comments on multiblog',
    'Jean-Christian Denis, Pierre Van Glabeke',
    '1.4.2',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'priority'    => 200,
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-03-02T10:25:42+00:00',
    ]
);

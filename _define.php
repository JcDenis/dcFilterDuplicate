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
    '1.4',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'priority'    => 200,
        'type'        => 'plugin',
        'support'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/issues',
        'details'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository'  => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);

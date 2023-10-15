<?php

declare(strict_types=1);

namespace Dotclear\Plugin\dcFilterDuplicate;

use Dotclear\App;
use Dotclear\Core\Process;
use Exception;

/**
 * @brief       dcFilterDuplicate install class.
 * @ingroup     dcFilterDuplicate
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            # Set module settings
            My::settings()->put(
                My::SETTING_PREFIX . 'minlen',
                30,
                'integer',
                'Minimum lenght of comment to filter',
                false,
                true
            );

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }
}

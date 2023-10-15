<?php

declare(strict_types=1);

namespace Dotclear\Plugin\dcFilterDuplicate;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       dcFilterDuplicate prepend class.
 * @ingroup     dcFilterDuplicate
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('AntispamInitFilters', function (ArrayObject $spamfilters): void {
            $spamfilters[] = FilterDuplicate::class;
        });

        return true;
    }
}

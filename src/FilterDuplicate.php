<?php

declare(strict_types=1);

namespace Dotclear\Plugin\dcFilterDuplicate;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;

/**
 * @brief       dcFilterDuplicate antispam class.
 * @ingroup     dcFilterDuplicate
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FilterDuplicate extends SpamFilter
{
    public string $name  = 'Duplicate filter';
    public bool $has_gui = true;

    protected function setInfo(): void
    {
        $this->name        = __('Duplicate');
        $this->description = __('Same comments on others blogs of a multiblog');
    }

    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status): ?bool
    {
        if ($type != 'comment' || is_null($content) || is_null($ip)) {
            return null;
        }
        if (strlen($content) < $this->getMinLength()) {
            return null;
        }

        if ($this->isDuplicate($content, $ip)) {
            $this->markDuplicate($content, $ip);
            $status = 'Duplicate on other blog';

            return true;
        }

        return null;
    }

    public function getStatusMessage(string $status, ?int $comment_id): string
    {
        // Status is stored in english
        return parent::getStatusMessage(__($status), $comment_id);
    }

    public function isDuplicate(string $content, string $ip): bool
    {
        if (!App::blog()->isDefined()) {
            return false;
        }

        $sql = new SelectStatement();
        $rs  = $sql->from($sql->as(App::db()->con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->join(
                (new JoinStatement())
                ->left()
                ->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                ->on('C.post_id = P.post_id')
                ->statement()
            )
            ->where('P.blog_id != ' . $sql->quote(App::blog()->id()))
            ->and('C.comment_content = ' . $sql->quote($content))
            ->and('C.comment_ip=' . $sql->quote($ip))
            ->select();

        return !is_null($rs) && !$rs->isEmpty();
    }

    public function markDuplicate(string $content, string $ip): void
    {
        // Only comments of other blogs, the current one is checked by antispam
        $sql = new SelectStatement();
        $sql->from(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME)
            ->column('post_id')
            ->where('blog_id != ' . $sql->quote(App::blog()->id()));

        $cur = App::blog()->openCommentCursor();
        $cur->setField('comment_status', App::status()->comment()::JUNK);
        $cur->setField('comment_spam_status', 'Duplicate on other blog');
        $cur->setField('comment_spam_filter', $this->id);
        $cur->update(
            'WHERE comment_content = ' . $sql->quote($content) . ' ' .
            'AND comment_ip = ' . $sql->quote($ip) . ' ' .
            'AND post_id IN (' . $sql->statement() . ')'
        );

        $this->triggerOtherBlogs($content, $ip);
    }

    public function gui(string $url): string
    {
        if (!App::blog()->isDefined()) {
            return '';
        }

        if (App::auth()->isSuperAdmin()) {
            My::settings()->drop(My::SETTING_PREFIX . 'minlen');
            if (isset($_POST[My::SETTING_PREFIX . 'minlen'])) {
                My::settings()->put(
                    My::SETTING_PREFIX . 'minlen',
                    is_numeric($_POST[My::SETTING_PREFIX . 'minlen']) ? abs((int) $_POST[My::SETTING_PREFIX . 'minlen']) : 0,
                    'integer',
                    'Minimum lenght of comment to filter',
                    true,
                    true
                );
                Notices::addSuccessNotice(__('Configuration successfully updated.'));
                Http::redirect($url);
            }

            return
            (new Form(My::id() . '_gui'))->method('post')->action(Html::escapeURL($url))->fields([
                (new Para())->items([
                    (new Label(__('Minimum content length before check for duplicate:')))->for(My::SETTING_PREFIX . 'minlen'),
                    (new Input(My::SETTING_PREFIX . 'minlen'))->size(65)->maxlength(255)->value($this->getMinLength()),
                ]),
                (new Para())->items([
                    (new Submit('save'))->value(__('Save')),
                    App::nonce()->formNonce(),
                ]),
            ])->render();
        }

        return
        '<p class="info">' . sprintf(
            __('Super administrator set the minimum length of comment content to %d chars.'),
            $this->getMinLength()
        ) . '</p>';
    }

    private function getMinLength(): int
    {
        if (!App::blog()->isDefined()) {
            return 0;
        }

        $min = My::settings()->getGlobal(My::SETTING_PREFIX . 'minlen');

        return is_numeric($min) ? abs((int) $min) : 0;
    }

    public function triggerOtherBlogs(string $content, string $ip): void
    {
        $sql = new SelectStatement();
        $rs  = $sql->from($sql->as(App::db()->con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->column('P.blog_id')
            ->join(
                (new JoinStatement())
                ->left()
                ->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                ->on('C.post_id = P.post_id')
                ->statement()
            )
            ->where('C.comment_content = ' . $sql->quote($content))
            ->and('C.comment_ip=' . $sql->quote($ip))
            ->select();

        if (is_null($rs) || $rs->isEmpty()) {
            return;
        }

        $old = App::blog()->id();

        while ($rs->fetch()) {
            App::blog()->loadFromBlog($rs->strField('blog_id'))->triggerBlog();
        }

        App::blog()->loadFromBlog($old);
    }
}

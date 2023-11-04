<?php

declare(strict_types=1);

namespace Dotclear\Plugin\dcFilterDuplicate;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Database\Statement\{
    JoinStatement,
    SelectStatement
};
use Dotclear\Helper\Html\Form\{
    Form,
    Input,
    Label,
    Para,
    Submit
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

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

    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status)
    {
        if ($type != 'comment' || is_null($content) || is_null($ip)) {
            return null;
        }
        if (strlen($content) < $this->getMinLength()) {
            return null;
        }

        try {
            if ($this->isDuplicate($content, $ip)) {
                $this->markDuplicate($content, $ip);
                $status = 'Duplicate on other blog';

                return true;
            }

            return null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function isDuplicate(string $content, string $ip): bool
    {
        if (!App::blog()->isDefined()) {
            return false;
        }

        $sql = new SelectStatement();
        $rs  = $sql->from($sql->as(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->join(
                (new JoinStatement())
                ->left()
                ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
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
        $cur = App::blog()->openCommentCursor();
        App::con()->writeLock(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME);

        $cur->setField('comment_status', -2);
        $cur->setField('comment_spam_status', 'Duplicate on other blog');
        $cur->setField('comment_spam_filter', My::id());
        $cur->update(
            "WHERE comment_content='" . App::con()->escapeStr($content) . "' " .
            "AND comment_ip='" . $ip . "' "
        );
        App::con()->unlock();
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
                    abs((int) $_POST[My::SETTING_PREFIX . 'minlen']),
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
                    (new Input(My::SETTING_PREFIX . 'minlen'))->size(65)->maxlength(255)->value($this->getMinlength()),
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
            $this->getMinlength()
        ) . '</p>';
    }

    private function getMinLength(): int
    {
        if (!App::blog()->isDefined()) {
            return 0;
        }

        return abs((int) My::settings()->getGlobal(My::SETTING_PREFIX . 'minlen'));
    }

    public function triggerOtherBlogs(string $content, string $ip): void
    {
        $sql = new SelectStatement();
        $rs  = $sql->from($sql->as(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->column('P.blog_id')
            ->join(
                (new JoinStatement())
                ->left()
                ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
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
            App::blog()->loadFromBlog($rs->f('blog_id'))->triggerBlog();
        }

        App::blog()->loadFromBlog($old);
    }
}

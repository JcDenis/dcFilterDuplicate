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
declare(strict_types=1);

namespace Dotclear\Plugin\dcFilterDuplicate;

use dcBlog;
use dcCore;
use dcPage;
use dcSpamFilter;
use Dotclear\Helper\Html\Form\{
    Form,
    Input,
    Label,
    Para,
    Submit
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

/**
 * @ingroup DC_PLUGIN_DCFILTERDUPLICATE
 * @brief Filter duplicate comments on multiblogs.
 * @since 2.6
 */
class FilterDuplicate extends dcSpamFilter
{
    public $name    = 'Duplicate filter';
    public $has_gui = true;

    protected function setInfo(): void
    {
        $this->name        = __('Duplicate');
        $this->description = __('Same comments on others blogs of a multiblog');
    }

    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status): ?bool
    {
        if ($type != 'comment') {
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

    public function isDuplicate($content, $ip): bool
    {
        $rs = dcCore::app()->con->select(
            'SELECT C.comment_id ' .
            'FROM ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' C ' .
            'LEFT JOIN ' . dcCore::app()->prefix . 'post P ON C.post_id=P.post_id ' .
            "WHERE P.blog_id != '" . dcCore::app()->blog->id . "' " .
            "AND C.comment_content='" . dcCore::app()->con->escapeStr($content) . "' " .
            "AND C.comment_ip='" . $ip . "' "
        );

        return !$rs->isEmpty();
    }

    public function markDuplicate($content, $ip): void
    {
        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);
        dcCore::app()->con->writeLock(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);

        $cur->setField('comment_status', -2);
        $cur->setField('comment_spam_status', 'Duplicate on other blog');
        $cur->setField('comment_spam_filter', My::id());
        $cur->update(
            "WHERE comment_content='" . dcCore::app()->con->escapeStr($content) . "' " .
            "AND comment_ip='" . $ip . "' "
        );
        dcCore::app()->con->unlock();
        $this->triggerOtherBlogs($content, $ip);
    }

    public function gui(string $url): string
    {
        if (dcCore::app()->auth->isSuperAdmin()) {
            dcCore::app()->blog->settings->get(My::id())->drop(My::SETTING_PREFIX . 'minlen');
            if (isset($_POST[My::SETTING_PREFIX . 'minlen'])) {
                dcCore::app()->blog->settings->get(My::id())->put(
                    My::SETTING_PREFIX . 'minlen',
                    abs((int) $_POST[My::SETTING_PREFIX . 'minlen']),
                    'integer',
                    'Minimum lenght of comment to filter',
                    true,
                    true
                );
                dcPage::addSuccessNotice(__('Configuration successfully updated.'));
                Http::redirect($url);
            }

            return
            (new Form(My::id() . '_gui'))->method('post')->action(Html::escapeURL($url))->fields([
                (new Para())->items([
                    (new Label(__('Minimum content length before check for duplicate:')))->for(My::SETTING_PREFIX . 'minlen'),
                    (new Input(My::SETTING_PREFIX . 'minlen'))->size(65)->maxlenght(255)->value($this->getMinlength()),
                ]),
                (new Para())->items([
                    (new Submit('save'))->value(__('Save')),
                    dcCore::app()->formNonce(false),
                ]),
            ]);
        }

        return
        '<p class="info">' . sprintf(
            __('Super administrator set the minimum length of comment content to %d chars.'),
            $this->getMinlength()
        ) . '</p>';
    }

    private function getMinLength(): int
    {
        return abs((int) dcCore::app()->blog->settings->get(My::id())->getGlobal(My::SETTING_PREFIX . 'minlen'));
    }

    public function triggerOtherBlogs($content, $ip): void
    {
        $rs = dcCore::app()->con->select(
            'SELECT P.blog_id ' .
            'FROM ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' C ' .
            'LEFT JOIN ' . dcCore::app()->prefix . 'post P ON C.post_id=P.post_id ' .
            "WHERE C.comment_content='" . dcCore::app()->con->escapeStr($content) . "' " .
            "AND C.comment_ip='" . $ip . "' "
        );

        while ($rs->fetch()) {
            $b = new dcBlog($rs->f('blog_id'));
            $b->triggerBlog();
            unset($b);
        }
    }
}

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

/**
 * @ingroup DC_PLUGIN_DCFILTERDUPLICATE
 * @brief Filter duplicate comments on multiblogs.
 * @since 2.6
 */
class dcFilterDuplicate extends dcSpamFilter
{
    public $name    = 'Duplicate filter';
    public $has_gui = true;

    protected function setInfo()
    {
        $this->name        = __('Duplicate');
        $this->description = __('Same comments on others blogs of a multiblog');
    }

    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
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

    public function isDuplicate($content, $ip)
    {
        $rs = dcCore::app()->con->select(
            'SELECT C.comment_id ' .
            'FROM ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' C ' .
            'LEFT JOIN ' . dcCore::app()->prefix . 'post P ON C.post_id=P.post_id ' .
            "WHERE P.blog_id != '" . dcCore::app()->blog->id . "' " .
            "AND C.comment_content='" . dcCore::app()->con->escape($content) . "' " .
            "AND C.comment_ip='" . $ip . "' "
        );

        return !$rs->isEmpty();
    }

    public function markDuplicate($content, $ip)
    {
        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);
        dcCore::app()->con->writeLock(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);

        $cur->comment_status      = -2;
        $cur->comment_spam_status = 'Duplicate on other blog';
        $cur->comment_spam_filter = 'dcFilterDuplicate';
        $cur->update(
            "WHERE comment_content='" . dcCore::app()->con->escape($content) . "' " .
            "AND comment_ip='" . $ip . "' "
        );
        dcCore::app()->con->unlock();
        $this->triggerOtherBlogs($content, $ip);
    }

    public function gui(string $url): string
    {
        if (dcCore::app()->auth->isSuperAdmin()) {
            dcCore::app()->blog->settings->dcFilterDuplicate->drop('dcfilterduplicate_minlen');
            if (isset($_POST['dcfilterduplicate_minlen'])) {
                dcCore::app()->blog->settings->dcFilterDuplicate->put(
                    'dcfilterduplicate_minlen',
                    abs((int) $_POST['dcfilterduplicate_minlen']),
                    'integer',
                    'Minimum lenght of comment to filter',
                    true,
                    true
                );
                dcPage::addSuccessNotice(__('Configuration successfully updated.'));
                http::redirect($url);
            }

            return
            '<form action="' . html::escapeURL($url) . '" method="post">' .
            '<p><label class="classic">' . __('Minimum content length before check for duplicate:') . '<br />' .
            form::field(
                ['dcfilterduplicate_minlen'],
                65,
                255,
                $this->getMinlength(),
            ) . '</label></p>' .
            '<p><input type="submit" name="save" value="' . __('Save') . '" />' .
            dcCore::app()->formNonce() . '</p>' .
            '</form>';
        }

        return
        '<p class="info">' . sprintf(
            __('Super administrator set the minimum length of comment content to %d chars.'),
            $this->getMinlength()
        ) . '</p>';
    }

    private function getMinLength()
    {
        return abs((int) dcCore::app()->blog->settings->dcFilterDuplicate->getGlobal('dcfilterduplicate_minlen'));
    }

    public function triggerOtherBlogs($content, $ip)
    {
        $rs = dcCore::app()->con->select(
            'SELECT P.blog_id ' .
            'FROM ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' C ' .
            'LEFT JOIN ' . dcCore::app()->prefix . 'post P ON C.post_id=P.post_id ' .
            "WHERE C.comment_content='" . dcCore::app()->con->escape($content) . "' " .
            "AND C.comment_ip='" . $ip . "' "
        );

        while ($rs->fetch()) {
            $b = new dcBlog($rs->blog_id);
            $b->triggerBlog();
            unset($b);
        }
    }
}

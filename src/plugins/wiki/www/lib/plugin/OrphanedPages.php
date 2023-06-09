<?php
/**
 * This file is part of PhpWiki.
 *
 * PhpWiki is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * PhpWiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with PhpWiki; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 *
 */

/**
 * A plugin which returns a list of pages which are not linked to by
 * any other page
 *
 * Initial version by Lawrence Akka
 *
 */

require_once 'lib/PageList.php';

class WikiPlugin_OrphanedPages
    extends WikiPlugin
{
    function getDescription()
    {
        return _("List pages which are not linked to by any other page.");
    }

    function getDefaultArguments()
    {
        return array('noheader' => false,
            'include_empty' => false,
            'exclude' => '',
            'info' => '',
            'sortby' => false,
            'limit' => 0,
            'paging' => 'auto',
        );
    }

    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor,markup or all
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges

    /**
     * @param WikiDB $dbi
     * @param string $argstr
     * @param WikiRequest $request
     * @param string $basepage
     * @return mixed
     */
    function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);

        if (isset($args['limit']) && !is_limit($args['limit'])) {
            return HTML::p(array('class' => "error"),
                           _("Illegal “limit” argument: must be an integer or two integers separated by comma"));
        }

        extract($args);

        if (($noheader == '0') || ($noheader == 'false')) {
            $noheader = false;
        } elseif (($noheader == '1') || ($noheader == 'true')) {
            $noheader = true;
        } else {
            return $this->error(sprintf(_("Argument '%s' must be a boolean"), "noheader"));
        }

        if (($include_empty == '0') || ($include_empty == 'false')) {
            $include_empty = false;
        } elseif (($include_empty == '1') || ($include_empty == 'true')) {
            $include_empty = true;
        } else {
            return $this->error(sprintf(_("Argument '%s' must be a boolean"), "include_empty"));
        }

        // There's probably a more efficient way to do this (eg a
        // tailored SQL query via the backend, but this does the job

        $allpages_iter = $dbi->getAllPages($include_empty);
        $pages = array();
        while ($page = $allpages_iter->next()) {
            $links_iter = $page->getBackLinks();
            // Test for absence of backlinks. If a page is linked to
            // only by itself, it is still an orphan
            $parent = $links_iter->next();
            if (!$parent // page has no parents
                or (($parent->getName() == $page->getName())
                    and !$links_iter->next())
            ) // or page has only itself as a parent
            {
                $pages[] = $page;
            }
        }
        $args['count'] = count($pages);
        $pagelist = new PageList($info, $exclude, $args);
        if (!$noheader)
            $pagelist->setCaption(_("Orphaned Pages in this wiki (%d total):"));
        // deleted pages show up as version 0.
        if ($include_empty)
            $pagelist->_addColumn('version');
        list($offset, $pagesize) = $pagelist->limit($args['limit']);
        if (!$pagesize) $pagelist->addPageList($pages);
        else {
            for ($i = $offset; $i < $offset + $pagesize - 1; $i++) {
                if ($i >= $args['count']) break;
                $pagelist->addPage($pages[$i]);
            }
        }
        return $pagelist;
    }
}

<?php
/**
 * Search Engine
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2004 (c) Guillaume Smet / Open Wide
 * Copyright 2013, French Ministry of National Education
 * http://fusionforge.org
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require_once $gfwww.'search/include/renderers/HtmlSearchRenderer.class.php';
require_once $gfcommon.'search/ProjectSearchQuery.class.php';

class ProjectHtmlSearchRenderer extends HtmlSearchRenderer {

	/**
	 * @param string $words words we are searching for
	 * @param int $offset offset
	 * @param bool $isExact if we want to search for all the words or if only one matching the query is sufficient
	 */
	function __construct($words, $offset, $isExact) {

		$searchQuery = new ProjectSearchQuery($words, $offset, $isExact);

		parent::__construct(SEARCH__TYPE_IS_SOFTWARE, $words, $isExact, $searchQuery);

		$this->tableHeaders = array(
			_('Project Name'),
			_('Description')
		);
	}

	/**
	 * writeHeader - write the header of the output
	 */
	function writeHeader() {
		$GLOBALS['HTML']->header(array('title'=>_('Project Search'), 'pagename'=>'search'));
		parent::writeHeader();
	}

	/**
	 * getRows - get the html output for result rows
	 *
	 * @return string html output
	 */
	function getRows() {
		$result = $this->searchQuery->getData($this->searchQuery->getRowsPerPage(),$this->searchQuery->getOffset());

		$return = '';
		$i = 0;

		foreach ($result as $row) {
			$i++;
			$return .= '<tr>'
				.'<td style="width: 30%">'.util_make_link('/projects/'.$row['unix_group_name'].'/', html_image('ic/msg.png', 10, 12).' '.$this->highlightTargetWords($row['group_name'])).'</td>'
				.'<td style="width: 70%">'.$this->highlightTargetWords($row['short_description']).'</td></tr>';
		}

		return $return;
	}

	/**
	 * redirectToResult - redirect the user  directly to the result when there is only one matching result
	 */
	function redirectToResult() {
		$result = $this->searchQuery->getData(1)[0];
		$project_name = $result['unix_group_name'];
		$project_id = $result['group_id'];

		$project_name = str_replace('<strong>', '', $project_name);
		$project_name = str_replace('</strong>', '', $project_name);

		if (forge_check_perm('project_read', $project_id)) {
			header('Location: '.util_make_url_g($project_name));
		} else {
			$this->writeHeader();
			$html = '<h2>'.sprintf(_('Search results for “%s”'), $project_name).'</h2>';
			$html .= '<p><strong>'.sprintf(_('No matches found for “%s”'), $project_name).'</strong></p>';
			echo $html;
			$this->writeFooter();
		}
		exit();
	}
}

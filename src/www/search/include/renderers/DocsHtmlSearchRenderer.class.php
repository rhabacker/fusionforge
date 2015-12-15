<?php
/**
 * Search Engine
 *
 * Copyright 2004 (c) Dominik Haas, GForge Team
 * Copyright (C) 2012 Alain Peyrat - Alcatel-Lucent
 * Copyright 2013,2015 Franck Villaume - TrivialDev
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

require_once $gfwww.'search/include/renderers/HtmlGroupSearchRenderer.class.php';
require_once $gfcommon.'search/DocsSearchQuery.class.php';
require_once $gfcommon.'docman/Document.class.php';

class DocsHtmlSearchRenderer extends HtmlGroupSearchRenderer {

	/**
	 * Constructor
	 *
	 * @param string $words words we are searching for
	 * @param int $offset offset
	 * @param boolean $isExact if we want to search for all the words or if only one matching the query is sufficient
	 * @param int $groupId group id
	 * @param array|string $sections array of all sections to search in (array of strings)
	 *
	 */
	function DocsHtmlSearchRenderer($words, $offset, $isExact, $groupId, $sections = SEARCH__ALL_SECTIONS, $rowsPerPage = SEARCH__DEFAULT_ROWS_PER_PAGE, $options = array()) {

		$userIsGroupMember = $this->isGroupMember($groupId);

		$searchQuery = new DocsSearchQuery($words, $offset, $isExact, array($groupId), $sections, $userIsGroupMember, $rowsPerPage, $options);

		$this->HtmlGroupSearchRenderer(SEARCH__TYPE_IS_DOCS, $words, $isExact, $searchQuery, $groupId, 'docman');

		$this->tableHeaders = array(
			_('Directory'),
			_('&nbsp;'),
			_('Title'),
			_('Description'),
			_('Actions')
		);
	}

	/**
	 * getRows - get the html output for result rows
	 *
	 * @return string html output
	 */
	function getRows() {
		if (!forge_check_perm('docman', $this->groupId, 'read')) {
			return '';
		}

		global $HTML;
		global $LUSER;
		$rowsCount = $this->searchQuery->getRowsCount();
		$result =& $this->searchQuery->getResult();

		$return = '';

		$lastDocGroupID = null;

		$rowColor = 0;
		if ($rowsCount) {
			use_javascript('/docman/scripts/DocManController.js');
			$return .= $HTML->getJavascripts();
			$return .= $HTML->getStylesheets();
			echo html_ao('script', array('type' => 'text/javascript'));
			?>
			//<![CDATA[
			var controllerListFile;

			jQuery(document).ready(function() {
				controllerListFile = new DocManListFileController({
					groupId:		<?php echo $this->groupId ?>,
					docManURL:		'<?php echo util_make_uri('/docman') ?>',
					childGroupId:		<?php echo util_ifsetor($childgroup_id, 0) ?>,
				});
			});

			//]]>
			<?php
			echo html_ac(html_ap() - 1);
		}
		for($i = 0; $i < $rowsCount; $i++) {
			$document = document_get_object(db_result($result, $i, 'docid'));
			$currentDocGroup = documentgroup_get_object($document->getDocGroupID());
			//section changed
			if ($lastDocGroupID != $currentDocGroup->getID()) {
				//project changed
				$content = '';
				if ($this->groupId != $currentDocGroup->Group->getID()) {
					$content = _('Project')._(': ').util_make_link('/docman/?group_id='.$currentDocGroup->Group->getID(),$currentDocGroup->Group->getPublicName()).' ';
				}
				$cells = array();
				$cells[] = array($content.html_image('ic/folder.png', 22, 22, array('border' => '0')).$currentDocGroup->getPath(true), 'colspan' => 4);
				$lastDocGroupID = $currentDocGroup->getID();
				$return .= $HTML->multiTableRow(array(), $cells);
				$rowColor = 0;
			}
			$cells = array();
			if (!$document->getLocked() && !$document->getReserved()) {
				$cells[][] = html_e('input', array('type' => 'checkbox', 'value' => $document->getID(), 'class' => 'checkeddocidactive', 'title' => _('Select / Deselect this document for massaction'), 'onClick' => 'controllerListFile.checkgeneral("active")'));
			} else {
				if (session_loggedin() && ($document->getReservedBy() != $LUSER->getID())) {
					$cells[][] = html_e('input', array('type' => 'checkbox', 'name' => 'disabled', 'disabled' => 'disabled'));
				} else {
					$cells[][] = html_e('input', array('type' => 'checkbox', 'value' => $document->getID(), 'class' => 'checkeddocidactive', 'title' => _('Select / Deselect this document for massaction'), 'onClick' => 'controllerListFile.checkgeneral("active")'));
				}
			}
			$cells[][] = util_make_link('/docman/view.php/'.$document->Group->getID().'/'.$document->getID().'/'.urlencode($document->getFileName()), html_image($document->getFileTypeImage(), 22, 22), array('title' => _('View this document')));
			$cells[][] = db_result($result, $i, 'title');
			$cells[][] = db_result($result, $i, 'description');
			if (forge_check_perm('docman', $document->Group->getID(), 'approve')) {
				if (!$document->getLocked() && !$document->getReserved()) {
					$cells[][] = util_make_link('/docman/?group_id='.$document->Group->getID().'&view=listfile&dirid='.$document->getDocGroupID().'&filedetailid='.$document->getID(), html_image('docman/edit-file.png', 22, 22, array('alt' => _('Edit this document'))), array('title' => _('Edit this document')));
				} else {
					$cells[][] = '&nbsp;';
				}
			} else {
				$cells[][] = '&nbsp;';
			}
			$return .= $HTML->multiTableRow(array('class' => $HTML->boxGetAltRowStyle($rowColor, true)), $cells);
			$rowColor++;
		}
		$content = html_ao('span', array('id' => 'massactionactive', 'class' => 'hide'));
		$content .=  html_e('span', array('id' => 'docman-massactionmessage', 'title' => _('Actions availables for selected documents, you need to check at least one document to get actions')), _('Mass actions for selected documents')._(':'), false);
		$content .= util_make_link('#', html_image('docman/download-directory-zip.png', 22, 22, array('alt' => _('Download as a ZIP'))) , array('onclick' => 'window.location.href=\''.util_make_uri('/docman/view.php/'.$this->groupId.'/zip/selected/files/\'+controllerListFile.buildUrlByCheckbox("active")'), 'title' => _('Download as a ZIP')), true);
		$content .= html_ac(html_ap() - 1);
		$cells = array();
		$cells[] = array($content, 'colspan' => 4);
		$return .= $HTML->multiTableRow(array(), $cells);
		return $return;
	}

	/**
	 * getSections - get the array of possible sections to search in
	 *
	 * @param int $groupId
  	 * @return array sections
	 */
	static function getSections($groupId) {
		$userIsGroupMember = DocsHtmlSearchRenderer::isGroupMember($groupId);

		return DocsSearchQuery::getSections($groupId, $userIsGroupMember);
	}
}

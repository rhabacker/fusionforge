<?php
/*
 * Adullact V3 theme
 *
 * Permission to use, copy, modify, distribute, and sell this software and its
 * documentation for any purpose is hereby granted without fee, provided that
 * the above copyright notice appear in all copies and that both that
 * copyright notice and this permission notice appear in supporting
 * documentation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN
 * AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the author shall not be
 * used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the author.

 *
 * Based on the "debian" theme, which is apparantly :-
 * 		Copyright 1999-2001 (c) VA Linux Systems
 *
 * @version   $Id: Theme.class,v 1.12 2004/03/25 17:38:54 tperdue Exp $
*/

require_once $gfwww.'include/Layout.class.php';

class Theme extends Layout {

    /**
     * Theme() - Constructor
     */
    function Theme() {

        // Parent constructor
        $this->Layout();
        $this->imgproj = '/themes/adullact/images/proj/';

        // ------------------------------------------------
        // variables introduced for theme "adullact-v3"
        // ------------------------------------------------

        $this->themeroot	= '/themes/adullact-v3';
        $this->cssroot		= $this->themeroot . '/css/';
        $this->jsroot		= $this->themeroot . '/js/';
        $this->imageroot	= $this->themeroot . '/image/';		// imageroot is a transition variable to set up adullact-v3 theme
        $this->imgroot		= $this->imageroot; 				// imgroot is used all over the gforge code
        $this->rootindex	= 'www' . $this->themeroot . '/index-adullact-v3.php';

        //used for titles (h) generation
        $this->hrank    = 1;

	$this->addStylesheet('/scripts/yui/reset-fonts-grids/reset-fonts-grids.css');
	$this->addStylesheet('/scripts/yui/base/base-min.css');
	$this->addStylesheet('/themes/css/fusionforge.css');
	$this->addStylesheet($this->cssroot .'adullact-v3.css');

    }

    /**
     *	header() - "steel theme" top of page
     *
     * @param	array	Header parameters array
     */
    function header($params) {
        if (!$params['title']) {
            $params['title'] = forge_get_config('forge_name');
        } else {
            $params['title'] = forge_get_config('forge_name').": " . $params['title'];
        }

        echo '
		<?xml version="1.0" encoding="utf-8"?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'. _('en') .'" lang="'. _('en') .'">
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8;" />
		<title>'. $params['title'] .'</title>';

	$this->headerFavIcon();
	$this->headerRSS();
	$this->headerSearch();
	$this->headerCSS();
	$this->headerJS(); 

        echo '<script type="text/javascript" src="'. $this->jsroot .'adullact-v3.js">';
        plugin_hook ("javascript",false);
        echo '</script>';

	$this->headerForgepluckerMeta(); 
	echo '</head>
		<body>';

        $this->bodyHeader($params);
    }

    function headerCSS() {
        plugin_hook ('cssfile',$this);
	echo $this->getStylesheets();
    }

    function bodyHeader($params) {
        echo '
		<div id="doc2" class="lien-soulignement">
		<table id="hd" summary="">
		<tr>
			<td id="hd-col1">
			<h1><a href="/"> <img id="gforge-logo"
				src="'. $this->imageroot .'hd_logo_gforge_adullact_false.gif"
				alt="Accueil FusionForge Adullact.net" /> </a></h1>
			</td>
			<td id="hd-col2">

			<div id="login-box" class="lien-soulignement">';
        if (session_loggedin()) {
            echo '
				<a id="login-box-logout" href="/account/logout.php">'. _('Log Out') .'</a>
				<a id="login-box-myaccount" href="/account/">'. _('My Account') .'</a>';
        } else {
            echo '
				<a id="login-box-login" href="/account/login.php">'. _('Log In') .'</a>
				<a id="login-box-newaccount" href="/account/register.php">'. _('New Account') .'</a>';
        }
        echo '
			</div> <!-- id="login-box" -->
			<div id="search-box">';
        echo $this->searchBox();
        echo '
			</div><!-- id="search-box" -->
			<div id="nav" class="lien-soulignement">';
        echo $this->outerTabs($params);
        echo '
			</div><!-- id="nav" -->
			</td>
		</tr>
		</table><!-- id="hd" -->
		<div id="nav2" class="lien-soulignement">
        ';

        plugin_hook ('headermenu', $params);

        if (isset($params['group'])) {
            echo $this->projectTabs($params['toptab'],$params['group']);
        }
        echo '</div><!-- id="nav2" -->
<div id="maindiv">';

	if(isset($GLOBALS['error_msg']) && $GLOBALS['error_msg']) {
		echo $this->error_msg($GLOBALS['error_msg']);
	}
	if(isset($GLOBALS['warning_msg']) && $GLOBALS['warning_msg']) {
		echo $this->warning_msg($GLOBALS['warning_msg']);
	}
	if(isset($GLOBALS['feedback']) && $GLOBALS['feedback']) {
		echo $this->feedback($GLOBALS['feedback']);
	}

	if (isset($params['submenu']))
		echo $params['submenu'];
    }

    /**
     *	footer() - bottom of page
     *
     * @param	array	Footer parameters array
     */

    function footer($params) {
        echo '
	        </div> <!-- maindiv -->
		<div id="ft" class="lien-soulignement">
		<table summary="">
			<tr>
				<td id="ft-col1"><img src="'. $this->imageroot .'logo_feder.jpg" alt="FEDER" /></td>
				<td id="ft-col2">
					<a href="http://pole-aquinetic.fr/179-H-bergement-de-la-forge-Adullact">
					<img src="'. $this->imageroot .'logo_aquinetic.png" alt="AQUINETIC Aquitaine" />
					</a>
</td>
				<td id="ft-col3">
					<a href="http://fusionforge.org/">
					<img src="'. util_make_url ('/images/pow-fusionforge.png') .'" alt="Powered By FusionForge Collaborative Development Environment" />
					</a>
				</td>
				<td id="ft-col4">
					<a href="/themes/adullact-v3/charte-adullact.php">Charte d\'utilisation</a> / 
					<a href="mailto:support@adullact.org">Nous contacter</a> / 
					<a href="/themes/adullact-v3/mentionslegales.php">Mentions l&eacute;gales</a> 
			';

        if (forge_get_config('show_source')) {
            global $SCRIPT_NAME;
            echo '<a class="showsource" href="/source.php?file=' . $SCRIPT_NAME . '">Show Source</a>';
        }

        echo '
				</td>
				<td id="ft-col5"><a href="#hd" class="gototop">Haut de page</a></td>
			</tr>
			</table>
			</div>
		</div><!-- id="doc2" -->
		</body>
		</html>
		';
    }

    /**
     * boxTop() - Top HTML box
     * @param string Box title
     * @param  bool Whether to echo or return the results
     * @param string The box background color
     */
    function boxTop($title, $id = '') {
        $t_result = '
        	<div id="'. $this->toSlug($id) .'" class="box-surround">
            	<div id="'. $this->toSlug($id) . '-title" class="box-title">
            		<div class="box-title-left">
            			<div class="box-title-right">
                			<h3 class="box-title-content bordure-dessous" id="'. $this->toSlug($id) .'-title-content">'. $title .'</h3>
                		</div>
                	</div>
                </div>
            	<div id="'. $this->toSlug($id) .'-content" class="box-content">
            ';
        return $t_result;
    }

    /** * boxMiddle() -	 Middle HTML box
     *  @param string Box title
     *  @param string The box background color
     */
    function boxMiddle($title, $id = 'boxMiddle') {
        $t_result ='
	        	</div> <!-- class="box-content" -->
	        <h3 id="title-'. $this->toSlug($id).'" class="box-middle bordure-dessous">'.$title.'</h3>
	       	<div class="box-content">
        ';
        return $t_result;
    }

    /**
     * boxBottom() - Bottom HTML box
     *
     */
    function boxBottom() {
        $t_result='
                </div>
            </div> <!-- class="box-surround" -->
		';
        return $t_result;
    }

    /**
     * boxGetAltRowStyle() - Get an alternating row style for tables
     *  @param int Row number
     */
    function boxGetAltRowStyle($i) {
        switch ($i % 2 ) {
            case 0: return 'class="bgcolor-white"';
            case 1:
                return 'class="bgcolor-grey"';
        }
    }

    /**
     * listTableTop() - Takes an array of titles and builds the first row of a new table.
     *
     * @param	array	The array of titles
     * @param	array	The array of title links
     * @param	string	The css classes to add (optional)
     * @param	string	The id of the table (needed by sortable for example)
     * @param	array	specific class for th column
     * @return	string	the html code
     */
    function listTableTop($titleArray, $linksArray=false, $class='', $id='', $thClassArray=array()) {
	    $args = '';
	    if ($class) {
		    $args .= ' class="listing '.$class.' adullact-data-table"';
	    } else {
		    $args .= ' class="listing full adullact-data-table"';
	    }
	    if ($id) {
		    $args .= ' id="'.$id.'"';
	    }
	    $return = "\n".
		    '<table'.$args.'>';
		
	    if (count($titleArray)) {
		    $return .= '<thead><tr>';

		    $count=count($titleArray);
		    for ($i=0; $i<$count; $i++) {
			    $th = '';
			    if ($thClassArray && $thClassArray[$i]) {
				    $th .= ' class="titlebar '.$thClassArray[$i].'"';
			    } else {
				    $th .= ' class="titlebar"';
			    }
			    $cell = $titleArray[$i];
			    if ($linksArray) {
				    $cell = util_make_link($linksArray[$i],$titleArray[$i]);
			    }
			    $return .= "\n".' <th'.$th.'>'.$cell.'</th>';
		    }
		    $return .= "\n".'</tr></thead>'."\n";
	    }
	    $return .= '<tbody>';
	    return $return;
    }

    function listTableBottom() {
        return "
</tbody>
            </table>
            <!--class=\"adullact-data-table\" -->
            \n";
    }

    /**
     * tabGenerator
     */
    function tabGeneratorWrapper($TABS_DIRS,$TABS_TITLES,$nested=false,$selected=false,$sel_tab_bgcolor='WHITE',$total_width='100%') {
        $t_return = tabGeneratorWithCSS($TABS_DIRS, $TABS_TITLES, $nested,
            $selected, $sel_tab_bgcolor, $total_width);
        print "
            <!--$TABS_DIRS -->
            \n";
        print "
            <!-- $TABS_TITLES -->
            \n";
        print "
            <!-- $nested -->
            \n";
        print "
            <!-- $selected -->
            \n";
        print "
            <!-- $sel_tab_bgcolor -->
            \n";
        print "
            <!-- $total_width -->
            \n";
        //$t_return = tabGeneratorWithTable($TABS_DIRS,$TABS_TITLES,$nested,$selected,$sel_tab_bgcolor,$total_width);
        return $t_return;
    }

    /**
     * tabGenerator (with CSS) * */ function
    tabGeneratorWithCSS($TABS_DIRS,$TABS_TITLES,$nested=false,$selected=false,$sel_tab_bgcolor='WHITE',$total_width='100%') {
        $count=count($TABS_DIRS);
        $return .= '
            <!-- tabGenerator (with CSS/lists) -->
            <ul class="tab">
                ';
        if ($nested) {
            $inner='-inner';
        } else {
            $inner='';
        } for


        ($i=0;
        $i<$count; $i++) {
            $TABS_TITLES[$i] = preg_replace("/ +/", "&nbsp;",
                $TABS_TITLES[$i]);
            if ( $selected == $i ) {
                $return .= '
                	<li class="tab-selected"><span><a href="' . $TABS_DIRS[$i] . '">' .
                    $TABS_TITLES[$i] . '</a></span></li>
                    ';
            } else {
                $return .= '
                    <li><span><a href="' . $TABS_DIRS[$i] . '">' . $TABS_TITLES[$i] . '</a></span></li>
                    ';
            }
        }
        $return .= '
                </ul>
                ';
        return $return;
    }

    /**
     * tabGenerator with table (former function)
     */
    function tabGenerator($TABS_DIRS, $TABS_TITLES, $TABS_TOOLTIPS, $nested = false,
        $selected = false, $sel_tab_bgcolor = 'WHITE', $total_width = '100%') {

        $deselect_tab='style="background-image:url('.$this->imgroot.'tabs/deselect.png);"';
        $select_tab='style="background-image:url('.$this->imgroot.'tabs/select.png);"';
        $deselect_rule='style="background-image:url('.$this->imgroot.'tabs/ruledeselect.png);"';
        $select_rule='style="background-image:url('.$this->imgroot.'tabs/ruleselect.png);"';
        $count=count($TABS_DIRS);
        $width=intval((100/($count+1)));
        $space=intval($width/2);
        $return = '
            <!-- tabGenerator (with table) -->

            <table width="'.$total_width.'">
                <tr>
                    ';
        if ($nested) {
            $inner='-inner';
        } else {
            $inner='';
        } for


        ($i=0;
        $i<$count; $i++) {
            $TABS_TITLES[$i] = preg_replace("/ +/", "&nbsp;",
                $TABS_TITLES[$i]);
            $bgimg=(($selected==$i)?$select_tab:$deselect_tab);
            $cornerimg=(($selected==$i)?'select':'deselect');
            $return .= '
            <td ' . 'style="width: ' . $width . ' %"
                class="align-center' . (($selected==$i)?' tab-selected':'') . '">';
            /* if ( $selected == $i ) { // if selected tab then no link $return .=
				 $TABS_TITLES[$i]; } else { $return .= '<a href="'. $TABS_DIRS[$i] .'">'.
				 $TABS_TITLES[$i] .'</a>'; }
            */
            $return .= '<a href="'. $TABS_DIRS[$i] .'">'. $TABS_TITLES[$i] .'</a>';
            $return .= "</td> \n";
        }
        $return .= "</tr>\n";
        $return .= "</table>
            \n
            <!-- end tabGenerator -->
            \n";
        return $return;
    }

    function getMonitorPic($title = '', $alt = '') {
        return $this->getPicto('picto_gris_enveloppe.png', $title, $alt);
    }

    function getReleaseNotesPic($title = '', $alt = '') {
        return
            $this->getPicto('picto_remarques.png', $title, $alt);
    }
    
    function getDownloadPic($title = '', $alt = '') {
        return
            $this->getPicto('picto_gris_telecharger.png', $title, $alt);
    }

    function getHomePic($title = '', $alt = '') {
        return
            $this->getPicto('picto_gris_accueil.png', $title, $alt);
    }
    
    function getFollowPic($title = '', $alt = '') {
        return
            $this->getPicto('picto_gris_outils_de_suivi.png', $title, $alt);
    }

    function getForumPic($title = '', $alt = '') {
        return
            $this->getPicto('picto_gris_forum.png', $title, $alt);
    }
    
    function getDocmanPic($title = '', $alt = '') {
        return
            $this->getPicto('picto_gris_gestion_de_documents.png', $title, $alt);
    }

    function getMailPic($title = '', $alt = '') {
        return
            $this->getPicto('picto_gris_listes_de_diffusion.png', $title, $alt);
    }

    function getPmPic($title = '', $alt = '') {
        return
            $this->getPicto('picto_gris_gestion_des_taches.png', $title, $alt);
    }

    function getScmPic($title = '', $alt = '') {
        return
            $this->getPicto('picto_gris_cvs.png', $title, $alt);
    }
    
    function getFtpPic($title = '', $alt = '') {
        return $this->getPicto('picto_gris_telecharger.png', $title, $alt);
    }
    
    function getPicto($url, $title, $alt, $width = '21', $height = '21') {
        if (!$alt) {
            $alt = $title;
        }
        return html_image($url, $width, $height, array('title'=>$title, 'alt'=>$alt));
    }


}
?>

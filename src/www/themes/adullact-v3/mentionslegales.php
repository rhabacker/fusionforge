<?php
require_once('../../env.inc.php');
require_once('pre.php');    // Initial db and session library, opens session
$HTML->header(array('title'=>_('Welcome'),'pagename'=>'home'));
?>

<!-- whole page table -->
<table id="bd" summary="" class="lien-soulignement">
<tr>
	<td id="bd-col1" class="col1">
		
		<div id="charte-adullact">
			<div> <!-- empty div useful for design -->


<h2>Mentions légales</h2>

<ul>
<li><em>Editeur</em> : ADULLACT</li>
<li><em>Directeur de la publication</em> : Pascal Kuczynski, directeur technique ADULLACT</li>
<li><em>Hébergement</em> : <a href="http://www.citic74.fr/">CITIC74</a>, partenaire de l'ADULLACT<br>
Le CITIC74 (Centre de l’Informatique et des TIC de Haute-Savoie) est dédié aux structures publiques du département 74.
</li>
</ul>

			</div>
		</div><!-- id="charte-adullact" -->

	</td><!-- id="bd-col1" -->
  </tr>
</table><!-- id="bd" -->

<?php
$HTML->footer(array());
?>

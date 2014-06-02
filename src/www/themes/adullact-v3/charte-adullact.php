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


<h2>Conditions d'utilisation de la forge Adullact.net</h2>

<p>En utilisant Adullact.net, vous reconnaissez être lié par les termes des
présentes conditions d'utilisation de la plate-forme.</p>

<h2>Description du service</h2>

<p>Adullact.net est un service d'hébergement et de travail collaboratif consacré
au développement de logiciels libres métiers. Les services offerts en ligne
vont de l'hébergement versionné du code source en passant par des outils de
communication forums, listes de diffusions, jusqu'aux outils de reporting
d'anomalies ou de planning.</p>

<h2>Ouverture d'un projet</h2>

<p>Toute personne peut ouvrir un compte, et proposer un projet. L'Adullact se
réserve le droit de refuser l'hébergement d'un projet qui ne présenterait pas
d'intérêt pour la sphère de l'argent public au sens large, ou/et qui ne serait
pas sous une licence GPL-compatible ou reconnue par l'OSI. Chaque projet est
sous la responsabilité de l'administrateur à l'initiative du dépôt sur
Adullact.net. Il est libre d'allouer et organiser les moyens et les ressources
utiles au développement et au support du projet.</p>

<h2>Accès à la plate-forme</h2>

<p>L'accès à Adullact.net est public. Toute personne peut visualiser, télécharger,
réutiliser, les éléments en ligne, dans le respect des licences associées à ces
éléments.</p>

<h2>Responsabilité relative au contenu</h2>

<p>Tout contenu, c'est-à-dire information, donnée, texte, logiciel, musique, son,
photographie, vidéo, message ou matériau de quelque type que ce soit, transmis
et conservé sur Adullact.net, est sous la seule responsabilité de la personne à
l'origine du dépôt. Ni le Centre de Ressources Informatique de Haute-Savoie,
qui héberge le service, ni les équipes de l'Adullact, ne sont pas responsables
des contenus déposés sur Adullact.net. L'Adullact s'engage à retirer tout
contenu illicite conformément aux dispositions de la loi n°2004-575 du 21 juin
2004 pour la confiance dans l'économie numérique. Les ressources collaboratives
ne peuvent être utilisées comme supports de publicité ou de prospection
commerciale.</p>

<h2>Retrait d'un projet</h2>

<p>Le retrait d'un projet est admis, sur demande unanime de ses administrateurs.
L'ensemble des traces, données, documents, relatifs au projet sont alors remis
aux administrateurs. Il pourra être mis fin au compte Adullact.net d'une
personne pour les raisons suivantes, sans que cette liste soit limitative :</p>

<ul>
  <li>En cas d'agissement détournant l'usage ou mettant en péril la plate-forme</li>

  <li>En cas de violation de lois en vigueur et à la demande de la Justice</li>
</ul>

<h2>Limitation de garantie</h2>

<p>L'Adullact ne donne aucune garantie de service en termes de disponibilité du
service et de préservations des données. L'Adullact s'engage engage à faire
opérer le service de la forge Adullact.net en accord avec l'état de l'art,
particulièrement en terme de conservation de l'intégrité des données stockées.</p>

<h2>Particularités techniques</h2>

<ul>
  <li>Toutes les données stockées sur la forge sont sauvegardées quotidiennement
    à 04H00.</li>

  <li>Les développeurs peuvent facilement oeuvrer leurs nighty-builds via les
    snapshot (export CVS) automatiques réalisés chaque nuit, pour chaque projet
    à 04H00.</li>

  <li>La home page de la forge Adullact affiche un outil de recherche
    multi-forges. Les forges partenaire d'Adullact pour le projet HEPHAISTOS
    sont référencées.</li>

  <li>Chaque administrateur d'un projet a la possibilité de gérer les onglets
    qu'il souhaite proposer pour son projet (cf. page d'administration du
    projet), et d'ajouter ses propres onglets qui seront de simples URLs
    (onglets dynamiques).</li>

  <li>l'Adullact offre un label à tous les projets hébergés: le projet du mois.
    Ce label est attribué par l'Adullact et apparaît sur la page du projet (ex:
    garennes), et sur la http://adullact.org/.</li>
</ul>
			</div>
		</div><!-- id="charte-adullact" -->

	</td><!-- id="bd-col1" -->
</tr>
</table><!-- id="bd" -->

<?php
$HTML->footer(array());
?>

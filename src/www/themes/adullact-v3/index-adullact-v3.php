<?php
require_once $gfcommon.'include/FusionForge.class.php';
?>
<!-- whole page table -->
<table id="bd" summary="" class="lien-soulignement">
<tr>	
	<td id="bd-col1" class="col1">
		
		<div id="presentation-forge">
			<div> <!-- empty div useful for design -->
				<h2>Bienvenue sur Adullact.Net</h2>
				<p>
La plate-forme FusionForge ADULLACT offre le meilleur du d&eacute;veloppement coop&eacute;ratif via
son interface web ergonomique.
Pour pouvoir utiliser au mieux la forge ADULLACT, vous devrez vous
<a href="http://adullact.net/account/register.php">inscrire</a>. 
Une fois <a href="http://adullact.net/account/login.php">identifi&eacute;</a>, 
il vous sera possible d'exploiter toutes les fonctionnalit&eacute;s de la forge 
pour tester ou participer aux nombreux projets d&eacute;j&agrave; enregistr&eacute;s, 
voire <a href="http://adullact.net/register/projectinfo.php">enregistrer</a> votre propre projet.
				</p>
				<p>Merci de votre visite et de votre contribution &agrave; notre id&eacute;al de mutualisation.</p>
			</div>
		</div><!-- id="presentation-forge" -->

		<div id="selection-metier">
			<h2>Sélectionnez votre métier</h2>
			<div> <!-- empty div useful for design -->
				<table summary="">
					<tr>
						<td><a href="/softwaremap/trove_list.php?form_cat=386" id="metier-collectivite" title="Logiciels m&eacute;tiers de la Fonction Publique Territoriale (cimetierres, &eacute;lections...)">Collectivités</a></td>
						<td><a href="/softwaremap/trove_list.php?form_cat=387" id="metier-sante" title="Logiciels m&eacute;tiers de la Sant&eacute; et de la Fonction Publique Hospitali&egrave;re (suivi patient, banques de pr&eacute;l&egrave;vements...)">Santé</a></td>
						<td><a href="/softwaremap/trove_list.php?form_cat=385" id="metier-administration" title="Logiciels m&eacute;tiers de l'Administration publique (imp&ocirc;ts, TVA...)">Administration</a></td>
					</tr>
					<tr>
						<td><a href="/softwaremap/trove_list.php?form_cat=388" id="metier-education" title="Logiciels m&eacute;tiers de l'Enseignement et de l'Education Nationale (gestion des notes, cahier de texte...)">Education</a></td>
						<td><a href="/softwaremap/trove_list.php?form_cat=390" id="metier-association">Associations</a></td>
						<td><a href="/softwaremap/trove_list.php?form_cat=389" id="metier-informatique" title="Logiciels m&eacute;tiers de l'Informatique et des Syst&egrave;mes d'Information (outils web, parc informatique...)">Informatique</a></td>
					</tr>
				</table>
			</div>
		</div><!-- id="selection-metier" -->

		<div id="dernieres-annonces">
			<h2 class="bordure-dessous">Dernières annonces</h2>
			<?php
			echo news_show_latest(forge_get_config('news_group'),3,true,false,false,0);
			?>
		</div><!-- id="dernieres-news" -->
	</td><!-- id="bd-col1" -->
  
	<!-- = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = -->
	
	<td id="bd-col2" class="col2">
	<?php
		echo show_features_boxes();
	?>
	</td><!-- id="bd-col2" -->
</tr>
</table><!-- id="bd" -->

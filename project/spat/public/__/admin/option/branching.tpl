<!-- AGENT 'header' title="Branchement des options" form=$form -->

<!-- AGENT 'dtree'

	name='d'
	tree=$branching

	rootId='c0'
	rootLabel="Branchement des options"
	rootUrl='admin/option/branching/connect/0'

-->

<!-- IF g$__1__ == 'edit' -->
Ici : titre de l'option, liste des choix possibles, possibilité de modifier les paramètres du noeud (min, max, price, enabled)
<!-- ELSEIF g$__1__ == 'connect' -->
Ici : liste des options disponibles, et cases à cocher (multiple) pour choisir des options à connecter au choix. Les options proposées sont celles qui napparaissent à aucun niveau inférieur dans l'arborscence vers le noeud en cours d'extension.
<!-- END:IF -->
<!-- AGENT 'footer' form=$form -->

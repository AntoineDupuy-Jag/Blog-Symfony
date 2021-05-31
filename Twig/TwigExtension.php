<?php

/*----------------------------------
|  CRÉER SES PROPRES FILTRES TWIG  |
----------------------------------*/

// * ATTENTION : si ça ne fonctionne pas, il faut tagger cette extension comme un service, dans le fichier 'services.yaml'
// du dossier 'config' -> App\Twig\TwigExtension: tags: ['twig.extension']

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension {

    // Fonction de la classe AbstractExtention, doit retourner un tableau de filtres Twig ->
    public function getFilters()
    {
        return [
            new TwigFilter('GreenUpText', [$this, 'GreenUpTextFilter'], ['is_safe' => ['html']]),
            new TwigFilter('TextFilter', [$this, 'TextFilter'], ['is_safe' => ['html']]),
            new TwigFilter('ArticleBeginning', [$this, 'ArticleBeginning'], ['is_safe' => ['html']])          
          ];

    }// EO getFilters

    // Notre fonction GreenTextFilter (doit prendre au moins 1 paramètre : le contenu) ->
    public function GreenUpTextFilter($content) : string {

        $contentUpper = strtoupper($content);
        return '<span class="text-success">'.$contentUpper.'</span>';

    }// EO GreenTextFilter

    // Autre exemple de fonction, avec un tableau d'options supplémentaires (le type Bootstrap du texte) ->
    public function TextFilter($content, array $options = []) : string {

        $defaultOptions = [
          'color' => 'success'
        ];
        // array_merge : fusionne plusieurs tableaux en un seul ->
        $options = array_merge($defaultOptions, $options);
        
        $color = $options['color'];

        // Utilisation d'un string template pour fonction sprintf (remplace le '%s' par les paramètres, dans l'ordre) ->
        $template = '<span class="text-%s">%s</span>';
        return sprintf(
            $template, 
            $color, 
            $content
        );

    }// EO TextFilter

    // Fonction pour n'afficher que le début des articles sur la view 'index' ->
    // (avec pour paramètre du filtre le nombre de caractères que l'on souhaite afficher, en partant du début)
    public function ArticleBeginning($content, array $options = []) : string {

        $defaultOptions = [
          'nbrchar' => 200
        ];

        $options = array_merge($defaultOptions, $options);

        $nbrChar = $options['nbrchar'];

        $articleBeginning = substr($content, 0, $nbrChar);

        $template = '<p>%s... <span class="text-muted" style="font-size: 14px;">(voir ci-dessous pour afficher la suite de l\'article...)</span></p>';

        return sprintf($template, $articleBeginning);

    }// EO ArticleBeginning

}// EO class TwigExtension
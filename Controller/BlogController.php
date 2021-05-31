<?php

// NAMESPACE ->
namespace App\Controller;

// DÉPENDANCES ->
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Doctrine\Persistence\ObjectManager;

use App\Entity\Article;
use App\Entity\ArticleLike;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Form\CommentType;
use App\Repository\ArticleLikeRepository;

// Documentation Symfony ->
// https://symfony.com/doc/current/index.html

class BlogController extends AbstractController
{
    /*--------------------------------------------------------
    |             H O M E - (PAGE D'ACCUEIL)                 |
    --------------------------------------------------------*/
    
    /**
     * @Route("/", name="home")
     */
    public function home(User $user = null) {

        // On récupère l'utilisateur s'il se connecte ->
        $user = $this->getUser();

        return $this->render('blog/home.html.twig', [
            'user' => $user
        ]);
    }
    
    /*--------------------------------------------------------
    |   R E A D - (AFFICHER LA LISTE DE TOUS LES ARTICLES)   |
    --------------------------------------------------------*/
    
    /**
     * @Route("/blog", name="blog")
     */
    public function index(ArticleRepository $repo): Response
    {
        $articles = $repo->findAll();

        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
            'articles' => $articles
        ]);
    }
    
    /*---------------------------------------------------------------------------------
    |   C R E A T E  &  U P D A T E - (AVEC LA MÊME FONCTION ET LE MÊME FORMULAIRE)   |
    ---------------------------------------------------------------------------------*/

    // Documentation Symfony sur les formulaires -> https://symfony.com/doc/current/forms.html

    /**
     * @Route("/blog/new", name="blog_create")
     * @Route("/blog/{id}/edit", name="blog_edit")
     */
    public function create(Article $article = null, Request $request, ObjectManager $manager) {

        // Si l'article n'existe pas, on instancie un nouvel article vide pour la création ->
        if (!$article) {
            $article = new Article();
        }

        $form = $this->createFormBuilder($article)
                     ->add('title', TextType::class, [
                         'attr' => [
                             'placeholder' => "Titre de l'article"
                         ],
                         'label' => 'Titre'
                     ])
                     ->add('category', EntityType::class, [
                         'class' => Category::class,
                         'choice_label' => 'title',
                         'label' => 'Catégorie'
                     ])
                     ->add('content', TextareaType::class, [
                         'label' => 'Contenu'
                     ])
                     ->add('image')
                     ->getForm();

        // Traitement du formulaire grâce à la classe Request ->
        $form->handleRequest($request);
        
        // Vérifications : si le formulaire a été soumis et si les champs sont valides ->
        if ($form->isSubmitted() && $form->isValid()) {

            if (!$article->getId()) {
                $article->setCreatedAt(new \DateTime());
            }
       
            $manager->persist($article);
            $manager->flush();

            return $this->redirectToRoute('blog_show', ['id' => $article->getId()]);
        }

        return $this->render('blog/create.html.twig', [
            'formArticle' => $form->createView(),
            'editMode' => $article->getId() !== null
        ]);
    }

    /*----------------------------------------------------------------------------------------------------------
    |   S H O W - (AFFICHER UN ARTICLE EN PARTICULIER DEPUIS SON ID ET GESTION DU FORMULAIRE DE COMMENTAIRE)   |
    ----------------------------------------------------------------------------------------------------------*/

    /**
    * @Route("/blog/{id}", name="blog_show")
    */
    public function show(Article $article, Request $request, ObjectManager $manager) {

        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
   
            $comment->setCreatedAt(new \DateTime())
                    ->setArticle($article);

            $manager->persist($comment);
            $manager->flush();

            $this->redirectToRoute('blog_show', ['id' => $article->getId()]);
        }

        return $this->render('blog/show.html.twig', [
            'article' => $article,
            'commentForm' => $form->createView()
        ]);
    }
    
    /*-------------------------------------------------------------
    |                     GESTION DES LIKES                       |
    -------------------------------------------------------------*/
        
    /**
     * @Route("/blog/{id}/like", name="article_like")
     *
     * @param  Article $article
     * @param  ObjectManager $manager
     * @param  ArticleLikeRepository $likeRepo
     * 
     * @return Response
     */
    public function like(Article $article, ObjectManager $manager, ArticleLikeRepository $likeRepo) : Response {

        // On récupère l'utilisateur connecté (méthode getUser possédée par tous les contrôleurs) ->
        $user = $this->getUser();

        /*-----------------------------
        |  PLUSIEURS CAS POSSIBLES :  |
        -----------------------------*/

        // Si l'utilisateur n'est pas connecté (n'existe pas) avec envoi de json au javascript->
        if (!$user) return $this->json([
            'code' => 403,
            'message' => 'Unauthorized'
        ], 403);

        // --------------------------------------------------------------

        // Si l'utilisateur a déjà aimé l'article, on SUPPRIME le like ->
        if ($article->isLikedByUser($user)) {
            // Recherche du like grâce au repository d'ArticleLike
            $like = $likeRepo->findOneBy([
                'article' => $article,
                'user' => $user
            ]);
            // Suppression du like grâce au manager
            $manager->remove($like);
            $manager->flush();
            // (Envoi du json au javascript)
            return $this->json([
                'code' => 200,
                'message' => 'Like supprimé',
                'likes' => $likeRepo->count(['article' => $article]) // (Pour compter le nombre de likes de cet article)
            ], 200);
        }

        // --------------------------------------------------------------

        // Si l'utilisateur n'a pas déjà aimé l'article, on CRÉÉ le like ->
        $like = new ArticleLike;
        $like->setArticle($article)
             ->setUser($user);
        
        $manager->persist($like);
        $manager->flush();

        // Envoi du json au javascript
        return $this->json([
            'code' => 200,
            'message' => 'Like ajouté',
            'likes' => $likeRepo->count(['article' => $article])
        ], 200);
    
    }// EO like

}// EO BlogController
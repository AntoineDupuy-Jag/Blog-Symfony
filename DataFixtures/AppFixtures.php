<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Entity\Article;
use App\Entity\ArticleLike;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\User;

class AppFixtures extends Fixture
{
    /*------------------------------------------------------
    |  CONSTRUCTEUR DE L'ENCODEUR POUR LE MDP UTILISATEUR  |
    ------------------------------------------------------*/

    /**
     * @var UserPasswordEncoderInterface
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /*-----------------------------------------------
    |  CREATION DE FAKES DATA VIA FIXTURE ET FAKER  |
    -----------------------------------------------*/
    // cf. doc Fakerphp/Formatters -> https://fakerphp.github.io/formatters/

    public function load(ObjectManager $manager)
    {
        // Instance de la classe Faker avec un paramètre pour obtenir les données dans la langue souhaitée ->
        $faker = \Faker\Factory::create('fr_FR');

        // Création d'un tableau regroupant tous les faux utilisateurs ->
        $users = [];

        /*-------------------------------
        |    CREATION D'UTILISATEURS    |
        -------------------------------*/

        for ($m = 0; $m <= 10; $m++) {
            $user = new User();
            $user->setEmail($faker->email())
                ->setUsername($faker->name())
                ->setPassword($this->encoder->encodePassword($user, 'password'));

            $manager->persist($user);

            // Envoi de l'utilisateur vers le tableau ->
            $users[] = $user;
        } // EO for

        /*-------------------------------------------------------------------
        |  CREATION DE CATEGORIES, D'ARTICLES, DE COMMENTAIRES ET DE LIKES  |
        -------------------------------------------------------------------*/

        // Création de 3 fakes catégories ->
        for ($i = 1; $i <= 3; $i++) {
            $category = new Category();
            $category->setTitle($faker->sentence())
                ->setDescription($faker->paragraph());

            // Pour préparer la persistance des données ->
            $manager->persist($category);

            // Création de fakes articles à l'intérieur de ces catégories (entre 4 et 6) ->
            for ($j = 1; $j <= mt_rand(4, 6); $j++) {
                $article = new Article();
                $article->setTitle($faker->sentence())
                    ->setContent($faker->paragraphs(5, true))
                    ->setImage($faker->imageUrl(640, 480, 'Article illustration'))
                    ->setCreatedAt($faker->dateTimeBetween('-6 months'))
                    ->setCategory($category);

                $manager->persist($article);

                // Création de fakes commentaires pour ces articles (entre 4 et 10) ->
                for ($k = 1; $k <= mt_rand(4, 10); $k++) {
                    $comment = new Comment();

                    // Pour le createdAt du commentaire (forcément compris entre la date de création de l'article et aujourd'hui) ->
                    $days = (new \DateTime())->diff($article->getCreatedAt())->days;

                    $comment->setAuthor($faker->name())
                        ->setContent($faker->paragraphs(2, true))
                        ->setCreatedAt($faker->dateTimeBetween('-' . $days . ' days'))
                        ->setArticle($article);

                    $manager->persist($comment);
                } // EO for

                // Création de fakes likes pour ces articles (entre 0 et 10) ->
                for ($l = 1; $l <= mt_rand(0, 10); $l++) {
                    $like = new ArticleLike();
                    $like->setArticle($article)
                        ->setUser($faker->randomElement($users));

                    $manager->persist($like);
                } // EO for

            } // EO for

        } // EO for

        /*-----------------------------
        |  ENVOI DES FAUSSES DONNÉES  |
        -----------------------------*/

        $manager->flush();

    } // EO load

}// EO ArticleFixtures
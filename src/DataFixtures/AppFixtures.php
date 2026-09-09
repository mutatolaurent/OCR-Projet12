<?php

namespace App\DataFixtures;

use App\Entity\Conseil;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création de users pour l'application, avec des rôles et des informations de localisation.

        // Création d'un user "normal"
        $user = new User();
        $user->setEmail("user@gmail.com");
        $user->setVille("Metz");
        $user->setLatitude("49.119113");
        $user->setLongitude("6.172696");
        $user->setRoles(["ROLE_USER"]);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "password"));
        $manager->persist($user);

        // Création d'un user admin
        $userAdmin = new User();
        $userAdmin->setEmail("admin@ecogarden.com");
        $userAdmin->setVille("Nancy");
        $userAdmin->setLatitude("48.692054");
        $userAdmin->setLongitude("6.184417");
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "password"));
        $manager->persist($userAdmin);

        // Création de 15 conseils pour le jardinage, chacun associé à un ou plusieurs mois de l'année.

        // Conseil 1 : Taille d'hiver
        $conseil = new Conseil();
        $conseil->setTexte('C\'est la période idéale pour la taille de formation et de fructification des arbres fruitiers à pépins (pommiers, poiriers) en dehors des périodes de gel.');
        $conseil->setMois([1, 2]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 2 : Semis au chaud
        $conseil = new Conseil();
        $conseil->setTexte('Semez vos graines de tomates, poivrons et aubergines au chaud, en intérieur ou sous serre chauffée (à environ 20°C).');
        $conseil->setMois([2, 3]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 3 : Semis en pleine terre
        $conseil = new Conseil();
        $conseil->setTexte('Vous pouvez commencer les semis en pleine terre pour les légumes rustiques : radis, carottes, pois et fèves.');
        $conseil->setMois([3, 4]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 4 : Plantation après les Saints de Glace
        $conseil = new Conseil();
        $conseil->setTexte('Les risques de gelées étant écartés après la mi-mai, repiquez en pleine terre vos plants de tomates, courgettes et melons.');
        $conseil->setMois([5]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 5 : Paillage pour l'été
        $conseil = new Conseil();
        $conseil->setTexte('Paillez généreusement le pied de vos cultures (avec de la paille, des tontes de gazon séchées ou du BRF) pour conserver l\'humidité du sol et limiter la pousse des adventices.');
        $conseil->setMois([5, 6, 7]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 6 : Arrosage optimisé
        $conseil = new Conseil();
        $conseil->setTexte('Arrosez abondamment au pied des plantes plutôt que d\'asperger le feuillage (pour éviter les maladies cryptogamiques). Privilégiez un arrosage tard le soir ou très tôt le matin.');
        $conseil->setMois([6, 7, 8]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 7 : Pincement et taille d'été
        $conseil = new Conseil();
        $conseil->setTexte('Pincez les gourmands de vos tomates et taillez les tiges coureuses de vos courges pour favoriser le développement des fruits plutôt que du feuillage.');
        $conseil->setMois([6, 7]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 8 : Récolte des graines
        $conseil = new Conseil();
        $conseil->setTexte('Sélectionnez vos plus beaux légumes (non hybrides F1) et laissez-les monter en graine pour récolter vos propres semences pour l\'année prochaine.');
        $conseil->setMois([8, 9]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 9 : Plantation des fraisiers
        $conseil = new Conseil();
        $conseil->setTexte('Plantez ou renouvelez vos plants de fraisiers. La terre est encore chaude, ce qui facilitera leur enracinement avant l\'hiver.');
        $conseil->setMois([8, 9]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 10 : Semis d'engrais verts
        $conseil = new Conseil();
        $conseil->setTexte('Semez un engrais vert (comme la moutarde, la phacélie ou le seigle) sur vos parcelles nues. Cela protégera le sol de l\'érosion hivernale et l\'enrichira.');
        $conseil->setMois([9, 10]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 11 : Plantation des bulbes printaniers
        $conseil = new Conseil();
        $conseil->setTexte('Plantez vos bulbes à floraison printanière (tulipes, narcisses, crocus) avant les premières gelées pour assurer un jardin fleuri au début du printemps.');
        $conseil->setMois([10, 11]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 12 : Enrichissement du sol
        $conseil = new Conseil();
        $conseil->setTexte('Épandez du compost mûr ou du fumier bien décomposé à la surface de votre potager. Les vers de terre l\'incorporeront doucement durant l\'hiver.');
        $conseil->setMois([10, 11]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 13 : Protection hivernale
        $conseil = new Conseil();
        $conseil->setTexte('Installez des voiles d\'hivernage sur vos plantes les plus fragiles et rentrez les potées gélives à l\'abri dans une pièce lumineuse et hors gel.');
        $conseil->setMois([11, 12]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 14 : Entretien de la biodiversité
        $conseil = new Conseil();
        $conseil->setTexte('Laissez quelques tas de feuilles mortes et de branchages dans un coin du jardin pour offrir un abri hivernal aux hérissons et aux insectes auxiliaires.');
        $conseil->setMois([11, 12, 1]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        // Conseil 15 : Nettoyage des outils
        $conseil = new Conseil();
        $conseil->setTexte('Profitez du repos végétatif pour nettoyer, désinfecter, affûter vos outils de coupe (sécateurs, cisailles) et passer de l\'huile de lin sur les manches en bois.');
        $conseil->setMois([12, 1]);
        $conseil->setUser($userAdmin);
        $manager->persist($conseil);

        $manager->flush();
    }
}

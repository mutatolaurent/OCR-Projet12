<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * @extends ServiceEntityRepository<Conseil>
 */
class ConseilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conseil::class);
    }

    //    /**
    //     * @return Conseil[] Returns an array of Conseil objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Conseil
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Retourne tous les conseils associés à un mois donné
     *
     * @param int $mois (de 1 à 12)
     * @return Conseil[]
     */
    public function findByMois(int $mois): array
    {
        $em = $this->getEntityManager();

        // ResultSetMappingBuilder permet de dire à Doctrine :
        // "Prends ce résultat SQL brut et transforme-le en entités Conseil"
        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(Conseil::class, 'c');

        // On utilise la fonction JSON_CONTAINS native de MySQL/MariaDB
        $query = $em->createNativeQuery(
            'SELECT * FROM conseil WHERE JSON_CONTAINS(mois, :mois)',
            $rsm
        );

        // JSON_CONTAINS attend une valeur formatée en JSON pour la comparaison.
        // Convertir l'entier en string permet à la base de faire la correspondance.
        $query->setParameter('mois', (string) $mois);

        return $query->getResult();
    }
}

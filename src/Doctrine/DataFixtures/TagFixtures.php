<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class TagFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $names = ['RPG', 'Action', 'Aventure', 'Puzzle', 'Indépendant', 'Multijoueur', 'Stratégie'];

        foreach ($names as $name) {
            $tag = (new Tag())->setName($name);
            $manager->persist($tag);
        }

        $manager->flush();
    }
}

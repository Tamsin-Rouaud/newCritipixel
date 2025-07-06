<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Model\Entity\Tag;
use App\Model\Entity\Review;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();
        $allTags = $manager->getRepository(Tag::class)->findAll();

        $videoGames = array_map(function (int $index) {
            return (new VideoGame())
                ->setTitle(sprintf('Jeu vidéo %d', $index))
                ->setDescription($this->faker->paragraphs(10, true))
                ->setReleaseDate(new DateTimeImmutable())
                ->setTest($this->faker->paragraphs(6, true))
                ->setRating(($index % 5) + 1)
                ->setImageName(sprintf('video_game_%d.png', $index))
                ->setImageSize(2_098_872);
        }, range(0, 49));

        foreach ($videoGames as $videoGame) {
            // Ajouter entre 1 et 3 tags aléatoires
            $tags = $this->faker->randomElements($allTags, rand(1, 3));
            foreach ($tags as $tag) {
                $videoGame->getTags()->add($tag);
            }

            // Ajouter entre 2 et 5 reviews
            $reviewers = $this->faker->randomElements($users, rand(2, 5));
            foreach ($reviewers as $user) {
                $review = (new Review())
                    ->setVideoGame($videoGame)
                    ->setUser($user)
                    ->setRating($rating = rand(1, 5))
                    ->setComment($this->faker->optional(0.7)->paragraph());

                $videoGame->getReviews()->add($review);
                $manager->persist($review);
            }

            // Calculs statistiques
           $this->calculateAverageRating->calculateAverage($videoGame);
            $this->countRatingsPerValue->countRatingsPerValue($videoGame);

            $manager->persist($videoGame);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TagFixtures::class
        ];
    }
}

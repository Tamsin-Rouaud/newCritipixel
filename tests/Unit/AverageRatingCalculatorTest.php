<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

final class AverageRatingCalculatorTest extends TestCase
{
    private RatingHandler $calculator;

    protected function setUp(): void
    {
        $this->calculator = new RatingHandler();
    }

    public function testAverageWithNoReview(): void
    {
        $videoGame = new VideoGame();

        $this->calculator->calculateAverage($videoGame);

        self::assertNull($videoGame->getAverageRating());
    }

    public function testAverageWithOneReview(): void
    {
        $videoGame = new VideoGame();
        $review = (new Review())
            ->setRating(4)
            ->setUser(new User())
            ->setVideoGame($videoGame);
        $videoGame->getReviews()->add($review);

        $this->calculator->calculateAverage($videoGame);

        self::assertSame(4, $videoGame->getAverageRating());
    }

    public function testAverageWithMultipleReviews(): void
    {
        $videoGame = new VideoGame();

        $ratings = [3, 5, 4];
        foreach ($ratings as $rate) {
            $review = (new Review())
                ->setRating($rate)
                ->setUser(new User())
                ->setVideoGame($videoGame);
            $videoGame->getReviews()->add($review);
        }

        $this->calculator->calculateAverage($videoGame);

        // Moyenne = (3+5+4)/3 = 4 arrondi
        self::assertSame(4, $videoGame->getAverageRating());
    }
}

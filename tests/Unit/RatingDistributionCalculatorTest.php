<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

final class RatingDistributionCalculatorTest extends TestCase
{
    private RatingHandler $calculator;

    protected function setUp(): void
    {
        $this->calculator = new RatingHandler();
    }

    /**
     * @dataProvider provideRatingDistributions
     */
    public function testRatingDistribution(array $ratings, array $expectedCounts): void
    {
        $videoGame = new VideoGame();

        foreach ($ratings as $rate) {
            $review = (new Review())
                ->setRating($rate)
                ->setUser(new User())
                ->setVideoGame($videoGame);
            $videoGame->getReviews()->add($review);
        }

        $this->calculator->countRatingsPerValue($videoGame);
        $dist = $videoGame->getNumberOfRatingsPerValue();

        self::assertSame($expectedCounts[1], $dist->getNumberOfOne(), '1-star count mismatch');
        self::assertSame($expectedCounts[2], $dist->getNumberOfTwo(), '2-star count mismatch');
        self::assertSame($expectedCounts[3], $dist->getNumberOfThree(), '3-star count mismatch');
        self::assertSame($expectedCounts[4], $dist->getNumberOfFour(), '4-star count mismatch');
        self::assertSame($expectedCounts[5], $dist->getNumberOfFive(), '5-star count mismatch');
    }

    public static function provideRatingDistributions(): array
    {
        return [
            'no ratings' => [
                [], [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]
            ],
            'all fives' => [
                [5, 5, 5], [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 3]
            ],
            'one of each' => [
                [1, 2, 3, 4, 5], [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1]
            ],
            'mixed set' => [
                [1, 1, 3, 5, 5, 5, 2], [1 => 2, 2 => 1, 3 => 1, 4 => 0, 5 => 3]
            ],
            'no fives' => [
                [1, 2, 3, 4, 4, 3], [1 => 1, 2 => 1, 3 => 2, 4 => 2, 5 => 0]
            ],
        ];
    }
}

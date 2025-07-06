<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;

final class AddReviewTest extends FunctionalTestCase
{
    /**
     * @dataProvider provideInvalidReviewData
     */
    public function testInvalidReviewSubmissions(array $formData): void
    {
        $entityManager = $this->getEntityManager();
        $passwordHasher = $this->service(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setUsername('invalid_user_' . uniqid());
        $user->setEmail('invalid_' . uniqid() . '@example.com');
        $user->setPlainPassword('Test123!');
        $user->setPassword($passwordHasher->hashPassword($user, 'Test123!'));

        $entityManager->persist($user);
        $videoGame = $entityManager->getRepository(VideoGame::class)->findOneBy([]);
        self::assertNotNull($videoGame);

        $entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->get('/' . $videoGame->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="review"]');

        $form = $crawler->filter('form[name="review"]')->form($formData);

        $this->client->submit($form);
        self::assertResponseStatusCodeSame(422);

        $review = $entityManager->getRepository(Review::class)->findOneBy([
            'videoGame' => $videoGame,
            'user' => $user,
        ]);
        self::assertNull($review);
    }

    public static function provideInvalidReviewData(): iterable
    {
        yield 'missing rating' => [
            [ 'review[comment]' => 'Commentaire sans note.' ]
        ];

        yield 'too long comment' => [
            [
                'review[rating]' => 3,
                'review[comment]' => str_repeat('A', 3001),
            ]
        ];
    }

    public function testThatReviewCanBeSubmitted(): void
    {
        $entityManager = $this->getEntityManager();
        $passwordHasher = $this->service(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setUsername('test_user_' . uniqid());
        $user->setEmail('test_' . uniqid() . '@example.com');
        $user->setPlainPassword('Test123!');
        $user->setPassword($passwordHasher->hashPassword($user, 'Test123!'));

        $entityManager->persist($user);
        $videoGame = $entityManager->getRepository(VideoGame::class)->findOneBy([]);
        self::assertNotNull($videoGame);

        $entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->get('/' . $videoGame->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="review"]');

        $form = $crawler->filter('form[name="review"]')->form([
            'review[rating]' => 4,
            'review[comment]' => 'Très bon jeu !',
        ]);

        $this->client->submit($form);
        self::assertResponseRedirects();

        $review = $entityManager->getRepository(Review::class)->findOneBy([
            'videoGame' => $videoGame,
            'user' => $user,
        ]);

        self::assertNotNull($review);
        self::assertSame(4, $review->getRating());
        self::assertSame('Très bon jeu !', $review->getComment());
    }

    public function testThatAnonymousUserCannotSeeReviewForm(): void
    {
        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy([]);
        self::assertNotNull($videoGame);

        $this->get('/' . $videoGame->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('form[name="review"]');
    }

    public function testThatAnonymousUserCannotPostReview(): void
    {
        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy([]);
        self::assertNotNull($videoGame);

        $this->client->request('POST', '/' . $videoGame->getSlug(), [
            'review' => [
                'rating' => 3,
                'comment' => 'Tentative anonyme',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);

        $review = $this->getEntityManager()
            ->getRepository(Review::class)
            ->findOneBy([
                'videoGame' => $videoGame,
                'comment' => 'Tentative anonyme',
            ]);

        self::assertNull($review);
    }

    public function testThatUserCannotSeeReviewFormAfterSubmitting(): void
    {
        $entityManager = $this->getEntityManager();
        $passwordHasher = $this->service(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setUsername('once_only_user_' . uniqid());
        $user->setEmail('once_' . uniqid() . '@example.com');
        $user->setPlainPassword('Test123!');
        $user->setPassword($passwordHasher->hashPassword($user, 'Test123!'));

        $entityManager->persist($user);
        $videoGame = $entityManager->getRepository(VideoGame::class)->findOneBy([]);
        self::assertNotNull($videoGame);

        $entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->get('/' . $videoGame->getSlug());

        $form = $crawler->filter('form[name="review"]')->form([
            'review[rating]' => 5,
            'review[comment]' => 'Unique avis.',
        ]);

        $this->client->submit($form);
        self::assertResponseRedirects();

        $this->client->followRedirect();
        self::assertSelectorNotExists('form[name="review"]');
    }
}

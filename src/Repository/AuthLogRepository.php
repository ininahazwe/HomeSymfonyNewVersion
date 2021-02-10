<?php

namespace App\Repository;

use App\Entity\AuthLog;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AuthLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthLog[]    findAll()
 * @method AuthLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthLog::class);
    }
    public const BLACK_LISTING_DELAY_IN_MINUTES = 10;

    public const MAX_FAILED_AUTH_ATTEMPTS = 5;

    /**
     * @param string $emailEntered
     * @param string|null $userIP
     * @param boolean $isBlackListed Set to true if the email/userIp pair must be blacklisted
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @return void
     */

    public function addFailedAuthAttempt(string $emailEntered, ?string $userIP, bool $isBlackListed = false): void
    {
        $authAttempt = (new AuthLog($emailEntered, $userIP)) ->setIsSuccessfulAuth(false);

        if($isBlackListed){
            $authAttempt->setStartOfBlackListing(new DateTimeImmutable('now'))
                        ->setEndOfBlackListing(new DateTimeImmutable(sprintf('+%d minutes', self::BLACK_LISTING_DELAY_IN_MINUTES)));
        }

        $this->_em->persist($authAttempt);

        $this->_em->flush();
    }

    /**
     * Adds a successful authentication attempt
     *
     * @param string $emailEntered
     * @param string|null $userIP
     * @param boolean $isRememberMeAuth Set to true if the user is authenticated by remember cookie.
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addSuccessfulAuthAttempt(string $emailEntered, ?string $userIP, bool $isRememberMeAuth = false): void
    {
        $authAttempt = new AuthLog($emailEntered, $userIP);

        $authAttempt->setIsSuccessfulAuth(true)
                    ->setIsRememberMeAuth($isRememberMeAuth);

        $this->_em->persist($authAttempt);
        $this->_em->flush();
    }

    /**
     * Return the number of recent failed authentification attempts.
     *
     * @param string $emailEntered
     * @param string|null $userIP
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getRecentAuthAttemptFailure(string $emailEntered, ?string $userIP): int
    {
        return $this->createQueryBuilder('af')
                    ->select('COUNT(af)')
                    ->where('af.authAttemptAt >= :datetime')
                    ->andWhere('af.userIP = :user_IP')
                    ->andWhere('af.emailEntered = :email_entered')
                    ->andWhere('af.isSuccessfulAuth = false')
                    ->setParameters([
                        'datetime' => new DateTimeImmutable(sprintf('-%d minutes', self::BLACK_LISTING_DELAY_IN_MINUTES)),
                        'email_entered' => $emailEntered,
                        'user_IP' => $userIP
                    ])
                    ->getQuery()
                    ->getSingleScalarResult();
    }

    /**
     * Returns whether or not the user will be blacklisted on the next failed attempt.
     *
     * @param string $emailEntered
     * @param string|null $userIP
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */

    public function isBlackListedWithThisAttemptFailure(string $emailEntered, ?string $userIP): bool
    {
        return $this->getRecentAuthAttemptFailure($emailEntered, $userIP) >= self::MAX_FAILED_AUTH_ATTEMPTS - 2;
    }

    /**
     * Returns the last entry in the black list of an userIP/email pair if it exists.
     *
     * @param string $emailEntered
     * @param string|null $userIP
     * @return AuthLog|null
     * @throws \Exception
     */
    public function getEmailAndUserIpPairBlackListedIfExists(string $emailEntered, ?string $userIP): ?AuthLog
    {
        return $this->createQueryBuilder('bl')
                    ->select('bl')
                    ->where('bl.userIP = :user_IP')
                    ->andWhere('bl.emailEntered = :email_entered')
                    ->andWhere('bl.endOfBlackListing IS NOT NULL')
                    ->andWhere('bl.endOfBlackListing >= :datetime')
                    ->setParameters([
                        'datetime' => new DateTimeImmutable(sprintf('-%d minutes', self::BLACK_LISTING_DELAY_IN_MINUTES)),
                        'email_entered' => $emailEntered,
                        'user_IP' => $userIP
                    ])
                    ->orderBy('bl.id', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    /**
     * Returns the end of blacklisting rounded up to the next minute
     *
     * @param string $emailEntered
     * @param string|null $userIP
     * @return string|null The time with format like this: 12h00.
     * @throws \Exception
     */
    public function getEndOfBlackListing(string $emailEntered, ?string $userIP): ?string
    {
        $blackListing = $this->getEmailAndUserIpPairBlackListedIfExists($emailEntered, $userIP);
        if(!$blackListing || $blackListing->getEndOfBlackListing() === null) {
            return null;
        }

        return $blackListing->getEndOfBlackListing()->add(new \DateInterval("PT1M"))->format('H\hi');
    }
}

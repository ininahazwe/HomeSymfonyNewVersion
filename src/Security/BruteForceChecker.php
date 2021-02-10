<?php

namespace App\Security;

use App\Repository\AuthLogRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class BruteForceChecker
{
    private $authLogRepository;

    private $requestStack;

    public function __construct(AuthLogRepository $authLogRepository, RequestStack $requestStack)
    {
        $this->authLogRepository = $authLogRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * Adds a failed authentification attemps and adds a blacklisting according to the number of failed attempts.
     *
     * @param string $emailEntered
     * @param string|null $userIP
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addFailedAuthAttempt(string $emailEntered, ?string $userIP): void
    {
        if($this->authLogRepository->isBlackListedWithThisAttemptFailure($emailEntered, $userIP)){
            $this->authLogRepository->addFailedAuthAttempt($emailEntered, $userIP, true);
        }else{
            $this->authLogRepository->addFailedAuthAttempt($emailEntered, $userIP);
        }

    }

    /**
     * Returns the end of blacklisting rounded up to the next minute or null.
     *
     * @return string|null Example: if the end of blacklisting is 12:01:37, it returns 12h02
     * @throws \Exception
     */
    public function getEndOfBlackListing() : ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if(!$request){
            return null;
        }
        $userIP = $request->getClientIp();
        $emailEntered = $request->request->get('email');

        return $this->authLogRepository->getEndOfBlackListing($emailEntered, $userIP);
    }
}
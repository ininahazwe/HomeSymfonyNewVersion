<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HCaptcha
{
    private const HCAPTCHA_ENDPOINT = 'https://hcaptcha.com/siteverify';

    private $httpClient;

    private $requestStack;

    private $hCaptchaSecretKey;

    public function __construct(HttpClientInterface $httpClient, RequestStack $requestStack, string $hCaptchaSecretKey)
    {
        $this->httpClient = $httpClient;
        $this->requestStack = $requestStack;
        $this->hCaptchaSecretKey = $hCaptchaSecretKey;
    }

    public function isHCaptchaValid(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if(!$request){
            return false;
        }

        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => [
                'secret' => $this->hCaptchaSecretKey,
                'response' => $request->request->get('h-captcha-response')
            ]
        ];

        $response = $this->httpClient->request('POST', self::HCAPTCHA_ENDPOINT, $options);

        $data = $response->toArray();

        return $data['success'];

    }
}
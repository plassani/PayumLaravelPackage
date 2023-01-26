<?php
namespace Payum\LaravelPackage\Controller;

use Payum\Core\Reply\ReplyInterface;
use Payum\Core\Request\Refund;
use Payum\Core\Reply\HttpRedirect;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RefundController extends PayumController
{
    public function doAction($payum_token)
    {
        /** @var Request $request */
        $request = app()->make('request');
        $request->attributes->set('payum_token', $payum_token);

        $token = $this->getPayum()->getHttpRequestVerifier()->verify($request);

        $gateway = $this->getPayum()->getGateway($token->getGatewayName());

        try {
            $gateway->execute(new Refund($token));
        } catch (ReplyInterface $reply) {
            return $this->convertReply($reply);
        }

        $this->getPayum()->getHttpRequestVerifier()->invalidate($token);

        if($token->getAfterUrl()){
            return new HttpRedirect($token->getAfterUrl());
        }

        return Response::make(null, 204);

    }
}

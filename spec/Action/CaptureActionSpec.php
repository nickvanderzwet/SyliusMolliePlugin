<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace spec\BitBag\SyliusMolliePlugin\Action;

use BitBag\SyliusMolliePlugin\Action\CaptureAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Payum;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactory;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\TokenInterface;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\PaymentInterface;

final class CaptureActionSpec extends ObjectBehavior
{
    function it_is_initializable(): void
    {
        $this->shouldHaveType(CaptureAction::class);
    }

    function it_implements_action_interface(): void
    {
        $this->shouldHaveType(ActionInterface::class);
    }

    function it_implements_generic_token_factory_aware(): void
    {
        $this->shouldHaveType(GenericTokenFactoryAwareInterface::class);
    }

    function it_implements_api_aware_interface(): void
    {
        $this->shouldHaveType(ApiAwareInterface::class);
    }

    function it_implements_gateway_aware_interface(): void
    {
        $this->shouldHaveType(GatewayAwareInterface::class);
    }

    function it_executes(
        Capture $request,
        ArrayObject $arrayObject,
        PaymentInterface $payment,
        TokenInterface $token,
        TokenInterface $notifyToken,
        Payum $payum,
        GenericTokenFactory $genericTokenFactory,
        GatewayInterface $gateway,
        \Mollie_API_Client $mollieApiClient,
        \Mollie_API_Resource_Base $mollieApiResourceBase
    ): void {
        $this->setGateway($gateway);

        $this->setApi($mollieApiClient);

        $notifyToken->getTargetUrl()->willReturn('url');
        $notifyToken->getHash()->willReturn('test');

        $token->getTargetUrl()->willReturn('url');
        $token->getAfterUrl()->willReturn('url');
        $token->getGatewayName()->willReturn('test');
        $token->getDetails()->willReturn([]);
        $token->getHash()->willReturn('test');

        $genericTokenFactory->createNotifyToken('test', [])->willReturn($notifyToken);
        $genericTokenFactory->createRefundToken('test', [])->willReturn($notifyToken);

        $this->setGenericTokenFactory($genericTokenFactory);

        $payum->getTokenFactory()->willReturn($genericTokenFactory);

        $arrayObject->toUnsafeArray()->willReturn([]);
        $arrayObject->offsetExists('mollie_id')->shouldBeCalled();
        $arrayObject->offsetSet('redirectUrl', 'url')->shouldBeCalled();
        $arrayObject->offsetSet('webhookUrl', 'url')->shouldBeCalled();
        $arrayObject->offsetExists('mollie_id')->shouldBeCalled();
        $arrayObject->offsetGet('metadata')->shouldBeCalled();
        $arrayObject->offsetSet('metadata', ['refund_token' => 'test'])->shouldBeCalled();
        $arrayObject->offsetSet('mollie_id', 1)->shouldBeCalled();

        $request->getModel()->willReturn($arrayObject);
        $request->getFirstModel()->willReturn($payment);
        $request->getToken()->willReturn($token);

        $payment = \Mockery::mock('payment');

        $payment->id = 1;

        $payment->shouldReceive('getPaymentUrl')->andReturn('');

        $mollieApiResourceBase->create([])->willReturn($payment);

        $mollieApiClient->payments = $mollieApiResourceBase;

        $this
            ->shouldThrow(HttpPostRedirect::class)
            ->during('execute', [$request])
        ;
    }

    function it_supports_only_capture_request_and_array_access(
        Capture $request,
        \ArrayAccess $arrayAccess
    ): void {
        $request->getModel()->willReturn($arrayAccess);

        $this->supports($request)->shouldReturn(true);
    }
}

<?php

declare(strict_types=1);

namespace BestSellers\Service;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Domain\Ci\ClassMissingFromThePublishedCore;

// Reverted before merge.
final class CiSabotage implements EventSubscriberInterface
{
    public function __construct(private readonly ClassMissingFromThePublishedCore $missing)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return ['kernel.request' => 'onRequest'];
    }

    public function onRequest(): void
    {
        $this->missing->run();
    }
}

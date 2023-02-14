<?php declare(strict_types=1);

namespace Souplette\FusBup\Exception;

final class UnknownTLDException extends ForbiddenDomainException
{
    public function __construct(string $domain)
    {
        parent::__construct(sprintf(
            'TLD of "%s" is not in the public suffix list.',
            $domain,
        ));
    }
}

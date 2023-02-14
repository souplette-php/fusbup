<?php declare(strict_types=1);

namespace Souplette\FusBup\Exception;

final class PrivateETLDException extends ForbiddenDomainException
{
    public function __construct(string $domain)
    {
        parent::__construct(sprintf(
            'Effective TLD of "%s" is not in the ICANN section of the public suffix list.',
            $domain,
        ));
    }
}

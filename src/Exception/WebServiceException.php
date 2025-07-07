<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Throwable;

/**
 * Excepción para errores generales al interactuar con un Web Service de AFIP.
 * Puede ser utilizada para encapsular SoapFaults.
 */
class WebServiceException extends AfipException
{
    /**
     * Constructor.
     *
     * @param  string  $message  El mensaje de la excepción.
     * @param  int  $code  El código de la excepción.
     * @param  string|int|null  $soapFaultCode  El código de fallo SOAP.
     * @param  Throwable|null  $throwable  La excepción previa utilizada para encadenar excepciones.
     */
    public function __construct(string $message, int $code = 0, protected string|int|null $soapFaultCode = null, ?Throwable $throwable = null)
    {
        parent::__construct($message, $code, $throwable);
    }

    /**
     * Obtiene el código de fallo SOAP, si fue proporcionado.
     */
    public function getSoapFaultCode(): string|int|null
    {
        return $this->soapFaultCode;
    }
}

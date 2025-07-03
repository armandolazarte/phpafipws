<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Exception;

/**
 * Excepción personalizada para la Libreria PhpAfipWs.
 *
 * Se utiliza para lanzar errores específicos de la librería, facilitando
 * la captura y manejo de errores por parte del consumidor de la Librería PhpAfipWs.
 */
class AfipException extends Exception
{
    /**
     * Constructor de PhpAfipWsException.
     *
     * @param  string  $message  El mensaje de la excepción.
     * @param  int  $code  El código de la excepción.
     * @param  Exception|null  $exception  La excepción anterior, si existe, para encadenamiento de excepciones.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $exception = null
    ) {
        parent::__construct($message, $code, $exception);
    }
}

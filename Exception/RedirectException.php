<?php
/**
 * This file is part of the Beyerz/SimpleHMVCBundle package.
 *
 * Copyright (c) 2017. Lance Bailey <bailz777@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Beyerz\SimpleHMVCBundle\Exception;


use Symfony\Component\HttpFoundation\Response;

/**
 * Class RedirectException
 *
 * @author Lance Bailey <bailz777@gmail.com>
 */
class RedirectException extends \Exception
{

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    private $response;

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
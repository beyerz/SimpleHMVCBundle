<?php
/**
 * This file is part of the Beyerz/SimpleHMVCBundle package.
 *
 * Copyright (c) 2017. Lance Bailey <bailz777@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Beyerz\SimpleHMVCBundle\Context\Element;


interface ElementContextInterface
{
    /**
     * @param array $parameters
     * @return $this
     */
    public function setJavascriptParameters(array $parameters);

    /**
     * @return array
     */
    public function getJavascriptParameters();

    /**
     * @param array $javascripts
     * @return $this
     */
    public function setJavascripts(array $javascripts);

    /**
     * @return array
     */
    public function getJavascripts();

    /**
     * @param array $stylesheets
     * @return $this
     */
    public function setStylesheets(array $stylesheets);

    /**
     * @return array
     */
    public function getStylesheets();

    /**
     * @param string $name
     * @param array $context
     * @return $this
     */
    public function addElement($name, $context);

    /**
     * @param string $path
     * @return $this
     */
    public function setViewPath($path);

    /**
     * @return string
     */
    public function getViewPath();

    /**
     * represent the context as an array
     * @return array
     */
    public function toArray();
}
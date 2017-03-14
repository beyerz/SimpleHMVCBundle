<?php
/**
 * This file is part of the Beyerz/SimpleHMVCBundle package.
 *
 * Copyright (c) 2017. Lance Bailey <bailz777@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Beyerz\SimpleHMVCBundle\Context;


use Doctrine\Common\Collections\ArrayCollection;
use Beyerz\SimpleHMVCBundle\Context\Element\ElementContextInterface;

trait ElementContextTrait
{
    private $javascriptParameters = [];

    private $javascripts = [];

    private $stylesheets = [];

    private $elements = null;

    private $viewPath = '';

    /**
     * @param array $parameters
     * @return $this
     */
    public function setJavascriptParameters(array $parameters){
        $this->javascriptParameters = $parameters;
        return $this;
    }

    /**
     * @return array
     */
    public function getJavascriptParameters(){
        return $this->javascriptParameters;
    }

    /**
     * @param array $javascripts
     */
    public function setJavascripts(array $javascripts)
    {
        $this->javascripts = $javascripts;
    }

    /**
     * @return array
     */
    public function getJavascripts()
    {
        return $this->javascripts;
    }

    /**
     * @param array $stylesheets
     */
    public function setStylesheets(array $stylesheets)
    {
        $this->stylesheets = $stylesheets;
    }

    /**
     * @return array
     */
    public function getStylesheets()
    {
        return $this->stylesheets;
    }

    /**
     * @return ArrayCollection|null
     */
    public function getElements(){
        if(is_null($this->elements)){
            $this->elements = new ArrayCollection();
        }
        return $this->elements;
    }

    /**
     * @param $name
     * @param ElementContextInterface $context
     */
    public function addElement($name, $context)
    {
        $elements = $this->getElements();
        $elements->set($name,$context);
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setViewPath($path){
        $this->viewPath = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getViewPath(){
        return $this->viewPath;
    }
}
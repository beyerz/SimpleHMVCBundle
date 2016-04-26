<?php
/**
 * Created by PhpStorm.
 * User: bailz777
 * Date: 26/04/2016
 * Time: 22:03
 */

namespace Beyerz\SimpleHMVCBundle\Controller;


use Beyerz\SimpleHMVCBundle\Exception\ElementException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

abstract class ElementController extends Controller
{
    protected $elements = [];
    protected $context = [];
    protected $javascripts = [];
    protected $stylesheets = [];
    /**
     * @param $element
     * @return $this
     * @throws ElementException
     */
    public function addElement($element)
    {
        //the element should be defined as a service
        if(!$this->container->has($element)){
            throw new ElementException(sprintf('Undefined element %s in services, please ensure this controller exists and is defined in the service container',$element));
        }
        array_push($this->elements,$element);
        return $this;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function execute(Request $request)
    {
        $context = $this->generateContext($request);
        $this->appendContextToCorrectNS($request,$this,$context);
        foreach($this->elements as $element){
            /* @var ElementController $controller */
            $controller = $this->get($element);
            $this->validElement($request, $controller);
            $controller->setContainer($this->container);
            $context = $controller->execute($request);
            $this->context = array_merge_recursive($context,$this->context);
        }
        unset($context);
        return $this->context;
    }

    private function appendContextToCorrectNS(Request $request, $controller, $context){
        $namespace = $this->getContextNS($request,$controller);
        $namespace = self::normalizeContextNS($namespace);
        $this->context['hmvc'][$namespace] = $context;
    }

    public static function normalizeContextNS($namespace){
        $b = $c = $n = $f = $e = null;
        sscanf($namespace,"%[^:]:%[^:]:%[^.].%[^.].%s",$b, $c,$n,$f,$e);
        $namespace = sprintf("%s_%s",$b,$c);
        $namespace = str_replace('\\','_',$namespace);
        $namespace = strtolower($namespace);
        return $namespace;
    }

    private function getContextNS(Request $request, $controller){
        $templateReference = $this->getTemplateReference($request,$controller);
        return $templateReference->getLogicalName();
    }

    /**
     * Ensures all the right pieces are in all the right places
     * @param Request $request
     * @param ElementController $controller
     */
    private function validElement(Request $request, ElementController $controller){
        $templateReference = $this->getTemplateReference($request,$controller);
        //ensure that the JS is in the correct place with the correct name
        //ensure that the CSS template is in the correct place with the correct name
        $errors = [];
        //Attempt to locate the template, this function will throw an error if it does not find the template in the specified path defined by the reference
        try {
            $this->get('templating.locator')->locate($templateReference);
        } catch(\InvalidArgumentException $e) {
            $errors[] = $e;
        }

        if(count($errors)>0){
            //TODO: Throw all the listed errors
        }
    }

    /**
     * @param Request $request
     * @param ElementController $controller
     * @return \Symfony\Bundle\FrameworkBundle\Templating\TemplateReference
     */
    private function getTemplateReference(Request $request, ElementController $controller){
        //ensure that the twig template is in the correct place with the correct name
        $templateGuesser = $this->get('sensio_framework_extra.view.guesser');
        //get the class short name as this is the name we will define as the action name
        $c = (new \ReflectionClass($this))->getShortName();
        $c = strtolower(str_replace('Controller','',$c));
        $templateReference = $templateGuesser->guessTemplateName([$controller,$c.'Action'],$request);
        return $templateReference;
    }

    /**
     * @param Request $request
     * @return array
     */
    abstract function generateContext(Request $request);

}
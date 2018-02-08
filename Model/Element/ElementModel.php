<?php
/**
 * This file is part of the Beyerz/SimpleHMVCBundle package.
 *
 * Copyright (c) 2017. Lance Bailey <bailz777@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Beyerz\SimpleHMVCBundle\Model\Element;


use Beyerz\SimpleHMVCBundle\Context\Element\ElementContextInterface;
use Beyerz\SimpleHMVCBundle\Input\Element\ElementInputInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ElementModel
 * @author Lance Bailey <bailz777@gmail.com>
 */
abstract class ElementModel
{
    use ContainerAwareTrait;

    /**
     * @var ArrayCollection
     */
    private $elements = null;

    /**
     * @var ParameterBag
     */
    private $javascriptParameters;

    /**
     * @var ElementInputInterface
     */
    private $input;

    /**
     * @var ElementContextInterface
     */
    protected $context = null;

    /**
     * ElementModel constructor.
     *
     * @param ElementInputInterface $input
     */
    public function __construct(ElementInputInterface $input)
    {
        $this->input = $input;
        $this->javascriptParameters = new ParameterBag();
    }


    public function registerElement($name, ElementModel $element)
    {
        $elements = $this->getElements();
        if (!$elements->containsKey($name)) {
            $elements->set($name, $element);
        }

        return $this;
    }

    public function registerJavascriptParameter($key, $value)
    {
        $this->javascriptParameters->set($key, $value);

        return $this;
    }

    private function getElements()
    {
        if (is_null($this->elements)) {
            $this->elements = new ArrayCollection();
        }

        return $this->elements;
    }

    /**
     * @param ElementContextInterface $context
     *
     * @return ElementContextInterface
     */
    public function compile(ElementContextInterface $context)
    {
        if ($this->validate($this, $context)) {
            //add javascripts
            $context->setJavascripts($this->loadJavascripts());
            //add stylesheets
            $context->setStylesheets($this->loadStylesheets());
            //set view path
            $context->setViewPath($this->generateViewPath());
            //set Javascript Parameters
            $context->setJavascriptParameters(array_merge($context->getJavascriptParameters(), $this->javascriptParameters->all()));


            //load elements
            $elements = $this->getElements();
            /** @var ElementModel $element */
            foreach ($elements as $name => $element) {
                $element->registerElements();
                $elementContext = $element->buildContext();
                $compiled = $element->compile($elementContext);
                $context->setJavascripts(array_unique(array_merge($compiled->getJavascripts(), $context->getJavascripts())));
                $compiled->setJavascripts([]);
                $context->setStylesheets(array_unique(array_merge($compiled->getStylesheets(), $context->getStylesheets())));
                $compiled->setStylesheets([]);
                $context->setJavascriptParameters(array_merge($compiled->getJavascriptParameters(), $context->getJavascriptParameters()));
                $compiled->setJavascriptParameters([]);

                $context->addElement($name, $compiled->toArray());
            }

            return $context;
        }
    }

    private function validate(ElementModel $model, ElementContextInterface $context)
    {
        //validate the model name
        $modelNS = get_class($model);
        if (!preg_match('/(Model)$/', $modelNS)) {
            throw new \Exception(sprintf("Models must end with word Model, invalid model: %s", $modelNS));
        }

        $contextNS = get_class($context);
        if (!preg_match('/(Context)$/', $contextNS)) {
            throw new \Exception(sprintf("Contexts must end with word Context, invalid context: %s", $contextNS));
        }

        return true;
    }

    private function generateViewPath()
    {
        $ns = get_class($this);
        $ns = preg_replace('/(Model)/', '', $ns);
        $parts = preg_split('/Bundle/', $ns);
        $bundle = sprintf("%sBundle", str_replace("\\", "", $parts[0]));
        $bundle = $this->container->get('kernel')->getBundle($bundle);
        $parts = array_filter(explode("\\", $parts[1]));
        //re-index the array
        $parts = array_values($parts);
        $templateFormat = "twig";
        $pathToView = implode("\\", array_splice($parts, 0, count($parts) - 1));
        $template = $bundle->getName() . ':' . $pathToView . ':' .
            strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $parts[count($parts) - 1]))
            . '.html.' . $templateFormat;
        $loader = $this->container->get('twig.loader');
        if (!$loader->exists($template)) {
            throw new \Exception(sprintf("Non existant view for model: %s, Expecting View at: %s", get_class($this), $template));
        }
        return $template;
    }

    private function loadJavascripts()
    {
        return static::javascripts();
    }

    private function loadStylesheets()
    {
        return static::stylesheets();
    }

    /**
     * @return ElementInputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param ElementInputInterface $input
     *
     * @return ElementModel
     */
    public function setInput(ElementInputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @return $this
     */
    abstract public function registerElements();

    /**
     * @return ElementContextInterface
     */
    abstract public function buildContext();

    /**
     * @return array
     */
    abstract public static function javascripts();

    /**
     * @return array
     */
    abstract public static function stylesheets();


    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route         The name of the route
     * @param mixed  $parameters    An array of parameters
     * @param int    $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    public function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }

    /**
     * Adds a flash message to the current session for type.
     *
     * @param string $type    The type
     * @param string $message The message
     *
     * @throws \LogicException
     */
    protected function addFlash($type, $message)
    {
        if (!$this->container->has('session')) {
            throw new \LogicException('You can not use the addFlash method if sessions are disabled.');
        }

        $this->container->get('session')->getFlashBag()->add($type, $message);
    }
    
    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied object.
     *
     * @param mixed $attributes The attributes
     * @param mixed $object     The object
     *
     * @return bool
     *
     * @throws \LogicException
     */
    protected function isGranted($attributes, $object = null)
    {
        if (!$this->container->has('security.authorization_checker')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        return $this->container->get('security.authorization_checker')->isGranted($attributes, $object);
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return mixed
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    public function getUser()
    {
        if (!$this->container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return $user;
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string|FormTypeInterface $type    The built type of the form
     * @param mixed                    $data    The initial data for the form
     * @param array                    $options Options for the form
     *
     * @return Form
     */
    public function createForm($type, $data = null, array $options = [])
    {
        return $this->container->get('form.factory')->create($type, $data, $options);
    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilder
     */
    public function createFormBuilder($data = null, array $options = [])
    {
        if (method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            $type = 'Symfony\Component\Form\Extension\Core\Type\FormType';
        } else {
            // not using the class name is deprecated since Symfony 2.8 and
            // is only used for backwards compatibility with older versions
            // of the Form component
            $type = 'form';
        }

        return $this->container->get('form.factory')->createBuilder($type, $data, $options);
    }

    /**
     * @param $id
     *
     * @return object
     */
    protected function get($id)
    {
        return $this->container->get($id);
    }

}

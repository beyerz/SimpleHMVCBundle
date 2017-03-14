<?php
/**
 * This file is part of the Beyerz/SimpleHMVCBundle package.
 *
 * Copyright (c) 2017. Lance Bailey <bailz777@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Beyerz\SimpleHMVCBundle\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Class PageGenerator
 * @author Lance Bailey <bailz777@gmail.com>
 */
class ElementGenerator extends Generator
{

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * PageGenerator constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function generate(BundleInterface $bundle, $element, $templateFormat, $template)
    {
        $dir = $bundle->getPath();

        $contextFile = $dir . '/Context' . DIRECTORY_SEPARATOR . $element . 'Context.php';
        if (file_exists($contextFile)) {
            throw new \RuntimeException(sprintf('Controller "%s" already exists', $element));
        }

        $inputFile = $dir . '/Input' . DIRECTORY_SEPARATOR . $element . 'Input.php';
        if (file_exists($inputFile)) {
            throw new \RuntimeException(sprintf('Controller "%s" already exists', $element));
        }

        $modelFile = $dir . '/Model' . DIRECTORY_SEPARATOR . $element . 'Model.php';
        if (file_exists($modelFile)) {
            throw new \RuntimeException(sprintf('Controller "%s" already exists', $element));
        }

        $elementNamespaceSuffix = str_replace(DIRECTORY_SEPARATOR, "\\", substr($element, 0, strrpos($element, DIRECTORY_SEPARATOR)));
        $supportNamespaceSuffix = str_replace(DIRECTORY_SEPARATOR, "\\", $element);
        $className = substr($element, strrpos($element, DIRECTORY_SEPARATOR) + 1);

        $parameters = [
            'namespace'                => $bundle->getNamespace(),
            'element_namespace_suffix' => $elementNamespaceSuffix,
            'use_path'                 => $supportNamespaceSuffix,
            'bundle'                   => $bundle->getName(),
            'format'                   => [
                'templating' => $templateFormat,
            ],
            'element'                  => $element,
            'class_name'               => $className,
            'path_to_element'          => str_replace("\\", DIRECTORY_SEPARATOR, $elementNamespaceSuffix),
        ];

        if ('twig' == $templateFormat) {
            $this->renderFile('view/element/Template.html.twig.twig', $dir . '/Resources/views/' . $this->parseTemplatePath($template), $parameters);
        } else {
            $this->renderFile('view/element/Template.html.php.twig', $dir . '/Resources/views/' . $this->parseTemplatePath($template), $parameters);
        }

        $this->generateModel($dir, $parameters);
        $this->generateContext($dir, $parameters);
        $this->generateInput($dir, $parameters);

    }

    private function generateModel($dir, array $parameters)
    {
        $modelFile = $dir . $this->generateRelativeFilePath("model", $parameters);
        $this->renderFile('model/element/Model.php.twig', $modelFile, $parameters);
    }

    private function generateContext($dir, array $parameters)
    {
        $contextFile = $dir . $this->generateRelativeFilePath("context", $parameters);
        $this->renderFile('context/element/Context.php.twig', $contextFile, $parameters);
    }

    private function generateInput($dir, array $parameters)
    {
        $inputFile = $dir . $this->generateRelativeFilePath("input", $parameters);
        $this->renderFile('input/element/Input.php.twig', $inputFile, $parameters);

    }

    private function generateRelativeFilePath($type, $parameters)
    {
        return DIRECTORY_SEPARATOR . ucfirst($type) . DIRECTORY_SEPARATOR . $parameters['path_to_element'] . DIRECTORY_SEPARATOR . ucfirst($parameters['class_name']) . ucfirst($type) . '.php';
    }

    protected function parseTemplatePath($template)
    {
        $data = $this->parseLogicalTemplateName($template);

        return $data['element'] . '/' . $data['template'];
    }

    protected function parseLogicalTemplateName($logicalName, $part = '')
    {
        if (2 !== substr_count($logicalName, ':')) {
            throw new \RuntimeException(sprintf('The given template name ("%s") is not correct (it must contain two colons).', $logicalName));
        }

        $data = [];

        list($data['bundle'], $data['element'], $data['template']) = explode(':', $logicalName);

        return $part ? $data[$part] : $data;
    }
}
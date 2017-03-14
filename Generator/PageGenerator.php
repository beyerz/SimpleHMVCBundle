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
class PageGenerator extends Generator
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

    public function generate(BundleInterface $bundle, $controller, $routeFormat, $templateFormat, array $actions = [])
    {
        $dir = $bundle->getPath();
        $controllerFile = $dir . '/Controller' . DIRECTORY_SEPARATOR . $controller . 'Controller.php';
        if (file_exists($controllerFile)) {
            throw new \RuntimeException(sprintf('Controller "%s" already exists', $controller));
        }

        $contextFile = $dir . '/Context' . DIRECTORY_SEPARATOR . $controller . 'Context.php';
        if (file_exists($contextFile)) {
            throw new \RuntimeException(sprintf('Controller "%s" already exists', $controller));
        }

        $inputFile = $dir . '/Input' . DIRECTORY_SEPARATOR . $controller . 'Input.php';
        if (file_exists($inputFile)) {
            throw new \RuntimeException(sprintf('Controller "%s" already exists', $controller));
        }

        $modelFile = $dir . '/Model' . DIRECTORY_SEPARATOR . $controller . 'Model.php';
        if (file_exists($modelFile)) {
            throw new \RuntimeException(sprintf('Controller "%s" already exists', $controller));
        }

        $controllerNamespaceSuffix = str_replace(DIRECTORY_SEPARATOR, "\\", substr($controller, 0, strrpos($controller, DIRECTORY_SEPARATOR)));
        $supportNamespaceSuffix = str_replace(DIRECTORY_SEPARATOR, "\\", $controller);
        $className = substr($controller, strrpos($controller, DIRECTORY_SEPARATOR) + 1);

        $parameters = [
            'namespace'        => $bundle->getNamespace(),
            'controller_namespace_suffix' => $controllerNamespaceSuffix,
            'support_namespace_suffix' => $supportNamespaceSuffix,
            'bundle'           => $bundle->getName(),
            'format'           => [
                'routing'    => $routeFormat,
                'templating' => $templateFormat,
            ],
            'controller'       => $controller,
            'class_name'       => $className,
        ];

        foreach ($actions as $i => $action) {
            // get the action name without the suffix Action (for the template logical name)
            $actions[$i]['basename'] = substr($action['name'], 0, -6);
            $actions[$i]['class_name'] = ucfirst($actions[$i]['basename']);
            $actions[$i]['use_path'] = str_replace(DIRECTORY_SEPARATOR, "\\", $controller . DIRECTORY_SEPARATOR . ucfirst($actions[$i]['basename']));
            $params = $parameters;
            $params['action'] = $actions[$i];

            // create a template
            $template = $actions[$i]['template'];
            if ('default' == $template) {
                @trigger_error('The use of the "default" keyword is deprecated. Use the real template name instead.', E_USER_DEPRECATED);
                $template = $bundle->getName() . ':' . $controller . ':' .
                    strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], strtr(substr($action['name'], 0, -6), '_', '.')))
                    . '.html.' . $templateFormat;
            }

            if ('twig' == $templateFormat) {
                $this->renderFile('view/page/Template.html.twig.twig', $dir . '/Resources/views/' . $this->parseTemplatePath($template), $params);
            } else {
                $this->renderFile('view/page/Template.html.php.twig', $dir . '/Resources/views/' . $this->parseTemplatePath($template), $params);
            }

            $this->generateRouting($bundle, $controller, $actions[$i], $routeFormat);
            $this->generateModel($dir, $controller, $actions[$i], $parameters);
            $this->generateContext($dir, $controller, $actions[$i], $parameters);
            $this->generateInput($dir, $controller, $actions[$i], $parameters);

        }
        $parameters['actions'] = $actions;

        $this->generateController($dir, $controller, $parameters);
    }

    private function generateController($dir, $controller, array $parameters)
    {
        $controllerFile = $dir . '/Controller' . DIRECTORY_SEPARATOR . $controller . 'Controller.php';
        $this->renderFile('controller/page/Controller.php.twig', $controllerFile, $parameters);
        $controllerTestFile = $dir . DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . $controller . 'ControllerTest.php';
        $this->renderFile('controller/page/ControllerTest.php.twig', $controllerTestFile, $parameters);
    }

    private function generateModel($dir, $controller, array  $action, array $parameters)
    {
        $modelFile = $dir . '/Model' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . ucfirst($action['basename']) . 'Model.php';
        $this->renderFile('model/page/Model.php.twig', $modelFile, array_merge($parameters,["action_class_name"=>$action['class_name']]));
    }

    private function generateContext($dir, $controller, array  $action, array $parameters)
    {
        $contextFile = $dir . '/Context' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . ucfirst($action['basename']) . 'Context.php';
        $this->renderFile('context/page/Context.php.twig', $contextFile, array_merge($parameters,["action_class_name"=>$action['class_name']]));
    }

    private function generateInput($dir, $controller, array  $action, array $parameters)
    {
        $inputFile = $dir . '/Input' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . ucfirst($action['basename']) . 'Input.php';
        $this->renderFile('input/page/Input.php.twig', $inputFile, array_merge($parameters,["action_class_name"=>$action['class_name']]));

    }

    public function generateRouting(BundleInterface $bundle, $controller, array $action, $format)
    {
        // annotation is generated in the templates
        if ('annotation' == $format) {
            return true;
        }

        $file = $bundle->getPath() . '/Resources/config/routing.' . $format;
        if (file_exists($file)) {
            $content = file_get_contents($file);
        } elseif (!is_dir($dir = $bundle->getPath() . '/Resources/config')) {
            self::mkdir($dir);
        }

        $controller = $bundle->getName() . ':' . $controller . ':' . $action['basename'];
        $name = strtolower(preg_replace('/([A-Z])/', '_\\1', $action['basename']));

        if ('yml' == $format) {
            // yaml
            if (!isset($content)) {
                $content = '';
            }

            $content .= sprintf(
                "\n%s:\n    path:     %s\n    defaults: { _controller: %s }\n",
                $name,
                $action['route'],
                $controller
            );
        } elseif ('xml' == $format) {
            // xml
            if (!isset($content)) {
                // new file
                $content = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<routes xmlns="http://symfony.com/schema/routing"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/routing http://symfony.com/schema/routing/routing-1.0.xsd">
</routes>
EOT;
            }

            $sxe = simplexml_load_string($content);

            $route = $sxe->addChild('route');
            $route->addAttribute('id', $name);
            $route->addAttribute('path', $action['route']);

            $default = $route->addChild('default', $controller);
            $default->addAttribute('key', '_controller');

            $dom = new \DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($sxe->asXML());
            $content = $dom->saveXML();
        } elseif ('php' == $format) {
            // php
            if (isset($content)) {
                // edit current file
                $pointer = strpos($content, 'return');
                if (!preg_match('/(\$[^ ]*).*?new RouteCollection\(\)/', $content, $collection) || false === $pointer) {
                    throw new \RuntimeException('Routing.php file is not correct, please initialize RouteCollection.');
                }

                $content = substr($content, 0, $pointer);
                $content .= sprintf("%s->add('%s', new Route('%s', array(", $collection[1], $name, $action['route']);
                $content .= sprintf("\n    '_controller' => '%s',", $controller);
                $content .= "\n)));\n\nreturn " . $collection[1] . ';';
            } else {
                // new file
                $content = <<<EOT
<?php
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

\$collection = new RouteCollection();
EOT;
                $content .= sprintf("\n\$collection->add('%s', new Route('%s', array(", $name, $action['route']);
                $content .= sprintf("\n    '_controller' => '%s',", $controller);
                $content .= "\n)));\n\nreturn \$collection;";
            }
        }

        $flink = fopen($file, 'w');
        if ($flink) {
            $write = fwrite($flink, $content);

            if ($write) {
                fclose($flink);
            } else {
                throw new \RuntimeException(sprintf('We cannot write into file "%s", has that file the correct access level?', $file));
            }
        } else {
            throw new \RuntimeException(sprintf('Problems with generating file "%s", did you gave write access to that directory?', $file));
        }
    }

    protected function parseTemplatePath($template)
    {
        $data = $this->parseLogicalTemplateName($template);

        return $data['controller'] . '/' . $data['template'];
    }

    protected function parseLogicalTemplateName($logicalName, $part = '')
    {
        if (2 !== substr_count($logicalName, ':')) {
            throw new \RuntimeException(sprintf('The given template name ("%s") is not correct (it must contain two colons).', $logicalName));
        }

        $data = [];

        list($data['bundle'], $data['controller'], $data['template']) = explode(':', $logicalName);

        return $part ? $data[$part] : $data;
    }
}
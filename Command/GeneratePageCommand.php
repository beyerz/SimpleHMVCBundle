<?php

/**
 * This file is part of the Beyerz/SimpleHMVCBundle package.
 *
 * Copyright (c) 2017. Lance Bailey <bailz777@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Beyerz\SimpleHMVCBundle\Command;

use Beyerz\SimpleHMVCBundle\Generator\PageGenerator;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;

/**
 * Generates page.
 *
 * @author Lance Bailey <bailz777@gmail.com>
 */
class GeneratePageCommand extends GeneratorCommand
{
    /**
     * @see Command
     */
    public function configure()
    {
        $this
            ->setName('hmvc:page')
            ->setDescription('Generates a page based on the hmvc principal')
            ->setDefinition([
                new InputOption('bundle', '', InputOption::VALUE_REQUIRED, 'The name of the bundle to create the page in'),
                new InputOption('controller', '', InputOption::VALUE_REQUIRED, 'The name of the controller to create'),
                new InputOption('path', '', InputOption::VALUE_REQUIRED, 'The path to the controller'),
                new InputOption('route-format', '', InputOption::VALUE_REQUIRED, 'The format that is used for the routing (yml, xml, php, annotation)', 'annotation'),
                new InputOption('template-format', '', InputOption::VALUE_REQUIRED, 'The format that is used for templating (twig, php)', 'twig'),
                new InputOption('actions', '', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The actions in the controller'),
            ])
            ->setHelp(<<<EOT
The <info>%command.name%</info> command helps you generates new controllers
inside bundles.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--controller</comment> is the only one needed if you follow the conventions):

<info>php %command.full_name% --controller=AcmeBlogBundle:Post</info>

If you want to disable any user interaction, use <comment>--no-interaction</comment>
but don't forget to pass all needed options:

<info>php %command.full_name% --controller=AcmeBlogBundle:Post --no-interaction</info>

Every generated file is based on a template. There are default templates but they can
be overridden by placing custom templates in one of the following locations, by order of priority:

<info>BUNDLE_PATH/Resources/SensioGeneratorBundle/skeleton/controller
APP_PATH/Resources/SensioGeneratorBundle/skeleton/controller</info>

You can check https://github.com/sensio/SensioGeneratorBundle/tree/master/Resources/skeleton
in order to know the file structure of the skeleton
EOT
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        if ($input->isInteractive()) {
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true);
            if (!$questionHelper->ask($input, $output, $question)) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        if (null === $input->getOption('controller')) {
            throw new \RuntimeException('The controller option must be provided.');
        }
        $bundle = $input->getOption('bundle');
        if(preg_match('/^(Page)/',$input->getOption("path"))){
            if(strpos($input->getOption("path"),"/")){
                $path = substr($input->getOption("path"),strpos($input->getOption("path"),"/"));
            }
            else{
                $path = "";
            }
        }
        $controller = $input->getOption('controller');
        $shortNotation = $bundle . ":" . $path . $controller;
        list($bundle, $path, $controller) = $this->parseShortcutNotation($shortNotation);

        $controller = is_null($path)?"":$path . DIRECTORY_SEPARATOR . $controller;
        if (is_string($bundle)) {
            $bundle = Validators::validateBundleName($bundle);

            try {
                $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);
            } catch (\Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundle));
            }
        }

        $questionHelper->writeSection($output, 'Controller generation');

        $generator = $this->getGenerator($bundle);

        $generator->generate($bundle, $controller, $input->getOption('route-format'), $input->getOption('template-format'), $this->parseActions($input->getOption('actions')));

        $output->writeln('Generating the bundle code: <info>OK</info>');

        $questionHelper->writeGeneratorSummary($output, []);
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the HMVC page generator');

        // namespace
        $output->writeln([
            '',
            'Every page, and even sections of a page, are rendered by a <comment>controller</comment>.',
            'This command helps you generate them easily.',
            '',
            'First, you need to give the controller name you want to generate.',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>',
            'Or with a path',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:Path/To/Post</comment>',
        ]);

        $bundleNames = array_keys($this->getContainer()->get('kernel')->getBundles());

        while (true) {
            $question = new Question($questionHelper->getQuestion('Controller name', $input->getOption('controller')), $input->getOption('controller'));
            $question->setAutocompleterValues($bundleNames);
            $question->setValidator(['Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateControllerName']);
            $controller = $questionHelper->ask($input, $output, $question);
            list($bundle, $path, $controller) = $this->parseShortcutNotation($controller);

            try {
                $b = $this->getContainer()->get('kernel')->getBundle($bundle);
                if (!file_exists($b->getPath() . '/Controller/' . (!is_null($path) ? $path . DIRECTORY_SEPARATOR : '') . $controller . 'Controller.php')) {
                    break;
                }

                $output->writeln(sprintf('<bg=red>Controller "%s:%s/%s" already exists.</>', $bundle, $path, $controller));
            } catch (\Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundle));
            }
        }

        $input->setOption('bundle', $bundle);
        $input->setOption('path', $path);
        $input->setOption('controller', $controller);

        // routing format
        $defaultFormat = (null !== $input->getOption('route-format') ? $input->getOption('route-format') : 'annotation');
        $output->writeln([
            '',
            'Determine the format to use for the routing.',
            '',
        ]);
        $question = new Question($questionHelper->getQuestion('Routing format (php, xml, yml, annotation)', $defaultFormat), $defaultFormat);
        $question->setValidator(['Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateFormat']);
        $routeFormat = $questionHelper->ask($input, $output, $question);
        $input->setOption('route-format', $routeFormat);

        // templating format
        $validateTemplateFormat = function ($format) {
            if (!in_array($format, ['twig', 'php'])) {
                throw new \InvalidArgumentException(sprintf('The template format must be twig or php, "%s" given', $format));
            }

            return $format;
        };

        $defaultFormat = (null !== $input->getOption('template-format') ? $input->getOption('template-format') : 'twig');
        $output->writeln([
            '',
            'Determine the format to use for templating.',
            '',
        ]);
        $question = new Question($questionHelper->getQuestion('Template format (twig, php)', $defaultFormat), $defaultFormat);
        $question->setValidator($validateTemplateFormat);

        $templateFormat = $questionHelper->ask($input, $output, $question);
        $input->setOption('template-format', $templateFormat);

        // actions
        $input->setOption('actions', $this->addActions($input, $output, $questionHelper));

        // summary
        $output->writeln([
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg-white', true),
            '',
            sprintf('You are going to generate a "<info>%s:%s:%s</info>" controller', $bundle, $path, $controller),
            sprintf('using the "<info>%s</info>" format for the routing and the "<info>%s</info>" format', $routeFormat, $templateFormat),
            'for templating',
        ]);
    }

    public function addActions(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper)
    {
        $output->writeln([
            '',
            'Instead of starting with a blank controller, you can add some actions now. An action',
            'is a PHP function or method that executes, for example, when a given route is matched.',
            'Actions should be suffixed by <comment>Action</comment>.',
            '',
        ]);

        $actions = $this->parseActions($input->getOption('actions'));

        while (true) {
            // name
            $output->writeln('');
            $question = new Question($questionHelper->getQuestion('New action name (press <return> to stop adding actions)', null), null);
            $question->setValidator(function ($name) use ($actions) {
                if (null == $name) {
                    return $name;
                }

                if (isset($actions[$name])) {
                    throw new \InvalidArgumentException(sprintf('Action "%s" is already defined', $name));
                }

                if ('Action' != substr($name, -6)) {
                    throw new \InvalidArgumentException(sprintf('Name "%s" is not suffixed by Action', $name));
                }

                return $name;
            });

            $actionName = $questionHelper->ask($input, $output, $question);
            if (!$actionName) {
                break;
            }

            // route
            $defaultRoute = strtolower(str_replace("Page", "", $input->getOption('path')) . DIRECTORY_SEPARATOR . substr($actionName, 0, -6));
            $question = new Question($questionHelper->getQuestion('Action route', $defaultRoute), $defaultRoute);
            $route = $questionHelper->ask($input, $output, $question);
            $placeholders = $this->getPlaceholdersFromRoute($route);

            $template = $input->getOption('bundle') . ':' . $input->getOption('path') . DIRECTORY_SEPARATOR . $input->getOption('controller') . ':' .
                strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], strtr(substr($actionName, 0, -6), '_', '.')))
                . '.html.' . $input->getOption('template-format');

            // adding action
            $actions[$actionName] = [
                'name'         => $actionName,
                'route'        => $route,
                'placeholders' => $placeholders,
                'template'     => $template,
            ];
        }

        return $actions;
    }

    public function parseActions($actions)
    {
        if (empty($actions) || $actions !== array_values($actions)) {
            return $actions;
        }

        // '$actions' can be an array with just 1 element defining several actions
        // separated by white spaces: $actions = array('... ... ...');
        if (1 === count($actions)) {
            $actions = explode(' ', $actions[0]);
        }

        $parsedActions = [];

        foreach ($actions as $action) {
            $data = explode(':', $action);

            // name
            if (!isset($data[0])) {
                throw new \InvalidArgumentException('An action must have a name');
            }
            $name = array_shift($data);

            // route
            $route = (isset($data[0]) && '' != $data[0]) ? array_shift($data) : '/' . substr($name, 0, -6);
            if ($route) {
                $placeholders = $this->getPlaceholdersFromRoute($route);
            } else {
                $placeholders = [];
            }

            // template
            $template = (0 < count($data) && '' != $data[0]) ? implode(':', $data) : 'default';

            $parsedActions[$name] = [
                'name'         => $name,
                'route'        => $route,
                'placeholders' => $placeholders,
                'template'     => $template,
            ];
        }

        return $parsedActions;
    }

    public function getPlaceholdersFromRoute($route)
    {
        preg_match_all('/{(.*?)}/', $route, $placeholders);
        $placeholders = $placeholders[1];

        return $placeholders;
    }

    public function parseShortcutNotation($shortcut)
    {
        $entity = str_replace('\\', DIRECTORY_SEPARATOR, $shortcut);
        $entity = rtrim($entity, DIRECTORY_SEPARATOR);

        if (false === $pos = strpos($entity, ':')) {
            throw new \InvalidArgumentException(sprintf('The controller name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Post)', $entity));
        }

        $hasPath = strrpos($entity, DIRECTORY_SEPARATOR);
        $bundle = substr($entity, 0, $pos);
        if ($hasPath) {
            $path = "Page" . DIRECTORY_SEPARATOR . substr($entity, $pos + 1, strrpos($entity, '/') - ($pos + 1));
            $controller = substr($entity, strrpos($entity, '/') + 1);
        } else {
            $path = "Page";
            $controller = substr($entity, $pos + 1);
        }

        return [$bundle, $path, $controller];
    }

    protected function createGenerator()
    {
        return new PageGenerator($this->getContainer()->get('filesystem'));
    }
}

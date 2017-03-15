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

use Beyerz\SimpleHMVCBundle\Generator\ElementGenerator;
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
class GenerateElementCommand extends GeneratorCommand
{

    /**
     * @see Command
     */
    public function configure()
    {
        $this
            ->setName('hmvc:element')
            ->setDescription('Generates an element based on the hmvc principal')
            ->setDefinition([
                new InputOption('bundle', '', InputOption::VALUE_REQUIRED, 'The name of the bundle to create the page in'),
                new InputOption('element', '', InputOption::VALUE_REQUIRED, 'The name of the element to create'),
                new InputOption('path', '', InputOption::VALUE_REQUIRED, 'The path to the element'),
                new InputOption('template-format', '', InputOption::VALUE_REQUIRED, 'The format that is used for templating (twig, php)', 'twig'),
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

        if (null === $input->getOption('element')) {
            throw new \RuntimeException('The element option must be provided.');
        }

        $bundle = $input->getOption('bundle');
        if(preg_match('/^(Element)/',$input->getOption("path"))){
            if(strpos($input->getOption("path"),"/")){
                $path = substr($input->getOption("path"),strpos($input->getOption("path"),"/")+1);
            }
            else{
                $path = "";
            }
        }
        $element = $input->getOption('element');
        $shortNotation = $bundle . ":" . (empty($path)?"":$path . DIRECTORY_SEPARATOR) . $element;
        list($bundle, $path, $element) = $this->parseShortcutNotation($shortNotation);
        $element = is_null($path)?"":$path . DIRECTORY_SEPARATOR . $element;
        if (is_string($bundle)) {
            $bundle = Validators::validateBundleName($bundle);

            try {
                $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);
            } catch (\Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundle));
            }
        }

        $questionHelper->writeSection($output, 'Element generation');

        $generator = $this->getGenerator($bundle);
        $generator->generate($bundle, $element, $input->getOption('template-format'), $this->getTemplate($input));

        $output->writeln('Generating the bundle code: <info>OK</info>');

        $questionHelper->writeGeneratorSummary($output, []);
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the HMVC element generator');

        // namespace
        $output->writeln([
            '',
            'Every element is represented by a set of <comment>Model, Input, Context and View</comment>.',
            'This command helps you generate them easily.',
            '',
            'First, you need to give the element name you want to generate.',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:Element</comment>',
            'Or with a path',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:Path/To/Element</comment>',
        ]);

        $bundleNames = array_keys($this->getContainer()->get('kernel')->getBundles());

        while (true) {
            $question = new Question($questionHelper->getQuestion('Element name', $input->getOption('element')), $input->getOption('element'));
            $question->setAutocompleterValues($bundleNames);
            $question->setValidator(['Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateControllerName']);
            $element = $questionHelper->ask($input, $output, $question);
            list($bundle, $path, $element) = $this->parseShortcutNotation($element);

            try {
                $b = $this->getContainer()->get('kernel')->getBundle($bundle);
                if (!file_exists($b->getPath() . '/Model/' . (!is_null($path) ? $path . DIRECTORY_SEPARATOR : '') . $element . 'Model.php')) {
                    break;
                }

                $output->writeln(sprintf('<bg=red>Element "%s:%s/%s" already exists.</>', $bundle, $path, $element));
            } catch (\Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundle));
            }
        }

        $input->setOption('bundle', $bundle);
        $input->setOption('path', $path);
        $input->setOption('element', $element);

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

        // summary
        $output->writeln([
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg-white', true),
            '',
            sprintf('You are going to generate a "<info>%s:%s:%s</info>" element', $bundle, $path, $element),
            sprintf('using the "<info>%s</info>" format', $templateFormat),
            'for templating',
        ]);
    }

    public function getTemplate(InputInterface $input)
    {
        $template = $input->getOption('bundle') . ':' . $input->getOption('path') . ':' .
            strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], strtr($input->getOption('element'), '_', '.')))
            . '.html.' . $input->getOption('template-format');

        return $template;
    }

    public function parseShortcutNotation($shortcut)
    {
        $entity = str_replace('\\', DIRECTORY_SEPARATOR, $shortcut);
        $entity = rtrim($entity, DIRECTORY_SEPARATOR);

        if (false === $pos = strpos($entity, ':')) {
            throw new \InvalidArgumentException(sprintf('The element name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Post)', $entity));
        }

        $hasPath = strrpos($entity, DIRECTORY_SEPARATOR);
        $bundle = substr($entity, 0, $pos);
        if ($hasPath) {
            $path = "Element" . DIRECTORY_SEPARATOR . substr($entity, $pos + 1, strrpos($entity, '/') - ($pos + 1));
            $element = substr($entity, strrpos($entity, '/') + 1);
        } else {
            $path = "Element";
            $element = substr($entity, $pos + 1);
        }

        return [$bundle, $path, $element];
    }

    protected function createGenerator()
    {
        return new ElementGenerator($this->getContainer()->get('filesystem'));
    }
}

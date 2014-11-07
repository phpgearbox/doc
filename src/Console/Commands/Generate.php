<?php namespace Gears\Doc\Console\Commands;
////////////////////////////////////////////////////////////////////////////////
// __________ __             ________                   __________              
// \______   \  |__ ______  /  _____/  ____ _____ ______\______   \ _______  ___
//  |     ___/  |  \\____ \/   \  ____/ __ \\__  \\_  __ \    |  _//  _ \  \/  /
//  |    |   |   Y  \  |_> >    \_\  \  ___/ / __ \|  | \/    |   (  <_> >    < 
//  |____|   |___|  /   __/ \______  /\___  >____  /__|  |______  /\____/__/\_ \
//                \/|__|           \/     \/     \/             \/            \/
// -----------------------------------------------------------------------------
//          Designed and Developed by Brad Jones <brad @="bjc.id.au" />         
// -----------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

use Gears\String as Str;
use Gears\Doc\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Generate extends Command
{
	/**
	 * Method: configure
	 * =========================================================================
	 * The following describes our commands arguments and options.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	protected function configure()
	{
		$this->setName('generate');

		$this->addOption
		(
			'name',
			null,
			InputOption::VALUE_REQUIRED,
			'A project name to use for the documentation site.'
		);

		$this->addOption
		(
			'input',
			null,
			InputOption::VALUE_REQUIRED,
			'The path of where your src files are located.'
		);

		$this->addOption
		(
			'ignore',
			null,
			InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
			'Path(s) to ignore in the input path.'
		);

		$this->addOption
		(
			'output',
			null,
			InputOption::VALUE_REQUIRED,
			'The path of where we will output the generated documentation.'
		);

		$this->addOption
		(
			'index',
			null,
			InputOption::VALUE_REQUIRED,
			'A document that we will use for the main index or home page.'
		);

		$this->addOption
		(
			'link',
			null,
			InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
			'Markdown link(s) to place in the header bar top right corner.'
		);

		$this->addOption
		(
			'ext',
			null,
			InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
			'Add more file extensions to be checked for doc blocks.'
		);

		$this->addOption
		(
			'additional-docs',
			null,
			InputOption::VALUE_REQUIRED,
			'A path to additional markdown documents to be merged into the site.'
		);
	}

	/**
	 * Method: execute
	 * =========================================================================
	 * So this is where the magic happens.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 *  - $input: This contains the commands input arguments and options, etc.
	 * 
	 *  - $output: This provides a means to output data back to the terminal
	 *             is a structured manner.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{		
		// First lets create ourselves a new generator object
		$generator = new Generator();

		// Set the project name
		if (!empty($name = $input->getOption('name')))
		{
			$generator->projectName = $name;
		}

		// Set the input path
		if (!empty($inputPath = $input->getOption('input')))
		{
			$generator->inputPath = $inputPath;
		}

		// Set the ignore paths
		if (!empty($ignore = $input->getOption('ignore')))
		{
			$generator->ignorePaths = $ignore;
		}

		// Set the output path
		if (!empty($outputPath = $input->getOption('output')))
		{
			$generator->outputPath = $outputPath;
		}

		// Set the index page
		if (!empty($index = $input->getOption('index')))
		{
			$generator->indexPage = $index;
		}

		// Set the header links
		if (!empty($links = $input->getOption('link')))
		{
			$parsedLinks = [];
			foreach ($links as $link)
			{
				$matches = Str::wildCardMatch($link, '[*](*)');
				$parsedLinks[$matches[2][0]] = $matches[1][0];
			}

			$generator->headerLinks = $parsedLinks;
		}

		// Set the file extensions
		if (!empty($exts = $input->getOption('ext')))
		{
			$generator->exts = array_merge
			(
				['php', 'js', 'css', 'less', 'sccs'],
				$exts
			);
		}

		// Set the additional docs path
		if (!empty($additional = $input->getOption('additional-docs')))
		{
			$generator->additionalMdDocuments = $additional;
		}

		// Finally run the generator
		$generator->run();
	}
}
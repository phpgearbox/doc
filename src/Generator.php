<?php namespace Gears\Doc;
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

use SplFileInfo;
use RuntimeException;
use Parsedown;
use Gears\View;
use Gears\Di\Container;
use Gears\String as Str;
use Gears\Arrays as Arr;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class Generator extends Container
{
	/**
	 * Property: inputPath
	 * =========================================================================
	 * The path of where your src files are located.
	 * Defaults to: ```./src```
	 */
	protected $injectInputPath;

	/**
	 * Property: outputPath
	 * =========================================================================
	 * The path of where we will output the generated documentation.
	 * Defaults to: ```./docs```
	 */
	protected $injectOutputPath;

	/**
	 * Property: ignorePaths
	 * =========================================================================
	 * Optional array that can be supplied of paths to ignore.
	 * Each path would be relative to the inputPath.
	 */
	protected $injectIgnorePaths;

	/**
	 * Property: exts
	 * =========================================================================
	 * An array of file extensions to parse.
	 * Only extensions listed here will be checked for docblocks.
	 * It defaults to: ```php, js, css, less, sccs```
	 */
	protected $injectExts;

	/**
	 * Property: view
	 * =========================================================================
	 * An instance of: ```Gears\View``` or ```Illuminate\View\Factory```
	 */
	protected $injectView;

	/**
	 * Property: parsedown
	 * =========================================================================
	 * An instance of: ```Parsedown```
	 */
	protected $injectParsedown;

	/**
	 * Property: filesystem
	 * =========================================================================
	 * An instance of: ```Symfony\Component\Filesystem\Filesystem```
	 */
	protected $injectFilesystem;

	/**
	 * Property: finder
	 * =========================================================================
	 * An factory for: ```Symfony\Component\Finder\Finder```
	 */
	protected $injectFinder;

	/**
	 * Property: base
	 * =========================================================================
	 * This is the base path we use for the HTML ```<base href="">``` tag.
	 */
	protected $injectBase;

	/**
	 * Method: setDefaults
	 * =========================================================================
	 * This is where we set all our defaults. If you need to customise this
	 * container this is a good place to look to see what can be configured
	 * and how to configure it.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	protected function setDefaults()
	{
		$this->inputPath = './src';

		$this->outputPath = './docs';

		$this->ignorePaths = [];

		$this->exts = ['php', 'js', 'css', 'less', 'sccs'];

		$this->view = function () { return new View(__DIR__.'/Views'); };

		$this->parsedown = function () { return new Parsedown(); };

		$this->filesystem = function () { return new Filesystem(); };

		$this->finder = $this->factory(function () { return new Finder(); });

		$this->base = false;
	}

	/**
	 * Method: run
	 * =========================================================================
	 * Once the container is configured, simply run this method
	 * and we will generate the documenation.
	 * 
	 * > WARNING: All content in the output path will be deleted!
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 * 
	 * Throws:
	 * -------------------------------------------------------------------------
	 * - RuntimeException: When the output path is not writeable.
	 */
	public function run()
	{
		// Make sure the paths don't have trailing slashes
		$this->normalisePaths();

		// Make sure the output folder exists and is writeable
		if (!is_dir($this->outputPath) || !is_writeable($this->outputPath))
		{
			// It is on the user to create the root output folder
			throw new RuntimeException
			(
				'Please create the output folder with appropriate permissions!'
			);
		}

		// Remove all contents of output folder
		$this->filesystem->remove($this->finder->in($this->outputPath));

		// Add the base detection file
		if (!$this->base)
		{
			file_put_contents($this->outputPath.'/base.html', '<!-- base -->');
		}

		// Create the data needed to make all our views
		$output_files = []; $nav = [];

		// Loop through each source file
		foreach ($this->getInputFiles() as $file)
		{
			// Extract the docblocks from the source file
			$blocks = $this->extractDocBlocks($file);

			// Skip to next file if no blocks found
			if (empty($blocks)) continue;

			// The following sets up our nav array
			$segments = Str::split($file->getRelativePath(), DIRECTORY_SEPARATOR);
			$existing = Arr::get($nav, $segments, []);
			$link = Str::replace($file->getRelativePathname(), $file->getExtension(), 'html');
			$existing[] = $link;
			Arr::set($nav, $segments, $existing);

			// Add the file and blocks to our list of views to create
			$output_files[$this->outputPath.'/'.$link] =
			[
				'src_file' => $file,
				'blocks' => $blocks
			];
		}

		// Now finally write each static file
		foreach ($output_files as $output_file => $data)
		{
			// Create our blade view
			$html = $this->view
				->make('master')
				->withNav($nav)
				->withFileInfo($data['src_file'])
				->withBlocks($data['blocks'])
				->withBase($this->base)
			;

			// Save the generated html
			$this->writeHtmlDocument($output_file, $html);
		}
	}

	/**
	 * Method: normalisePaths
	 * =========================================================================
	 * Ensure the paths don't have trailing slashes.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	private function normalisePaths()
	{
		if (Str::endsWith($this->inputPath, DIRECTORY_SEPARATOR))
		{
			$this->inputPath = substr($this->inputPath, 0, -1);
		}

		if (Str::endsWith($this->outputPath, DIRECTORY_SEPARATOR))
		{
			$this->outputPath = substr($this->outputPath, 0, -1);
		}
	}

	/**
	 * Method: getInputFiles
	 * =========================================================================
	 * Sets up a finder object to list all our input source files.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * Symfony\Component\Finder\Finder
	 */
	private function getInputFiles()
	{
		// Create our finder
		$finder = $this->finder;

		// Look for files in the input dir
		$finder->files()->in($this->inputPath);

		// Exclude any dirs
		foreach ($this->ignorePaths as $dir)
		{
			$finder->exclude($dir);
		}

		// Only look for registered extensions
		foreach ($this->exts as $ext)
		{
			$finder->name('*.'.$ext);	
		}

		// Return the finder
		return $finder;
	}

	/**
	 * Method: writeHtmlDocument
	 * =========================================================================
	 * Writes the given html to the given filename
	 * and creates folders as needed.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $filepath: The filepath to the new document.
	 * - $html: The html to write into the new document.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 * 
	 * Throws:
	 * -------------------------------------------------------------------------
	 * - RuntimeException: When we failed to write the new file.
	 */
	private function writeHtmlDocument($filepath, $html)
	{
		// Create any needed folders
		$folder = pathinfo($filepath, PATHINFO_DIRNAME);
		if (!$this->filesystem->exists($folder))
		{
			$this->filesystem->mkdir($folder);
		}

		// Save the new html document
		if (file_put_contents($filepath, $html) === false)
		{
			throw new RuntimeException('Failed to write file: '.$filepath);
		}
	}

	/**
	 * Method: extractDocBlocks
	 * =========================================================================
	 * Extracts each docblock using regular expression in the given file.
	 * We do not use any form of Reflection, we do not include the file.
	 * We only read it as a string.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $file: A SplFileInfo object for the file.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * array
	 */
	private function extractDocBlocks(SplFileInfo $file)
	{
		// Create our docblocks array
		$blocks = [];

		// Read in the file contents
		$lines = file($file->getPathname());

		// Get a list of lines that contain docblocks
		$linesFound = preg_grep('/^\s*(\/\*\*|\*\/?)/', $lines);

		// Setup some vars for the following loop
		$start = false; $current_block = '';

		// Loop through each line that we found
		foreach ($linesFound as $line_no => $line)
		{
			if (!$start)
			{
				// We are looking for the start of a docblock
				if (trim($line) == '/**') $start = $line_no;
			}
			else
			{
				// We are now looking for the end of a docblock
				if (trim($line) == '*/')
				{
					// Add a new block to our array
					$block = [];
					$block['lines'] = [$start+1, $line_no+1];
					$block['md'] = rtrim($current_block);
					$block['html'] = $this->parsedown->text($block['md']);
					$block['signature'] = trim($lines[$line_no+1]);
					$blocks[] = $block;

					// Reset the loop
					$start = false; $current_block = '';
				}
				else
				{
					// Remove leading whitespace
					$line = ltrim($line);

					// Remove the leading asterisk
					$line = ltrim($line, "* ");

					// Add the line to our current block
					$current_block .= $line;
				}
			}
		}

		// Return the blocks we found, if any
		return $blocks;
	}
}
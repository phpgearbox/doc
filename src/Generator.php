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

/**
 * Class: Generator
 * =============================================================================
 * Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo
 * ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis
 * parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec,
 * pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim.
 * 
 * How To Use:
 * -----------------------------------------------------------------------------
 * Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu.
 * In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo.
 * Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus.
 * Vivamus elementum semper nisi. Aenean vulputate eleifend tellus.
 * 
 * ```php
 * // Make sure the paths don't have trailing slashes
 * $this->normalisePaths();
 * 
 * // Make sure the output folder exists and is writeable
 * if (!is_dir($this->outputPath) || !is_writeable($this->outputPath))
 * {
 * 	// It is on the user to create the root output folder
 * 	throw new RuntimeException
 * 	(
 * 		'Please create the output folder with appropriate permissions!'
 * 	);
 * }
 * 
 * // Remove all contents of output folder
 * $this->filesystem->remove($this->finder->in($this->outputPath));
 * 
 * // Create the data needed to make all our views
 * $output_files = []; $nav = [];
 * ```
 * 
 * Other Info:
 * -----------------------------------------------------------------------------
 * - 123
 * - abc
 * - xyz
 */
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

	private $relativeUrls;

	private $lunrIndex;

	private $lunrIndexLookup;

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

		$this->relativeUrls = [];

		$this->lunrIndex = [];

		$this->lunrIndexLookup = [];
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
			$segments = Str::split($file->getRelativePath(), '/');
			if ($segments == [''])
			{
				$nav[] = $file;
			}
			else
			{
				$existing = Arr::get($nav, $segments, []);
				$existing[] = $file;
				Arr::set($nav, $segments, $existing);
			}

			// Create the output filename
			$output_file_name = $this->outputPath.'/'.Str::replace
			(
				$file->getRelativePathname(),
				$file->getExtension(),
				'html'
			);

			$this->generateLunrIndex($blocks, $file);

			// Add the file and blocks to our list of views to create
			$output_files[$output_file_name] =
			[
				'src_file' => $file,
				'blocks' => $blocks
			];
		}

		// Now finally write each static file
		foreach ($output_files as $output_file => $data)
		{
			$this->relativeUrls = [];

			$tree = $this->generateJsonTree($nav, $data['src_file']);

			// Create our blade view
			$html = $this->view
				->make('master')
				->withNav($tree)
				->withRelativeUrls('var relative_urls = '.json_encode($this->relativeUrls).';')
				->withLunrIndex('var lunr_index = '.json_encode($this->lunrIndex).';'."\n".'var lunr_index_lookup = '.json_encode($this->lunrIndexLookup).';')
				->withFileInfo($data['src_file'])
				->withBlocks($data['blocks'])
			;

			// Save the generated html
			$this->writeHtmlDocument($output_file, $html);
		}
	}

	private function generateLunrIndex($blocks, $file)
	{
		foreach ($blocks as $key => $block)
		{
			$index = [];
			$index['id'] = $file->getRelativePathname().'--gearsdoc--'.$key;
			$index['body'] = strip_tags($block['html']);
			if (isset($block['title'])) $index['title'] = $block['title'];
			if (isset($block['signature'])) $index['signature'] = $block['signature'];
			$this->lunrIndex[] = $index;
			$this->lunrIndexLookup[$index['id']] = count($this->lunrIndex)-1;
		}
	}

	private function generateJsonTree($nav, $file)
	{
		$tree = [];

		foreach ($nav as $key => $link)
		{
			$active = false;

			if (is_array($link))
			{
				if (in_array($key, Str::split($file->getRelativePath(), '/')))
				{
					$expanded = true;
				}
				else
				{
					$expanded = false;
				}

				$tree[] =
				[
					'title' => $key,
					'folder' => true,
					'expanded' => $expanded,
					'children' => $this->generateJsonTree($link, $file)
				];
			}
			else
			{
				// This is our own link
				if ($link->getRelativePathname() == $file->getRelativePathname())
				{
					$active = true;
					$uri = '#';
				}

				// We are in root, thus the full relative path will do.
				elseif ($file->getRelativePath() == '')
				{
					$uri = $link->getRelativePathname();
				}

				// The link is below us in the tree.
				elseif (Str::contains($link->getRelativePathname(), $file->getRelativePath()))
				{
					$uri = Str::replace
					(
						$link->getRelativePathname(),
						$file->getRelativePath().'/',
						''
					);
				}

				// The link must be above us in the tree
				else
				{
					// Start our uri string
					$uri = '';

					// Split the relative paths
					$segments_file = Str::split($file->getRelativePath(), '/');
					$segments_link = Str::split($link->getRelativePath(), '/');

					// Remove similar segments
					foreach ($segments_file as $segkey => $segment)
					{
						if (isset($segments_link[$segkey]))
						{
							if ($segment == $segments_link[$segkey])
							{
								array_shift($segments_file);
								array_shift($segments_link);
							}
						}
						else
						{
							break;
						}
					}

					// Add parent directory paths
					for ($i = 1; $i <= count($segments_file); $i++)
					{
						$uri .= '../';
					}

					// Join the link segments again
					$segments_link = implode('/', $segments_link);

					// Create the final uri
					$uri = Str::replace
					(
						$uri.$segments_link.'/'.$link->getFileName(),
						'//',
						'/'
					);
				}

				$uri = Str::replace
				(
					$uri,
					'.'.$link->getExtension(),
					'.html'
				);

				$this->relativeUrls[$link->getRelativePathname()] = $uri;

				// Add the new tree element
				$tree[] =
				[
					'title' => $link->getFileName(),
					'active' => $active,
					'focus' => $active,
					'href' => $uri
				];
			}
		}

		// Ensure folders are on top
		$temp = [];

		foreach ($tree as $key => $value)
		{
			if (isset($value['folder']))
			{
				$temp[] = $value;
			}
		}

		foreach ($tree as $key => $value)
		{
			if (!isset($value['folder']))
			{
				$temp[] = $value;
			}
		}

		$tree = $temp;

		// Return our tree
		return $tree;
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
					// Create a new block
					$block = [];
					$block['lines'] = [$start+1, $line_no+1];
					$block['md'] = rtrim($current_block);
					$block['html'] = $this->parsedown->text($block['md']);

					// The first <h1> element we consider the block title
					if (Str::contains($block['html'], '<h1>'))
					{
						$block['title'] = Str::between
						(
							$block['html'],
							'<h1>',
							'</h1>'
						);

						$block['html'] = Str::replace
						(
							$block['html'],
							'<h1>'.$block['title'].'</h1>',
							''
						);

						// Create the block context
						$title = Str::s($block['title'])->to('lower');

						if ($title->startsWith('class:'))
						{
							$block['context'] = 'panel-primary';
						}
						elseif ($title->startsWith('property:'))
						{
							$block['context'] = 'panel-success';
						}
						elseif ($title->startsWith('method:'))
						{
							$block['context'] = 'panel-info';
						}
						elseif ($title->startsWith('function:'))
						{
							$block['context'] = 'panel-warning';
						}
						else
						{
							$block['context'] = '';
						}
					}
					else
					{
						$block['context'] = '';
					}
					
					// The very next line after a docblock
					// we consider the method signature.
					if (isset($lines[$line_no+1]))
					{
						$sig = trim($lines[$line_no+1]);
						if ($sig != '') $block['signature'] = $sig;
					}
					
					// Add a new block to our array
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
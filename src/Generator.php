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

use Exception;
use SplFileInfo;
use Parsedown;
use Gears\View;
use Gears\String as Str;
use Gears\Arrays as Arr;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class Generator
{
	private $input = './src/';

	private $output = './docs';

	private $ignore = [];

	private $exts = ['php', 'js', 'css', 'less', 'sccs'];

	private $deps = [];

	public function __construct($options = [])
	{
		foreach ($options as $key => $value)
		{
			if (isset($this->{$key}))
			{
				$this->{$key} = $value;
			}
		}

		if (!isset($this->deps['view'])) $this->deps['view'] = new View(__DIR__.'/Views');
		if (!isset($this->deps['md'])) $this->deps['md'] = new Parsedown();
		if (!isset($this->deps['fileSystem'])) $this->deps['fileSystem'] = new Filesystem();
		if (!isset($this->deps['finder'])) $this->deps['finder'] = function() { return new Finder(); };

		$this->normalisePaths();
	}

	private function normalisePaths()
	{
		if (Str::endsWith($this->input, DIRECTORY_SEPARATOR))
		{
			$this->input = substr($this->input, 0, -1);
		}

		if (Str::endsWith($this->output, DIRECTORY_SEPARATOR))
		{
			$this->output = substr($this->output, 0, -1);
		}
	}

	public function run()
	{
		// Make sure the output folder exists and is writeable
		if (!is_dir($this->output) || !is_writable($this->output))
		{
			// It is on the user to create the root output folder
			throw new Exception
			(
				'Please create the output folder with appropriate permissions!'
			);
		}

		// Remove all contents of output folder
		$this->deps['fileSystem']->remove
		(
			$this->deps['finder']()->in($this->output)
		);

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
			$output_files[$this->output.'/'.$link] =
			[
				'src_file' => $file,
				'blocks' => $blocks
			];
		}

		// Now finally write each static file
		foreach ($output_files as $output_file => $data)
		{
			// Create our blade view
			$html = $this->deps['view']
				->make('master')
				->withNav($nav)
				->withFileInfo($data['src_file'])
				->withBlocks($data['blocks'])
			;

			// Save the generated html
			$this->writeHtmlDocument($output_file, $html);
		}
	}

	private function writeHtmlDocument($filename, $html)
	{
		// Create any needed folders
		$folder = pathinfo($filename, PATHINFO_DIRNAME);
		if (!$this->deps['fileSystem']->exists($folder))
		{
			$this->deps['fileSystem']->mkdir($folder);
		}

		// Save the new markdown document
		file_put_contents($filename, $html);
	}

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
					$block['html'] = $this->deps['md']->text($block['md']);
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

	private function getInputFiles()
	{
		// Create our finder
		$finder = $this->deps['finder']();

		// Look for files in the input dir
		$finder->files()->in($this->input);

		// Exclude any dirs
		foreach ($this->ignore as $dir)
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
}
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

use RuntimeException;
use Parsedown;
use CssMin;
use JShrink\Minifier as JsMin;
use Gears\View;
use Gears\Di\Container;
use Gears\String as Str;
use Gears\Arrays as Arr;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class: Generator
 * =============================================================================
 * TODO: When this class is actually finalised
 * I will write some nice documentation here.
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
	 * Property: indexPage
	 * =========================================================================
	 * This is a path to a document that we will use for the main
	 * index or home page of the documentation website.
	 * 
	 * If the document has a ```.html``` extension we will use the HTML as is
	 * without any modification, giving you ultimate control.
	 * 
	 * If the document ends in ```.md``` we of course will convert the markdown
	 * into HTML for you. But unlike the markdown *docblocks* we do no further
	 * processing.
	 * 
	 * Defaults to: ```$this->inputPath.'/../README.md'```
	 */
	protected $injectIndexPage;

	/**
	 * Property: projectName
	 * =========================================================================
	 * You don't have to set this but I would recommend that you do.
	 * It is used in the header on the top left.
	 * We default to: *The Doctor*
	 */
	protected $injectProjectName;

	/**
	 * Property: headerLinks
	 * =========================================================================
	 * This completely optional. We use this in the header at the top right.
	 * If you do decide you would like some links in your header please supply
	 * an array that looks like this:
	 * 
	 * ```php
	 * [
	 * 		'https://github.com/phpgearbox/doc' => 'GitHub',
	 * 		'https://travis-ci.org/phpgearbox/doc' => 'Travis CI'
	 * 		'http://www.bjc.id.au/' => 'Brad Jones Computing'
	 * ]
	 * ```
	 */
	protected $injectHeaderLinks;

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
	 * Property: cssMin
	 * =========================================================================
	 * This must be a protected callable that can minify css.
	 * For example:
	 *
	 * ```php
	 * $this->protect(function($css){ return CssMin::minify($css); });
	 * ```
	 */
	protected $injectCssMin;

	/**
	 * Property: jsMin
	 * =========================================================================
	 * This must be a protected callable that can minify js.
	 * For example:
	 *
	 * ```php
	 * $this->protect(function($js){ return JsMin::minify($js); });
	 * ```
	 */
	protected $injectJsMin;

	/**
	 * Property: nav
	 * =========================================================================
	 * We use this to store a hierarchal array of all the files we are
	 * generating documentation for. It is then used later on by
	 * ```generateJsonTree()``` to create the fancy tree and ```$relativeUrls```
	 * property.
	 */
	private $nav;

	/**
	 * Property: relativeUrls
	 * =========================================================================
	 * This helps with the lunr search index. It was a bit of an after thought
	 * that I added into the ```generateJsonTree()``` method. Ideally I could
	 * refactor the FancyTree to also use this to lookup the correct URL.
	 */
	private $relativeUrls;

	/**
	 * Property: lunrIndex
	 * =========================================================================
	 * This is the main lunr index array that gets converted to JSON just before
	 * sending to the view. The browser will then loop over this to create the
	 * final index. Ideally I would like to generate the index at generation
	 * time using node perhaps.
	 * 
	 * See: http://lunrjs.com/docs/#Index
	 * 
	 *   - lunr.Index.prototype.toJSON()
	 *   - lunr.Index.load()
	 */
	private $lunrIndex;

	/**
	 * Property: lunrIndexLookup
	 * =========================================================================
	 * This is an index of the index haha... Actually thats not too far from the
	 * truth. Unfortunately lunr does not return the full document when you
	 * perform a search query, only the document id. So we use this to tell us
	 * which document it is in our original lunrIndex json.
	 * 
	 * @see: Views/script.blade.php
	 * 
	 * ```var doc = lunr_index[lunr_index_lookup[result.ref]];```
	 */
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

		$this->indexPage = $this->inputPath.'/../README.md';

		$this->projectName = 'The Doctor';

		$this->headerLinks = ['https://github.com/phpgearbox/doc' => 'GitHub'];

		$this->exts = ['php', 'js', 'css', 'less', 'sccs'];

		$this->view = function () { return new View(__DIR__.'/Views'); };

		$this->parsedown = function () { return new Parsedown(); };

		$this->filesystem = function () { return new Filesystem(); };

		$this->finder = $this->factory(function () { return new Finder(); });

		$this->relativeUrls = [];

		$this->lunrIndex = [];

		$this->lunrIndexLookup = [];

		$this->cssMin = $this->protect(function($css){ return CssMin::minify($css); });

		$this->jsMin = $this->protect(function($js){ return JsMin::minify($js); });
	}

	/**
	 * Method: run
	 * =========================================================================
	 * Once the container is configured, simply run this method
	 * and we will generate the documentation.
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
	 * - RuntimeException: When the output path is not writable.
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

		// The below loop will fill this array
		$output_files = [];

		// Loop through each source file
		foreach ($this->getInputFiles() as $file)
		{
			// Extract the docblocks from the source file
			$blocks = $this->extractDocBlocks($file);

			// Skip to next file if no blocks found
			if (empty($blocks)) continue;

			// Create the lunr index
			$this->generateLunrIndex($blocks, $file);

			// The following sets up our nav array
			$segments = Str::split($file->getRelativePath(), '/');
			if ($segments == [''])
			{
				$this->nav[] = $file;
			}
			else
			{
				$existing = Arr::get($this->nav, $segments, []);
				$existing[] = $file;
				Arr::set($this->nav, $segments, $existing);
			}

			// Create the output filename
			$output_file_name = $this->outputPath.'/'.Str::replace
			(
				$file->getRelativePathname(),
				'.'.$file->getExtension(),
				'.html'
			);

			// Add the file and blocks to our list of views to create
			$output_files[$output_file_name] =
			[
				'src_file' => $file,
				'blocks' => $blocks
			];
		}

		// Now finally write each static file
		// NOTE: We need to do this in 2 loops because we need to
		// know about all files to generate the fancy tree navigation.
		foreach ($output_files as $output_file => $data)
		{
			// Reset our relative urls, these change per file obviously.
			$this->relativeUrls = [];

			// Create the fancy tree json
			$tree = $this->generateJsonTree($data['src_file']);

			// Create the home page link
			$homeLink = 'index.html';
			$stylePath = 'assets/css/style.css';
			$scriptPath = 'assets/js/script.js';
			$parts = Str::split($data['src_file']->getRelativePathname(), '/');
			for ($i = 2; $i <= count($parts); $i++)
			{
				$homeLink = '../'.$homeLink;
				$stylePath = '../'.$stylePath;
				$scriptPath = '../'.$scriptPath;
			}

			// Create our blade view
			$html = $this->view
				->make('layouts.doc')
				->withNav($tree)
				->withRelativeUrls(json_encode($this->relativeUrls))
				->withLunrIndex(json_encode($this->lunrIndex))
				->withLunrIndexLookup(json_encode($this->lunrIndexLookup))
				->withFileInfo($data['src_file'])
				->withBlocks($data['blocks'])
				->withHomeLink($homeLink)
				->withProjectName($this->projectName)
				->withHeaderLinks($this->headerLinks)
				->withStylePath($stylePath)
				->withScriptPath($scriptPath)
			;

			// Save the generated html
			$this->writeDocument($output_file, $html);
		}

		// Create the index / home page for our documentation site.
		$this->generateHomePage();

		// Lets build some css and js assets
		$this->buildAssets();
	}

	/**
	 * Method: buildAssets
	 * =========================================================================
	 *
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 *
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	private function buildAssets()
	{
		// Intialise some variables to keep the compiled assets
		$js = ''; $css = '';

		// NOTE: Order is important for the following arrays.
		$js_files =
		[
			'jquery',
			'jquery-ui',
			'bootstrap',
			'fancytree',
			'lunr',
			'highlight',
			'main'
		];

		$css_files =
		[
			'bootstrap',
			'bootstrap-theme',
			'font-awesome',
			'fancytree',
			'highlight-github-theme',
			'main'
		];

		// Loop through and build the js assets
		foreach ($js_files as $asset)
		{
			$js .= $this['jsMin']
			(
				file_get_contents
				(
					__DIR__.'/Views/assets/js/'.$asset.'.js'
				)
			);
		}

		// Loop through and build the css assets
		foreach ($css_files as $asset)
		{
			$css .= $this['cssMin']
			(
				file_get_contents
				(
					__DIR__.'/Views/assets/css/'.$asset.'.css'
				)
			);
		}

		// Save some compiled assets
		$this->writeDocument($this->outputPath.'/assets/js/script.js', $js);
		$this->writeDocument($this->outputPath.'/assets/css/style.css', $css);

		// Copy across some other static assets
		$this->filesystem->mirror(__DIR__.'/Views/assets/fonts', $this->outputPath.'/assets/fonts');
		$this->filesystem->mirror(__DIR__.'/Views/assets/img', $this->outputPath.'/assets/img');
	}

	/**
	 * Method: generateHomePage
	 * =========================================================================
	 * Every website needs a home page. This will generate one.
	 * The basic idea is that every project will have a README.md so
	 * why not just use that as the contents for the home page.
	 * 
	 * @see: ```Property: indexPage```
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	private function generateHomePage()
	{
		// Reset our relative urls, these change per file obviously.
		$this->relativeUrls = [];

		// Create a dummy file object so we can generate the tree
		$file = new SplFileInfo('index.html', '', 'index.html');

		// Create the fancy tree json
		$tree = $this->generateJsonTree($file);

		// Now lets read in the source index file
		$html = file_get_contents($this->indexPage);

		// If its markdown lets convert it
		if (Str::endsWith($this->indexPage, '.md'))
		{
			$html = $this->parsedown->text($html);
		}

		// Create our blade view
		// NOTE: We use a slightly different blade template for the home page
		$html = $this->view
			->make('layouts.home')
			->withNav($tree)
			->withRelativeUrls(json_encode($this->relativeUrls))
			->withLunrIndex(json_encode($this->lunrIndex))
			->withLunrIndexLookup(json_encode($this->lunrIndexLookup))
			->withContent($html)
			->withHomeLink('#')
			->withProjectName($this->projectName)
			->withHeaderLinks($this->headerLinks)
			->withStylePath('assets/css/style.css')
			->withScriptPath('assets/js/script.js')
		;

		// Save the generated html
		$this->writeDocument($this->outputPath.'/index.html', $html);
	}

	/**
	 * Method: generateLunrIndex
	 * =========================================================================
	 * We are using a jquery plugin that implements the *Lucene* search.
	 * @see: http://lunrjs.com/
	 * 
	 * This generates an array which then gets converted to JSON. The browser
	 * then loops over the JSON to create the final index. I almost could have
	 * passed the "blocks" array directly but needed to remove HTML tags, etc.
	 * 
	 * > NOTE: That currently every single document that we write gets a copy
	 * > of the index. From a space perspective this isn't very efficient.
	 * > And for very large projects this may need to be refactored to use 
	 * > some AJAX. But for now the searches are indeed *very fast* :)
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $blocks: An array of *docblocks* returned by the method
	 *   ```extractDocBlocks()```
	 * 
	 * - $file: This refers to the file that the *docblock* belongs to.
	 *   It must be an instance of ```Symfony\Component\Finder\SplFileInfo```
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * array
	 */
	private function generateLunrIndex($blocks, SplFileInfo $file)
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

	/**
	 * Method: generateJsonTree
	 * =========================================================================
	 * We are using a jquery plugin to create our sidebar tree menu.
	 * @see: https://github.com/mar10/fancytree
	 * 
	 * The plugin uses a JSON object to build the tree. This method generates
	 * the JSON object for each static file we output. The reason we can't share
	 * the same JSON is because the links change for each file as they are
	 * relative to one another.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $file: This refers to the current file we are generating the
	 *   documentation for. It must be an instance of
	 *   ```Symfony\Component\Finder\SplFileInfo```
	 * 
	 * - $recurse: This is only used recursively. If this is not set we start
	 *   looping over the ```$this->nav``` property.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * array
	 */
	private function generateJsonTree(SplFileInfo $file, $recurse = null)
	{
		// This is what gets returned
		$tree = [];

		// Have we been called recursively or not?
		if (is_null($recurse))
		{
			// Grab the root nav array
			$recurse = $this->nav;
		}

		foreach ($recurse as $key => $link)
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
					'children' => $this->generateJsonTree($file, $link)
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

				// Work out the icon relative path
				$icon_path = 'assets/img/silk';
				$parts = Str::split($file->getRelativePathname(), '/');
				for ($i = 2; $i <= count($parts); $i++)
				{
					$icon_path = '../'.$icon_path;
				}

				// Work out which icon we will show
				switch ($link->getExtension())
				{
					case 'php': $icon = $icon_path.'/page_white_php.png'; break;
					case 'html': $icon = $icon_path.'/html.png'; break;
					case 'css': $icon = $icon_path.'/css.png'; break;
					case 'js': $icon = $icon_path.'/script_code.png'; break;
					default: $icon = null;
				}

				// Add the new tree element
				$tree[] =
				[
					'title' => $link->getFileName(),
					'active' => $active,
					'focus' => $active,
					'href' => $uri,
					'icon' => $icon
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
	 * Method: writeDocument
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
	private function writeDocument($filepath, $html)
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
	 * We only read it as a string. This means we work the same regardless of
	 * language. This will work with LESS just as it does for PHP or JS.
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
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
 * This is main class of this package.
 * 
 * While it is rather large and complex _(version 2.0 will probably get a
 * re-factor so that we are slightly more object oriented rather one big
 * procedural mess)_, it's use is fairly straight forward.
 * 
 * It extends the [Gears\Di](https://github.com/phpgearbox/di)
 * container which makes configuration super easy.
 *
 * > NOTE: The following example is for someone that wants to consume this class
 * > with in some sort of larger system. If all you wish to do is run *gearsdoc*
 * > you should see the main [README](index.html).
 * 
 * For Example:
 * -----------------------------------------------------------------------------
 * ```php
 * // Create the generator
 * $g = new Gears\Doc\Generator();
 *
 * // At the very least you will probably want to set the following
 * $g->inputPath = '/the/path/to/your/source/code';
 * $g->outputPath = '/the/path/to/where/you/want/the/generated/docs/to/go';
 * $g->projectName = 'Your Awesome Project Name';
 *
 * // Once the generator has been configured all you need to do is run it
 * $g->run();
 * ```
 *
 * Thats it, apart from all the other injectable properties there are no other
 * public methods. I have set everything else to protected so feel free to
 * extend this class and make any modifications you wish.
 */
class Generator extends Container
{
	/**
	 * Property: inputPath
	 * =========================================================================
	 * The path of where your src files are located.
	 * 
	 * Defaults to: ```./src```
	 */
	protected $injectInputPath;

	/**
	 * Property: outputPath
	 * =========================================================================
	 * The path of where we will output the generated documentation.
	 * 
	 * Defaults to: ```./docs```
	 */
	protected $injectOutputPath;

	/**
	 * Property: ignorePaths
	 * =========================================================================
	 * Optional array that can be supplied of paths to ignore.
	 * Each path would be relative to the [Property: inputPath](#).
	 * 
	 * _The paths are ignored by using the Finder ```notPath()```
	 * method, which also accepts regular expressions._
	 * 
	 * For more info see:
	 * http://api.symfony.com/2.5/Symfony/Component/Finder/Finder.html
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
	 * 
	 * Defaults to: ```The Doctor```
	 */
	protected $injectProjectName;

	/**
	 * Property: headerLinks
	 * =========================================================================
	 * This is completely optional. We use this in the header at the top right.
	 * If you do decide you would like some links in your header please supply
	 * an array that looks like this.
	 * 
	 * For Example:
	 * -------------------------------------------------------------------------
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
	 * 
	 * Defaults to: ```php, js, css, less, sccs```
	 */
	protected $injectExts;

	/**
	 * Property: viewPath
	 * =========================================================================
	 * This is the path to the blade views. The bundled views are very
	 * customisable through a number of other injectable properties.
	 * Perhaps all you need to do is override some css or js,
	 * checkout:
	 * 
	 *   - [protected $injectJsAssets;](#)
	 *   - [protected $injectCssAssets;](#)
	 * 
	 * However if you do find yourself wanting to completely replace the blade
	 * templates with your own. Just provide the path to the templates here.
	 * 
	 * For Example:
	 * -------------------------------------------------------------------------
	 * ```php
	 * $g = new Generator();
	 * $g->viewPath = '/my/custom/blade/templates';
	 * ```
	 */
	protected $injectViewPath;

	/**
	 * Property: view
	 * =========================================================================
	 * An instance of: ```Gears\View``` or ```Illuminate\View\Factory```
	 *
	 * > NOTE: If you wanted to completely replace the view layer
	 * > this is where to do it. But the new view class must still
	 * > extend ```Illuminate\View\Factory```.
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
	 * A factory for: ```Symfony\Component\Finder\Finder```
	 */
	protected $injectFinder;

	/**
	 * Property: assetMin
	 * =========================================================================
	 * If set to true we will run the minfication on our main js and css assets.
	 * If set to false we will not perform the minfication thus allowing easier
	 * debugging.
	 *
	 * Defaults to: ```true```
	 */
	protected $injectAssetMin;

	/**
	 * Property: cssMin
	 * =========================================================================
	 * This must be a protected callable that can minify css.
	 * 
	 * For Example:
	 * -------------------------------------------------------------------------
	 * ```php
	 * $g = new Generator();
	 * $g->protect(function($css){ return CssMin::minify($css); });
	 * ```
	 * 
	 * _For more info on how the gears dependency injection container works,
	 * have a look at: https://github.com/phpgearbox/di_
	 */
	protected $injectCssMin;

	/**
	 * Property: jsMin
	 * =========================================================================
	 * This must be a protected callable that can minify js.
	 * 
	 * For Example:
	 * -------------------------------------------------------------------------
	 * ```php
	 * $g = new Generator();
	 * $g->protect(function($js){ return JsMin::minify($js); });
	 * ```
	 *
	 * _For more info on how the gears dependency injection container works,
	 * have a look at: https://github.com/phpgearbox/di_
	 */
	protected $injectJsMin;

	/**
	 * Property: jsAssets
	 * =========================================================================
	 * This is an array of javascript files that will get concatenated and
	 * minifed together. This should be obvious hopefully but if you replace
	 * one of the core assets for example ```highlight.js```, the new asset must
	 * provide the same public API / functionality.
	 */
	protected $injectJsAssets;

	/**
	 * Property: cssAssets
	 * =========================================================================
	 * Same idea as the jsAssets above, this is an array of stylesheet files
	 * that will get concatenated and minifed together. 
	 */
	protected $injectCssAssets;

	/**
	 * Property: staticAssets
	 * =========================================================================
	 * Again similar concept to the js and css asset arrays. However this time
	 * we provide entire folders. Each folder is then copied into the output
	 * paths ```/assets``` directory.
	 *
	 * For Example:
	 * -------------------------------------------------------------------------
	 *
	 * ```php
	 * $g = new Generator();
	 * $g->outputPath = '/path/to/generated/docs';
	 * $g->staticAssets = ['/path/to/some/images'];
	 * $g->run();
	 * ```
	 *
	 * Now you will find a copy of your images here:
	 * ```/path/to/generated/docs/assets/images```
	 */
	protected $injectStaticAssets;

	/**
	 * Property: blockContexts
	 * =========================================================================
	 * Each doc block can be given a special css class to style it differently
	 * from other doc blocks. This works by using the doc block title.
	 * 
	 * _Obviously this means if the doc block
	 * does not have a title this will not work._
	 * 
	 * The doc block title is the first ```<h1>``` element of the doc block.
	 * Thus the title of this very doc block is ```Property: blockContexts```.
	 * 
	 * Example:
	 * -------------------------------------------------------------------------
	 * The array that you provide must look something like this:
	 * 
	 * ```php
	 * $g = new Generator();
	 * $g->blockContexts =
	 * [
	 * 		'Property:' => 'panel-success',
	 * 		'Custom:'	=> 'custom-css-class'
	 * ];
	 * ```
	 * 
	 *  - Where the array keys denote the _"starting"_ value of the title.
	 *  - And the array values donate the custom css class you wish to apply.
	 * 
	 * > NOTE: The default setup just uses the built in bootstrap contexts but
	 * > there is no reason that with some custom css classes in conjunction
	 * > with this property that you could create any custom contexts you wish.
	 * 
	 * For consumption see:
	 * [```foreach ($this->blockContexts as $start => $class)```](#)
	 * 
	 * TODO: 
	 * -------------------------------------------------------------------------
	 * I could possibly take this one step further by providing custom
	 * blade views for different types of doc blocks.
	 * 
	 * Starting to stray away from the original simplicity though...
	 */
	protected $injectBlockContexts;

	/**
	 * Property: additionalMdDocuments
	 * =========================================================================
	 * Apart from the simplest of projects most of the time you will probably
	 * want to write additional documentation, such as detailed examples and
	 * tutorials, in addition to the _docblock_ documentation.
	 * 
	 * Example:
	 * -------------------------------------------------------------------------
	 * ```php
	 * $g = new Generator();
	 * $g->additionalMdDocuments = '/path/to/your/additional/md/docs';
	 * ```
	 */
	protected $injectAdditionalMdDocuments;

	/**
	 * Property: nav
	 * =========================================================================
	 * We use this to store a hierarchal array of all the files we are
	 * generating documentation for. It is then used later on by
	 * [Method: generateJsonTree](#) to create the fancy tree and
	 * the [Property: relativeUrls](#) property.
	 *
	 * > NOTE: This is not part of the public API.
	 */
	protected $nav;

	/**
	 * Property: relativeUrls
	 * =========================================================================
	 * This helps with the lunr search index. It was a bit of an after thought
	 * that I added into [Method: generateJsonTree](#). Ideally I could
	 * refactor the FancyTree to also use this to lookup the correct URL.
	 *
	 * > NOTE: This is not part of the public API.
	 */
	protected $relativeUrls;

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
	 *
	 * > NOTE: This is not part of the public API.
	 */
	protected $lunrIndex;

	/**
	 * Property: lunrIndexLookup
	 * =========================================================================
	 * This is an index of the index haha... Actually thats not too far from the
	 * truth. Unfortunately lunr does not return the full document when you
	 * perform a search query, only the document id. So we use this to tell us
	 * which document it is in our original lunr_index json.
	 * 
	 * See: [```var doc = lunr_index[lunr_index_lookup[result.ref]];```](Views/assets/js/main.js)
	 * 
	 * > NOTE: This is not part of the public API.
	 */
	protected $lunrIndexLookup;

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

		$this->viewPath = __DIR__.'/Views';

		$this->view = function () { return new View($this->viewPath); };

		$this->parsedown = function () { return new Parsedown(); };

		$this->filesystem = function () { return new Filesystem(); };

		$this->finder = $this->factory(function () { return new Finder(); });

		$this->relativeUrls = [];

		$this->lunrIndex = [];

		$this->lunrIndexLookup = [];

		$this->assetMin = false;

		$this->cssMin = $this->protect(function($css)
		{
			return CssMin::minify($css);
		});

		$this->jsMin = $this->protect(function($js)
		{
			return JsMin::minify($js);
		});

		// NOTE: Order is important for the following arrays.
		// Especially so for the js assets for obvious reasons.
		$this->jsAssets =
		[
			__DIR__.'/Views/assets/js/jquery.js',
			__DIR__.'/Views/assets/js/jquery-ui.js',
			__DIR__.'/Views/assets/js/bootstrap.js',
			__DIR__.'/Views/assets/js/fancytree.js',
			__DIR__.'/Views/assets/js/highlight.js',
			__DIR__.'/Views/assets/js/typeahead.js',
			__DIR__.'/Views/assets/js/lunr.js',
			__DIR__.'/Views/assets/js/main.js'
		];

		$this->cssAssets =
		[
			__DIR__.'/Views/assets/css/bootstrap.css',
			__DIR__.'/Views/assets/css/bootstrap-theme.css',
			__DIR__.'/Views/assets/css/font-awesome.css',
			__DIR__.'/Views/assets/css/fancytree.css',
			__DIR__.'/Views/assets/css/highlight-github-theme.css',
			__DIR__.'/Views/assets/css/typeahead.css',
			__DIR__.'/Views/assets/css/main.css'
		];

		$this->staticAssets =
		[
			__DIR__.'/Views/assets/img',
			__DIR__.'/Views/assets/fonts'
		];

		$this->blockContexts =
		[
			'Class:'	=> 'panel-primary',
			'Property:'	=> 'panel-success',
			'Method:'	=> 'panel-info',
			'Function:'	=> 'panel-info',
			'Section:'	=> 'panel-warning',
			'Event:'	=> 'panel-danger'
		];
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
			throw new RuntimeException
			(
				'Please create the output folder with appropriate permissions!'
			);
		}

		// Remove all contents of output folder
		$this->filesystem->remove($this->finder->in($this->outputPath));

		// Create a list of views to create
		$views = [];

		// This loop runs through all the src files and extracts the docblocks
		foreach ($this->getInputFiles() as $file)
		{
			// Grab the blocks from the file, skip to next file if no blocks
			$blocks = $this->extractDocBlocks($file);
			if (empty($blocks)) continue;

			// Create the Lunr Index for this file
			$this->generateLunrIndex($blocks, $file);

			// Add the file to our nav array
			$this->addFileToNav($file);

			// Add the file and blocks to our list of views to create
			$views[$this->getOutputFileName($file)] =
			[
				'src_file' => $file,
				'blocks' => $blocks
			];
		}

		// This loop runs through all the addtional mark down documents, if any
		foreach ($this->getMarkDownFiles() as $file)
		{
			// Grab the blocks from the file, skip to next file if no blocks
			$blocks = $this->extractPseudoDocBlocks($file);
			if (empty($blocks)) continue;

			// Add the file to our nav array
			$this->addFileToNav($file);

			// Create the Lunr Index for this file
			$this->generateLunrIndex($blocks, $file);

			// Add the file and blocks to our list of views to create
			$views[$this->getOutputFileName($file)] =
			[
				'src_file' => $file,
				'blocks' => $blocks,
				'additional' => true
			];
		}

		// Now finally write each static file
		foreach ($views as $output_file => $data)
		{
			// Reset our relative urls, these change per file obviously.
			$this->relativeUrls = [];

			// Create the fancy tree json
			$tree = $this->generateJsonTree($data['src_file']);

			// Search for any internal links
			$data['blocks'] = $this->searchForInternalLinks($data, $views);

			// Create some more relative links
			$homeLink = 'index.html';
			$stylePath = 'assets/style.css';
			$scriptPath = 'assets/script.js';
			$parts = Str::split($data['src_file']->getRelativePathname(), '/');
			for ($i = 2; $i <= count($parts); $i++)
			{
				$homeLink = '../'.$homeLink;
				$stylePath = '../'.$stylePath;
				$scriptPath = '../'.$scriptPath;
			}

			// Which layout will we use?
			if (isset($data['additional']))
			{
				$layout = 'layouts.additional';
			}
			else
			{
				$layout = 'layouts.doc';
			}

			// Create our blade view
			$html = $this->view
				->make($layout)
				->withNav($tree)
				->withRelativeUrls($this->relativeUrls)
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
	 * Method: generateHomePage
	 * =========================================================================
	 * Every website needs a home page. This will generate one.
	 * The basic idea is that every project will have a README.md so
	 * why not just use that as the contents for the home page.
	 * 
	 * See: [Property: indexPage](#)
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	protected function generateHomePage()
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

		/*
		 * Lets build an array of the sections, delimited by <h1> and <h2>
		 * elements. This is used for the Table of Contents in the right
		 * hand sidebar.
		 */
		$section_key = 0; $sections = [];
		$h1s = Str::betweenRegx($html, '<h1>', '</h1>');
		$h2s = Str::betweenRegx($html, '<h2>', '</h2>');

		foreach ($h1s[1] as $k => $v)
		{
			$sections[] = $v;

			$html = Str::replace
			(
				$html,
				$h1s[0][$k],
				'<h1 id="block_'.$section_key.'">'.$v.'</h1>'
			);

			$section_key++;
		}

		foreach ($h2s[1] as $k => $v)
		{
			$sections[] = $v;

			$html = Str::replace
			(
				$html,
				$h2s[0][$k],
				'<h2 id="block_'.$section_key.'">'.$v.'</h2>'
			);

			$section_key++;
		}

		// Create our blade view
		$html = $this->view
			->make('layouts.home')
			->withNav($tree)
			->withRelativeUrls($this->relativeUrls)
			->withLunrIndex($this->lunrIndex)
			->withLunrIndexLookup($this->lunrIndexLookup)
			->withContent($html)
			->withSections($sections)
			->withHomeLink('#')
			->withProjectName($this->projectName)
			->withHeaderLinks($this->headerLinks)
			->withStylePath('assets/style.css')
			->withScriptPath('assets/script.js')
		;

		// Save the generated html
		$this->writeDocument($this->outputPath.'/index.html', $html);
	}

	/**
	 * Method: buildAssets
	 * =========================================================================
	 * Here we build the javascript and stylesheet assets into 2 main files.
	 *
	 *   - ```$this->outputPath.'/assets/script.js'```
	 *   - ```$this->outputPath.'/assets/style.css'```
	 *
	 * We also copy some additional static assets, such as fonts and images,
	 * into the output path's ```/assets``` directory.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 *
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	protected function buildAssets()
	{
		// Intialise some variables to keep the compiled assets
		$js = ''; $css = '';

		// Loop through and build the js assets
		foreach ($this->jsAssets as $asset)
		{
			$js .= file_get_contents($asset);
		}

		// Loop through and build the css assets
		foreach ($this->cssAssets as $asset)
		{
			$css .= file_get_contents($asset);
		}

		// Create the lunr index view
		$js .= $this->view
			->make('snippets.lunr-index')
			->withLunrIndex($this->lunrIndex)
			->withLunrIndexLookup($this->lunrIndexLookup)
		;

		// Minify the assets
		if ($this->assetMin)
		{
			$js = $this['jsMin']($js);
			$css = $this['cssMin']($css);
		}

		// Save some compiled assets
		$this->writeDocument($this->outputPath.'/assets/script.js', $js);
		$this->writeDocument($this->outputPath.'/assets/style.css', $css);

		// Copy across some other static assets
		foreach ($this->staticAssets as $asset)
		{
			// Source folder
			$from = $asset;

			// Output folder
			$to = $this->outputPath.'/assets/'.basename($asset);

			// Mirror the folder
			$this->filesystem->mirror($from, $to);
		}
	}

	/**
	 * Method: getOutputFileName
	 * =========================================================================
	 * A very simple method to turn an input file into the output file name.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 *  - $file: The file object to tunr into the output file name.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * string
	 */
	protected function getOutputFileName(SplFileInfo $file)
	{
		return $this->outputPath.'/'.Str::replace
		(
			$file->getRelativePathname(),
			'.'.$file->getExtension(),
			'.html'
		);
	}

	/**
	 * Method: addFileToNav
	 * =========================================================================
	 * Creates the nav array, see: [Property: nav](#)
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 *  - $file: The file object that we are about to add to the nav array.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	protected function addFileToNav(SplFileInfo $file)
	{
		// Split the file path into it's segments
		$segments = Str::split($file->getRelativePath(), '/');

		// Are we at the root?
		if ($segments == [''])
		{
			$this->nav[] = $file;
		}
		else
		{
			// Grab the existing portion of the nav array if it exists
			$existing = Arr::get($this->nav, $segments, []);

			// Add our file to the array
			$existing[] = $file;

			// And then place it back into the root nav
			Arr::set($this->nav, $segments, $existing);
		}
	}
	
	/**
	 * Method: searchForInternalLinks
	 * =========================================================================
	 * This makes internal links to other doc blocks work.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 *  - $data: An array which represents the current file we are building.
	 *    
	 *  - $all_blocks: An array of all the blocks.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * array
	 */
	protected function searchForInternalLinks($data, $all_blocks)
	{
		$current_blocks = $data['blocks'];

		// Loop through each docblock
		foreach ($current_blocks as $block_key => $block)
		{
			// Does the block contain any links?
			if (Str::contains($block['html'], '</a>'))
			{
				// Extract those links
				$matches = Str::wildCardMatch($block['html'], '<a*href="*"*>*</a>');

				// Loop through the href attributes of the links
				foreach ($matches[2] as $match_key => $url)
				{
					// The link is local to the current file
					if ($url == '#')
					{
						// We will search the current set of blocks
						$blocks_to_search = $current_blocks;

						// The URL prefix is nothing as the link
						// is internal to the current document.
						$url_prefix = '';
					}

					// We assume the link is pointing to another doc file if
					// it does not have a protocol identifier.
					elseif (!Str::contains($url, '://'))
					{
						// Now we need to lookup the relative URL
						if (isset($this->relativeUrls[$url]))
						{
							$url_prefix = $this->relativeUrls[$url];
						}
						else
						{
							continue;
						}

						// Make sure the link has a .html extension
						$urlHTML = Str::replace
						(
							$url,
							'.'.pathinfo($url, PATHINFO_EXTENSION),
							'.html'
						);

						// Create the output file path
						$path = Str::replace($this->outputPath.'/'.$urlHTML, '//', '/');

						// Grab the blocks from that file
						if (isset($all_blocks[$path]))
						{
							$blocks_to_search = $all_blocks[$path]['blocks'];
						}
						else
						{
							continue;
						}
					}

					// Skip all other types of links
					else
					{
						continue;
					}

					// Grab some other data
					$original_link = $matches[0][$match_key];
					$link_text = $matches[4][$match_key];

					// Is it a code link?
					if (Str::contains($link_text, '<code>'))
					{
						// Extract the code
						$code = Str::between($link_text, '<code>', '</code>');

						// Decode HTML entities
						$code = html_entity_decode($code);

						// Read in the src file
						if ($url == '#')
						{
							$src = file($data['src_file']->getPathname());
						}
						else
						{
							$src = file($this->inputPath.'/'.$url);
						}

						// Find the code line number in our src file
						$line = 0;
						foreach ($src as $key => $value)
						{
							if (Str::contains($value, $code) && !Str::contains($value, '[```'.$code.'```]'))
							{
								$line = $key+1; break;
							}
						}

						if ($line > 0)
						{
							// Link directly to the line number
							$block['html'] = Str::replace
							(
								$block['html'],
								$original_link,
								'<a href="'.$url_prefix.'#line_'.$line.'" class="internal-link">'.$link_text.'</a>'
							);
						}
						else
						{
							// Link directly to the file
							$block['html'] = Str::replace
							(
								$block['html'],
								$original_link,
								'<a href="'.$url_prefix.'" class="internal-link">'.$link_text.'</a>'
							);
						}
					}
					else
					{
						// Now we need to find the first block that has a title
						// or signature which is the same as the link text.
						$found_block_key = Arr::firstKey
						(
							$blocks_to_search,
							function($v) use ($link_text)
							{
								if (isset($v['title']) && $v['title'] == $link_text)
								{
									return true;
								}

								if (isset($v['signature']) && $v['signature'] == $link_text)
								{
									return true;
								}

								return false;
							}
						);

						// Check to see if we got a result
						if (!is_null($found_block_key))
						{
							// Replace the link with one that will actually work
							$block['html'] = Str::replace
							(
								$block['html'],
								$original_link,
								'<a href="'.$url_prefix.'#'.$found_block_key.'" class="internal-link">'.$link_text.'</a>'
							);
						}
					}
				}

				// Replace the block html with our modified version
				$current_blocks[$block_key]['html'] = $block['html'];
			}
		}

		return $current_blocks;
	}

	/**
	 * Method: generateLunrIndex
	 * =========================================================================
	 * We are using a jquery plugin that implements the *Lucene* search.
	 * See: http://lunrjs.com/
	 * 
	 * This generates an array which then gets converted to JSON. The browser
	 * then loops over the JSON to create the final index. I almost could have
	 * passed the "blocks" array directly but needed to remove HTML tags, etc.
	 *
	 * The final js index is built by using a blade view,
	 * see here for further details: [```make('snippets.lunr-index')```](#)
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $blocks: This must be an array of *docblocks* returned
	 *   by the [Method: extractDocBlocks](#).
	 * 
	 * - $file: This refers to the file that the *docblock* belongs to.
	 *   It must be an instance of ```Symfony\Component\Finder\SplFileInfo```
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * array
	 */
	protected function generateLunrIndex($blocks, SplFileInfo $file)
	{
		foreach ($blocks as $key => $block)
		{
			$index = [];

			$index['id'] = $file->getRelativePathname().'--gearsdoc--'.$key;

			$index['body'] = strip_tags($block['html']);

			if (isset($block['title']))
			{
				$index['title'] = $block['title'];
			}

			if (isset($block['signature']))
			{
				$index['signature'] = $block['signature'];
			}

			$this->lunrIndex[] = $index;

			$this->lunrIndexLookup[$index['id']] = count($this->lunrIndex)-1;
		}
	}

	/**
	 * Method: generateJsonTree
	 * =========================================================================
	 * We are using a jquery plugin to create our sidebar tree menu.
	 * See: https://github.com/mar10/fancytree
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
	protected function generateJsonTree(SplFileInfo $file, $recurse = null)
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

				// Swap the extension to '.html'
				$uri = Str::replace
				(
					$uri,
					'.'.$link->getExtension(),
					'.html'
				);

				// Add the relative url mapping
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
	protected function normalisePaths()
	{
		if (Str::endsWith($this->inputPath, DIRECTORY_SEPARATOR))
		{
			$this->inputPath = substr($this->inputPath, 0, -1);
		}

		if (Str::endsWith($this->outputPath, DIRECTORY_SEPARATOR))
		{
			$this->outputPath = substr($this->outputPath, 0, -1);
		}

		if (Str::endsWith($this->additionalMdDocuments, DIRECTORY_SEPARATOR))
		{
			$this->additionalMdDocuments = substr($this->additionalMdDocuments, 0, -1);
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
	protected function getInputFiles()
	{
		// Create our finder
		$finder = $this->finder;

		// Look for files in the input dir
		$finder->files()->in($this->inputPath);

		// Exclude some paths
		foreach ($this->ignorePaths as $path)
		{
			$finder->notPath($path);
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
	 * Method: getMarkDownFiles
	 * =========================================================================
	 * Sets up a finder object to list all our
	 * [Property: additionalMdDocuments](#) files.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * ```Symfony\Component\Finder\Finder``` or just an empty array
	 */
	protected function getMarkDownFiles()
	{
		// If the property has not been given return an empty array
		if (is_null($this->additionalMdDocuments)) return [];

		// Create our finder
		$finder = $this->finder;

		// Look for files in the input dir
		$finder->files()->in($this->additionalMdDocuments);

		// Only look for markdown documents
		$finder->name('*.md');

		// Return the finder
		return $finder;
	}

	/**
	 * Method: writeDocument
	 * =========================================================================
	 * Writes the given data to the given filename
	 * and creates folders as needed.
	 * 
	 * We also add ```target="_blank"``` to any links that are external.
	 * We do this here so we catch all links, even ones in the blade
	 * views for example.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 *  - $filepath: The filepath to the new document.
	 *  
	 *  - $data: The data to write into the new document.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 * 
	 * Throws:
	 * -------------------------------------------------------------------------
	 *  - RuntimeException: When we failed to write the new file.
	 */
	protected function writeDocument($filepath, $data)
	{
		// Create any needed folders
		$folder = pathinfo($filepath, PATHINFO_DIRNAME);
		if (!$this->filesystem->exists($folder))
		{
			$this->filesystem->mkdir($folder);
		}

		// Add target="_blank" as needed
		if (pathinfo($filepath, PATHINFO_EXTENSION) == 'html')
		{
			$data = $this->setLinkTarget($data);
		}

		// Save the new document
		if (file_put_contents($filepath, $data) === false)
		{
			throw new RuntimeException('Failed to write file: '.$filepath);
		}
	}

	/**
	 * Method: setLinkTarget
	 * =========================================================================
	 * Finds all ```<a>``` tags, checks their ```href```
	 * attributes for an external link.
	 * 
	 * If one is found we ensure that the ```target``` for the
	 * link is set to ```_blank```, making a new window open.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 *  - $html: A html string to read through.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * The modified HTML.
	 */
	protected function setLinkTarget($html)
	{
		// Check to see if we have any links, if not we do nothing
		if (Str::contains($html, '</a>'))
		{
			// Extract those links
			$matches = Str::betweenRegx($html, '<a', '</a>');

			// Loop through each link
			foreach ($matches[1] as $key => $link)
			{
				// Does the link contain a href attribute & protocol identifier?
				if (Str::contains($link, 'href') && Str::contains($link, '://'))
				{
					// Extract the url
					$url = Str::between($link, 'href="', '"');

					// Extract the link text
					$text = Str::between($matches[0][$key], '">', '</a>');

					// This link is external so lets add in the target attribute
					$html = Str::replace
					(
						$html,
						$matches[0][$key],
						'<a href="'.$url.'" target="_blank">'.$text.'</a>'
					);
				}
			}
		}

		// Return the modified HTML
		return $html;
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
	 *  - $file: A SplFileInfo object for the file.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * array
	 */
	protected function extractDocBlocks(SplFileInfo $file)
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
			if ($start === false)
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

					// The very next line after a docblock
					// we consider the method signature.
					if (isset($lines[$line_no+1]))
					{
						$sig = trim($lines[$line_no+1]);
						if ($sig != '') $block['signature'] = $sig;
					}

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
						$block['context'] = '';

						// Grab the title and lowercase it
						$title = Str::s($block['title'])->to('lower');

						// Loop through all our contexts
						foreach ($this->blockContexts as $start => $class)
						{
							if ($title->startsWith(Str::to($start, 'lower')))
							{
								$block['context'] = $class;
								break;
							}
						}
					}
					else
					{
						$block['title'] = Str::split($block['md'], "\n")[0];

						$block['html'] = $this->parsedown->text(Str::replace
						(
							$block['md'],
							$block['title'],
							''
						));

						$block['context'] = '';
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

	/**
	 * Method: extractPseudoDocBlocks
	 * =========================================================================
	 * This is used to put the [Property: additionalMdDocuments](#) into the
	 * same sort of format as a normal file with doc blocks. All we do is split
	 * the file at every ```<h1>``` element.
	 * 
	 * For consumption see:
	 * [```$blocks = $this->extractPseudoDocBlocks($file);```](#)
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 *  - $file: A SplFileInfo object for the file.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * array
	 */
	protected function extractPseudoDocBlocks(SplFileInfo $file)
	{
		// Create our blocks array
		$blocks = [];

		// Convert the mardown to html
		$html = $this->parsedown->text($file->getContents());

		// Grab some headings
		$h1s = Str::betweenRegx($html, '<h1>', '</h1>');

		// Loop through the h1s and create the pseudo blocks
		foreach ($h1s[1] as $k => $v)
		{
			$block = [];

			$block['title'] = $v;

			if (isset($h1s[0][$k+1]))
			{
				$block['html'] = Str::between
				(
					$html,
					$h1s[0][$k],
					$h1s[0][$k+1]
				);
			}
			else
			{
				$block['html'] = Str::between
				(
					$html.'<END>',
					$h1s[0][$k],
					'<END>'
				);
			}

			$blocks[] = $block;
		}

		// Return the blocks
		return $blocks;
	}
}
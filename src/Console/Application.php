<?php namespace Gears\Doc\Console;
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

use Gears\Doc\Console\Commands\Generate;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Finder\Finder;

class Application extends SymfonyApplication
{
	/**
	 * Method: boot
	 * =========================================================================
	 * Our factory method to create our "gearsdoc" application.
	 *
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 *
	 * Returns:
	 * -------------------------------------------------------------------------
	 * static
	 */
	public static function boot()
	{
		$app = new static();
		$app->run();
		return $app;
	}

	/**
	 * Method: getDefaultCommands
	 * =========================================================================
	 * Add our commands to the list of default commands,
	 * so we don't have to call the "add" method at run time.
	 *
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 *
	 * Returns:
	 * -------------------------------------------------------------------------
	 * array
	 */
	protected function getDefaultCommands()
	{
		// Grab the default commands from upstream
		$defaultCommands = parent::getDefaultCommands();

		// Loop through our own command classes and add them.
		$finder = new Finder();
		$finder->files()->in(__DIR__.'/Commands')->name('*.php');
		foreach ($finder as $file)
		{
			$base = $file->getBasename('.php');
			$command = '\Gears\Doc\Console\Commands\\'.$base;
			$defaultCommands[] = new $command();
		}

		// Finally return the modified array
		return $defaultCommands;
	}
}
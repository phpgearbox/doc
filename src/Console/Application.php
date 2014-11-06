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
	 * self
	 */
	public static function boot()
	{
		$app = new static();
		$app->run();
		return $app;
	}

	/**
	 * Method: getCommandName
	 * =========================================================================
	 * Force the use of our command.
	 *
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 *
	 * Returns:
	 * -------------------------------------------------------------------------
	 * string
	 */
	protected function getCommandName(InputInterface $input)
	{
		return 'generate';
	}

	/**
	 * Method: getDefaultCommands
	 * =========================================================================
	 * Add our generate command as the one and only command, along with the
	 * default commands and arguments such as ```--help```.
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
		$defaultCommands = parent::getDefaultCommands();
		$defaultCommands[] = new Generate();
		return $defaultCommands;
	}

    /**
     * Method: getDefinition
     * =========================================================================
     * Overridden so that the application doesn't expect
     * the command name to be the first argument.
     * 
     * Parameters:
     * -------------------------------------------------------------------------
     * n/a
     * 
     * Returns:
     * -------------------------------------------------------------------------
     * array
     */
	public function getDefinition()
	{
		$inputDefinition = parent::getDefinition();
		$inputDefinition->setArguments();
		return $inputDefinition;
	}
}
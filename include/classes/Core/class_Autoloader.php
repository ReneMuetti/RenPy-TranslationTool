<?php
/**
 * Main Loader hub class
 */
final class Autoloader
{
	/**
	 * Array with all loading paths
	 */
	static private $loadingDirs;

	/**
	 * Prefix BEFORE each class file
	 */
	static private $classFilePrefix  = 'class_';

	/**
	 * File extension required to find the class file
	 */
	static private $classFilePostfix = '.php';

	/**
	 * By default, all (new) classes are stored in this path
	 */
	static private $defaultClassPath = './include/classes';



	/**
	 * Return array with the loading paths
	 * f�r DEBUG-Zwecke
	 *
	 * @access      public
	 */
	public static function getLoadingDirs()
	{
		return self :: $loadingDirs;
	}

	/**
	 * Insert an additional entry in the path list for classes
	 *
	 * @access      public
	 * @param       string     $dirPath
	 */
	public static function addLoadingDir($dirPath = null)
	{
		$dirPath = trim($dirPath);
		if ( strlen($dirPath) ) {
			$newPath = self :: $defaultClassPath . DIRECTORY_SEPARATOR . realpath($dirPath);
			if ( !in_array($newPath, self :: $loadingDirs) ) {
				self :: $loadingDirs[] = $newPath;
			}
		}
	}

	/**
     * Klasse starten
     */
	public static function start()
	{
		// Set default paths
		self :: $loadingDirs = array(
		                           realpath( self :: $defaultClassPath . DIRECTORY_SEPARATOR ),
		                           realpath( self :: $defaultClassPath . DIRECTORY_SEPARATOR . 'Core' ),
		                           realpath( self :: $defaultClassPath . DIRECTORY_SEPARATOR . 'Database' ),
		                           realpath( self :: $defaultClassPath . DIRECTORY_SEPARATOR . 'Filesystem' ),
		                           realpath( self :: $defaultClassPath . DIRECTORY_SEPARATOR . 'Translation' ),
		                           realpath( self :: $defaultClassPath . DIRECTORY_SEPARATOR . 'Xml' ),
		                           realpath( self :: $defaultClassPath . DIRECTORY_SEPARATOR . 'XLIFF' ),
		                           realpath( self :: $defaultClassPath . DIRECTORY_SEPARATOR . 'PHPMailer' ),
		                       );
	}

	/**
	 * Default Class-Loader
	 *
	 * @access      public
	 * @param       string     $className
	 */
	public static function loadClass($className)
	{
		$className   = trim($className);
		$classLoaded = false;

		// Class name contains "_" in the name => conversion to path information
		if ( strpos($className, '_') ) {
			$newPath = self :: replaceUnderscore($className);
			self :: addLoadingDir($newPath);
		}

		foreach( self :: $loadingDirs AS $loadPath ) {
		    if ( !strpos($className, '\\') ) {
    			$fullClassPath = $loadPath .
    			                 DIRECTORY_SEPARATOR .
    			                 self :: $classFilePrefix .
    			                 $className .
    			                 self :: $classFilePostfix;
		    }
		    else {
		        $fullClassPath = $loadPath .
    			                 DIRECTORY_SEPARATOR .
    			                 str_replace('\\', DIRECTORY_SEPARATOR, $className) .
    			                 self :: $classFilePostfix;
		    }

            if ( is_file($fullClassPath) ) {
				$classLoaded = true;
				require_once($fullClassPath);
    			break;
			}
		}

		if ( $classLoaded == false ) {
		    $removePath = realpath( self :: $defaultClassPath . DIRECTORY_SEPARATOR );
		    ob_start();
		    debug_print_backtrace();
		    $trace = ob_get_contents();
		    ob_end_clean();

		    $trace = str_replace($removePath, '..', $trace);

			echo __CLASS__ . '::' . __FUNCTION__ . ': Fail to load Class-File {' . $className .'}';
			echo '<pre>' . $trace . '</pre>';
        	exit;
		}
	}

	/**
	 * Debug-Output als HIDDEN-DIV
	 *
	 * @access      public
	 * @param       string     $stringOut
	 */
	public static function getDebugOutput($stringOut = null)
	{
		if ( is_string($stringOut) AND strlen($stringOut) ) {
			echo '<pre style="display:block !important">'.
			     $stringOut .
			     '</pre>';
		}
	}

	private static function replaceUnderscore($className)
	{
		$pices = explode('_', $className);
		// Remove the last part of the array
		$pices = array_pop($pices);

		if ( is_array($pices) ) {
			return implode(DIRECTORY_SEPARATOR, $pices);
		}
		else {
			return DIRECTORY_SEPARATOR . $pices;
		}
	}
}
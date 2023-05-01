<?php
/**
 * Main Loader hub class
 */
final class Autoloader
{
	/**
	 * Array mit allen Lade-Pfaden
	 */
	static private $loadingDirs;
	
	/**
	 * Prefix VOR jeder Klassen-Datei
	 */
	static private $classFilePrefix  = 'class_';
	
	/**
	 * Datei-Endung, welche zum finden der Klassen-Datei benötigt wird
	 */
	static private $classFilePostfix = '.php';
	
	/**
	 * Per Default sind alle (neuen) Klassen in diesem Pfad abngelegt
	 */
	static private $defaultClassPath = './include/classes';
	


	/**
	 * Array mit den Loading-Pfaden zurückgeben
	 * für DEBUG-Zwecke
	 *
	 * @access      public
	 */
	public static function getLoadingDirs()
	{
		return self :: $loadingDirs;
	}
	
	/**
	 * einen weiteren Eintrag in die Pfadliste für Klassen einfügen
	 *
	 * @access      public
	 * @param       string     $dirPath
	 */
	public static function addLoadingDir($dirPath = null)
	{
		$dirPath = trim($dirPath);
		if ( strlen($dirPath) ) {
			$newPath = self :: $defaultClassPath . '/' . realpath($dirPath);
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
		// Default-Pfade setzen
		self :: $loadingDirs = array(
		                           //realpath( './include/' ),
		                           realpath( self :: $defaultClassPath . '/' ),
		                           realpath( self :: $defaultClassPath . '/Core/' ),
		                           realpath( self :: $defaultClassPath . '/Database/' ),
		                           realpath( self :: $defaultClassPath . '/Filesystem/' ),
		                           realpath( self :: $defaultClassPath . '/Translation/' ),
		                           realpath( self :: $defaultClassPath . '/Xml/' ),
		                           realpath( self :: $defaultClassPath . '/XLIFF/' ),
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

        //self::getDebugOutput( implode( "\n", self::getLoadingDirs() ) );
		
		// Klassenname enthällt "_" im Namen => Umgewandlung in Pfadangaben
		if ( strpos($className, '_') ) {
			$newPath = self :: replaceUnderscore($className);
			self :: addLoadingDir($newPath);
		}

		foreach( self :: $loadingDirs AS $loadPath ) {
			$fullClassPath = $loadPath .
			                 DIRECTORY_SEPARATOR .
			                 self :: $classFilePrefix .
			                 $className .
			                 self :: $classFilePostfix;
            
            //self::getDebugOutput( $fullClassPath );
            if ( is_file($fullClassPath) ) {
				$classLoaded = true;
				require_once($fullClassPath);
				break;
			}
		}
		
		if ( $classLoaded == false ) {
			echo __CLASS__ . '::' . __FUNCTION__ . ': Fail to load Class-File <' . $className .'>';
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
		// den letzten Teil des Array entfernen
		$pices = array_pop($pices);
		
		if ( is_array($pices) ) {
			return implode(DIRECTORY_SEPARATOR, $pices);
		}
		else {
			return DIRECTORY_SEPARATOR . $pices;
		}
	}
}
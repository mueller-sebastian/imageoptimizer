<?php
namespace Lemming\Imageoptimizer;

use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class OptimizeImageService {

	/**
	 * Initialize
	 */
	public function __construct() {
		$this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['imageoptimizer']);
	}

	/**
	 * Perform image optimization
	 *
	 * @param string $file
	 * @param string $extension
	 * @param boolean $fileIsUploaded
	 */
	public function process($file, $extension = NULL, $fileIsUploaded = FALSE) {
		if ($extension === NULL) {
			$pathinfo = pathinfo($file);
			if ($pathinfo['extension'] !== NULL) {
				$extension = $pathinfo['extension'];
			}
		}
		$extension = strtolower($extension);

		if ($extension == 'png' && (bool)$this->configuration['enableOptipng'] === TRUE) {
			$binary = CommandUtility::getCommand('optipng');
			if (is_string($binary)) {
				$level = MathUtility::forceIntegerInRange($this->configuration['optipngOptimizationLevel'],1,7,2);
				$command = sprintf($binary . ' -o%u %s 2>&1', $level, $file);
			}

		} elseif (($extension == 'jpg' || $extension == 'jpeg') && (bool)$this->configuration['enableJpegtran'] === TRUE) {
			$binary = CommandUtility::getCommand('jpegtran');
			if (is_string($binary)) {
				$stripMarker = $fileIsUploaded === TRUE && (bool)$this->configuration['jpegtranStripMarker'] === FALSE ? '' : ' -copy none';
				$command = sprintf('%s -optimize %s -outfile %s %s 2>&1', $binary, $stripMarker, $file, $file);
			}
		}

		if (isset($command)) {
			$output = [];
			$returnValue = 0;
			CommandUtility::exec($command, $output, $returnValue);
			if ((bool)$this->configuration['debug'] === TRUE && is_object($GLOBALS['BE_USER'])) {
				$GLOBALS['BE_USER']->writelog(4, 0, 0, 1467124014, $command . ' exited with ' . $returnValue . '. Output was: ' . implode(' ', $output), $output);
			}
		}
	}
}
<?php

namespace Language;

use Exception;
use Language\Singleton\ISingleton;
use Language\Singleton\TSingleton;

/**
 * Business logic related to generating language files.
 */
class LanguageBatchBo implements ISingleton
{
	use TSingleton;

	/**
	 * Contains the applications which ones require translations.
	 *
	 * @var array
	 */
	protected array $applications = [];

	/**
	 * Starts the language file generation.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function generateLanguageFiles()
	{
		// The applications where we need to translate.
		$this->applications = Config::get('system.translated_applications');

		echo "\nGenerating language files\n";
		foreach ($this->applications as $application => $languages) {
			echo "[APPLICATION: " . $application . "]\n";
			foreach ($languages as $language) {
				echo "\t[LANGUAGE: " . $language . "]";
				if ($this->getLanguageFile($application, $language)) {
					echo " OK\n";
				}
				else {
					throw new Exception('Unable to generate language file!');
				}
			}
		}
	}

	/**
	 * Gets the language files for the applet and puts them into the cache.
	 *
	 * @throws Exception   If there was an error.
	 *
	 * @return void
	 */
	public function generateAppletLanguageXmlFiles()
	{
		// List of the applets [directory => applet_id].
		$applets = [
			'memberapplet' => 'JSM2_MemberApplet',
		];

		echo "\nGetting applet language XMLs..\n";

		foreach ($applets as $appletDirectory => $appletLanguageId) {
			echo " Getting > $appletLanguageId ($appletDirectory) language xmls..\n";
			$languages = $this->getAppletLanguages($appletLanguageId);
			if (empty($languages)) {
				throw new Exception('There is no available languages for the ' . $appletLanguageId . ' applet.');
			}
			else {
				echo ' - Available languages: ' . implode(', ', $languages) . "\n";
			}
			$path = Config::get('system.paths.root') . '/cache/flash';
			foreach ($languages as $language) {
				$xmlContent = $this->getAppletLanguageFile($appletLanguageId, $language);
				$xmlFile    = $path . '/lang_' . $language . '.xml';
				if (strlen($xmlContent) == file_put_contents($xmlFile, $xmlContent)) {
					echo " OK saving $xmlFile was successful.\n";
				}
				else {
					throw new Exception('Unable to save applet: (' . $appletLanguageId . ') language: (' . $language
						. ') xml (' . $xmlFile . ')!');
				}
			}
			echo " < $appletLanguageId ($appletDirectory) language xml cached.\n";
		}

		echo "\nApplet language XMLs generated.\n";
	}

	/**
	 * Gets the language file for the given language and stores it.
	 *
	 * @param string $application The name of the application.
	 * @param string $language The identifier of the language.
	 *
	 * @return bool   The success of the operation.
	 * @throws Exception
	 */
	protected function getLanguageFile(string $application, string $language): bool
	{
		$languageResponse = ApiCall::call(
			'system_api',
			'language_api',
			[
				'system' => 'LanguageFiles',
				'action' => 'getLanguageFile',
			],
			[
				'language' => $language
			],
		);

		try {
			$this->checkForApiErrorResult($languageResponse);
		}
		catch (Exception $e) {
			throw new Exception('Error during getting language file: (' . $application . '/' . $language . ')');
		}

		// If we got correct data we store it.
		$destination = $this->getLanguageCachePath($application) . $language . '.php';
		// If there is no folder yet, we'll create it.
		var_dump($destination);
		if (!is_dir(dirname($destination))) {
			mkdir(dirname($destination), 0755, true);
		}

		$result = file_put_contents($destination, $languageResponse['data']);

		return (bool)$result;
	}

	/**
	 * Gets the directory of the cached language files.
	 *
	 * @param string $application   The application.
	 *
	 * @return string   The directory of the cached language files.
	 */
	protected function getLanguageCachePath(string $application): string
	{
		return Config::get('system.paths.root') . '/cache/' . $application. '/';
	}

	/**
	 * Gets the available languages for the given applet.
	 *
	 * @param string $applet The applet identifier.
	 *
	 * @return array   The list of the available applet languages.
	 * @throws Exception
	 */
	protected function getAppletLanguages(string $applet): array
	{
		$result = ApiCall::call(
			'system_api',
			'language_api',
			[
				'system' => 'LanguageFiles',
				'action' => 'getAppletLanguages',
			],
			[
				'applet' => $applet,
			]
		);

		try {
			$this->checkForApiErrorResult($result);
		}
		catch (Exception $e) {
			throw new Exception('Getting languages for applet (' . $applet . ') was unsuccessful ' . $e->getMessage());
		}

		return $result['data'];
	}


	/**
	 * Gets a language xml for an applet.
	 *
	 * @param string $applet The identifier of the applet.
	 * @param string $language The language identifier.
	 *
	 * @return string|false   The content of the language file or false if weren't able to get it.
	 * @throws Exception
	 */
	protected function getAppletLanguageFile(string $applet, string $language)
	{
		$result = ApiCall::call(
			'system_api',
			'language_api',
			[
				'system' => 'LanguageFiles',
				'action' => 'getAppletLanguageFile',
			],
			[
				'applet' => $applet,
				'language' => $language
			]
		);

		try {
			$this->checkForApiErrorResult($result);
		}
		catch (Exception $e) {
			throw new Exception('Getting language xml for applet: (' . $applet . ') on language: (' . $language . ') was unsuccessful: '
				. $e->getMessage());
		}

		return $result['data'];
	}

	/**
	 * Checks the api call result.
	 *
	 * @param mixed  $result   The api call result to check.
	 *
	 * @throws Exception   If the api call was not successful.
	 *
	 * @return void
	 */
	protected function checkForApiErrorResult($result)
	{
		// Error during the api call.
		if ($result === false || !isset($result['status'])) {
			throw new Exception('Error during the api call');
		}
		// Wrong response.
		if ($result['status'] != 'OK') {
			throw new Exception('Wrong response: '
				. (!empty($result['error_type']) ? 'Type(' . $result['error_type'] . ') ' : '')
				. (!empty($result['error_code']) ? 'Code(' . $result['error_code'] . ') ' : '')
				. ((string)$result['data']));
		}
		// Wrong content.
		if ($result['data'] === false) {
			throw new Exception('Wrong content!');
		}
	}
}

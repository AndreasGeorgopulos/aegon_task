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
	 * Starts the language file generation.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function generateLanguageFiles()
	{
		echo "\nGenerating language files\n";

		// The applications where we need to translate.
		foreach (Config::get('system.translated_applications') as $application => $languages) {
			echo "[APPLICATION: " . $application . "]\n";
			foreach ($languages as $language) {
				if (!$this->getLanguageFile($application, $language)) {
					throw new Exception('Unable to generate language file!');
				}

				echo "\t[LANGUAGE: " . $language . "] OK\n";
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
			if (!($languages = $this->getAppletLanguages($appletLanguageId))) {
				throw new Exception('There is no available languages for the ' . $appletLanguageId . ' applet.');
			}

			echo ' - Available languages: ' . implode(', ', $languages) . "\n";

			$path = Config::get('system.paths.root') . '/cache/flash';
			foreach ($languages as $language) {
				$xmlContent = $this->getAppletLanguageFile($appletLanguageId, $language);
				$xmlFile    = $path . '/lang_' . $language . '.xml';
				if (strlen($xmlContent) != file_put_contents($xmlFile, $xmlContent)) {
					throw new Exception('Unable to save applet: (' . $appletLanguageId . ') language: (' . $language
						. ') xml (' . $xmlFile . ')!');
				}

				echo " OK saving $xmlFile was successful.\n";
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
		try {
			$languageResponse = $this->callApiCall('getLanguageFile', ['language' => $language]);

			// If we got correct data we store it.
			$destination = $this->getLanguageCachePath($application) . $language . '.php';

			// If there is no folder yet, we'll create it.
			echo "$destination\n";
			if (!is_dir(dirname($destination))) {
				mkdir(dirname($destination), 0755, true);
			}

			$result = file_put_contents($destination, $languageResponse['data']);
		}
		catch (Exception $e) {
			throw new Exception('Error during getting language file: (' . $application . '/' . $language . ')');
		}

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
		try {
			$result = $this->callApiCall('getAppletLanguages', ['applet' => $applet]);
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
		try {
			$result = $this->callApiCall('getAppletLanguageFile', ['applet' => $applet, 'language' => $language]);
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

	/**
	 * Calls the api call and checks the result.
	 *
	 * @param string $action
	 * @param array $postParameters
	 * @param string $target
	 * @param string $mode
	 * @param string $system
	 * @return array|null
	 * @throws Exception
	 */
	protected function callApiCall(string $action, array $postParameters, string $target = 'system_api', string $mode = 'language_api', string $system = 'LanguageFiles'): ?array
	{
		$response = ApiCall::call($target, $mode, ['system' => $system, 'action' => $action], $postParameters);
		$this->checkForApiErrorResult($response);
		return $response;
	}
}

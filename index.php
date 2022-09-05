<?php

/**
 * $Rev: 61 $
 * $Author: omgitsjustjava $
 * $Date: 2016-09-04 14:20:56 +0200 (So, 04 Sep 2016) $
 */
class audiovideo extends Plugin {
	
	// Versionsflag
	private $isVersionZweiNull;
	
	// unterstützte Dateiformate und ihre Mediatypes
	private $fileformats;

	/**
	 * Instanziiert das Plugin-Objekt.
	 */
	function __construct() {
		parent::__construct();
		
		// welche CMS-Version haben wir?
		$this->isVersionZweiNull = (defined('CMSVERSION') && CMSVERSION == '2.0');
		
		// unterstützte Dateiformate und ihre Mimetypes
		$this->fileformats = array(
			'audio' => array(
				'mp3' => 'audio/mpeg',
				'ogg' => 'audio/ogg',
				'wav' => 'audio/wav'
			),
			'video' => array(
				'mp4' => 'video/mp4',
				'webm' => 'video/webm',
				'ogg' => 'video/ogg'
			)
		);
	} // __construct()
	
	/**
	 * Gibt den Inhalt des Plugins zurück.
	 *
	 * @param string $value
	 *        	der vom User angegebene Inhalt des Plugin-Platzhalters
	 * @return string der Inhalt des Plugins
	 */
	function getContent($value) {
		global $CatPage;
		global $specialchars;
		global $URL_BASE;
		global $PLUGIN_DIR_NAME;
		
		// Eigenschaften des angezeigten Players aus der Plugin-Konfiguration lesen
		$optionControls = $this->settings->get('controls') == 'true';
		$optionAutoplay = $this->settings->get('autoplay') == 'true';
		$optionWidth = $this->settings->get('width');
		$optionHeight = $this->settings->get('height');
		
		// übergebene(n) Parameter prüfen
		$valueArray = explode('|', $value);
		switch (count($valueArray)) {
			// kein Parameter übergeben
			case 0:
				return $this->buildErrorMsg('Kein Parameter übergeben.');
				break;
			
			// ein Parameter übergeben: Für die Eigenschaften des angezeigten Players gelten die globalen Einstellungen aus der Pluginkonfiguration
			case 1:
				break;
			
			// zwei Parameter übergeben: Die globalen Einstellungen aus der Pluginkonfiguration werden durch Werte nur für diesen einen Player überschrieben
			case 2:
				$value = $valueArray [0];
				$additionalParamsArray = explode(',', $valueArray [1]);
				foreach ($additionalParamsArray as $additionalParamString) {
					$equalpos = strpos($additionalParamString, '=');
					$additionalParamKey = substr($additionalParamString, 0, $equalpos);
					$additionalParamValue = substr($additionalParamString, $equalpos + 1);
					switch (trim($additionalParamKey)) {
						case 'controls':
							$optionControls = trim($additionalParamValue) == 1;
							break;
						case 'autoplay':
							$optionAutoplay = trim($additionalParamValue) == 1;
							break;
						case 'width':
							$optionWidth = trim($additionalParamValue);
							break;
						case 'height':
							$optionHeight = trim($additionalParamValue);
							break;
						// unbekannte Parameter werden schlicht ignoriert
						default:
							break;
					}
				}
				break;
			
			// mehr als zwei Parameter: MEH.
			default:
				return $this->buildErrorMsg('Fehlerhafte Parameter übergeben.');
				break;
		}
		
		// Pfad zur Datei (URL)
		$filepath = '';
		
		// handelt es sich um eine CMS-interne Datei?
		$isInternalFile = true;
		
		// moziloCMS Version 2.0
		if ($this->isVersionZweiNull) {
			list ($cat, $file) = $CatPage->split_CatPage_fromSyntax($value, true);
			if ($cat == "" && $file == "") {
				$values = explode("%3A", $value);
				if (count($values) == 2) {
					$datei_text = $specialchars->rebuildSpecialChars($values [1], true, true);
					$cat_text = $specialchars->rebuildSpecialChars($values [0], true, true);
				} else {
					$datei_text = "";
					$cat_text = "";
				}
				$isInternalFile = false;
				$filepath = $value;
				// return $this->buildErrorMsg("Angeforderte Mediendatei ist nicht vorhanden!");
			}
			if ($isInternalFile) {
				if (!$CatPage->exists_File($cat, $file)) {
					$isInternalFile = false;
					// return $this->buildErrorMsg("Angeforderte Mediendatei ist nicht vorhanden!");
				}
				$filepath = URL_BASE . 'kategorien/' . str_replace("%", "%25", $cat) . '/dateien/' . $file;
			}
		} 		// if ($this->isVersionZweiNull)
		  
		// moziloCMS Version 1.12
		else {
			// Kategorie und Datei aus dem übergebenen Wert lesen
			list ($cat, $file) = $CatPage->split_CatPage_fromSyntax($value, false, true);
			// Datei auf Existenz prüfen
			if (!$CatPage->exists_File($cat, $file)) {
				$isInternalFile = false;
				$filepath = $value;
				// return $this->buildErrorMsg('Angeforderte Mediendatei ist nicht vorhanden!');
			} else {
				$cat = $CatPage->get_FileSystemName($cat, false);
				$filepath = $URL_BASE . 'kategorien/' . str_replace("%", "%25", $cat) . '/dateien/' . $file;
			}
		}
		
		if ($isInternalFile) {
			// Pfad zur Datei (Filesystem)
			$filepathFilesystem = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'kategorien' . DIRECTORY_SEPARATOR . $cat . DIRECTORY_SEPARATOR . 'dateien' . DIRECTORY_SEPARATOR . $file;
			// Mimetype der Datei rausfinden
			$mimetype = $this->getMimeType($filepathFilesystem);
			if (!isset($mimetype)) {
				return $this->buildErrorMsg('Nicht unterstützte Dateiendung');
			}
		} else {
			// Mimetype per Extension bestimmen
			$mimetype = $this->getMimeTypeByExtension($filepath);
		}
		
		// abhängig vom Mimetype: Brauchen wir ein audio- oder ein video-Element?
		$mediatypeToUse = null;
		foreach ($this->fileformats as $currentMediaType => $currentMediaTypeFormats) {
			foreach ($currentMediaTypeFormats as $currentExtension => $currentMimetype) {
				if ($mimetype == $currentMimetype) {
					$mediatypeToUse = $currentMediaType;
				}
			}
		}
		// wenn die Datei einen nicht unterstützten Mimetype hat
		if (!isset($mediatypeToUse)) {
			return $this->buildErrorMsg('Nicht unterstützter Dateityp ' . $mimetype);
		}
		
		// Parameter entsprechend der Plugin-Konfiguration und der ggfs. angegebenen zusätzlichen Plugin-Parameter setzen
		$controls = $optionControls ? ' controls' : '';
		$autoplay = $optionAutoplay ? ' autoplay' : '';
		$width = '';
		$height = '';
		if ($mediatypeToUse == 'video') {
			$width = $optionWidth != '' ? ' width="' . $optionWidth . '"' : '';
			$height = $optionHeight != '' ? ' height="' . $optionHeight . '"' : '';
		}
		
		// audio- bzw. video-Element ausgeben - fertig :)
		return '<' . $mediatypeToUse . ' class="audiovideo" type="' . $mimetype . '" src="' . $filepath . '"' . $controls . $autoplay . $width . $height . '>Kann Mediendatei nicht abspielen - <a href="' . $filepath . '">stattdessen herunterladen</a></' . $mediatypeToUse . '>';
	} // getContent()
	
	/**
	 * Gibt die Plugin-Konfiguration als Array zurück.
	 *
	 * @return array die Plugin-Konfiguration
	 */
	function getConfig() {
		global $CatPage;
		global $specialchars;
		global $URL_BASE;
		global $PLUGIN_DIR_NAME;
		global $BASE_DIR;
		
		// Pluginkonfig
		$config = array();
		$config ['controls'] = array(
			'type' => 'checkbox',
			'description' => 'Bedienelemente des Players anzeigen?'
		);
		$config ['autoplay'] = array(
			'type' => 'checkbox',
			'description' => 'Soll die Wiedergabe direkt beim Laden beginnen ("Autoplay")?'
		);
		$config ['width'] = array(
			"type" => "text",
			"description" => "Nur für Videos: Breite des Players (leer lassen für unskalierte Darstellung des Videos)",
			"regex" => "/^\d+$/",
			"regex_error" => "Nur numerische Werte erlaubt."
		);
		$config ['height'] = array(
			"type" => "text",
			"description" => "Nur für Videos: Höhe des Players (leer lassen für unskalierte Darstellung des Videos)",
			"regex" => "/^\d+$/",
			"regex_error" => "Nur numerische Werte erlaubt."
		);
		return $config;
	} // getConfig()
	
	/**
	 * Gibt die Plugin-Infos als Array zurück.
	 *
	 * @return array die Plugin-Infos
	 */
	function getInfo() {
		global $ADMIN_CONF;
		$language = $ADMIN_CONF->get("language");
		$prefix = $this->isVersionZweiNull ? '@=' : '';
		$suffix = $this->isVersionZweiNull ? '=@' : '';
		$description = 'Ein einfacher Player für Audio- und Video-Dateien.<br />' . 		//
		'<br />' . 		//
		'<span style="font-weight:bold;">Verwendung:</span><br />{audiovideo|' . $prefix . 'dateiname.mp3' . $suffix . '}<br />' . 		//
		'bzw.<br />' . 		//
		'{audiovideo|' . $prefix . 'Kategorie:dateiname.mp3' . $suffix . '}<br />' . 		//
		'<br />' . 		//
		'<span style="font-weight:bold;">Expertenoptionen:</span><br />{audiovideo|' . $prefix . 'dateiname.mp3' . $suffix . '<span style="font-weight:bold;">|controls=1,autoplay=1,width=600,height=480</span>}<br />' . 		//
		'Die so angegebenen Optionen gelten nur für diesen einen Player, die entsprechenden globalen Einstellungen aus der Plugin-Konfiguration werden dabei ignoriert.<br />' . 		//
		'Diese Parameter können einzeln oder gemeinsam (dann kommasepariert) verwendet werden:<br />' . 		//
		'- controls: Bedienelemente des Players anzeigen? Werte: 0 (nein) / 1 (ja)<br />' . 		//
		'- autoplay: Soll die Wiedergabe direkt beim Laden beginnen ("Autoplay")? Werte: 0 (nein) / 1 (ja)<br />' . 		//
		'- width: Breite des Players (nur für Videos)<br />' . 		//
		'- height: Höhe des Players (nur für Videos)<br />' . 		//
		'<br />' . 		//
		'<span style="font-weight:bold;">Unterstützte Dateitypen:</span>';
		foreach (array(
			'audio',
			'video'
		) as $type) {
			$description .= '<br />' . ucfirst($type) . ':';
			foreach ($this->fileformats [$type] as $format => $mediatype) {
				$description .= '<br />- ' . $format . ' (' . $mediatype . ')';
			}
		}
		$rev = preg_replace("/[^0-9]/", "", '$Rev: 61 $');
		$info = array(
			// Plugin-Name + Version
			'<b>Audio- und Videoplayer</b> Revision ' . $rev,
			// moziloCMS-Version
			'1.12 / 2.0',
			// Kurzbeschreibung nur <span> und <br /> sind erlaubt
			$description,
			// Name des Autors
			'Arvid Zimmermann',
			// Download-URL
			'http://www.arvidzimmermann.de',
			// Platzhalter für die Selectbox in der Editieransicht
			// - ist das Array leer, erscheint das Plugin nicht in der Selectbox
			array(
				'{audiovideo|}' => 'Player für Audio- und Video-Dateien'
			)
		);
		// Rückgabe der Infos.
		return $info;
	} // getInfo()
	
	/**
	 * Gibt den übergebenen String als Fehlermeldung formatiert zurück.
	 *
	 * @param string $msg
	 *        	der String
	 * @return string die formatierte Fehlermeldung
	 */
	function buildErrorMsg($msg) {
		return '<span class="deadlink">Fehler im Plugin ' . get_class($this) . ': ' . $msg . '</span>';
	}

	/**
	 * Gibt den Mimetype der übergebenen Datei zurück.
	 *
	 * @param string $filepath
	 *        	der absolute Pfad zur Datei
	 * @return string der Mimetype - oder <code>null</code>, wenn die Datei einen unbekannten Typ hat
	 */
	function getMimeType($filepath) {
		// Der saubere Weg ab PHP 5.1.0: Per FileInfo
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filepath);
			$mimetype = substr($mimetype, 0, strpos($mimetype, ';'));
			finfo_close($finfo);
			return $mimetype;
		} 		// Vor PHP 5.1.: Per mime_content_type()
		elseif (function_exists('mime_content_type')) {
			return mime_content_type($filepath);
		} 		// wenn auch die nicht auf dem Server vorhanden ist, machen wirs von Hand:
		else {
			return $this->getMimeTypeByExtension($filepath);
		}
		// nicht gefunden?
		return null;
	} // getMimeType()
	
	/**
	 * Gibt den Mimetype der übergebenen Datei anhand ihrer Dateinamenerweiterung zurück.
	 *
	 * @param string $filepath
	 *        	der absolute Pfad zur Datei
	 * @return string der Mimetype - oder <code>null</code>, wenn die Datei einen unbekannten Typ hat
	 */
	function getMimeTypeByExtension($filepath) {
		// Extension der Datei holen
		$exploded = explode('.', $filepath);
		$ext = strtolower(array_pop($exploded));
		// Im fileformats-Array suchen ('ogg' matcht dann halt zuerst als Audio...)
		foreach ($this->fileformats as $currentMediaType => $currentMediaTypeFormats) {
			if (array_key_exists($ext, $currentMediaTypeFormats)) {
				return $currentMediaTypeFormats [$ext];
			}
		}
		// nicht gefunden?
		return null;
	} // getMimeTypeByExtension()
} // class audiovideo

?>
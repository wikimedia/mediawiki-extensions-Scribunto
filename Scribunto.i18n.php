<?php
/**
 * Internationalisation file for extension Scribunto.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Victor Vasiliev
 */
$messages['en'] = array(
	'scribunto-desc' => 'Framework for embedding scripting languages into MediaWiki pages',
	'scribunto-line' => 'at line $1',
	'scribunto-module-line' => 'in $1 at line $2',
	'scribunto-parser-error' => 'Script error',
	'scribunto-parser-dialog-title' => 'Script error',
	'scribunto-error-short' => 'Script error: $1',
	'scribunto-error-long' => 'Script errors:

$1',
	'scribunto-common-nosuchmodule' => 'Script error: No such module.',
	'scribunto-common-nofunction' => 'Script error: You must specify a function to call.',
	'scribunto-common-nosuchfunction' => 'Script error: The function you specified did not exist.',
	'scribunto-common-timeout' => 'The time allocated for running scripts has expired.',
	'scribunto-common-oom' => 'The amount of memory allowed for running scripts has been exceeded.',
	'scribunto-common-backtrace' => 'Backtrace:',
	'scribunto-lua-in-function' => 'in function "$1"',
	'scribunto-lua-in-main' => 'in main chunk',
	'scribunto-lua-in-function-at' => 'in the function at $1:$2',
	'scribunto-lua-backtrace-line' => '$1: $2',
	'scribunto-lua-error-location' => 'Lua error $1: $2.',
	'scribunto-lua-error' => 'Lua error: $2.',
	'scribunto-lua-noreturn' => 'Script error: The module did not return a value, it should return an export table.',
	'scribunto-lua-notarrayreturn' => 'Script error: The module returned something other than a table, it should return an export table.',
	'scribunto-luastandalone-proc-error' => 'Lua error: Cannot create process.',
	'scribunto-luastandalone-decode-error' => 'Lua error: Internal error: Unable to decode message.',
	'scribunto-luastandalone-write-error' => 'Lua error: Internal error: Error writing to pipe.',
	'scribunto-luastandalone-read-error' => 'Lua error: Internal error: Error reading from pipe.',
	'scribunto-luastandalone-gone' => 'Lua error: Internal error: The interpreter has already exited.',
	'scribunto-luastandalone-signal' => 'Lua error: Internal error: The interpreter has terminated with signal "$2".',
	'scribunto-luastandalone-exited' => 'Lua error: Internal error: The interpreter exited with status $2.',
);

/** Message documentation (Message documentation)
 * @author Siebrand
 */
$messages['qqq'] = array(
	'scribunto-desc' => '{{desc}}',
	'scribunto-line' => 'Reference to a code location. Parameters:
* $1 is a line number.',
	'scribunto-module-line' => 'Reference to a code location. Parameters:
* $1 is a module;
* $2 is a line number.',
	'scribunto-parser-error' => 'Error message.',
	'scribunto-parser-dialog-title' => 'Error message.',
	'scribunto-error-short' => 'Error message. Parameters:
* $1 are the error details.',
	'scribunto-error-long' => 'Error message. Parameters:
* $1 are the error details.',
	'scribunto-common-nosuchmodule' => 'Error message displayed when referencing a non-existing module.',
	'scribunto-common-nofunction' => 'Error message displayed when not specifying a function to call.',
	'scribunto-common-nosuchfunction' => 'Error message displayed when referencing a non-existing function.',
	'scribunto-common-timeout' => 'Error message displayed when script execution has passed a threshold.',
	'scribunto-common-oom' => 'Error message displayed when the script requires more memory than the threshold.',
	'scribunto-common-backtrace' => 'A backtrace is a list of the function calls that are currently active in a thread. This message is followed by a backtrace.',
	'scribunto-lua-in-function' => 'Reference to a function name. Parameters:
* $1 is a function name.',
	'scribunto-lua-in-main' => 'Part of the backtrace creation routines. Refers to the main part of the code.',
	'scribunto-lua-in-function-at' => 'Part of the backtrace creation routines. Parameters:
* $1 is a function name;
* $2 is a line number.',
	'scribunto-lua-error-location' => 'Error message. Parameters:
* $1 is ...;
* $2 is ....',
	'scribunto-lua-error' => 'Error message. Parameters:
* $2 is ....',
	'scribunto-lua-noreturn' => 'Error message.',
	'scribunto-lua-notarrayreturn' => 'Error message.',
	'scribunto-luastandalone-proc-error' => 'Exception message.',
	'scribunto-luastandalone-decode-error' => 'Exception message.',
	'scribunto-luastandalone-write-error' => 'Exception message.',
	'scribunto-luastandalone-read-error' => 'Exception message.',
	'scribunto-luastandalone-gone' => 'Exception message.',
	'scribunto-luastandalone-signal' => 'Exception message. Parameters:
* $2 is an exit status.',
	'scribunto-luastandalone-exited' => 'Exception message. Parameters:
* $2 is an exit status.',
);

/** German (Deutsch)
 * @author Kghbln
 */
$messages['de'] = array(
	'scribunto-desc' => 'Ermöglicht eine Umgebung zum Einbetten von Skriptsprachen in Wikiseiten',
	'scribunto-line' => 'in Zeile $1',
	'scribunto-module-line' => 'in $1, Zeile $2',
	'scribunto-parser-error' => 'Skriptfehler',
	'scribunto-parser-dialog-title' => 'Skriptfehler',
	'scribunto-error-short' => 'Skriptfehler: $1',
	'scribunto-error-long' => 'Skriptfehler:

$1',
	'scribunto-common-nosuchmodule' => 'Skriptfehler: Ein solches Modul ist nicht vorhanden.',
	'scribunto-common-nofunction' => 'Skriptfehler: Es muss eine aufzurufende Funktion angegeben werden.',
	'scribunto-common-nosuchfunction' => 'Skriptfehler: Die angegebene Funktion ist nicht vorhanden.',
	'scribunto-common-timeout' => 'Die Zeit zum Ausführen von Skripten vorgesehene Zeit ist abgelaufen.',
	'scribunto-common-oom' => 'Der zum Ausführen von Skripten vorgesehene Arbeitsspeicher wurde erschöpft.',
	'scribunto-common-backtrace' => 'Ablaufrückverfolgung:',
	'scribunto-lua-in-function' => 'in der Funktion „$1“',
	'scribunto-lua-in-main' => 'im Hauptsegment',
	'scribunto-lua-in-function-at' => 'in der Funktion bei  $1:$2',
	'scribunto-lua-error-location' => 'Lua-Fehler  $1: $2',
	'scribunto-lua-error' => 'Lua-Fehler: $2',
	'scribunto-lua-noreturn' => 'Skriptfehler: Das Modul gab keinen Wert zurück, obwohl es eine Tabelle zum Export hätte zurückgeben sollen.',
	'scribunto-lua-notarrayreturn' => 'Skriptfehler: Das Modul gab etwas anderes als eine Tabelle zum Export zurück. Es hätte eine Tabelle zum Export hätte zurückgeben sollen.',
	'scribunto-luastandalone-proc-error' => 'Lua-Fehler: Der Vorgang kann nicht erstellt werden.',
	'scribunto-luastandalone-decode-error' => 'Interner Lua-Fehler: Die Nachricht konnte nicht dekodiert werden.',
	'scribunto-luastandalone-write-error' => 'Interner Lua-Fehler: Es trat ein Fehler beim Schreiben auf.',
	'scribunto-luastandalone-read-error' => 'Interner Lua-Fehler: Es trat ein Fehler beim Lesen auf.',
	'scribunto-luastandalone-gone' => 'Interner Lua-Fehler: Der Interpreter wurde bereits beendet.',
	'scribunto-luastandalone-signal' => 'Interner Lua-Fehler: Der Interpreter beendet sich mit dem Signal „$2“.',
	'scribunto-luastandalone-exited' => 'Interner Lua-Fehler: Der Interpreter beendet sich mit dem Status $2.',
);

/** Spanish (español)
 * @author Armando-Martin
 */
$messages['es'] = array(
	'scribunto-desc' => 'Marco para la incorporación de lenguajes de script en páginas de MediaWiki',
	'scribunto-line' => 'en la línea $1',
	'scribunto-module-line' => 'en $1 en la línea $2',
	'scribunto-parser-error' => 'Error de script',
	'scribunto-parser-dialog-title' => 'Error de script',
	'scribunto-error-short' => 'Error de secuencia de comandos: $1',
	'scribunto-error-long' => 'Errores de secuencia de comandos (script):

$1',
	'scribunto-common-nosuchmodule' => 'Error de secuencia de comandos (script): no existe ese módulo.',
	'scribunto-common-nofunction' => 'Error de script: debe especificar una función a la que llamar.',
	'scribunto-common-nosuchfunction' => 'Error de script: la función especificada no existe.',
	'scribunto-common-timeout' => 'Ha caducado el tiempo asignado para ejecutar secuencias de comandos (scripts).',
	'scribunto-common-oom' => 'Se ha superado la cantidad de memoria permitida para ejecutar secuencias de comandos (script).',
	'scribunto-common-backtrace' => 'LLamadas de funciones activas (backtrace):',
	'scribunto-lua-in-function' => 'en la función "$1"',
	'scribunto-lua-in-main' => 'en el campo principal',
	'scribunto-lua-in-function-at' => 'en la función en $1: $2',
	'scribunto-lua-error-location' => 'Error de Lua $1: $2.',
	'scribunto-lua-error' => 'Error de Lua: $2.',
	'scribunto-lua-noreturn' => 'Error de secuencia de comandos: El módulo no devolvió ningún valor; debería devolver una tabla de exportación.',
	'scribunto-lua-notarrayreturn' => 'Error de secuencia de comandos: El módulo devolvió algo que no era una tabla; debería devolver una tabla de exportación.',
	'scribunto-luastandalone-proc-error' => 'Error de Lua: No se puede crear el proceso.',
	'scribunto-luastandalone-decode-error' => 'Error de Lua: Error interno: No se pudo decodificar el mensaje.',
	'scribunto-luastandalone-write-error' => 'Error de Lua: Error interno: Error al escribir en la canalización (pipe).',
	'scribunto-luastandalone-read-error' => 'Error de Lua: Error interno: Error al leer desde la canalización (pipe).',
	'scribunto-luastandalone-gone' => 'Error de Lua: Error interno: El intérprete ya ha finalizado.',
	'scribunto-luastandalone-signal' => 'Error de Lua: Error interno: El intérprete ha finalizado con la señal "$2".',
	'scribunto-luastandalone-exited' => 'Error de Lua: Error interno: El intérprete ha finalizado con el estado $2.',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'scribunto-desc' => 'Estrutura para incorporar linguaxes de script nas páxinas de MediaWiki',
	'scribunto-line' => 'na liña $1',
	'scribunto-module-line' => 'en $1 na liña $2',
	'scribunto-parser-error' => 'Erro de script',
	'scribunto-parser-dialog-title' => 'Erro de script',
	'scribunto-error-short' => 'Erro de script: $1',
	'scribunto-error-long' => 'Erros de script:

$1',
	'scribunto-common-nosuchmodule' => 'Erro de script: Non existe ese módulo.',
	'scribunto-common-nofunction' => 'Erro de script: Cómpre especificar a función que se quere chamar.',
	'scribunto-common-nosuchfunction' => 'Erro de script: A función especificada non existe.',
	'scribunto-common-timeout' => 'O tempo reservado para executar os scripts rematou.',
	'scribunto-common-oom' => 'Superouse a cantidade de memoria permitida para executar os scripts.',
	'scribunto-common-backtrace' => 'Rastro inverso (backtrace):',
	'scribunto-lua-in-function' => 'na función "$1"',
	'scribunto-lua-in-main' => 'no bloque principal',
	'scribunto-lua-in-function-at' => 'na función en $1:$2',
	'scribunto-lua-error-location' => 'Erro de Lua $1: $2.',
	'scribunto-lua-error' => 'Erro de Lua: $2.',
	'scribunto-lua-noreturn' => 'Erro de script: O módulo non devolveu ningún valor; debería devolver unha táboa de exportación.',
	'scribunto-lua-notarrayreturn' => 'Erro de script: O módulo devolveu algo que non era unha táboa; debería devolver unha táboa de exportación.',
	'scribunto-luastandalone-proc-error' => 'Erro de Lua: Non se pode crear o proceso.',
	'scribunto-luastandalone-decode-error' => 'Erro de Lua: Erro interno: Non se puido descodificar a mensaxe.',
	'scribunto-luastandalone-write-error' => 'Erro de Lua: Erro interno: Erro ao escribir na canalización (pipe).',
	'scribunto-luastandalone-read-error' => 'Erro de Lua: Erro interno: Erro ao ler desde a canalización (pipe).',
	'scribunto-luastandalone-gone' => 'Erro de Lua: Erro interno: O intérprete xa rematou.',
	'scribunto-luastandalone-signal' => 'Erro de Lua: Erro interno: O intérprete rematou co sinal "$2".',
	'scribunto-luastandalone-exited' => 'Erro de Lua: Erro interno: O intérprete rematou co estado $2.',
);

/** Upper Sorbian (hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'scribunto-line' => 'w lince $1',
	'scribunto-module-line' => 'w $1, w lince $2',
	'scribunto-parser-error' => 'Skriptowy zmylk',
	'scribunto-parser-dialog-title' => 'Skriptowy zmylk',
	'scribunto-error-short' => 'Skriptowy zmylk: $1',
	'scribunto-error-long' => 'Skriptowe zmylki:

$1',
	'scribunto-common-nosuchmodule' => 'Skriptowy zmylk: Tajki modul njeje.',
	'scribunto-common-nofunction' => 'Skriptowy zmylk: Dyrbiš funkciju podać, kotraž ma so wołać.',
	'scribunto-common-nosuchfunction' => 'Skriptowy zmylk: Funkcija, kotruž sy podał, njeeksistuje.',
	'scribunto-common-timeout' => 'Čas, kotryž je so za wuwjedźenje skriptow postajił, je spadnył.',
	'scribunto-common-backtrace' => 'Wróćoslědowanje:',
	'scribunto-lua-in-function' => 'we funkciji "$1"',
	'scribunto-lua-in-main' => 'we hłownym segmenće',
	'scribunto-lua-in-function-at' => 'we funkciji při $1:$2',
	'scribunto-lua-error-location' => 'Lua-zmylk $1:  $2.',
	'scribunto-lua-error' => 'Lua-zmylk:  $2.',
	'scribunto-lua-noreturn' => 'Skriptowy zmylk: Modul njeje hódnotu wróćił, byrnjež měł eksportowu tabelu wróćić.',
);

/** Interlingua (interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'scribunto-desc' => 'Structura pro incorporar linguages de script in paginas de MediaWiki',
	'scribunto-line' => 'al linea $1',
	'scribunto-module-line' => 'in $1 al linea $2',
	'scribunto-parser-error' => 'Error de script',
	'scribunto-parser-dialog-title' => 'Error de script',
	'scribunto-error-short' => 'Error de script: $1',
	'scribunto-error-long' => 'Errores de script:

$1',
	'scribunto-common-nosuchmodule' => 'Error de script: modulo non trovate',
	'scribunto-common-nofunction' => 'Error de script: tu debe specificar un function a appellar.',
	'scribunto-common-nosuchfunction' => 'Error de script: le function specificate non existe.',
	'scribunto-common-timeout' => 'Le tempore allocate pro le execution de scripts ha expirate.',
	'scribunto-common-oom' => 'Le quantitate de memoria permittite pro le execution de scripts ha essite excedite.',
	'scribunto-common-backtrace' => 'Tracia a retro:',
	'scribunto-lua-in-function' => 'in function "$1"',
	'scribunto-lua-in-main' => 'in le parte principal',
	'scribunto-lua-in-function-at' => 'in le function a $1:$2',
	'scribunto-lua-error-location' => 'Error de Lua $1: $2',
	'scribunto-lua-error' => 'Error de Lua: $2',
	'scribunto-lua-noreturn' => 'Error de script: Le modulo non retornava un valor, illo deberea retornar un tabella de exportation.',
	'scribunto-lua-notarrayreturn' => 'Error de script: Le modulo retornava qualcosa altere que un tabella, illo deberea retornar un tabella de exportation.',
	'scribunto-luastandalone-proc-error' => 'Error de Lua: non pote crear processo',
	'scribunto-luastandalone-decode-error' => 'Error de Lua: error interne: impossibile decodificar le message',
	'scribunto-luastandalone-write-error' => 'Error de Lua: error interne: error de scriptura al tubo',
	'scribunto-luastandalone-read-error' => 'Error de Lua: error interne: error de lectura del tubo',
	'scribunto-luastandalone-gone' => 'Error de Lua: error interne: le interpretator ha jam exite',
	'scribunto-luastandalone-signal' => 'Error de Lua: error interne: le interpretator ha terminate con le signal "$2"',
	'scribunto-luastandalone-exited' => 'Error de Lua: error interne: le interpretator exiva con le stato $2',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'scribunto-desc' => 'MediaWiki ページにスクリプト言語を埋め込むフレームワーク',
	'scribunto-parser-error' => 'スクリプトエラー',
	'scribunto-parser-dialog-title' => 'スクリプトエラー',
	'scribunto-error-short' => 'スクリプトエラー：$1',
	'scribunto-error-long' => 'スクリプトエラー：

$1',
	'scribunto-common-nofunction' => 'スクリプトエラー：呼び出す関数を指定してください。',
	'scribunto-common-nosuchfunction' => 'スクリプトエラー：指定した関数は存在しません。',
	'scribunto-common-timeout' => 'スクリプトに割り当てた時間が終了しました。',
	'scribunto-common-backtrace' => 'バックトレース：',
	'scribunto-lua-error-location' => 'Lua エラー $1：$2',
	'scribunto-lua-error' => 'Lua エラー：$2',
	'scribunto-luastandalone-proc-error' => 'Lua エラー：プロセスを作成できません。',
	'scribunto-luastandalone-decode-error' => 'Lua エラー：内部エラー：メッセージを復号できません。',
	'scribunto-luastandalone-write-error' => 'Lua エラー：内部エラー：パイプへの書き込みエラーです。',
	'scribunto-luastandalone-read-error' => 'Lua エラー：内部エラー：パイプからの読み込みエラーです。',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'scribunto-line' => 'an der Linn $1',
	'scribunto-parser-error' => 'Script-Feeler',
	'scribunto-parser-dialog-title' => 'Script-Feeler',
	'scribunto-error-long' => 'Script-Feeler:

$1',
	'scribunto-lua-error' => 'Lua Feeler: $2',
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'scribunto-desc' => 'Рамка за вметнување на скриптни јазици во страница на МедијаВики',
	'scribunto-line' => 'во редот $1',
	'scribunto-module-line' => 'во $1, ред $2',
	'scribunto-parser-error' => 'Грешка во скриптата',
	'scribunto-parser-dialog-title' => 'Грешка во скриптата',
	'scribunto-error-short' => 'Грешка во скриптата: $1',
	'scribunto-error-long' => 'Грешки во скриптата:

$1',
	'scribunto-common-nosuchmodule' => 'Грешка во скриптата: Нема таков модул',
	'scribunto-common-nofunction' => 'Грешка во скриптата: Мора да ја наведете функцијата што треба да се повика.',
	'scribunto-common-nosuchfunction' => 'Грешка во скриптата: Наведената функција не постои.',
	'scribunto-common-timeout' => 'Зададеното време за работа на скриптите истече.',
	'scribunto-common-oom' => 'Надминат е дозволениот складишен простор за работа на скрипти.',
	'scribunto-common-backtrace' => 'Проследување на текот:',
	'scribunto-lua-in-function' => 'во функцијата „$1“',
	'scribunto-lua-in-main' => 'во главниот дел',
	'scribunto-lua-in-function-at' => 'во функцијата, кај $1:$2',
	'scribunto-lua-error-location' => 'Грешка во Lua $1: $2',
	'scribunto-lua-error' => 'Грешка во Lua: $2',
	'scribunto-lua-noreturn' => 'Грешка во скриптата: Модулот не врати вредност. Треба да врати извозна табела.',
	'scribunto-lua-notarrayreturn' => 'Грешка во скриптата: Модулот не врати табела, туку нешто друго. Треба да врати извозна табела.',
	'scribunto-luastandalone-proc-error' => 'Грешка во Lua: не можам да ја создадам постапката',
	'scribunto-luastandalone-decode-error' => 'Грешка во Lua: внатрешна грешка: не можам да ја декодирам пораката',
	'scribunto-luastandalone-write-error' => 'Грешка во Lua: внатрешна грешка: грешка при записот',
	'scribunto-luastandalone-read-error' => 'Грешка во Lua: внатрешна грешка: грешка при читањето',
	'scribunto-luastandalone-gone' => 'Грешка во Lua: внатрешна грешка: толкувачот веќе напушти',
	'scribunto-luastandalone-signal' => 'Грешка во Lua: внатрешна грешка: толкувачот прекина да работи со сигналот „$2“',
	'scribunto-luastandalone-exited' => 'Грешка во Lua: внатрешна грешка: толкувачот напушти со статусот $2',
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'scribunto-desc' => "Framework voor het inbedden van scripttalen in pagina's",
	'scribunto-line' => 'op regel $1',
	'scribunto-module-line' => 'in $1 op regel $2',
	'scribunto-parser-error' => 'Scriptfout',
	'scribunto-parser-dialog-title' => 'Scriptfout',
	'scribunto-error-short' => 'Scriptfout: $1',
	'scribunto-error-long' => 'Scriptfouten:

$1',
	'scribunto-common-nosuchmodule' => 'Scriptfout: de module bestaat niet.',
	'scribunto-common-nofunction' => 'Scriptfout: u moet een aan te roepen functie opgeven.',
	'scribunto-common-nosuchfunction' => 'Scriptfout: de opgegeven functie bestaat niet.',
	'scribunto-common-timeout' => 'De maximale uitvoertijd voor scripts is verlopen.',
	'scribunto-common-oom' => 'De hoeveelheid geheugen die uitgevoerde scripts mogen gebruiken is overschreden.',
	'scribunto-common-backtrace' => 'Backtrace:',
	'scribunto-lua-in-function' => 'in functie "$1"',
	'scribunto-lua-in-main' => 'in het hoofdgedeelte',
	'scribunto-lua-in-function-at' => 'in de functie op $1:$2',
	'scribunto-lua-error-location' => 'Luafout $1: $2',
	'scribunto-lua-error' => 'Luafout: $2',
	'scribunto-lua-noreturn' => 'Scriptfout: de module heeft geen waarde teruggegeven. Deze hoort een exporttabel terug te geven.',
	'scribunto-lua-notarrayreturn' => 'Scriptfout: de module heeft iets anders dan een tabel teruggegeven. Deze hoort een exporttabel terug te geven.',
	'scribunto-luastandalone-proc-error' => 'Luafout: het was niet mogelijk een proces te creëren.',
	'scribunto-luastandalone-decode-error' => 'Luafout: interne fout: het was niet mogelijk het bericht te decoderen.',
	'scribunto-luastandalone-write-error' => 'Luafout: interne fout: fout tijdens het schrijven naar de pipe.',
	'scribunto-luastandalone-read-error' => 'Luafout: interne fout: fout tijdens het lezen van de pipe.',
	'scribunto-luastandalone-gone' => 'Luafout: interne fout: de verwerkingsmodule is al klaar',
	'scribunto-luastandalone-signal' => 'Luafout: interne fout: de verwerkingsmodule is gestopt met het signaal "$2".',
	'scribunto-luastandalone-exited' => 'Luafout: interne fout: de verwerkingsmodule is gestopt met de status $2.',
);

/** Brazilian Portuguese (português do Brasil)
 * @author Jaideraf
 */
$messages['pt-br'] = array(
	'scribunto-desc' => 'Estrutura para incorporar linguagens de script em páginas do MediaWiki',
	'scribunto-line' => 'na linha $1',
	'scribunto-module-line' => 'em $1 na linha $2',
	'scribunto-parser-error' => 'Erro de script',
	'scribunto-parser-dialog-title' => 'Erro de script',
	'scribunto-error-short' => 'Erro de script: $1',
	'scribunto-error-long' => 'Erros de script:

$1',
	'scribunto-common-nosuchmodule' => 'Erro de script: módulo não encontrado',
	'scribunto-common-nofunction' => 'Erro de script: você deve especificar uma função para chamar.',
	'scribunto-common-nosuchfunction' => 'Erro de script: a função especificada não existe.',
	'scribunto-common-timeout' => 'O tempo alocado para a execução de scripts expirou.',
	'scribunto-common-oom' => 'A quantidade de memória permitida para a execução de scripts foi excedida.',
	'scribunto-common-backtrace' => 'Backtrace:',
	'scribunto-lua-in-function' => 'na função "$1"',
	'scribunto-lua-in-main' => 'na parte principal',
	'scribunto-lua-in-function-at' => 'na função em $1:$2',
	'scribunto-lua-error-location' => 'Erro em Lua $1: $2',
	'scribunto-lua-error' => 'Erro em lua: $2',
	'scribunto-lua-noreturn' => 'Erro de script: o módulo não retornou um valor, ele deveria retornar uma tabela de exportação.',
	'scribunto-lua-notarrayreturn' => 'Erro de script: o módulo retornou algo diferente de uma tabela, ele deveria retornar uma tabela de exportação.',
	'scribunto-luastandalone-proc-error' => 'Erro em Lua: impossível criar o processo',
	'scribunto-luastandalone-decode-error' => 'Erro em Lua: erro interno: não foi possível decodificar a mensagem',
	'scribunto-luastandalone-write-error' => 'Erro em Lua: erro interno: erro ao gravar pipe',
	'scribunto-luastandalone-read-error' => 'Erro em Lua: erro interno: erro ao ler do pipe',
	'scribunto-luastandalone-gone' => 'Erro em Lua: erro interno: o interpretador já foi encerrado.',
	'scribunto-luastandalone-signal' => 'Erro em Lua: erro interno: o interpretador foi finalizado com o sinal "$2"',
	'scribunto-luastandalone-exited' => 'Erro em Lua: erro interno: o interpretador saiu com status $2',
);

/** Tagalog (Tagalog)
 * @author AnakngAraw
 */
$messages['tl'] = array(
	'scribunto-desc' => 'Baskagan para sa pagbabaon ng mga wikang pampagpapanitik papaloob sa mga pahina ng MediaWiki',
	'scribunto-line' => 'sa guhit na $1',
	'scribunto-module-line' => 'sa loob ng $1 na nasa guhit na $2',
	'scribunto-parser-error' => 'Kamalian sa panitik',
	'scribunto-parser-dialog-title' => 'Kamalian sa panitik',
	'scribunto-error-short' => 'Kamalian sa panitik: $1',
	'scribunto-error-long' => 'Mga kamalian sa panitik:

$1',
	'scribunto-common-nosuchmodule' => 'Kamalian sa panitik: Walang ganyang modulo',
	'scribunto-common-nofunction' => 'Kamalian sa panitik: Dapat kang magtukoy ng isang tungkuling tatawagin.',
	'scribunto-common-nosuchfunction' => 'Kamalian sa panitik: Ang tinukoy mong tungkulin ay hindi umiiral.',
	'scribunto-common-timeout' => 'Ang panahong inilaan para sa pagpapatakbo ng mga panitik ay lipas na.',
	'scribunto-common-oom' => 'Ang dami ng pinahintulutang alaala para sa pagpapatakbo ng mga panitik ay nalampasan na.',
	'scribunto-common-backtrace' => 'Paurong na pagbabakas:',
	'scribunto-lua-in-function' => 'sa loob ng tungkuling "$1"',
	'scribunto-lua-in-main' => 'sa loob ng pangunahing tipak',
	'scribunto-lua-in-function-at' => 'sa loob ng tungkuling nasa $1:$2',
	'scribunto-lua-error-location' => 'Kamalian ng lua na $1: $2',
	'scribunto-lua-error' => 'Kamalian ng lua: $2',
	'scribunto-lua-noreturn' => 'Kamalian sa panitik: Ang modyul ay hindi nagbalik ng isang halaga, dapat itong magbalik ng isang talahanayan ng pag-aangkat.',
	'scribunto-lua-notarrayreturn' => 'Kamalian sa panitik: Ang modulo ay nagbalik ng isang bagay na bukod sa isang talahanayan, dapat itong magbalik ng isang talahanayan ng pag-aangkat.',
	'scribunto-luastandalone-proc-error' => 'Kamalian ng lua: hindi malikha ang proseso',
	'scribunto-luastandalone-decode-error' => 'Kamalian ng lua: panloob na kamalian: hindi nagawang alamin ang kodigo ng mensahe',
	'scribunto-luastandalone-write-error' => 'Kamalian ng lua: panloob na kamalian: kamalian sa pagsusulat sa tubo',
	'scribunto-luastandalone-read-error' => 'Kamalian sa lua: kamaliang panloob: kamalian sa pagbabasa mula sa tubo',
	'scribunto-luastandalone-gone' => 'Kamalian sa lua: panloob na kamalian: lumabas na ang tagapagpaunawa',
	'scribunto-luastandalone-signal' => 'Kamalian sa lua: panloob na kamalian: huminto ang tagapagpaliwanag na mayroong senyas na "$2"',
	'scribunto-luastandalone-exited' => 'Kamalian sa lua: panloob na kamalian: ang tagapagpaunawa ay lumabas na mayroong katayuang $2',
);


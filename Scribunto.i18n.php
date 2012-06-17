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
	'scribunto-common-nosuchmodule' => 'Script error: No such module',
	'scribunto-common-nofunction' => 'Script error: You must specify a function to call.',
	'scribunto-common-nosuchfunction' => 'Script error: The function you specified did not exist.',
	'scribunto-common-timeout' => 'The time allocated for running scripts has expired.',
	'scribunto-common-oom' => 'The amount of memory allowed for running scripts has been exceeded.',
	'scribunto-common-backtrace' => 'Backtrace:',
	'scribunto-lua-in-function' => 'in function "$1"',
	'scribunto-lua-in-main' => 'in main chunk',
	'scribunto-lua-in-function-at' => 'in the function at $1:$2',
	'scribunto-lua-backtrace-line' => '$1: $2',
	'scribunto-lua-error-location' => 'Lua error $1: $2',
	'scribunto-lua-error' => 'Lua error: $2',
	'scribunto-lua-noreturn' => 'Script error: The module did not return a value, it should return an export table.',
	'scribunto-lua-notarrayreturn' => 'Script error: The module returned something other than a table, it should return an export table.',
	'scribunto-luastandalone-proc-error' => 'Lua error: cannot create process',
	'scribunto-luastandalone-decode-error' => 'Lua error: internal error: unable to decode message',
	'scribunto-luastandalone-write-error' => 'Lua error: internal error: error writing to pipe',
	'scribunto-luastandalone-read-error' => 'Lua error: internal error: error reading from pipe',
	'scribunto-luastandalone-gone' => 'Lua error: internal error: the interpreter has already exited',
	'scribunto-luastandalone-signal' => 'Lua error: internal error: the interpreter has terminated with signal "$2"',
	'scribunto-luastandalone-exited' => 'Lua error: internal error: the interpreter exited with status $2',
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

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
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


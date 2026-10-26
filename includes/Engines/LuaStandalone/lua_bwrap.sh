#!/bin/sh
#
# Run the standalone Lua interpreter inside a bubblewrap sandbox.
#
# Don't call this directly, enable it with
#
#   $wgScribuntoEngineConf['luastandalone']['useBubblewrap'] = true;
#
# The interpreter gets no network, no IPC, its own PID namespace, an empty
# environment and read-only access to /usr and Scribunto's includes directory
# only (plus a /proc showing just the sandbox, read by getStatus). It cannot
# see LocalSettings.php or anything else writable by the web server.
#
# Arguments are passed by LuaStandaloneInterpreter:
# <lua binary> <mw_main.lua> <includes dir> ...

lua=$(realpath "$1")
includes=$(realpath "$3")
shift

# Recreate the usual top-level symlinks of merged-/usr systems, so that the
# dynamic loader and libc are found under /usr
links=
for d in bin sbin lib lib64 lib32; do
	if [ -L "/$d" ]; then
		links="$links --symlink $(readlink "/$d") /$d"
	fi
done

# shellcheck disable=SC2086
exec bwrap \
	--unshare-all \
	--die-with-parent \
	--new-session \
	--cap-drop ALL \
	--clearenv \
	--ro-bind /usr /usr \
	$links \
	--dev /dev \
	--proc /proc \
	--ro-bind "$includes" "$includes" \
	--ro-bind "$lua" "$lua" \
	--chdir / \
	"$lua" "$@"

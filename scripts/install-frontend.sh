#!/usr/bin/env bash
# Install a trusted CI artifact; publish its manifest only after all assets exist.
set -euo pipefail

archive=${1:?Usage: install-frontend.sh ARCHIVE COMMIT [APP_PATH]}
revision=${2:?Expected commit is required}
app_path=${3:-$(pwd)}
[[ "$revision" =~ ^[0-9a-f]{40}$ ]] || { echo 'Invalid commit.' >&2; exit 1; }
test -d "$app_path/public"
stage=$(mktemp -d "$app_path/public/.frontend.XXXXXX")
trap 'rm -rf -- "$stage"' EXIT
tar -xzf "$archive" -C "$stage"
test "$(cat "$stage/build/.revision")" = "$revision" || {
    echo 'Frontend artifact does not match the deployed commit.' >&2
    exit 1
}

php -r '
    $root = $argv[1];
    $manifest = json_decode(file_get_contents("$root/manifest.json"), true, 512, JSON_THROW_ON_ERROR);
    if (!$manifest || !is_array($manifest)) { throw new RuntimeException("Empty manifest"); }
    foreach ($manifest as $entry) {
        foreach (array_merge([$entry["file"] ?? ""], $entry["css"] ?? [], $entry["assets"] ?? []) as $file) {
            if (!str_starts_with($file, "assets/") || str_contains($file, "..") || !is_file("$root/$file")) {
                throw new RuntimeException("Missing or invalid frontend asset");
            }
        }
    }
' "$stage/build"

# Keep previous hashed files so already-open pages can finish loading them.
mkdir -p "$app_path/public/build/assets"
cp -R "$stage/build/assets/." "$app_path/public/build/assets/"
chmod -R a+rX "$app_path/public/build/assets"
chmod 644 "$stage/build/manifest.json" "$stage/build/.revision"
mv -f "$stage/build/.revision" "$app_path/public/build/.revision"
mv -f "$stage/build/manifest.json" "$app_path/public/build/manifest.json"
echo "Installed frontend for $revision"

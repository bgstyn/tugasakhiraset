"""Exercise the real installer against disposable artifacts, without Laravel/DB."""
import json
import pathlib
import shutil
import subprocess
import tarfile
import tempfile
import unittest


def bash_path(path):
    value = path.as_posix()
    return "/" + value[0].lower() + value[2:] if path.drive else value


@unittest.skipUnless(shutil.which("bash") and shutil.which("php"), "Requires Linux bash and PHP")
class FrontendInstallTest(unittest.TestCase):
    revision = "a" * 40
    installer = pathlib.Path(__file__).resolve().parents[1] / "install-frontend.sh"

    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = pathlib.Path(self.temporary.name)
        self.app = self.root / "app with spaces"
        self.build = self.app / "public/build"
        (self.build / "assets").mkdir(parents=True)
        self.old_manifest = '{"old": {"file": "assets/old.js"}}'
        (self.build / "manifest.json").write_text(self.old_manifest)
        (self.build / "assets/old.js").write_text("old code")
        (self.app / ".env").write_text("PRODUCTION_SECRET=keep")
        self.source = self.root / "source/build"
        (self.source / "assets").mkdir(parents=True)
        (self.source / "assets/new.js").write_text("new code")
        (self.source / ".revision").write_text(self.revision)
        self.manifest = {"app.js": {"file": "assets/new.js"}}

    def install(self):
        (self.source / "manifest.json").write_text(json.dumps(self.manifest))
        archive = self.root / "frontend.tar.gz"
        with tarfile.open(archive, "w:gz") as bundle:
            bundle.add(self.source, arcname="build")
        return subprocess.run(
            [shutil.which("bash"), bash_path(self.installer), bash_path(archive), self.revision, bash_path(self.app)],
            text=True, capture_output=True,
        )

    def assert_unchanged(self):
        self.assertEqual((self.build / "manifest.json").read_text(), self.old_manifest)
        self.assertEqual((self.app / ".env").read_text(), "PRODUCTION_SECRET=keep")
        self.assertFalse(list((self.app / "public").glob(".frontend.*")))

    def test_install_and_retry_preserve_old_assets_and_env(self):
        for _ in range(2):
            result = self.install()
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual(json.loads((self.build / "manifest.json").read_text()), self.manifest)
            self.assertEqual((self.build / "assets/new.js").read_text(), "new code")
            self.assertEqual((self.build / "assets/old.js").read_text(), "old code")
            self.assertEqual((self.app / ".env").read_text(), "PRODUCTION_SECRET=keep")
            self.assertFalse(list((self.app / "public").glob(".frontend.*")))

    def test_wrong_revision_keeps_previous_manifest(self):
        (self.source / ".revision").write_text("b" * 40)
        result = self.install()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("does not match", result.stderr)
        self.assert_unchanged()

    def test_missing_javascript_keeps_previous_manifest(self):
        (self.source / "assets/new.js").unlink()
        result = self.install()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Missing or invalid frontend asset", result.stderr)
        self.assert_unchanged()

    def test_missing_css_keeps_previous_manifest(self):
        self.manifest["app.js"]["css"] = ["assets/missing.css"]
        result = self.install()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Missing or invalid frontend asset", result.stderr)
        self.assert_unchanged()

    def test_empty_manifest_keeps_previous_manifest(self):
        self.manifest = {}
        result = self.install()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Empty manifest", result.stderr)
        self.assert_unchanged()


if __name__ == "__main__":
    unittest.main()

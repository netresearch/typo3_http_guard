"""Offline controls for the existing synthetic server's connection witnesses."""
import ast
import importlib.util
from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / "Tests/HttpGuard/Integration/wire_server.py"


def fixture_module():
    tree = ast.parse(SOURCE.read_text())
    guarded = any(isinstance(node, ast.If) and isinstance(node.test, ast.Compare)
                  and ast.unparse(node.test) == "__name__ == '__main__'" for node in tree.body)
    if not guarded:
        raise AssertionError("Importing fixture helpers must not start network targets")
    spec = importlib.util.spec_from_file_location("wire_lifecycle_fixture", SOURCE)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class WireLifecycleTests(unittest.TestCase):
    def setUp(self):
        self.lifecycle = fixture_module().ConnectionLifecycle(history_limit=2)

    def test_closing_one_connection_preserves_other_active_connection(self):
        first, second = object(), object()
        first_id = self.lifecycle.accept(first)
        second_id = self.lifecycle.accept(second)
        self.lifecycle.request(first, "/slow/first")
        self.lifecycle.request(second, "/echo/second")
        self.lifecycle.close(first)
        snapshot = self.lifecycle.snapshot()
        self.assertNotIn(str(first_id), snapshot["connections"])
        self.assertEqual({"path": "/slow/first", "requests": 1},
                         snapshot["closed_connections"][str(first_id)])
        self.assertEqual({"path": "/echo/second", "requests": 1},
                         snapshot["connections"][str(second_id)])

    def test_reused_connection_keeps_identity_and_actual_request_count(self):
        connection = object()
        identity = self.lifecycle.accept(connection)
        self.lifecycle.request(connection, "/first")
        self.lifecycle.request(connection, "/second")
        self.lifecycle.close(connection)
        self.assertEqual({"path": "/second", "requests": 2},
                         self.lifecycle.snapshot()["closed_connections"][str(identity)])

    def test_tls_wrapping_preserves_the_accepted_tcp_identity(self):
        original, wrapped = object(), object()
        identity = self.lifecycle.accept(original)
        self.lifecycle.rebind(original, wrapped)
        self.lifecycle.request(wrapped, "/tls/request")
        self.assertEqual([str(identity)], list(self.lifecycle.snapshot()["connections"]))
        self.lifecycle.close(wrapped)
        self.assertEqual({"path": "/tls/request", "requests": 1},
                         self.lifecycle.snapshot()["closed_connections"][str(identity)])

    def test_completed_history_is_bounded_without_discarding_active_connections(self):
        active = object()
        active_id = self.lifecycle.accept(active)
        self.lifecycle.request(active, "/still-active")
        completed = []
        for index in range(3):
            connection = object()
            completed.append(self.lifecycle.accept(connection))
            self.lifecycle.request(connection, f"/completed/{index}")
            self.lifecycle.close(connection)
        snapshot = self.lifecycle.snapshot()
        self.assertEqual([str(identity) for identity in completed[-2:]],
                         list(snapshot["closed_connections"]))
        self.assertEqual([str(active_id)], list(snapshot["connections"]))

    def test_duplicate_close_is_rejected_without_erasing_an_unrelated_witness(self):
        connection, other = object(), object()
        identity = self.lifecycle.accept(connection)
        other_id = self.lifecycle.accept(other)
        self.lifecycle.close(connection)
        with self.assertRaises(KeyError):
            self.lifecycle.close(connection)
        snapshot = self.lifecycle.snapshot()
        self.assertIn(str(identity), snapshot["closed_connections"])
        self.assertIn(str(other_id), snapshot["connections"])


if __name__ == "__main__":
    unittest.main()

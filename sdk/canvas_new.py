"""
canvas.new Python SDK

Usage:
    import canvas_new

    page = canvas_new.generate(
        context="Q3 revenue was $2.4M, up 34% YoY...",
        title="Q3 Revenue Report"
    )
    print(page['url'])  # https://yourdomain.com/c/aB3xKp9m

Or with explicit client:
    from canvas_new import CanvasNew
    client = CanvasNew(base_url="https://yourdomain.com")
    page = client.generate(context="...", style="dashboard")
"""

from __future__ import annotations

import os
import json
import urllib.request
import urllib.error
from typing import Literal, Optional


StyleType = Literal["auto", "dashboard", "report", "tool", "data", "list", "creative"]


class CanvasNew:
    def __init__(self, base_url: str = "http://localhost:8080"):
        self.base_url = base_url.rstrip("/")

    def generate(
        self,
        context: str,
        title: Optional[str] = None,
        style: StyleType = "auto",
    ) -> dict:
        """
        Generate a canvas page from text/context.

        Args:
            context: The text, data, or context to turn into a page.
            title:   Optional page title. Auto-detected from HTML <title> if omitted.
            style:   Layout hint. One of: auto, dashboard, report, tool, data, list, creative.

        Returns:
            dict with keys: ok, id, url, edit_token, title
        """
        if not context or not context.strip():
            raise ValueError("context is required")

        payload = {"context": context, "style": style}
        if title:
            payload["title"] = title

        return self._post("/pipeline/generate.php", payload)

    def list(self, limit: int = 20) -> list[dict]:
        """List recent canvases. Returns list of {id, title, created_at, url}."""
        url = f"{self.base_url}/api/canvas.php?action=list&limit={limit}"
        req = urllib.request.Request(url)
        with urllib.request.urlopen(req, timeout=10) as resp:
            return json.loads(resp.read())

    def delete(self, canvas_id: str, edit_token: str) -> dict:
        """Delete a canvas by ID using its edit token."""
        url = f"{self.base_url}/api/canvas.php?id={canvas_id}&token={edit_token}"
        req = urllib.request.Request(url, method="DELETE")
        try:
            with urllib.request.urlopen(req, timeout=10) as resp:
                return json.loads(resp.read())
        except urllib.error.HTTPError as e:
            body = e.read().decode()
            raise RuntimeError(f"Delete failed ({e.code}): {body}") from e

    # ── Private ───────────────────────────────────────────────────────────────

    def _post(self, path: str, data: dict) -> dict:
        url = self.base_url + path
        body = json.dumps(data).encode()
        req = urllib.request.Request(
            url,
            data=body,
            headers={"Content-Type": "application/json"},
            method="POST",
        )
        try:
            with urllib.request.urlopen(req, timeout=120) as resp:
                result = json.loads(resp.read())
        except urllib.error.HTTPError as e:
            body_text = e.read().decode()
            try:
                err = json.loads(body_text)
            except Exception:
                err = {"error": body_text}
            raise RuntimeError(f"API error ({e.code}): {err.get('error', body_text)}") from e

        if not result.get("ok"):
            raise RuntimeError(f"Generation failed: {result.get('error', 'unknown error')}")

        return result


# ── Module-level convenience functions ────────────────────────────────────────

_default_client: Optional[CanvasNew] = None


def _get_client() -> CanvasNew:
    global _default_client
    if _default_client is None:
        base_url = os.environ.get("CANVAS_BASE_URL", "http://localhost:8080")
        _default_client = CanvasNew(base_url=base_url)
    return _default_client


def generate(
    context: str,
    title: Optional[str] = None,
    style: StyleType = "auto",
    base_url: Optional[str] = None,
) -> dict:
    """Generate a canvas page. Returns {ok, id, url, edit_token, title}."""
    client = CanvasNew(base_url) if base_url else _get_client()
    return client.generate(context=context, title=title, style=style)


def list_canvases(limit: int = 20, base_url: Optional[str] = None) -> list[dict]:
    """List recent canvases."""
    client = CanvasNew(base_url) if base_url else _get_client()
    return client.list(limit=limit)


# ── CLI usage ─────────────────────────────────────────────────────────────────

if __name__ == "__main__":
    import sys
    import argparse

    parser = argparse.ArgumentParser(description="canvas.new CLI")
    sub = parser.add_subparsers(dest="cmd")

    gen_p = sub.add_parser("generate", help="Generate a canvas page")
    gen_p.add_argument("context", help="Text or data to convert into a page")
    gen_p.add_argument("--title", default="", help="Page title (optional)")
    gen_p.add_argument("--style", default="auto",
                       choices=["auto","dashboard","report","tool","data","list","creative"])
    gen_p.add_argument("--base-url", default=os.environ.get("CANVAS_BASE_URL","http://localhost:8080"))

    list_p = sub.add_parser("list", help="List recent canvases")
    list_p.add_argument("--limit", type=int, default=10)
    list_p.add_argument("--base-url", default=os.environ.get("CANVAS_BASE_URL","http://localhost:8080"))

    args = parser.parse_args()

    if args.cmd == "generate":
        result = generate(args.context, title=args.title, style=args.style, base_url=args.base_url)
        print(f"✓ Canvas created: {result['url']}")
        print(f"  ID:         {result['id']}")
        print(f"  Title:      {result['title']}")
        print(f"  Edit token: {result['edit_token']}")
    elif args.cmd == "list":
        canvases = list_canvases(limit=args.limit, base_url=args.base_url)
        if not canvases:
            print("No canvases yet.")
        for c in canvases:
            print(f"  {c['id']}  {c['title']:<40}  {c['created_at'][:10]}  {c['url']}")
    else:
        parser.print_help()

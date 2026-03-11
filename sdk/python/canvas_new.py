"""
canvas-new Python SDK — zero dependencies (pure stdlib)

Install: pip install canvas-new

Usage:
    import canvas_new

    # Create a canvas
    c = canvas_new.create('<h1>Hello</h1>', title='My Dashboard')
    print(c['url'])  # https://yourdomain.com/c/aB3xKp9m

    # Update it (triggers SSE hot-swap for all live viewers)
    canvas_new.update(c['id'], c['edit_token'], html='<h1>Updated!</h1>')

    # Generate from text via AI pipeline
    page = canvas_new.generate('Q3 revenue $2.4M, up 34% YoY', style='dashboard')
    print(page['url'])
"""

from __future__ import annotations

import os
import json
import argparse
import urllib.request
import urllib.error
from typing import Optional, List, Literal


StyleType = Literal["auto", "dashboard", "report", "tool", "data", "list", "creative"]


class CanvasNew:
    def __init__(self, base_url: str = "http://localhost:8080"):
        self.base_url = base_url.rstrip("/")

    # ── Core CRUD ──────────────────────────────────────────────────────────────

    def create(
        self,
        html: str,
        title: Optional[str] = None,
        frames: Optional[List[dict]] = None,
        webhook_url: Optional[str] = None,
    ) -> dict:
        """
        Create a new canvas.
        Returns: {id, url, embed_url, edit_token}
        """
        if not html:
            raise ValueError("html is required")
        payload: dict = {"html": html}
        if title:       payload["title"]       = title
        if frames:      payload["frames"]      = frames
        if webhook_url: payload["webhook_url"] = webhook_url
        return self._post("/api/create.php", payload)

    def update(
        self,
        canvas_id: str,
        edit_token: str,
        html: Optional[str] = None,
        title: Optional[str] = None,
    ) -> dict:
        """
        Update canvas HTML and/or title. Triggers SSE broadcast to live viewers.
        Returns: {ok, updated_at}
        """
        if not canvas_id or not edit_token:
            raise ValueError("canvas_id and edit_token are required")
        payload: dict = {"id": canvas_id, "edit_token": edit_token}
        if html  is not None: payload["html"]  = html
        if title is not None: payload["title"] = title
        return self._post("/api/update.php", payload)

    def delete(self, canvas_id: str, edit_token: str) -> dict:
        """Delete a canvas. Returns: {ok}"""
        return self._post("/api/delete.php", {"id": canvas_id, "edit_token": edit_token})

    def get(self, canvas_id: str) -> dict:
        """Get canvas metadata + HTML. Returns: {id, title, html, frames, created_at, updated_at, views}"""
        url = f"{self.base_url}/api/get.php?id={urllib.parse.quote(canvas_id)}"
        req = urllib.request.Request(url)
        try:
            with urllib.request.urlopen(req, timeout=10) as resp:
                return json.loads(resp.read())
        except urllib.error.HTTPError as e:
            raise RuntimeError(self._parse_error(e)) from e

    def list(self, limit: int = 50, offset: int = 0) -> dict:
        """List canvases. Returns: {canvases: [...], total: int}"""
        url = f"{self.base_url}/api/list.php?limit={limit}&offset={offset}"
        req = urllib.request.Request(url)
        try:
            with urllib.request.urlopen(req, timeout=10) as resp:
                return json.loads(resp.read())
        except urllib.error.HTTPError as e:
            raise RuntimeError(self._parse_error(e)) from e

    # ── AI Pipeline ────────────────────────────────────────────────────────────

    def generate(
        self,
        context: str,
        title: Optional[str] = None,
        style: StyleType = "auto",
    ) -> dict:
        """
        Generate a beautiful canvas page from any text/context via MiniMax M2.5.
        Returns: {ok, id, url, edit_token, title}
        """
        if not context or not context.strip():
            raise ValueError("context is required")
        payload: dict = {"context": context, "style": style}
        if title: payload["title"] = title
        return self._post("/pipeline/generate.php", payload)

    # ── Private ───────────────────────────────────────────────────────────────

    def _post(self, path: str, data: dict) -> dict:
        url  = self.base_url + path
        body = json.dumps(data).encode()
        req  = urllib.request.Request(
            url, data=body,
            headers={"Content-Type": "application/json"},
            method="POST",
        )
        try:
            with urllib.request.urlopen(req, timeout=120) as resp:
                result = json.loads(resp.read())
        except urllib.error.HTTPError as e:
            raise RuntimeError(self._parse_error(e)) from e

        if isinstance(result, dict) and "error" in result:
            raise RuntimeError(f"API error: {result['error']}")
        return result

    def _parse_error(self, e: urllib.error.HTTPError) -> str:
        body = e.read().decode()
        try:
            return f"HTTP {e.code}: {json.loads(body).get('error', body)}"
        except Exception:
            return f"HTTP {e.code}: {body}"


# ── Module-level convenience API ──────────────────────────────────────────────

import urllib.parse  # noqa: E402 (imported here to avoid polluting top-level namespace)

_client: Optional[CanvasNew] = None


def _get() -> CanvasNew:
    global _client
    if _client is None:
        _client = CanvasNew(os.environ.get("CANVAS_BASE_URL", "http://localhost:8080"))
    return _client


def create(html: str, title: Optional[str] = None, frames=None, webhook_url: Optional[str] = None) -> dict:
    """Create a canvas. Returns {id, url, embed_url, edit_token}."""
    return _get().create(html, title=title, frames=frames, webhook_url=webhook_url)


def update(canvas_id: str, edit_token: str, html: Optional[str] = None, title: Optional[str] = None) -> dict:
    """Update a canvas. Returns {ok, updated_at}."""
    return _get().update(canvas_id, edit_token, html=html, title=title)


def delete(canvas_id: str, edit_token: str) -> dict:
    """Delete a canvas. Returns {ok}."""
    return _get().delete(canvas_id, edit_token)


def get(canvas_id: str) -> dict:
    """Get canvas metadata + HTML."""
    return _get().get(canvas_id)


def list_canvases(limit: int = 50, offset: int = 0) -> dict:
    """List canvases with pagination."""
    return _get().list(limit=limit, offset=offset)


def generate(context: str, title: Optional[str] = None, style: StyleType = "auto") -> dict:
    """Generate a canvas page from text via AI pipeline."""
    return _get().generate(context, title=title, style=style)


# ── CLI ───────────────────────────────────────────────────────────────────────

def main() -> None:
    parser = argparse.ArgumentParser(
        prog="canvas-new",
        description="canvas.new CLI — manage canvases from the command line",
    )
    sub = parser.add_subparsers(dest="cmd", metavar="command")

    # create
    p = sub.add_parser("create", help="Create a canvas from an HTML file or stdin")
    p.add_argument("file", nargs="?", help="HTML file path (or - for stdin)")
    p.add_argument("--title", default="")
    p.add_argument("--webhook-url", default="")
    p.add_argument("--base-url", default=os.environ.get("CANVAS_BASE_URL", "http://localhost:8080"))

    # update
    p = sub.add_parser("update", help="Update a canvas")
    p.add_argument("id")
    p.add_argument("edit_token")
    p.add_argument("file", nargs="?", help="HTML file path (or - for stdin)")
    p.add_argument("--title", default=None)
    p.add_argument("--base-url", default=os.environ.get("CANVAS_BASE_URL", "http://localhost:8080"))

    # delete
    p = sub.add_parser("delete", help="Delete a canvas")
    p.add_argument("id"); p.add_argument("edit_token")
    p.add_argument("--base-url", default=os.environ.get("CANVAS_BASE_URL", "http://localhost:8080"))

    # get
    p = sub.add_parser("get", help="Get canvas info")
    p.add_argument("id")
    p.add_argument("--base-url", default=os.environ.get("CANVAS_BASE_URL", "http://localhost:8080"))

    # list
    p = sub.add_parser("list", help="List canvases")
    p.add_argument("--limit", type=int, default=20)
    p.add_argument("--offset", type=int, default=0)
    p.add_argument("--base-url", default=os.environ.get("CANVAS_BASE_URL", "http://localhost:8080"))

    # generate
    p = sub.add_parser("generate", help="Generate a canvas from text via AI")
    p.add_argument("context", help="Text/data to convert into a page")
    p.add_argument("--title", default="")
    p.add_argument("--style", default="auto", choices=["auto","dashboard","report","tool","data","list","creative"])
    p.add_argument("--base-url", default=os.environ.get("CANVAS_BASE_URL", "http://localhost:8080"))

    args = parser.parse_args()
    if not args.cmd:
        parser.print_help()
        return

    client = CanvasNew(args.base_url)

    def read_html(file_arg: Optional[str]) -> str:
        import sys
        if not file_arg or file_arg == "-":
            return sys.stdin.read()
        with open(file_arg) as f:
            return f.read()

    try:
        if args.cmd == "create":
            html = read_html(getattr(args, 'file', None))
            r = client.create(html, title=args.title, webhook_url=args.webhook_url or None)
            print(f"✓ Created: {r['url']}")
            print(f"  ID:    {r['id']}")
            print(f"  Token: {r['edit_token']}")

        elif args.cmd == "update":
            html = read_html(getattr(args, 'file', None)) if getattr(args, 'file', None) else None
            r = client.update(args.id, args.edit_token, html=html, title=getattr(args,'title',None))
            print(f"✓ Updated at {r['updated_at']}")

        elif args.cmd == "delete":
            client.delete(args.id, args.edit_token)
            print(f"✓ Deleted {args.id}")

        elif args.cmd == "get":
            r = client.get(args.id)
            print(f"ID:         {r['id']}")
            print(f"Title:      {r.get('title','')}")
            print(f"Views:      {r['views']}")
            print(f"Created:    {r['created_at']}")
            print(f"Updated:    {r['updated_at']}")

        elif args.cmd == "list":
            r = client.list(limit=args.limit, offset=args.offset)
            for c in r['canvases']:
                print(f"  {c['id']}  {(c.get('title') or '(untitled)'):<40}  {str(c['views']).rjust(5)} views")
            print(f"\n  {len(r['canvases'])} of {r['total']} total")

        elif args.cmd == "generate":
            r = client.generate(args.context, title=args.title or None, style=args.style)
            print(f"✓ Generated: {r['url']}")
            print(f"  Title: {r['title']}")
            print(f"  ID:    {r['id']}")
            print(f"  Token: {r['edit_token']}")

    except RuntimeError as e:
        print(f"Error: {e}")
        raise SystemExit(1)


if __name__ == "__main__":
    main()

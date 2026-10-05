"""Capture PO/SO list and empty states from the disposable HTTP acceptance stack."""

from pathlib import Path
import subprocess
import tempfile

from http_acceptance import BASE, Browser


CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
OUTPUT = Path(__file__).resolve().parents[1] / 'docs/testing/screenshots'


def capture(browser, page, state, query):
    status, body, _ = browser.request(query)
    if status != 200:
        raise RuntimeError(f'{page} {state} returned HTTP {status}')
    if state == 'empty' and 'class="empty"' not in body:
        raise RuntimeError(f'{page} empty state is missing')
    body = body.replace('<head>', f'<head><base href="{BASE}">', 1)
    with tempfile.TemporaryDirectory(prefix='gudang-capture-') as directory:
        html_file = Path(directory) / 'page.html'
        html_file.write_text(body)
        for label, width in [('desktop', 1280), ('mobile-360', 360)]:
            target = OUTPUT / f'{page}-{state}-{label}.png'
            command = [
                CHROME, '--headless=new', '--no-sandbox', '--disable-gpu', '--disable-background-networking',
                '--hide-scrollbars', f'--user-data-dir={directory}/chrome-{label}',
                f'--window-size={width},900', f'--screenshot={target}', html_file.as_uri(),
            ]
            try:
                completed = subprocess.run(command, capture_output=True, text=True, timeout=10)
            except subprocess.TimeoutExpired:
                if not target.is_file():
                    raise RuntimeError(f'Chrome timed out without screenshot for {target}')
            else:
                if completed.returncode != 0 or not target.is_file():
                    raise RuntimeError(f'Chrome failed for {target}: {completed.stderr[-400:]}')
            print(target.relative_to(OUTPUT.parent.parent.parent))


def main():
    OUTPUT.mkdir(parents=True, exist_ok=True)
    browser = Browser()
    browser.login('Admin')
    for page in ['purchase', 'sales']:
        capture(browser, page, 'list', f'page={page}')
        capture(browser, page, 'empty', f'page={page}&search=NO_ORDER_999999')


if __name__ == '__main__':
    main()

"""HTTP checks against docker-compose.acceptance.yml only (disposable seed DB).

Run: python3 tests/http_acceptance.py
Never redirect this suite to the live app: it creates/deletes test users.
"""
import html
import http.cookiejar
import base64
import re
import unittest
import urllib.error
import urllib.parse
import urllib.request
import uuid

BASE = 'http://127.0.0.1:18081/'
ACCOUNTS = {
    'Admin': ('admin@example.test', 'Admin123!'),
    'Sales': ('member1@example.test', 'Member123!'),
    'WarehouseStaff': ('warehouse1@example.test', 'Member123!'),
}


class Browser:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies))
        self.body = ''

    def request(self, query, data=None):
        payload = None if data is None else urllib.parse.urlencode(data, doseq=True).encode()
        try:
            response = self.opener.open(BASE + '?' + query, payload, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        self.body = response.read().decode()
        return response.code, self.body, response.url

    def token(self):
        return html.unescape(re.search(r'name="csrf_token" value="([^"]+)"', self.body).group(1))

    def post(self, query, data):
        return self.request(query, dict(data, csrf_token=self.token()))

    def upload(self, query, data, filename, contents, content_type):
        boundary = 'acceptance-' + uuid.uuid4().hex
        parts = []
        for key, value in dict(data, csrf_token=self.token()).items():
            parts.append((f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n').encode())
        parts.append((f'--{boundary}\r\nContent-Disposition: form-data; name="image"; filename="{filename}"\r\nContent-Type: {content_type}\r\n\r\n').encode() + contents + b'\r\n')
        parts.append(f'--{boundary}--\r\n'.encode())
        request = urllib.request.Request(BASE + '?' + query, b''.join(parts), headers={'Content-Type': 'multipart/form-data; boundary=' + boundary})
        try:
            response = self.opener.open(request, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        self.body = response.read().decode()
        return response.code, self.body, response.url

    def login(self, role):
        self.request('page=login')
        email, password = ACCOUNTS[role]
        return self.post('page=login', {'email': email, 'password': password})


class AcceptanceTest(unittest.TestCase):
    def admin(self):
        browser = Browser()
        self.assertIn('page=dashboard', browser.login('Admin')[2])
        return browser

    def test_protected_page_and_failed_login(self):
        browser = Browser()
        self.assertIn('page=login', browser.request('page=users')[2])
        code, body, url = browser.post('page=login', {'email': ACCOUNTS['Admin'][0], 'password': 'incorrect'})
        self.assertEqual(code, 200)
        self.assertIn('Email atau password tidak valid.', body)
        self.assertIn('page=login', url)
        self.assertIn('page=login', browser.request('page=dashboard')[2])

    def test_three_role_login_session_rotation_and_logout(self):
        for role in ACCOUNTS:
            with self.subTest(role=role):
                browser = Browser()
                browser.request('page=login')
                before = next(c.value for c in browser.cookies if c.name == 'gudang_kita_session')
                email, password = ACCOUNTS[role]
                code, body, url = browser.post('page=login', {'email': email, 'password': password})
                self.assertEqual(code, 200)
                self.assertIn('page=dashboard', url)
                after = next(c.value for c in browser.cookies if c.name == 'gudang_kita_session')
                self.assertNotEqual(before, after)
                self.assertIn('page=login', browser.post('page=logout', {})[2])
                self.assertIn('page=login', browser.request('page=dashboard')[2])

    def test_role_page_matrix(self):
        expectations = {
            'page=users': [200, 403, 403],
            'page=masters&type=categories': [200, 403, 403],
            'page=masters&action=catalog': [403, 200, 200],
            'page=masters&type=products&action=create': [200, 403, 403],
            'page=purchase': [200, 403, 200],
            'page=stock&action=view&id=1': [200, 403, 200],
            'page=movements': [200, 403, 200],
            'page=sales&action=create': [200, 200, 403],
        }
        for index, role in enumerate(ACCOUNTS):
            browser = Browser()
            browser.login(role)
            for query, codes in expectations.items():
                with self.subTest(role=role, query=query):
                    self.assertEqual(browser.request(query)[0], codes[index])

    def test_invalid_csrf_and_missing_resource(self):
        browser = self.admin()
        self.assertEqual(browser.request('page=users&action=store', {'csrf_token': 'bad'})[0], 403)
        for query in ['page=unknown', 'page=users&action=edit&id=999999', 'page=stock&action=view&id=999999', 'page=sales&action=view&id=999999']:
            self.assertEqual(browser.request(query)[0], 404)

    def test_duplicate_email_preserves_form(self):
        browser = self.admin()
        browser.request('page=users&action=create')
        code, body, _ = browser.post('page=users&action=store', {
            'username': 'check_' + uuid.uuid4().hex[:8], 'name': 'Nama Tetap Tersimpan',
            'email': 'admin@example.test', 'role': 'Sales', 'is_active': '1', 'password': 'Example123!',
        })
        self.assertEqual(code, 200)
        self.assertIn('Email sudah digunakan.', body)
        self.assertIn('value="Nama Tetap Tersimpan"', body)
        self.assertNotIn('SQLSTATE', body)
        self.assertNotIn('Example123!', body)

    def test_user_crud_and_inactive_login(self):
        browser = self.admin()
        username = 'check_' + uuid.uuid4().hex[:8]
        data = {'username': username, 'name': 'Acceptance User', 'email': username + '@example.test',
                'role': 'Sales', 'is_active': '0', 'password': 'Example123!'}
        browser.request('page=users&action=create')
        code, body, _ = browser.post('page=users&action=store', data)
        self.assertEqual(code, 200)
        self.assertIn('User berhasil dibuat.', body)
        row = next(row for row in re.findall(r'<tr>(.*?)</tr>', body, re.S) if username in row)
        user_id = re.search(r'action=edit(?:&amp;|&)id=(\d+)', row).group(1)
        try:
            inactive = Browser()
            inactive.request('page=login')
            self.assertIn('Email atau password tidak valid.', inactive.post('page=login', {'email': data['email'], 'password': data['password']})[1])
            browser.request('page=users&action=edit&id=' + user_id)
            data.update(name='Nama Berubah', is_active='1', password='')
            self.assertIn('User berhasil diperbarui.', browser.post('page=users&action=update&id=' + user_id, data)[1])
            self.assertIn('value="Nama Berubah"', browser.request('page=users&action=edit&id=' + user_id)[1])
            # Duplicate email on update must retain edits and leave the saved identity intact.
            data.update(email='admin@example.test', name='Nama Belum Disimpan')
            body = browser.post('page=users&action=update&id=' + user_id, data)[1]
            self.assertIn('Email sudah digunakan.', body)
            self.assertIn('value="Nama Belum Disimpan"', body)
            self.assertIn('value="Nama Berubah"', browser.request('page=users&action=edit&id=' + user_id)[1])
        finally:
            browser.request('page=users')
            browser.post('page=users&action=delete&id=' + user_id, {})
        self.assertEqual(browser.request('page=users&action=edit&id=' + user_id)[0], 404)

    def test_product_search_sort_pagination_and_empty(self):
        for role in ACCOUNTS:
            browser = Browser()
            browser.login(role)
            base = 'page=masters&type=products' if role == 'Admin' else 'page=masters&action=catalog'
            first = browser.request(base + '&sort=code_asc')[1]
            second = browser.request(base + '&sort=code_asc&current_page=2')[1]
            codes = lambda body: re.findall(r'<td>\s*(PRD\d+)\s*</td>', body)
            with self.subTest(role=role):
                self.assertEqual(len(codes(first)), 10)
                self.assertEqual(len(codes(second)), 10)
                self.assertFalse(set(codes(first)) & set(codes(second)))
                self.assertEqual(codes(first), sorted(codes(first)))
                self.assertIn('sort=code_asc', first)
                self.assertEqual(codes(browser.request(base + '&search=PRD001')[1]), ['PRD001'])
                empty = browser.request(base + '&search=NO_SUCH_PRODUCT_999')[1]
                self.assertIn('class="empty"', empty)
                self.assertEqual(codes(empty), [])
                descending = codes(browser.request(base + '&sort=code_desc')[1])
                self.assertEqual(descending, sorted(descending, reverse=True))

    def test_order_search_filter_sort_pagination_and_empty(self):
        for role, page, prefix, allowed in [
            ('Admin', 'purchase', 'PO', True), ('WarehouseStaff', 'purchase', 'PO', True),
            ('Admin', 'sales', 'SO', True), ('Sales', 'sales', 'SO', True),
            ('WarehouseStaff', 'sales', 'SO', True), ('Sales', 'purchase', 'PO', False),
        ]:
            with self.subTest(role=role, page=page):
                browser = Browser()
                browser.login(role)
                if not allowed:
                    self.assertEqual(browser.request('page=' + page)[0], 403)
                    continue
                base = 'page=' + page
                first = browser.request(base + '&sort=date_asc')[1]
                numbers = lambda body: re.findall(r'>' + prefix + r'-SEED-\d{3}</a>', body)
                expected = 6 if role == 'Sales' else (13 if page == 'purchase' else 12)
                self.assertEqual(len(numbers(first)), 10 if expected > 10 else expected)
                if expected > 10:
                    second = browser.request(base + '&sort=date_asc&current_page=2')[1]
                    self.assertEqual(len(numbers(second)), expected - 10)
                    self.assertFalse(set(numbers(first)) & set(numbers(second)))
                    self.assertIn('sort=date_asc', second)
                filtered = browser.request(base + '&search=SEED&status=Draft&sort=date_asc')[1]
                self.assertIn('name="search" value="SEED"', filtered)
                self.assertIn('value="Draft" selected', filtered)
                self.assertIn('value="date_asc" selected', filtered)
                self.assertIn('>Draft</span>', filtered)
                self.assertEqual(len(numbers(browser.request(base + '&search=' + prefix + '-SEED-001')[1])), 1 if role != 'Sales' or page == 'purchase' else 0)
                empty = browser.request(base + '&search=NO_ORDER_999999')[1]
                self.assertIn('class="empty"', empty)
                self.assertEqual(numbers(empty), [])

    def test_product_image_upload_validation(self):
        browser = self.admin()
        data = {'type': 'products', 'name': 'Upload ' + uuid.uuid4().hex[:8],
                'category_id': '1', 'unit': 'pcs', 'purchase_price': '100',
                'selling_price': '150', 'minimum_stock': '3', 'status': 'Active'}
        browser.request('page=masters&type=products&action=create')
        invalid = browser.upload('page=masters&type=products&action=store', data, 'bad.txt', b'not an image', 'text/plain')[1]
        self.assertIn('Format gambar yang diterima hanya JPG, PNG, atau WebP.', invalid)
        self.assertIn('value="' + data['name'] + '"', invalid)
        browser.request('page=masters&type=products&action=create')
        oversized = browser.upload('page=masters&type=products&action=store', data, 'large.png', b'x' * (2 * 1024 * 1024 + 1), 'image/png')[1]
        self.assertIn('Ukuran gambar maksimal 2 MB.', oversized)
        browser.request('page=masters&type=products&action=create')
        png = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg==')
        code, body, _ = browser.upload('page=masters&type=products&action=store', data, 'valid.png', png, 'image/png')
        self.assertEqual(code, 200)
        self.assertIn('Data berhasil ditambahkan.', body)
        body = browser.request('page=masters&type=products&search=' + urllib.parse.quote(data['name']))[1]
        row = next(row for row in re.findall(r'<tr>(.*?)</tr>', body, re.S) if data['name'] in row)
        product_id = re.search(r'action=edit(?:&amp;|&)id=(\d+)', row).group(1)
        try:
            edit = browser.request('page=masters&type=products&action=edit&id=' + product_id)[1]
            self.assertRegex(edit, r'uploads/products/[a-f0-9]{32}\.png')
        finally:
            browser.request('page=masters&type=products&search=' + urllib.parse.quote(data['name']))
            browser.post('page=masters&type=products&action=delete&id=' + product_id, {'type': 'products'})

    def test_product_master_crud_validation_and_deactivation(self):
        browser = self.admin()
        product_name = 'Acceptance ' + uuid.uuid4().hex[:8]
        browser.request('page=masters&type=products&action=create')
        data = {'type': 'products', 'name': product_name, 'category_id': '1', 'unit': 'pcs',
                'purchase_price': '100', 'selling_price': '150', 'minimum_stock': '3', 'status': 'Active'}
        invalid = dict(data, purchase_price='-1')
        code, body, _ = browser.post('page=masters&type=products&action=store', invalid)
        self.assertEqual(code, 200)
        self.assertIn('Nilai tidak boleh negatif.', body)
        self.assertIn('value="' + product_name + '"', body)
        self.assertNotIn('SQLSTATE', body)

        browser.request('page=masters&type=products&action=create')
        code, body, _ = browser.post('page=masters&type=products&action=store', data)
        self.assertEqual(code, 200)
        self.assertIn('Data berhasil ditambahkan.', body)
        search = urllib.parse.quote(product_name)
        body = browser.request('page=masters&type=products&search=' + search)[1]
        row = next(row for row in re.findall(r'<tr>(.*?)</tr>', body, re.S) if product_name in row)
        product_id = re.search(r'action=edit(?:&amp;|&)id=(\d+)', row).group(1)
        try:
            edit = dict(data, name=product_name + ' Updated', selling_price='175')
            browser.request('page=masters&type=products&action=edit&id=' + product_id)
            self.assertIn('Data berhasil diperbarui.', browser.post('page=masters&type=products&action=update&id=' + product_id, edit)[1])
            body = browser.request('page=masters&type=products&search=' + search)[1]
            self.assertIn(product_name + ' Updated', body)
            browser.request('page=masters&type=products&search=' + search)
            self.assertIn('Data berhasil dinonaktifkan.', browser.post('page=masters&type=products&action=deactivate&id=' + product_id, {'type': 'products'})[1])
            sales = Browser()
            sales.login('Sales')
            catalog = sales.request('page=masters&action=catalog&search=' + search)[1]
            self.assertIn('Belum ada produk aktif dalam katalog.', catalog)
            self.assertNotIn('<td>' + product_name + ' Updated</td>', catalog)
        finally:
            browser.request('page=masters&type=products&search=' + search)
            browser.post('page=masters&type=products&action=delete&id=' + product_id, {'type': 'products'})
        self.assertEqual(browser.request('page=masters&type=products&action=edit&id=' + product_id)[0], 404)

    def test_invalid_order_keeps_multiple_lines_and_escaped_notes(self):
        browser = self.admin()
        for page, party, date in [('purchase', 'supplier_id', 'po_date'), ('sales', 'customer_id', 'so_date')]:
            with self.subTest(page=page):
                browser.request('page=' + page + '&action=create')
                code, body, _ = browser.post('page=' + page + '&action=store', {
                    party: '1', 'warehouse_id': '1', date: '2026-10-05', 'notes': '<script>kept</script>',
                    'product_id[]': ['1', '2'], 'qty[]': ['3', '-1'], 'price[]': ['12500', '25000'],
                })
                self.assertEqual(code, 422)
                self.assertIn('&lt;script&gt;kept&lt;/script&gt;', body)
                self.assertNotIn('<script>kept</script>', body)
                self.assertEqual(body.count('name="qty[]"'), 2)
                self.assertIn('value="12500"', body)
                self.assertIn('value="-1"', body)
                self.assertIn('value="2" data-price=', body)


if __name__ == '__main__':
    unittest.main(verbosity=2)

"""Check form control names across the disposable acceptance app's main pages."""

from html.parser import HTMLParser

from http_acceptance import Browser


PAGES = {
    'Admin': [
        'page=dashboard', 'page=users', 'page=users&action=create',
        'page=masters&type=categories', 'page=masters&type=products',
        'page=masters&type=products&action=create', 'page=masters&type=products&action=edit&id=1',
        'page=masters&type=warehouses', 'page=masters&type=customers',
        'page=masters&type=suppliers', 'page=stock',
        'page=stock&action=view&id=1', 'page=movements', 'page=purchase',
        'page=purchase&action=create', 'page=purchase&action=view&id=1',
        'page=sales', 'page=sales&action=create', 'page=sales&action=view&id=1',
        'page=reports', 'page=projects', 'page=projects&action=create',
        'page=projects&action=edit&id=1', 'page=tasks', 'page=tasks&action=create',
        'page=tasks&action=edit&id=1',
    ],
    'Sales': ['page=dashboard', 'page=masters&action=catalog', 'page=sales',
              'page=sales&action=create', 'page=projects', 'page=tasks'],
    'WarehouseStaff': ['page=dashboard', 'page=masters&action=catalog',
                       'page=purchase', 'page=purchase&action=create',
                       'page=sales', 'page=stock', 'page=movements'],
}


class LabelParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.label_depth = 0
        self.label_fors = set()
        self.controls = []

    def handle_starttag(self, tag, attributes):
        attrs = dict(attributes)
        if tag == 'label':
            self.label_depth += 1
            if attrs.get('for'):
                self.label_fors.add(attrs['for'])
        if tag in ('input', 'select', 'textarea') and attrs.get('type') not in ('hidden', 'submit', 'button'):
            self.controls.append((tag, attrs.get('name'), attrs.get('id'),
                                  bool(attrs.get('aria-label') or attrs.get('aria-labelledby') or self.label_depth)))

    def handle_endtag(self, tag):
        if tag == 'label':
            self.label_depth -= 1

    def unnamed(self):
        return [(tag, name, control_id) for tag, name, control_id, named in self.controls
                if not named and control_id not in self.label_fors]


def main():
    checked = 0
    problems = []
    for role, pages in PAGES.items():
        browser = Browser()
        browser.login(role)
        for query in pages:
            code, body, _ = browser.request(query)
            if code != 200:
                problems.append((role, query, f'HTTP {code}'))
                continue
            parser = LabelParser()
            parser.feed(body)
            checked += len(parser.controls)
            for item in parser.unnamed():
                problems.append((role, query, item))
    print(f'Checked {checked} form controls across {sum(map(len, PAGES.values()))} role/page combinations.')
    for problem in problems:
        print('UNNAMED/ERROR', *problem)
    if problems:
        raise SystemExit(1)
    print('PASS: all checked controls have a label or accessible name.')


if __name__ == '__main__':
    main()

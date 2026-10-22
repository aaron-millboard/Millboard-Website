<?php

namespace Theme\Summit;

/**
 * Summit > Form status: who has and has not completed the pre-arrival form.
 *
 * A live dashboard for whoever is chasing delegates. The page is a shell; the
 * numbers come from an admin-ajax call that the page repeats every few minutes
 * and on demand, so it never needs a reload and nothing is cached.
 *
 * The form posts to HubSpot with whatever address the delegate types, which is
 * often not the one they registered with. A registration counts as done when a
 * submission matches it by email (only where that address belongs to one person
 * alone) or by name: same surname and a first name that is the same or the start
 * of the other (Tom and Thomas). Shared addresses are therefore judged person
 * by person, not as one.
 *
 * Wooddeck guests have no registration to compare against, so their submissions
 * are listed as they arrive instead.
 */
class FormStatus
{
    private const SLUG = 'millboard-summit-form-status';
    private const ACTION = 'mb_summit_form_status';

    /** HubSpot form GUID => the group it belongs to. Public by design (they sit in every embed). */
    private const FORMS = [
        '1911dc63-beb6-4f9c-8c65-b98a83c2508e' => 'UK',
        '5878820f-a864-4c40-b31e-dd82d135538f' => 'INT',
        'f543de7c-a569-439c-9fb1-9a865083be35' => 'FR',
        'ee6acdad-7792-4552-b4ae-f90740753321' => 'US',
        'b79f67f8-7f46-4366-b52e-99160a1aa535' => 'WD',
    ];

    private const LABELS = [
        'UK' => 'UK',
        'INT' => 'International',
        'FR' => 'France',
        'US' => 'US',
        'WD' => 'Wooddeck guests',
    ];

    /** Staff test addresses that are not delegates. Anything @example.org is skipped too. */
    private const TEST_EMAILS = ['michelle.baird@millboard.com', 'mrsmichellebaird@icloud.com'];

    public static function init(): void
    {
        \add_action('admin_menu', [self::class, 'add_page']);
        \add_action('wp_ajax_' . self::ACTION, [self::class, 'ajax']);
    }

    public static function add_page(): void
    {
        \add_submenu_page(Admin::MENU, 'Summit form status', 'Form status',
            'manage_options', self::SLUG, [self::class, 'render']);
    }

    // ------------------------------------------------------------------- data

    /** Short forms a prefix match cannot see (Tom is not the start of Thomas). */
    private const NICKNAMES = ['tom' => 'thomas', 'mike' => 'michael', 'jim' => 'james', 'bill' => 'william', 'bob' => 'robert', 'liz' => 'elizabeth', 'kate' => 'katherine', 'dave' => 'david'];

    private static function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(\remove_accents($s)));
    }

    private static function same_person(string $first_a, string $last_a, string $first_b, string $last_b): bool
    {
        [$fa, $la, $fb, $lb] = [self::norm($first_a), self::norm($last_a), self::norm($first_b), self::norm($last_b)];

        $fa = self::NICKNAMES[$fa] ?? $fa;
        $fb = self::NICKNAMES[$fb] ?? $fb;

        if ($la === '' || $la !== $lb || $fa === '' || $fb === '') {
            return false;
        }

        return $fa === $fb || (min(strlen($fa), strlen($fb)) >= 3 && (strpos($fa, $fb) === 0 || strpos($fb, $fa) === 0));
    }

    /**
     * @return array{generated:string,counts:array,rows:array,wooddeck:array}
     */
    public static function data(): array
    {
        $subs = [];

        foreach (ReviewLog::submissions() as $row) {
            $email = strtolower(trim((string) $row['email']));

            if ($email === '' || strpos($email, '@example.org') !== false || in_array($email, self::TEST_EMAILS, true)) {
                continue;
            }

            $fields = [];
            foreach ((array) json_decode((string) $row['payload'], true) as $f) {
                if (isset($f['name'])) {
                    $fields[$f['name']] = (string) ($f['value'] ?? '');
                }
            }

            // Later submissions replace earlier ones from the same person.
            $subs[$email . '|' . self::norm(($fields['firstname'] ?? '') . ($fields['lastname'] ?? ''))] = [
                'email' => $email,
                'first' => $fields['firstname'] ?? '',
                'last' => $fields['lastname'] ?? '',
                'company' => $fields['company'] ?? '',
                'at' => (string) $row['created_at'],
                'group' => self::FORMS[$row['form_guid']] ?? '',
            ];
        }

        $regs = array_values(array_filter(Registrations::list_rows(), static function ($r) {
            return $r['status'] === 'Registered' && isset(self::LABELS[$r['audience']]);
        }));

        $per_email = [];
        foreach ($regs as $r) {
            $key = strtolower(trim($r['email']));
            $per_email[$key] = ($per_email[$key] ?? 0) + 1;
        }

        $rows = [];
        $counts = [];

        foreach ($regs as $r) {
            $email = strtolower(trim($r['email']));
            $hit = null;

            foreach ($subs as $s) {
                $by_name = self::same_person($r['first_name'], $r['last_name'], $s['first'], $s['last']);
                $by_email = $s['email'] === $email && $per_email[$email] === 1;

                if ($by_name || $by_email) {
                    $hit = $s;
                    break;
                }
            }

            $g = $r['audience'];
            $counts[$g] = $counts[$g] ?? ['total' => 0, 'done' => 0];
            $counts[$g]['total']++;
            $counts[$g]['done'] += $hit ? 1 : 0;

            $rows[] = [
                'group' => $g,
                'company' => $r['company'],
                'name' => trim($r['first_name'] . ' ' . $r['last_name']),
                'email' => $r['email'],
                'days' => implode(', ', array_map(static function ($d) {
                    return str_replace(' November', '', $d);
                }, (array) $r['days'])),
                'done' => (bool) $hit,
                'at' => $hit ? $hit['at'] : '',
                'via' => ($hit && $hit['email'] !== $email) ? $hit['email'] : '',
            ];
        }

        $wooddeck = [];
        foreach ($subs as $s) {
            if ($s['group'] === 'WD') {
                $wooddeck[] = ['name' => trim($s['first'] . ' ' . $s['last']), 'company' => $s['company'], 'email' => $s['email'], 'at' => $s['at']];
            }
        }

        return [
            'generated' => \gmdate('Y-m-d H:i:s'),
            'counts' => $counts,
            'rows' => $rows,
            'wooddeck' => $wooddeck,
        ];
    }

    public static function ajax(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error('Not allowed', 403);
        }

        \check_ajax_referer(self::ACTION, 'nonce');
        \wp_send_json_success(self::data());
    }

    // ------------------------------------------------------------------- page

    public static function render(): void
    {
        if (!\current_user_can('manage_options')) {
            return;
        }

        $config = \wp_json_encode([
            'url' => \admin_url('admin-ajax.php'),
            'action' => self::ACTION,
            'nonce' => \wp_create_nonce(self::ACTION),
            'labels' => self::LABELS,
            'every' => 180,
        ]);
        ?>
        <div class="wrap" id="mb-fs">
            <h1>Summit form status</h1>
            <p class="description">Who has completed the pre-arrival form (NDA, health and safety, dietary, photography, competition law and data). Matched on email or name, because people often use a different address on the form.</p>

            <div id="mb-fs-tiles" style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0"></div>

            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
                <select id="mb-fs-group" aria-label="Group"></select>
                <select id="mb-fs-status" aria-label="Status">
                    <option value="out">Outstanding</option>
                    <option value="done">Completed</option>
                    <option value="all">Everyone</option>
                </select>
                <select id="mb-fs-day" aria-label="Day">
                    <option value="">Any day</option>
                    <option value="3rd">3rd Nov</option>
                    <option value="4th">4th Nov</option>
                    <option value="5th">5th Nov</option>
                </select>
                <input type="search" id="mb-fs-q" placeholder="Search name, company or email" style="min-width:260px">
                <button type="button" class="button button-primary" id="mb-fs-refresh">Refresh now</button>
                <button type="button" class="button" id="mb-fs-copy">Copy emails</button>
                <button type="button" class="button" id="mb-fs-csv">Download CSV</button>
                <span id="mb-fs-stamp" class="description" role="status" aria-live="polite"></span>
            </div>

            <p id="mb-fs-summary" style="font-weight:600"></p>
            <table class="widefat striped" id="mb-fs-table">
                <thead><tr><th>Group</th><th>Company</th><th>Name</th><th>Email</th><th>Days</th><th>Status</th><th>Submitted (UTC)</th></tr></thead>
                <tbody></tbody>
            </table>

            <h2 style="margin-top:32px">Wooddeck guests who have completed it</h2>
            <p class="description">Wooddeck guests are not registered, so there is nothing to compare against. This lists each one as they submit.</p>
            <table class="widefat striped" id="mb-fs-wd" style="max-width:900px">
                <thead><tr><th>Name</th><th>Company</th><th>Email</th><th>Submitted (UTC)</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <script>
        (function () {
            var C = <?= $config; ?>;
            var $ = function (id) { return document.getElementById(id); };
            var data = null;
            var order = ['UK', 'INT', 'FR', 'US'];

            // Every value is user-typed text, so it only ever goes in through textContent.
            function cell(tr, text, title) {
                var td = document.createElement('td');
                td.textContent = text;
                if (title) { td.title = title; }
                tr.appendChild(td);
                return td;
            }

            function view() {
                var g = $('mb-fs-group').value, s = $('mb-fs-status').value, d = $('mb-fs-day').value;
                var q = $('mb-fs-q').value.trim().toLowerCase();
                return data.rows.filter(function (r) {
                    if (g && r.group !== g) { return false; }
                    if (s === 'out' && r.done) { return false; }
                    if (s === 'done' && !r.done) { return false; }
                    if (d && r.days.indexOf(d) === -1) { return false; }
                    if (q && (r.company + ' ' + r.name + ' ' + r.email).toLowerCase().indexOf(q) === -1) { return false; }
                    return true;
                });
            }

            function unique(rows) {
                var seen = {}, out = [];
                rows.forEach(function (r) {
                    var e = r.email.toLowerCase();
                    if (!seen[e]) { seen[e] = 1; out.push(r.email); }
                });
                return out;
            }

            function tiles() {
                var box = $('mb-fs-tiles');
                box.textContent = '';
                order.forEach(function (g) {
                    var c = data.counts[g] || { total: 0, done: 0 };
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'button';
                    b.style.cssText = 'text-align:left;padding:8px 14px;height:auto;min-width:150px';
                    var big = document.createElement('div');
                    big.style.cssText = 'font-size:22px;font-weight:600;line-height:1.2';
                    big.textContent = (c.total - c.done) + ' to do';
                    var small = document.createElement('div');
                    small.textContent = C.labels[g] + ': ' + c.done + ' of ' + c.total + ' done';
                    b.appendChild(big);
                    b.appendChild(small);
                    b.addEventListener('click', function () {
                        $('mb-fs-group').value = g;
                        $('mb-fs-status').value = 'out';
                        draw();
                    });
                    box.appendChild(b);
                });
            }

            function draw() {
                if (!data) { return; }
                var rows = view(), body = $('mb-fs-table').tBodies[0];
                body.textContent = '';
                rows.forEach(function (r) {
                    var tr = document.createElement('tr');
                    cell(tr, C.labels[r.group]);
                    cell(tr, r.company);
                    cell(tr, r.name);
                    cell(tr, r.email);
                    cell(tr, r.days);
                    var st = cell(tr, r.done ? 'Completed' : 'Outstanding', r.via ? 'Submitted from ' + r.via : '');
                    st.style.fontWeight = '600';
                    st.style.color = r.done ? '#1a7f37' : '#b32d2e';
                    cell(tr, r.at);
                    body.appendChild(tr);
                });
                $('mb-fs-summary').textContent = rows.length + ' people, ' + unique(rows).length + ' email addresses';

                var wb = $('mb-fs-wd').tBodies[0];
                wb.textContent = '';
                if (!data.wooddeck.length) {
                    var none = document.createElement('tr');
                    cell(none, 'Nobody yet').colSpan = 4;
                    wb.appendChild(none);
                }
                data.wooddeck.forEach(function (w) {
                    var tr = document.createElement('tr');
                    cell(tr, w.name);
                    cell(tr, w.company);
                    cell(tr, w.email);
                    cell(tr, w.at);
                    wb.appendChild(tr);
                });
            }

            function stamp(text) { $('mb-fs-stamp').textContent = text; }

            function load() {
                stamp('Refreshing…');
                var body = new URLSearchParams({ action: C.action, nonce: C.nonce });
                return fetch(C.url, { method: 'POST', credentials: 'same-origin', body: body })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (!j.success) { throw new Error('refused'); }
                        data = j.data;
                        tiles();
                        draw();
                        stamp('Updated ' + data.generated.slice(11) + ' UTC. Refreshes itself every ' + (C.every / 60) + ' minutes.');
                    })
                    .catch(function () { stamp('Could not refresh. Reload the page, or check you are still logged in.'); });
            }

            function csvCell(v) { return '"' + String(v).replace(/"/g, '""') + '"'; }

            var groupSel = $('mb-fs-group');
            [['', 'All groups']].concat(order.map(function (g) { return [g, C.labels[g]]; })).forEach(function (o) {
                var opt = document.createElement('option');
                opt.value = o[0];
                opt.textContent = o[1];
                groupSel.appendChild(opt);
            });

            ['mb-fs-group', 'mb-fs-status', 'mb-fs-day'].forEach(function (id) { $(id).addEventListener('change', draw); });
            $('mb-fs-q').addEventListener('input', draw);
            $('mb-fs-refresh').addEventListener('click', load);

            $('mb-fs-copy').addEventListener('click', function () {
                if (!data) { return; }
                var list = unique(view()), text = list.join(', ');
                var copied = navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject();
                copied.then(function () {
                    stamp('Copied ' + list.length + ' email addresses.');
                }, function () { window.prompt('Copy these addresses:', text); });
            });

            $('mb-fs-csv').addEventListener('click', function () {
                if (!data) { return; }
                var lines = [['Group', 'Company', 'Name', 'Email', 'Days', 'Status', 'Submitted (UTC)'].map(csvCell).join(',')];
                view().forEach(function (r) {
                    lines.push([C.labels[r.group], r.company, r.name, r.email, r.days, r.done ? 'Completed' : 'Outstanding', r.at].map(csvCell).join(','));
                });
                var a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' }));
                a.download = 'summit-form-status.csv';
                a.click();
            });

            load();
            setInterval(function () { if (!document.hidden) { load(); } }, C.every * 1000);
            document.addEventListener('visibilitychange', function () { if (!document.hidden) { load(); } });
        })();
        </script>
        <?php
    }
}

(function ($) {
    'use strict';
    var aiwbDashboardRequestInFlight = false;

    function renderList(items, formatter) {
        if (!items || !items.length) {
            return '<p>No items found.</p>';
        }
        var html = '<ul class="aiwb-simple-list">';
        items.forEach(function (item) {
            html += '<li>' + formatter(item) + '</li>';
        });
        html += '</ul>';
        return html;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function sanitizeHtml(html) {
        if (window.DOMPurify && typeof window.DOMPurify.sanitize === 'function') {
            return window.DOMPurify.sanitize(html);
        }
        var allowedTags = {
            div: true, span: true, p: true, small: true, strong: true, em: true, b: true, i: true, u: true,
            ul: true, ol: true, li: true, h1: true, h2: true, h3: true, h4: true, h5: true, h6: true,
            table: true, thead: true, tbody: true, tr: true, td: true, th: true, a: true, button: true,
            img: true, label: true, input: true, textarea: true
        };
        var allowedAttrs = {
            class: true, href: true, title: true, target: true, rel: true, style: true, role: true,
            src: true, alt: true, width: true, height: true, loading: true, decoding: true, srcset: true, sizes: true,
            type: true, name: true, value: true, checked: true, disabled: true,
            id: true, rows: true, cols: true, placeholder: true, for: true
        };
        var parser = new DOMParser();
        var doc = parser.parseFromString('<div>' + String(html || '') + '</div>', 'text/html');
        var root = doc.body;
        var wrapper = root.firstElementChild;
        var walk = function (node) {
            if (!node || !node.childNodes) {
                return;
            }
            var children = Array.prototype.slice.call(node.childNodes);
            children.forEach(function (child) {
                if (child.nodeType === 1) {
                    var tag = child.tagName.toLowerCase();
                    if (!allowedTags[tag]) {
                        var text = doc.createTextNode(child.textContent || '');
                        node.replaceChild(text, child);
                        return;
                    }
                    Array.prototype.slice.call(child.attributes || []).forEach(function (attr) {
                        var name = attr.name.toLowerCase();
                        var value = attr.value || '';
                        if (name.indexOf('on') === 0) {
                            child.removeAttribute(attr.name);
                            return;
                        }
                        if (!allowedAttrs[name] && name.indexOf('data-') !== 0 && name.indexOf('aria-') !== 0) {
                            child.removeAttribute(attr.name);
                            return;
                        }
                        if (name === 'href' && /^\s*javascript:/i.test(value)) {
                            child.removeAttribute(attr.name);
                        }
                        if (name === 'src' && /^\s*(javascript:|data:)/i.test(value)) {
                            child.removeAttribute(attr.name);
                        }
                        if (name === 'style' && /url\s*\(|expression\s*\(/i.test(value)) {
                            child.removeAttribute(attr.name);
                        }
                    });
                    walk(child);
                }
            });
        };
        walk(wrapper || root);
        return wrapper ? wrapper.innerHTML : root.innerHTML;
    }

    function safeSetHtml($el, html) {
        $el.html(sanitizeHtml(html));
        return $el;
    }

    function safeNavigate(url, allowedPrefix) {
        if (!url) {
            return;
        }
        if (allowedPrefix && url.indexOf(allowedPrefix) === 0) {
            window.location.href = url;
            return;
        }
        if (!allowedPrefix && url.indexOf('admin.php?page=') === 0) {
            window.location.href = url;
            return;
        }
        showToast('Blocked unsafe redirect.', 'error');
    }

    function buildDiffHtml(original, updated) {
        var origLines = String(original || '').split(/\r?\n/);
        var updLines = String(updated || '').split(/\r?\n/);
        var origSet = {};
        origLines.forEach(function (line) {
            var trimmed = line.trim();
            if (trimmed) {
                origSet[trimmed] = true;
            }
        });
        var updSet = {};
        updLines.forEach(function (line) {
            var trimmed = line.trim();
            if (trimmed) {
                updSet[trimmed] = true;
            }
        });

        var updatedHtml = updLines.map(function (line) {
            var trimmed = line.trim();
            if (trimmed && !origSet[trimmed]) {
                return '<span class="aiwb-diff-add">' + escapeHtml(line) + '</span>';
            }
            return escapeHtml(line);
        }).join('\n');

        var originalHtml = origLines.map(function (line) {
            var trimmed = line.trim();
            if (trimmed && !updSet[trimmed]) {
                return '<span class="aiwb-diff-remove">' + escapeHtml(line) + '</span>';
            }
            return escapeHtml(line);
        }).join('\n');

        return { original: originalHtml, updated: updatedHtml };
    }

    function setHealthResult(html) {
        if ($('#aiwb-health-result').length) {
            var $target = $('#aiwb-health-result');
            safeSetHtml($target, html);
            $target.addClass('aiwb-health-result');
        }
    }

    function setInlineStatus(message, type) {
        var $status = $('#aiwb-action-status');
        if (!$status.length) {
            return;
        }
        return;
    }

    function showToast(message, type) {
        var $toast = $('#aiwb-toast');
        if (!$toast.length) {
            return;
        }
        $toast.removeClass('aiwb-toast--success aiwb-toast--error');
        if (type === 'error') {
            $toast.addClass('aiwb-toast--error');
        } else {
            $toast.addClass('aiwb-toast--success');
        }
        $toast.text(message).fadeIn(200);
        clearTimeout($toast.data('timer'));
        var timer = setTimeout(function () {
            $toast.fadeOut(200);
        }, 3500);
        $toast.data('timer', timer);
    }

    function apiPost(action, payload, successCb, errorCb, options) {
        successCb = typeof successCb === 'function' ? successCb : function () {};
        errorCb = typeof errorCb === 'function' ? errorCb : function () {};
        options = options || {};
        var appData = window.aiwbData || {};
        if (!appData.ajaxUrl || !appData.nonce) {
            errorCb('Session data missing. Reload the page and try again.');
            return;
        }
        var data = $.extend({
            action: action,
            nonce: appData.nonce
        }, payload || {});
        $.ajax({
            url: appData.ajaxUrl,
            method: 'POST',
            data: data,
            dataType: 'json',
            timeout: parseInt(options.timeout || 20000, 10)
        })
            .done(function (response) {
                if (response.success) {
                    successCb(response.data);
                } else {
                    errorCb(response.data && response.data.message ? response.data.message : 'Request failed.');
                }
            })
            .fail(function (xhr, statusText, errorThrown) {
                if (xhr && xhr.responseText && String(xhr.responseText).trim() === '-1') {
                    errorCb('Security check failed. Please refresh the page and try again.');
                    return;
                }
                var detail = statusText || 'network error';
                if (errorThrown) {
                    detail = detail + ' (' + errorThrown + ')';
                }
                errorCb('Unable to reach server, please try again. [' + detail + ']');
            });
    }

    function restPost(endpoint, payload, successCb, errorCb) {
        successCb = typeof successCb === 'function' ? successCb : function () {};
        errorCb = typeof errorCb === 'function' ? errorCb : function () {};
        var appData = window.aiwbData || {};
        if (!appData.restUrl || !appData.restNonce) {
            errorCb('Session data missing. Reload the page and try again.');
            return;
        }
        fetch(appData.restUrl + endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': appData.restNonce
            },
            body: JSON.stringify(payload || {})
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (data && data.content) {
                successCb(data);
            } else if (data && data.message) {
                errorCb(data.message);
            } else if (data && data.code && data.message) {
                errorCb(data.message);
            } else {
                successCb(data);
            }
        }).catch(function () {
            errorCb('Unable to reach REST API.');
        });
    }

    function restPostWithFallback(endpoint, payload, fallbackAction, successCb, errorCb) {
        restPost(endpoint, payload, successCb, function (message) {
            if (!fallbackAction) {
                errorCb(message);
                return;
            }
            apiPost(fallbackAction, payload, function (data) {
                successCb(data);
            }, function (ajaxMessage) {
                errorCb(message || ajaxMessage || 'Request failed.');
            });
        });
    }

    function formatGeneratedContent(content) {
        var text = String(content || '');
        if (!text) {
            return text;
        }
        // Add line breaks between HTML tags for readability inside textarea.
        text = text.replace(/></g, '>\n<');
        return text;
    }

    function renderMiniBars(container, counts, labels, accent, ranges) {
        if (!container || !counts || !counts.length) {
            return;
        }
        var total = counts.reduce(function (sum, val) { return sum + val; }, 0) || 1;
        var html = '';
        for (var i = 0; i < counts.length; i++) {
            var height = (counts[i] / total) * 100;
            if (counts[i] > 0 && height < 8) {
                height = 8;
            }
            var safeCount = escapeHtml(String(counts[i] || 0));
            html += '<div class="aiwb-mini-bar">';
            html += '<div class="aiwb-mini-value">' + safeCount + '</div>';
            html += '<span style="height:' + height + '%"></span>';
            var label = (ranges && ranges[i]) ? ranges[i] : (labels[i] || ('Week ' + (i + 1)));
            html += '<small class="aiwb-mini-label">' + escapeHtml(label) + '</small>';
            html += '</div>';
        }
        container.innerHTML = sanitizeHtml(html);
        if (accent) {
            container.classList.add('aiwb-mini-bars--accent');
        }
    }

    function renderSecuritySnapshot() {
        if (!document.getElementById('aiwb-security-summary')) {
            return;
        }
        try {
            if (!sessionStorage.getItem('aiwb_health_report_ready')) {
                safeSetHtml($('#aiwb-security-summary'), '<p class="aiwb-muted">Run Full Security Scan to generate a fresh report.</p>');
                return;
            }
        } catch (e) {}
        if (!aiwbData || !aiwbData.lastModuleReports) {
            return;
        }
        var map = {
            login: 'Login Security',
            firewall: 'Firewall',
            integrity: 'File Integrity',
            malware: 'Malware Scanner',
            hardening: 'Hardening',
            headers: 'Security Headers'
        };
        var html = '<div class="aiwb-module-grid">';
        var hasAny = false;
        Object.keys(map).forEach(function (key) {
            var report = aiwbData.lastModuleReports[key];
            if (!report || !report.security) {
                return;
            }
            hasAny = true;
            var score = report.security.score || 0;
            var pass = 0;
            var warn = 0;
            if (report.security.checks && report.security.checks.length) {
                report.security.checks.forEach(function (item) {
                    if (item.status === 'pass') { pass++; } else { warn++; }
                });
            }
            var total = Math.max(1, pass + warn);
            var passPct = Math.round((pass / total) * 100);
            html += '<div class="aiwb-module-card">';
            html += '<div class="aiwb-module-head"><strong>' + map[key] + '</strong><span class="aiwb-module-score">' + score + '%</span></div>';
            html += '<div class="aiwb-module-bar"><span style="width:' + passPct + '%"></span></div>';
            html += '<div class="aiwb-module-meta"><span>Passed ' + pass + '</span><span>Warnings ' + warn + '</span></div>';
            html += '</div>';
        });
        html += '</div>';
        if (hasAny) {
            safeSetHtml($('#aiwb-security-summary'), html);
        }
    }

    function loadDashboardData() {
        if (!$('#aiwb-stat-generated').length) {
            return;
        }
        if (aiwbDashboardRequestInFlight) {
            return;
        }
        aiwbDashboardRequestInFlight = true;
        apiPost('aiwb_dashboard_data', {}, function (data) {
            try {
            if (!data || !data.stats) {
                aiwbDashboardRequestInFlight = false;
                return;
            }
            $('#aiwb-stat-generated').text(data.stats.generated || 0);
            $('#aiwb-stat-updated').text(data.stats.updated || 0);
            $('#aiwb-stat-avg-seo').text((data.stats.avg_seo || 0) + '%');
            $('#aiwb-stat-health').text((data.stats.health || 0) + '%');
            $('#aiwb-progress-avg-seo').css('width', (data.stats.avg_seo || 0) + '%');
            $('#aiwb-progress-health').css('width', (data.stats.health || 0) + '%');
            $('#aiwb-kpi-updates').text(data.stats.updates_total || 0);
            $('#aiwb-kpi-total-updated').text(data.stats.updated || 0);
            $('#aiwb-kpi-avg-seo').text((data.stats.avg_seo || 0) + '%');

            var weeks = data.weeks || [];
            var ranges = data.week_ranges || [];
            renderMiniBars(document.getElementById('aiwb-generated-bars'), data.generated_counts || [], weeks, false, ranges);
            renderMiniBars(document.getElementById('aiwb-updated-bars'), data.update_counts || [], weeks, true, ranges);
            renderModuleStatus(data.modules || []);
            aiwbDashboardRequestInFlight = false;
            } catch (e) {
                safeSetHtml($('#aiwb-module-status'), '<div class="aiwb-module-placeholder">Unable to render module data.</div>');
                aiwbDashboardRequestInFlight = false;
            }
        }, function (message) {
            safeSetHtml($('#aiwb-module-status'), '<div class="aiwb-module-placeholder">Unable to load module data. ' + escapeHtml(message || 'Unknown error') + '</div>');
            aiwbDashboardRequestInFlight = false;
        });
    }

    function formatRelativeTime(dateString) {
        if (!dateString) {
            return 'No activity yet';
        }
        var safeDate = String(dateString || '').replace(' ', 'T');
        var d = new Date(safeDate);
        if (isNaN(d.getTime())) {
            return 'No activity yet';
        }
        var diffMs = Date.now() - d.getTime();
        if (diffMs < 0) {
            return 'Just now';
        }
        var diffMin = Math.floor(diffMs / 60000);
        if (diffMin < 1) {
            return 'Just now';
        }
        if (diffMin < 60) {
            return diffMin + ' min ago';
        }
        var diffHr = Math.floor(diffMin / 60);
        if (diffHr < 24) {
            return diffHr + ' hr ago';
        }
        var diffDay = Math.floor(diffHr / 24);
        return diffDay + ' day' + (diffDay > 1 ? 's' : '') + ' ago';
    }

    function renderModuleStatus(modules) {
        var $grid = $('#aiwb-module-status');
        if (!$grid.length) {
            return;
        }
        if (!modules || !modules.length) {
            safeSetHtml($grid, '<div class="aiwb-module-placeholder">No module data available.</div>');
            return;
        }
        var html = '';
        modules.forEach(function (module) {
            var state = module.state || 'neutral';
            var status = escapeHtml(module.status || 'Unknown');
            var title = escapeHtml(module.title || 'Module');
            var last = formatRelativeTime(module.last || '');
            var metric = module.metric !== undefined && module.metric !== null && module.metric !== '' ? escapeHtml(String(module.metric)) : '';
            var metricLabel = module.metric_label ? escapeHtml(module.metric_label) : '';
            html += '<div class="aiwb-module-card">';
            html += '<div class="aiwb-module-head">';
            html += '<div class="aiwb-module-title">' + title + '</div>';
            html += '<div class="aiwb-module-status aiwb-module-status--' + state + '">' + status + '</div>';
            html += '</div>';
            html += '<div class="aiwb-module-meta">' + escapeHtml(last) + '</div>';
            if (metric) {
                html += '<div class="aiwb-module-metric"><strong>' + metric + '</strong><span>' + metricLabel + '</span></div>';
            }
            html += '</div>';
        });
        safeSetHtml($grid, html);
    }

    function loadSeoPosts() {
        if (!$('#aiwb-seo-post').length) {
            return;
        }
        apiPost('aiwb_get_posts_list', {}, function (data) {
            var items = data.items || [];
            var $select = $('#aiwb-seo-post');
            $select.find('option:not(:first)').remove();
            items.forEach(function (post) {
                $select.append('<option value="' + post.id + '">' + post.title + '</option>');
            });
            if (!items.length) {
                showToast('No posts found. Create a post first.', 'error');
            }
        }, function (message) {
            showToast(message || 'Unable to load posts for SEO tools.', 'error');
        });
    }

    function getSeoPayload() {
        return {
            meta_title: $('#aiwb-meta-title').val() || '',
            meta_desc: $('#aiwb-meta-description').val() || '',
            focus_keyword: $('#aiwb-meta-keyword').val() || '',
            faq_schema: $('#aiwb-seo-faq').val() || '',
            post_id: $('#aiwb-seo-post').val() || ''
        };
    }

    function copySeoMeta(payload) {
        var text = 'Meta Title: ' + (payload.meta_title || '') + '\n' +
            'Meta Description: ' + (payload.meta_desc || '') + '\n' +
            'Focus Keyword: ' + (payload.focus_keyword || '');
        if (payload.faq_schema) {
            text += '\nFAQ Schema: ' + payload.faq_schema;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            try {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'absolute';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                var ok = document.execCommand('copy');
                document.body.removeChild(textarea);
                if (ok) {
                    resolve();
                } else {
                    reject();
                }
            } catch (e) {
                reject(e);
            }
        });
    }

    function withButtonLoading($btn, label, action) {
        if (!$btn || !$btn.length) {
            action(function () {});
            return;
        }
        var original = $btn.text();
        $btn.prop('disabled', true).text(label || 'Working...');
        action(function () {
            $btn.prop('disabled', false).text(original);
        });
    }

    function initStarfield() {
        var canvas = document.getElementById('aiwb-star-canvas');
        if (!canvas) {
            return;
        }
        var wrap = canvas.closest('.aiwb-wrap');
        if (!wrap) {
            return;
        }
        var ctx = canvas.getContext('2d');
        var dpr = window.devicePixelRatio || 1;
        var width = 0;
        var height = 0;
        var stars = [];
        var palette = ['rgba(255,255,255,0.9)', 'rgba(120,156,255,0.9)', 'rgba(255,140,220,0.85)', 'rgba(120,220,255,0.85)'];

        function rand(min, max) {
            return Math.random() * (max - min) + min;
        }

        function resize() {
            width = wrap.clientWidth;
            height = wrap.clientHeight;
            canvas.style.width = width + 'px';
            canvas.style.height = height + 'px';
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            buildStars();
        }

        function buildStars() {
            var count = Math.min(260, Math.max(120, Math.floor((width * height) / 9000)));
            stars = [];
            for (var i = 0; i < count; i++) {
                stars.push({
                    x: rand(0, width),
                    y: rand(0, height),
                    r: rand(0.6, 2.4),
                    a: rand(0.35, 0.95),
                    s: rand(0.08, 0.35),
                    d: rand(-0.15, 0.15),
                    c: palette[Math.floor(rand(0, palette.length))]
                });
            }
        }

        function draw() {
            ctx.clearRect(0, 0, width, height);
            for (var i = 0; i < stars.length; i++) {
                var star = stars[i];
                star.y += star.s;
                star.x += star.d;
                if (star.y > height + 10) {
                    star.y = -10;
                    star.x = rand(0, width);
                }
                if (star.x > width + 10) {
                    star.x = -10;
                }
                if (star.x < -10) {
                    star.x = width + 10;
                }
                ctx.beginPath();
                ctx.fillStyle = star.c;
                ctx.globalAlpha = star.a;
                ctx.shadowBlur = 10;
                ctx.shadowColor = star.c;
                ctx.arc(star.x, star.y, star.r, 0, Math.PI * 2);
                ctx.fill();
            }
            ctx.globalAlpha = 1;
            requestAnimationFrame(draw);
        }

        resize();
        draw();
        window.addEventListener('resize', resize);
    }

    $(document).ready(function () {
        if ($('#aiwb-module-status').length) {
            $('body').addClass('aiwb-dashboard-page');
        }
        loadDashboardData();
        loadSeoPosts();
        setInterval(loadDashboardData, 120000);
        // Temporary stability mode: starfield disabled to prevent tab freezing on some environments.
        renderSecuritySnapshot();
        function applyEditorTheme() {
            if (!window.tinymce) {
                return;
            }
            ['aiwb-content-result'].forEach(function (editorId) {
                var editorInstance = tinymce.get(editorId);
                if (!editorInstance) {
                    return;
                }
                var body = editorInstance.getBody();
                if (body) {
                    body.style.backgroundColor = '#120f22';
                    body.style.color = '#f6f0ff';
                }
            });
        }
        setTimeout(applyEditorTheme, 600);
        if ($('.aiwb-stepper').length) {
            $('.aiwb-step[data-step="2"]').addClass('aiwb-step--disabled');
        }
        $('#aiwb-hero-generate').on('click', function () {
            safeNavigate(aiwbData.adminUrl + '?page=aiwb-ai-content', aiwbData.adminUrl);
        });

        $('#aiwb-hero-health').on('click', function () {
            safeNavigate(aiwbData.adminUrl + '?page=aiwb-health-scanner', aiwbData.adminUrl);
        });
        $('#aiwb-content-form').on('submit', function (event) {
            event.preventDefault();

            var topic = $('#aiwb-topic').val();
            var tone = $('#aiwb-tone').val();
            var length = $('#aiwb-length').val();
            var keyword = $('#aiwb-keyword').val();
            var language = $('#aiwb-language').val() || 'English';
            $('#aiwb-content-result').val('Generating content...').addClass('aiwb-editor-area--loading');
            $('.aiwb-editor').addClass('aiwb-editor--loading');
            var $genBtn = $('#aiwb-content-form .button-primary');
            $genBtn.prop('disabled', true).data('orig-text', $genBtn.text()).text('Generating...');
            $('#aiwb-post-title').val(topic);
            $('#aiwb-post-slug').val((topic || '').toString().trim().toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-'));
            $('#aiwb-post-tags').val((keyword || topic || '').toString().trim().toLowerCase());
            var today = new Date();
            var yyyy = today.getFullYear();
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var dd = String(today.getDate()).padStart(2, '0');
            $('#aiwb-post-schedule').val(yyyy + '-' + mm + '-' + dd);

            restPostWithFallback('/generate-content', { topic: topic, tone: tone, length: length, keyword: keyword, language: language }, 'aiwb_generate_content', function (response) {
                var content = response.content || 'No response.';
                if (window.tinymce && tinymce.get('aiwb-content-result')) {
                    tinymce.get('aiwb-content-result').setContent(content);
                    applyEditorTheme();
                } else {
                    $('#aiwb-content-result').val(formatGeneratedContent(content)).removeClass('aiwb-editor-area--loading');
                }
                $('#aiwb-content-result').removeClass('aiwb-editor-area--loading');
                $('.aiwb-editor').removeClass('aiwb-editor--loading');
                $genBtn.prop('disabled', false).text($genBtn.data('orig-text') || 'Generate Content');
                showToast('Content generated successfully.', 'success');
                $('.aiwb-copy-row').css('display', 'flex');
                $('.aiwb-step[data-step="2"]').removeClass('aiwb-step--disabled');
                $('#aiwb-go-step-2').prop('disabled', false);
            }, function (message) {
                $('#aiwb-content-result').val(message).removeClass('aiwb-editor-area--loading');
                $('.aiwb-editor').removeClass('aiwb-editor--loading');
                $genBtn.prop('disabled', false).text($genBtn.data('orig-text') || 'Generate Content');
                showToast(message, 'error');
                $('.aiwb-copy-row').hide();
            });
        });

        $('#aiwb-copy-content').on('click', function () {
            var content = $('#aiwb-content-result').val();
            if (window.tinymce && tinymce.get('aiwb-content-result')) {
                content = tinymce.get('aiwb-content-result').getContent({ format: 'text' });
            }
            if (!content) {
                showToast('No content to copy.', 'error');
                return;
            }
            navigator.clipboard.writeText(content).then(function () {
                showToast('Content copied to clipboard.', 'success');
            }).catch(function () {
                showToast('Copy failed. Select and copy manually.', 'error');
            });
        });

        function createPost(statusOverride) {
            var status = statusOverride || $('#aiwb-post-status').val();
            var content = $('#aiwb-content-result').val();
            if (window.tinymce && tinymce.get('aiwb-content-result')) {
                content = tinymce.get('aiwb-content-result').getContent();
            }
            if (!content || content.indexOf('Generating content') !== -1) {
                showToast('Please generate content before publishing.', 'error');
                return;
            }
            apiPost('aiwb_create_post', {
                title: $('#aiwb-post-title').val() || $('#aiwb-topic').val(),
                slug: $('#aiwb-post-slug').val(),
                content: content,
                status: status,
                schedule: $('#aiwb-post-schedule').val(),
                category: $('#aiwb-post-category').val(),
                tags: $('#aiwb-post-tags').val(),
                featured_id: $('#aiwb-featured-id').val()
            }, function (data) {
                showToast('Post created successfully.', 'success');
                $('#aiwb-save-draft, #aiwb-publish-post').prop('disabled', true);
                safeNavigate(aiwbData.adminUrl + '?page=aiwb-dashboard&tab=dashboard', aiwbData.adminUrl);
            }, function (message) {
                showToast(message, 'error');
            });
        }

        $('#aiwb-generate-post').on('click', function () {
            createPost('draft');
        });

        $('#aiwb-save-draft').on('click', function () {
            createPost('draft');
        });

        $('#aiwb-publish-post').on('click', function () {
            createPost('publish');
        });

        function goStep(step) {
            $('.aiwb-step').removeClass('aiwb-step--active');
            $('.aiwb-step-panel').removeClass('aiwb-step-panel--active');
            $('.aiwb-step[data-step="' + step + '"]').addClass('aiwb-step--active');
            $('.aiwb-step-panel[data-step="' + step + '"]').addClass('aiwb-step-panel--active');
        }

        $(document).on('click', '.aiwb-step', function () {
            var step = $(this).data('step');
            goStep(step);
        });

        $('#aiwb-go-step-2').on('click', function () {
            if ($(this).prop('disabled')) {
                return;
            }
            goStep(2);
        });
        $('#aiwb-go-step-1').on('click', function () { goStep(1); });

        $('#aiwb-generate-ideas').on('click', function () {
            var keyword = $('#aiwb-idea-keyword').val();
            safeSetHtml($('#aiwb-ideas-result'), '<p>Generating ideas...</p>');
            restPostWithFallback('/blog-ideas', { keyword: keyword, count: 20 }, 'aiwb_blog_ideas', function (response) {
                var ideas = response.ideas || [];
                var html = '<ol class="aiwb-idea-list">';
                ideas.forEach(function (idea) {
                    var safeLabel = escapeHtml(idea);
                    var safeValue = encodeURIComponent(idea);
                    html += '<li class="aiwb-idea-item"><label><input type="radio" name="aiwb-idea" value="' + safeValue + '"><span class="aiwb-idea-control" aria-hidden="true"></span><span class="aiwb-idea-text">' + safeLabel + '</span></label></li>';
                });
                html += '</ol>';
                safeSetHtml($('#aiwb-ideas-result'), html);
                $('#aiwb-ideas-result input[name="aiwb-idea"]').first().prop('checked', true).trigger('change');
                showToast('Ideas generated.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-ideas-result'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $(document).on('change', 'input[name="aiwb-idea"]', function () {
            $('.aiwb-idea-item').removeClass('is-selected');
            $(this).closest('.aiwb-idea-item').addClass('is-selected');
        });

        $('#aiwb-idea-to-post').on('click', function () {
            var selected = $('input[name="aiwb-idea"]:checked').val();
            var idea = '';
            if (selected) {
                try {
                    idea = decodeURIComponent(selected);
                } catch (e) {
                    idea = selected;
                }
            }
            if (!idea) {
                if (!$('#aiwb-ideas-result .aiwb-idea-error').length) {
                    $('#aiwb-ideas-result').prepend('<p class="aiwb-idea-error">Select an idea.</p>');
                }
                return;
            }
            $('#aiwb-ideas-result .aiwb-idea-error').remove();
            localStorage.setItem('aiwb_selected_idea', idea);
            var ideaParam = encodeURIComponent(idea);
            safeNavigate(aiwbData.adminUrl + '?page=aiwb-ai-content&idea=' + ideaParam, aiwbData.adminUrl);
        });

        var bulkImageTarget = null;
        var bulkDrafts = [];

        function setBulkLoadingState(isLoading, $activeButton, label) {
            var $buttons = $('#aiwb-bulk-generate, #aiwb-bulk-schedule, #aiwb-bulk-save');
            var $bulkEditor = $('.aiwb-bulk-editor');
            if (isLoading) {
                $buttons.each(function () {
                    var $btn = $(this);
                    if (!$btn.data('orig-text')) {
                        $btn.data('orig-text', $btn.text());
                    }
                    $btn.prop('disabled', true);
                });
                if ($activeButton && $activeButton.length) {
                    $activeButton.text(label || 'Processing...');
                }
                $bulkEditor.addClass('aiwb-editor--loading');
            } else {
                $buttons.each(function () {
                    var $btn = $(this);
                    $btn.prop('disabled', false);
                    if ($btn.data('orig-text')) {
                        $btn.text($btn.data('orig-text'));
                    }
                });
                $bulkEditor.removeClass('aiwb-editor--loading');
            }
        }

        function destroyBulkEditors() {
            if (!(window.wp && wp.editor && wp.editor.remove)) {
                return;
            }
            $('.aiwb-bulk-content').each(function () {
                var editorId = $(this).attr('id');
                if (editorId) {
                    wp.editor.remove(editorId);
                }
            });
        }

        function initBulkEditor(editorId) {
            if (!(window.wp && wp.editor && wp.editor.initialize)) {
                return;
            }
            wp.editor.initialize(editorId, {
                tinymce: {
                    wpautop: true,
                    content_style: 'body{background:#120f22 !important;color:#f6f0ff !important;font-family:Manrope,Segoe UI,sans-serif;} p,li,h1,h2,h3,h4,h5,h6{color:#f6f0ff !important;} a{color:#b992ff !important;}',
                    setup: function (editor) {
                        editor.on('init', function () {
                            var body = editor.getBody();
                            if (body) {
                                body.style.backgroundColor = '#120f22';
                                body.style.color = '#f6f0ff';
                            }
                        });
                    }
                },
                quicktags: true,
                mediaButtons: false
            });
        }

        function renderBulkDrafts(items) {
            var $container = $('#aiwb-bulk-generated');
            if (!items.length) {
                $container.empty();
                $('#aiwb-bulk-summary').text('No drafts yet. Generate posts to edit and save.');
                $('#aiwb-bulk-save').prop('disabled', true);
                return;
            }
            destroyBulkEditors();
            var html = '';
            items.forEach(function (item, index) {
                var title = escapeHtml(item.title);
                var keyword = escapeHtml(item.keyword || item.title);
                var editorId = 'aiwb-bulk-content-' + index;
                html += '<div class="aiwb-bulk-card" data-index="' + index + '" data-keyword="' + keyword + '">';
                html += '<div class="aiwb-bulk-card-header">';
                html += '<span class="aiwb-bulk-index">' + (index + 1) + '.</span>';
                html += '<input type="text" class="aiwb-bulk-title" value="' + title + '">';
                html += '<div class="aiwb-bulk-meta">';
                html += '<span class="aiwb-bulk-chip">Keyword: ' + keyword + '</span>';
                html += '<button type="button" class="button aiwb-bulk-remove">Remove</button>';
                html += '</div></div>';
                html += '<textarea id="' + editorId + '" class="aiwb-bulk-content" rows="10"></textarea>';
                html += '<input type="hidden" class="aiwb-bulk-featured-id" value="0">';
                html += '<div class="aiwb-bulk-image">';
                html += '<div class="aiwb-image-preview aiwb-bulk-image-preview"><span class="aiwb-muted">No featured image selected.</span></div>';
                html += '<div class="aiwb-bulk-image-actions">';
                html += '<button type="button" class="button aiwb-bulk-image-generate">Generate Image</button>';
                html += '<button type="button" class="button aiwb-bulk-image-upload">Upload</button>';
                html += '<button type="button" class="button aiwb-bulk-image-remove">Remove</button>';
                html += '</div></div></div>';
            });
            safeSetHtml($container, html);
            items.forEach(function (item, index) {
                $('#aiwb-bulk-content-' + index).val(item.content || '');
                initBulkEditor('aiwb-bulk-content-' + index);
            });
            $('#aiwb-bulk-summary').text('Generated ' + items.length + ' draft' + (items.length === 1 ? '' : 's') + '. Review, add images, and save.');
            $('#aiwb-bulk-save').prop('disabled', false);
        }

        function toggleBulkScheduleFields() {
            var status = $('#aiwb-bulk-status').val();
            if (status === 'schedule') {
                $('.aiwb-bulk-schedule-inline').css('display', 'block');
            } else {
                $('.aiwb-bulk-schedule-inline').hide();
            }
        }

        function setScheduleMinDate() {
            var today = new Date();
            var yyyy = today.getFullYear();
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var dd = String(today.getDate()).padStart(2, '0');
            $('#aiwb-bulk-schedule-date').attr('min', yyyy + '-' + mm + '-' + dd);
        }

        function syncScheduleFromDays() {
            var days = parseInt($('#aiwb-bulk-schedule-days').val(), 10);
            if (!Number.isFinite(days) || days < 0) {
                return;
            }
            var targetDate = new Date();
            targetDate.setDate(targetDate.getDate() + days);
            var yyyy = targetDate.getFullYear();
            var mm = String(targetDate.getMonth() + 1).padStart(2, '0');
            var dd = String(targetDate.getDate()).padStart(2, '0');
            $('#aiwb-bulk-schedule-date').val(yyyy + '-' + mm + '-' + dd);
        }

        function buildScheduleValue() {
            if ($('#aiwb-bulk-status').val() !== 'schedule') {
                return '';
            }
            var days = parseInt($('#aiwb-bulk-schedule-days').val(), 10) || 0;
            var time = $('#aiwb-bulk-schedule-time').val() || '09:00';
            var dateStr = $('#aiwb-bulk-schedule-date').val();
            var targetDate;
            if (days > 0) {
                targetDate = new Date();
                targetDate.setDate(targetDate.getDate() + days);
                var timeParts = time.split(':');
                var hours = parseInt(timeParts[0] || '9', 10);
                var minutes = parseInt(timeParts[1] || '0', 10);
                targetDate.setHours(hours, minutes, 0, 0);
            } else if (dateStr) {
                targetDate = new Date(dateStr + 'T' + time + ':00');
            } else {
                return '';
            }
            if (!targetDate || Number.isNaN(targetDate.getTime())) {
                return '';
            }
            var yyyy = targetDate.getFullYear();
            var mm = String(targetDate.getMonth() + 1).padStart(2, '0');
            var dd = String(targetDate.getDate()).padStart(2, '0');
            var hh = String(targetDate.getHours()).padStart(2, '0');
            var min = String(targetDate.getMinutes()).padStart(2, '0');
            return yyyy + '-' + mm + '-' + dd + ' ' + hh + ':' + min + ':00';
        }

        function bulkGenerate($triggerButton) {
            var keywords = ($('#aiwb-bulk-keywords').val() || '').trim();
            var count = parseInt($('#aiwb-bulk-count').val(), 10) || 0;
            if (!keywords.length) {
                showToast('Please enter at least one keyword.', 'error');
                return;
            }
            if (count <= 0) {
                showToast('Please enter a valid number of posts.', 'error');
                return;
            }
            setBulkLoadingState(true, $triggerButton, 'Generating...');
            apiPost('aiwb_bulk_generate', {
                keywords: keywords,
                count: count,
                tone: 'professional'
            }, function (data) {
                bulkDrafts = data.items || [];
                renderBulkDrafts(bulkDrafts);
                setBulkLoadingState(false);
                showToast('Drafts generated. Please review and save.', 'success');
            }, function (message) {
                setBulkLoadingState(false);
                showToast(message || 'Failed to generate drafts.', 'error');
            }, { timeout: 120000 });
        }

        $('#aiwb-bulk-generate').on('click', function () {
            bulkGenerate($(this));
        });

        $('#aiwb-bulk-schedule').on('click', function () {
            $('#aiwb-bulk-status').val('schedule');
            toggleBulkScheduleFields();
            setScheduleMinDate();
            bulkGenerate($(this));
        });

        $('#aiwb-bulk-status').on('change', function () {
            toggleBulkScheduleFields();
            if ($(this).val() === 'schedule') {
                setScheduleMinDate();
            }
        });
        $('#aiwb-bulk-schedule-days').on('input', function () {
            if ($('#aiwb-bulk-status').val() !== 'schedule') {
                $('#aiwb-bulk-status').val('schedule');
                toggleBulkScheduleFields();
            }
            syncScheduleFromDays();
        });
        $('#aiwb-bulk-schedule-date').on('change', function () {
            $('#aiwb-bulk-schedule-days').val('');
        });
        toggleBulkScheduleFields();
        setScheduleMinDate();

        $(document).on('click', '.aiwb-bulk-remove', function () {
            $(this).closest('.aiwb-bulk-card').remove();
            var remaining = $('.aiwb-bulk-card').length;
            $('#aiwb-bulk-summary').text(remaining ? ('Generated ' + remaining + ' draft' + (remaining === 1 ? '' : 's') + '. Review, add images, and save.') : 'No drafts yet. Generate posts to edit and save.');
            $('#aiwb-bulk-save').prop('disabled', !remaining);
        });

        $(document).on('click', '.aiwb-bulk-image-generate', function () {
            var $card = $(this).closest('.aiwb-bulk-card');
            var title = $card.find('.aiwb-bulk-title').val() || $card.data('keyword') || 'Featured Image';
            bulkImageTarget = $card;
            openImageModal(title);
        });

        $(document).on('click', '.aiwb-bulk-image-upload', function (event) {
            event.preventDefault();
            bulkImageTarget = $(this).closest('.aiwb-bulk-card');
            openMediaFrame();
        });

        $(document).on('click', '.aiwb-bulk-image-remove', function () {
            var $card = $(this).closest('.aiwb-bulk-card');
            $card.find('.aiwb-bulk-featured-id').val('0');
            safeSetHtml($card.find('.aiwb-bulk-image-preview'), '<span class="aiwb-muted">No featured image selected.</span>');
        });

        $('#aiwb-bulk-save').on('click', function () {
            var posts = [];
            $('.aiwb-bulk-card').each(function () {
                var $card = $(this);
                var title = ($card.find('.aiwb-bulk-title').val() || '').trim();
                var contentField = $card.find('.aiwb-bulk-content');
                var editorId = contentField.attr('id');
                var content = '';
                if (window.tinymce && editorId && tinymce.get(editorId)) {
                    content = tinymce.get(editorId).getContent();
                } else {
                    content = (contentField.val() || '').trim();
                }
                if (!title || !content) {
                    return;
                }
                posts.push({
                    title: title,
                    content: content,
                    keyword: $card.data('keyword') || title,
                    featured_id: parseInt($card.find('.aiwb-bulk-featured-id').val(), 10) || 0
                });
            });
            if (!posts.length) {
                showToast('Add at least one post with content before saving.', 'error');
                return;
            }
            var status = $('#aiwb-bulk-status').val();
            var schedule = buildScheduleValue();
            if (status === 'schedule' && !schedule) {
                showToast('Please provide a valid schedule date or days.', 'error');
                return;
            }
            setBulkLoadingState(true, $('#aiwb-bulk-save'), 'Saving...');
            apiPost('aiwb_bulk_save', {
                posts: JSON.stringify(posts),
                status: status,
                schedule: schedule,
                auto_image: $('#aiwb-bulk-auto-image').is(':checked') ? '1' : '0',
                save_reminder: $('#aiwb-bulk-reminder').is(':checked') ? '1' : '0'
            }, function (data) {
                setBulkLoadingState(false);
                var created = data.created || [];
                var imageSuccess = data.image_success || 0;
                var imageFailed = data.image_failed || 0;
                var toastMessage = 'Posts saved successfully.';
                if (status === 'schedule' && data.schedule_date) {
                    toastMessage = 'Posts scheduled for ' + data.schedule_date + '.';
                }
                if (created.length) {
                    toastMessage += ' Featured images: ' + imageSuccess + ' attached';
                    if (imageFailed) {
                        toastMessage += ', ' + imageFailed + ' missing';
                    }
                    toastMessage += '.';
                }
                showToast(toastMessage, 'success');
                // Clear drafts after successful save
                destroyBulkEditors();
                $('#aiwb-bulk-generated').empty();
                $('#aiwb-bulk-summary').text('No drafts yet. Generate posts to edit and save.');
                $('#aiwb-bulk-save').prop('disabled', true);
                bulkDrafts = [];
            }, function (message) {
                setBulkLoadingState(false);
                showToast(message || 'Failed to save posts.', 'error');
            }, { timeout: 120000 });
        });

        $('#aiwb-clear-bulk-reminder').on('click', function () {
            apiPost('aiwb_clear_schedule_reminder', {}, function () {
                showToast('Schedule reminder cleared.', 'success');
                $('#aiwb-clear-bulk-reminder').closest('.aiwb-card').find('.aiwb-muted').text('No schedule reminder saved yet. Save one from the Bulk Post Generator.');
                $('#aiwb-clear-bulk-reminder').remove();
            }, function (message) {
                showToast(message || 'Failed to clear reminder.', 'error');
            });
        });

        $('#aiwb-schedule-now').on('click', function () {
            apiPost('aiwb_schedule_now', {}, function (data) {
                showToast('Scheduled posts published now.', 'success');
                $('#aiwb-schedule-now').remove();
                $('#aiwb-clear-bulk-reminder').remove();
                $('#aiwb-schedule-now').closest('.aiwb-card').find('.aiwb-muted').text('No schedule reminder saved yet. Save one from the Bulk Post Generator.');
            }, function (message) {
                showToast(message || 'Failed to publish scheduled posts.', 'error');
            });
        });

        var updateTemplatePreview = function () {
            var templateLabel = $('#popup_template option:selected').text();
            if (templateLabel.length) {
                $('#aiwb-popup-template-preview').text(templateLabel);
            }
        };

        if ($('#popup_template').length) {
            updateTemplatePreview();
            $('#popup_template').on('change', updateTemplatePreview);
        }

        $('#aiwb-load-unused, #aiwb-health-unused').on('click', function () {
            safeSetHtml($('#aiwb-unused-result'), '<p>Loading...</p>');
            setHealthResult('<p>Loading...</p>');
            apiPost('aiwb_health_unused_assets', {}, function (data) {
                var pluginHtml = renderList(data.inactive_plugins, function (item) {
                    return escapeHtml(item.name) + ' <span class="aiwb-muted">(' + escapeHtml(item.path) + ')</span>';
                });
                var themeHtml = renderList(data.inactive_themes, function (item) {
                    return escapeHtml(item.name) + ' <span class="aiwb-muted">(' + escapeHtml(item.slug) + ')</span>';
                });
                safeSetHtml($('#aiwb-unused-result'), '<h4>Inactive Plugins</h4>' + pluginHtml + '<h4>Inactive Themes</h4>' + themeHtml);
                setHealthResult('<h4>Inactive Plugins</h4>' + pluginHtml + '<h4>Inactive Themes</h4>' + themeHtml);
                showToast('Unused assets loaded.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-unused-result'), '<p>' + escapeHtml(message) + '</p>');
                setHealthResult('<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $('#aiwb-scan-links, #aiwb-health-broken').on('click', function () {
            var postCount = $('#aiwb-links-posts').length ? $('#aiwb-links-posts').val() : 10;
            var linkLimit = $('#aiwb-links-limit').length ? $('#aiwb-links-limit').val() : 50;
            safeSetHtml($('#aiwb-links-result'), '<p>Scanning links...</p>');
            setHealthResult('<p>Scanning links...</p>');
            apiPost('aiwb_health_scan_links', {
                post_count: postCount,
                link_limit: linkLimit
            }, function (data) {
                var html = '<p>Checked ' + escapeHtml(data.checked) + ' links.</p>';
                if (data.broken && data.broken.length) {
                    html += '<ul class="aiwb-simple-list">';
                    data.broken.forEach(function (item) {
                        html += '<li><strong>' + escapeHtml(item.status) + '</strong> - ' + escapeHtml(item.url) + ' <span class="aiwb-muted">(' + escapeHtml(item.post_title) + ')</span></li>';
                    });
                    html += '</ul>';
                } else {
                    html += '<p>No broken links found.</p>';
                }
                safeSetHtml($('#aiwb-links-result'), html);
                setHealthResult(html);
                showToast('Link scan completed.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-links-result'), '<p>' + escapeHtml(message) + '</p>');
                setHealthResult('<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $('#aiwb-clean-db').on('click', function () {
            safeSetHtml($('#aiwb-db-result'), '<p>Cleaning database...</p>');
            apiPost('aiwb_health_cleanup_db', {}, function (data) {
                var html = '<p>Expired transients cleared: ' + escapeHtml(data.expired_transients_cleared) + '</p>';
                html += '<p>Revisions deleted: ' + escapeHtml(data.revisions_deleted) + '</p>';
                safeSetHtml($('#aiwb-db-result'), html);
                showToast('Database cleanup completed.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-db-result'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $('#aiwb-health-db').on('click', function () {
            safeSetHtml($('#aiwb-health-result'), '<p>Checking database size...</p>');
            apiPost('aiwb_health_db_size', {}, function (data) {
                safeSetHtml($('#aiwb-health-result'), '<p>Database size: ' + escapeHtml(data.size_mb) + ' MB</p>');
                showToast('Database size loaded.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-health-result'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $('#aiwb-health-images').on('click', function () {
            safeSetHtml($('#aiwb-health-result'), '<p>Scanning images...</p>');
            apiPost('aiwb_health_large_images', { limit: 10 }, function (data) {
                var html = '<p>Large images found: ' + escapeHtml(data.items.length) + '</p>';
                html += renderList(data.items, function (item) {
                    return escapeHtml(item.title) + ' <span class="aiwb-muted">(' + escapeHtml(item.size_kb) + ' KB)</span>';
                });
                safeSetHtml($('#aiwb-health-result'), html);
                showToast('Large image scan completed.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-health-result'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $('#aiwb-health-speed').on('click', function () {
            safeSetHtml($('#aiwb-health-result'), '<p>Loading tips...</p>');
            apiPost('aiwb_health_speed_tips', {}, function (data) {
                var html = renderList(data.tips, function (item) {
                    return escapeHtml(item);
                });
                safeSetHtml($('#aiwb-health-result'), html);
                showToast('Speed tips loaded.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-health-result'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

    function renderLineChart(history) {
        if (!history || !history.length) {
            return '<div class="aiwb-chart-empty">No history yet.</div>';
        }
        var max = 100;
        var width = 220;
        var height = 70;
        var points = '';
        var dots = '';
        history.forEach(function (item, idx) {
            var x = (history.length === 1) ? width / 2 : (idx / (history.length - 1)) * width;
            var y = height - ((item.score || 0) / max) * height;
            points += x + ',' + y + ' ';
            dots += '<circle cx="' + x + '" cy="' + y + '" r="3"></circle>';
        });
        return '<svg viewBox="0 0 ' + width + ' ' + height + '" class="aiwb-line-chart"><polyline points="' + points.trim() + '"></polyline>' + dots + '</svg>';
    }

    function buildSecurityReport(data) {
            function formatDbSize(value) {
                var num = parseFloat(value);
                if (Number.isFinite(num)) {
                    return num + ' MB';
                }
                return 'N/A';
            }
            var score = data && data.security && data.security.score ? data.security.score : 0;
            $('#aiwb-health-score').text(score + '%');
            $('#aiwb-health-progress').css('width', score + '%');

            var html = '<div class="aiwb-report">';
            var passCount = 0;
            var warnCount = 0;
            if (data.security && data.security.checks) {
                data.security.checks.forEach(function (item) {
                    if (item.status === 'pass') { passCount++; } else { warnCount++; }
                });
            }
            var reportTitle = data && data.module_label ? (escapeHtml(data.module_label) + ' Report') : 'Security Report';
            var generatedAt = data && data.generated_at ? escapeHtml(data.generated_at) : '';
            html += '<div class="aiwb-report-head"><div><h3>' + reportTitle + '</h3><p class="aiwb-muted">Summary of site security, performance, and maintenance signals.</p>';
            if (generatedAt) {
                html += '<p class="aiwb-muted">Last scan: ' + generatedAt + '</p>';
            }
            html += '</div><span class="aiwb-report-score">' + score + '%</span></div>';
            html += '<div class="aiwb-report-kpis">';
            html += '<div class="aiwb-kpi"><span>Passed</span><strong>' + passCount + '</strong></div>';
            html += '<div class="aiwb-kpi"><span>Warnings</span><strong>' + warnCount + '</strong></div>';
            html += '<div class="aiwb-kpi"><span>DB Size</span><strong>' + escapeHtml(formatDbSize(data.database_size_mb)) + '</strong></div>';
            html += '</div>';
            var total = Math.max(1, passCount + warnCount);
            var piePct = Math.round((passCount / total) * 100);
            var history = data && data.history ? data.history : [];
            html += '<div class="aiwb-report-charts">';
            html += '<div class="aiwb-chart-card"><h4>Pass Ratio</h4><div class="aiwb-pie" style="--aiwb-pie:' + piePct + '%"><span>' + piePct + '%</span></div></div>';
            html += '<div class="aiwb-chart-card"><h4>Score Trend</h4>' + renderLineChart(history) + '</div>';
            html += '</div>';
            html += '<div class="aiwb-report-grid">';
            html += '<div class="aiwb-report-card"><h4>Security Checks</h4>';
            if (data.security && data.security.checks && data.security.checks.length) {
                html += '<ul class="aiwb-simple-list">';
                data.security.checks.forEach(function (item) {
                    var status = item.status === 'pass' ? 'ok' : 'warn';
                    html += '<li><strong>' + escapeHtml(item.label) + '</strong> - <span class="aiwb-' + status + '">' + escapeHtml(item.status.toUpperCase()) + '</span><br><span class="aiwb-muted">' + escapeHtml(item.detail) + '</span></li>';
                });
                html += '</ul>';
            }
            html += '</div>';

            html += '<div class="aiwb-report-card"><h4>Broken Links</h4>';
            if (data.broken_links && data.broken_links.broken && data.broken_links.broken.length) {
                html += '<p>Checked ' + escapeHtml(data.broken_links.checked) + ' links.</p>';
                html += '<ul class="aiwb-simple-list">';
                data.broken_links.broken.forEach(function (item) {
                    html += '<li><strong>' + escapeHtml(item.status) + '</strong> - ' + escapeHtml(item.url) + ' <span class="aiwb-muted">(' + escapeHtml(item.post_title) + ')</span></li>';
                });
                html += '</ul>';
            } else {
                html += '<p>No broken links found.</p>';
            }
            html += '</div>';

            html += '<div class="aiwb-report-card"><h4>Unused Assets</h4>';
            if (data.unused_assets) {
                var pluginHtml = renderList(data.unused_assets.inactive_plugins || [], function (item) {
                    return escapeHtml(item.name) + ' <span class="aiwb-muted">(' + escapeHtml(item.path) + ')</span>';
                });
                var themeHtml = renderList(data.unused_assets.inactive_themes || [], function (item) {
                    return escapeHtml(item.name) + ' <span class="aiwb-muted">(' + escapeHtml(item.slug) + ')</span>';
                });
                html += '<h4>Inactive Plugins</h4>' + (pluginHtml || '<p class="aiwb-muted">No inactive plugins found.</p>');
                html += '<h4>Inactive Themes</h4>' + (themeHtml || '<p class="aiwb-muted">No inactive themes found.</p>');
            } else {
                html += '<p class="aiwb-muted">Not available in this scan. Run full scan for complete asset data.</p>';
            }
            html += '</div>';

            html += '<div class="aiwb-report-card"><h4>Database Size</h4>';
            html += '<p>' + escapeHtml(formatDbSize(data.database_size_mb)) + '</p>';
            html += '</div>';

            html += '<div class="aiwb-report-card"><h4>Malware Scan</h4>';
            if (data.malware_scan) {
                html += '<p>Files scanned: ' + escapeHtml(data.malware_scan.files_scanned || 0) + '</p>';
                html += '<p>Findings: ' + escapeHtml(data.malware_scan.findings || 0) + '</p>';
                if (data.malware_scan.samples && data.malware_scan.samples.length) {
                    html += '<ul class="aiwb-simple-list">';
                    data.malware_scan.samples.forEach(function (item) {
                        html += '<li>' + escapeHtml(item) + '</li>';
                    });
                    html += '</ul>';
                }
            } else {
                html += '<p class="aiwb-muted">Not part of this module scan.</p>';
            }
            html += '</div>';

            if (data.compliance && data.compliance.length) {
                html += '<div class="aiwb-report-card"><h4>ThemeForest / Compliance Checks</h4><ul class="aiwb-simple-list">';
                data.compliance.forEach(function (item) {
                    var cStatus = item.status === 'pass' ? 'ok' : 'warn';
                    html += '<li><strong>' + escapeHtml(item.label) + '</strong> - <span class="aiwb-' + cStatus + '">' + escapeHtml(item.status.toUpperCase()) + '</span><br><span class="aiwb-muted">' + escapeHtml(item.detail) + '</span></li>';
                });
                html += '</ul></div>';
            }

            if (data.module_reports) {
                var moduleKeys = Object.keys(data.module_reports || {});
                if (moduleKeys.length) {
                    html += '<div class="aiwb-report-card"><h4>Module Scan Summary</h4><ul class="aiwb-simple-list">';
                    moduleKeys.forEach(function (key) {
                        var rep = data.module_reports[key];
                        if (!rep || !rep.security) { return; }
                        var mScore = rep.security.score || 0;
                        html += '<li><strong>' + escapeHtml(key) + '</strong> - <span class="aiwb-muted">Score:</span> ' + escapeHtml(mScore) + '%</li>';
                    });
                    html += '</ul></div>';
                }
            }

            html += '<div class="aiwb-report-card"><h4>Large Images</h4>';
            if (data.large_images && data.large_images.length) {
                html += renderList(data.large_images, function (item) {
                    return escapeHtml(item.title) + ' <span class="aiwb-muted">(' + escapeHtml(item.size_kb) + ' KB)</span>';
                });
            } else {
                html += '<p>No large images detected.</p>';
            }
            html += '</div>';

            html += '<div class="aiwb-report-card"><h4>Speed Tips</h4>';
            if (data.speed_tips && data.speed_tips.length) {
                html += renderList(data.speed_tips, function (item) {
                    return escapeHtml(item);
                });
            } else {
                html += '<p class="aiwb-muted">No speed tips available for this scan.</p>';
            }
            html += '</div>';

            html += '</div></div>';
            return html;
        }

        if ($('#aiwb-health-result').length) {
            try {
                sessionStorage.removeItem('aiwb_health_report_ready');
            } catch (e) {}
            safeSetHtml($('#aiwb-health-result'), '<p>Run Full Security Scan to generate a fresh report.</p>');
        }

        function updateHealthExportState() {
            var ready = false;
            try {
                ready = !!sessionStorage.getItem('aiwb_health_report_ready');
            } catch (e) {}
            $('#aiwb-health-export-csv, #aiwb-health-export-pdf').prop('disabled', !ready);
        }
        updateHealthExportState();

        $('#aiwb-health-scan-all').on('click', function () {
            safeSetHtml($('#aiwb-health-result'), '<p>Running full security scan...</p>');
            apiPost('aiwb_security_scan_all', {}, function (data) {
                try {
                    sessionStorage.setItem('aiwb_health_report_ready', '1');
                } catch (e) {}
                updateHealthExportState();
                safeSetHtml($('#aiwb-health-result'), buildSecurityReport(data));
                renderSecuritySnapshot();
                showToast('Full security scan completed.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-health-result'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            }, { timeout: 120000 });
        });

        $(document).on('click', '.aiwb-module-scan', function () {
            var moduleKey = $(this).data('module');
            if (!moduleKey) {
                showToast('Module not specified.', 'error');
                return;
            }
            safeSetHtml($('#aiwb-health-result'), '<p>Running module scan...</p>');
            apiPost('aiwb_security_scan_module', { module: moduleKey }, function (data) {
                try {
                    sessionStorage.setItem('aiwb_health_report_ready', '1');
                } catch (e) {}
                updateHealthExportState();
                safeSetHtml($('#aiwb-health-result'), buildSecurityReport(data));
                renderSecuritySnapshot();
                showToast('Module scan completed.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-health-result'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            }, { timeout: 120000 });
        });

        $('#aiwb-health-export-csv').on('click', function () {
            if ($(this).prop('disabled')) {
                showToast('Run Full Security Scan first.', 'error');
                return;
            }
            var url = aiwbData.ajaxUrl + '?action=aiwb_export_security_csv&nonce=' + encodeURIComponent(aiwbData.nonce);
            safeNavigate(url, aiwbData.ajaxUrl);
        });

        $('#aiwb-health-export-pdf').on('click', function () {
            if ($(this).prop('disabled')) {
                showToast('Run Full Security Scan first.', 'error');
                return;
            }
            var url = aiwbData.ajaxUrl + '?action=aiwb_export_security_pdf&nonce=' + encodeURIComponent(aiwbData.nonce);
            window.open(url, '_blank', 'noopener,noreferrer');
        });

        $('.aiwb-health-scan-all-quick').on('click', function () {
            $('#aiwb-health-scan-all').trigger('click');
        });

        $('.aiwb-health-export-csv').on('click', function () {
            $('#aiwb-health-export-csv').trigger('click');
        });

        $('.aiwb-health-export-pdf').on('click', function () {
            $('#aiwb-health-export-pdf').trigger('click');
        });

        $('#aiwb-generate-alt').on('click', function () {
            safeSetHtml($('#aiwb-alt-result'), '<p>Generating ALT text...</p>');
            apiPost('aiwb_generate_missing_alt', { limit: $('#aiwb-alt-limit').val() }, function (data) {
                var html = '<p>Updated ' + escapeHtml(data.updated.length) + ' images.</p>';
                html += renderList(data.updated, function (item) {
                    return escapeHtml(item.title) + ' <span class="aiwb-muted">(#' + escapeHtml(item.id) + ')</span>';
                });
                safeSetHtml($('#aiwb-alt-result'), html);
                showToast('ALT text generated.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-alt-result'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $('#aiwb-scan-posts, #aiwb-updater-scan').on('click', function () {
            safeSetHtml($('#aiwb-posts-list'), '<p>Scanning posts...</p>');
            apiPost('aiwb_scan_old_posts', {
                age_days: $('#aiwb-update-age').val(),
                limit: $('#aiwb-update-limit').val()
            }, function (data) {
                if (!data.posts || !data.posts.length) {
                    safeSetHtml($('#aiwb-posts-list'), '<p>No old posts found.</p>');
                    return;
                }
                var html = '<div class="aiwb-checklist">';
                data.posts.forEach(function (post) {
                    var keywordText = (post.keywords && post.keywords.length) ? post.keywords.join(', ') : 'No keywords';
                    var linkText = (post.links && post.links.length) ? post.links.join(', ') : 'No links';
                    html += '<label><input type="checkbox" class="aiwb-post-checkbox" value="' + escapeHtml(post.id) + '" checked> ' + escapeHtml(post.title) + ' <span class="aiwb-muted">(' + escapeHtml(post.date) + ')</span>';
                    html += '<div class="aiwb-muted">Keywords: ' + escapeHtml(keywordText) + '</div>';
                    html += '<div class="aiwb-muted">Links: ' + escapeHtml(linkText) + '</div></label>';
                });
                html += '</div>';
                safeSetHtml($('#aiwb-posts-list'), html);
                showToast('Old posts scanned.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-posts-list'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $('.aiwb-clear-logs').on('click', function () {
            apiPost('aiwb_clear_logs', {}, function () {
                showToast('Logs cleared.', 'success');
                window.location.reload();
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $('#aiwb-log-filter').on('change', function () {
            var val = $(this).val();
            $('.aiwb-log-table tbody tr').each(function () {
                var action = $(this).data('action');
                if (!val || action === val) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        $('.aiwb-logs-export-csv').on('click', function () {
            var url = aiwbData.ajaxUrl + '?action=aiwb_export_security_logs_csv&nonce=' + encodeURIComponent(aiwbData.nonce);
            safeNavigate(url, aiwbData.ajaxUrl);
        });

        $('.aiwb-logs-export-pdf').on('click', function () {
            var url = aiwbData.ajaxUrl + '?action=aiwb_export_security_logs_pdf&nonce=' + encodeURIComponent(aiwbData.nonce);
            window.open(url, '_blank', 'noopener,noreferrer');
        });

        $('.aiwb-firewall-block').on('click', function () {
            var ip = $('#aiwb-firewall-ip').val();
            var reason = $('#aiwb-firewall-reason').val();
            var duration = $('#aiwb-firewall-duration').val();
            apiPost('aiwb_firewall_block_ip', { ip: ip, reason: reason, duration: duration }, function () {
                showToast('IP blocked.', 'success');
                window.location.reload();
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $('.aiwb-firewall-unblock').on('click', function () {
            var id = $(this).data('id');
            apiPost('aiwb_firewall_unblock_ip', { id: id }, function () {
                showToast('IP unblocked.', 'success');
                window.location.reload();
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $('.aiwb-firewall-save-settings').on('click', function () {
            var auto = $('#aiwb-firewall-auto').is(':checked') ? '1' : '0';
            var duration = $('#aiwb-firewall-default-duration').val();
            apiPost('aiwb_firewall_save_settings', { auto_unblock: auto, default_duration: duration }, function (data) {
                showToast(data.message || 'Firewall rules saved.', 'success');
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $('.aiwb-allowlist-add').on('click', function () {
            var ip = $('#aiwb-allow-ip').val();
            var reason = $('#aiwb-allow-reason').val();
            apiPost('aiwb_allowlist_add', { ip: ip, reason: reason }, function () {
                showToast('IP allowlisted.', 'success');
                window.location.reload();
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $('.aiwb-allowlist-remove').on('click', function () {
            var id = $(this).data('id');
            apiPost('aiwb_allowlist_remove', { id: id }, function () {
                showToast('Allowlist entry removed.', 'success');
                window.location.reload();
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $('.aiwb-malware-add-exclusion').on('click', function () {
            var path = $('#aiwb-malware-exclusion').val();
            apiPost('aiwb_malware_add_exclusion', { path: path }, function () {
                showToast('Exclusion added.', 'success');
                window.location.reload();
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $('.aiwb-malware-remove-exclusion').on('click', function () {
            var path = $(this).data('path');
            apiPost('aiwb_malware_remove_exclusion', { path: path }, function () {
                showToast('Exclusion removed.', 'success');
                window.location.reload();
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $('.aiwb-integrity-rebuild').on('click', function () {
            apiPost('aiwb_integrity_rebuild', {}, function (data) {
                showToast(data.message || 'Baseline rebuilt.', 'success');
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $('.aiwb-integrity-scan').on('click', function () {
            safeSetHtml($('#aiwb-integrity-result'), '<p>Running integrity scan...</p>');
            apiPost('aiwb_integrity_scan', {}, function (data) {
                var html = '<p>' + escapeHtml(data.message || '') + '</p>';
                if (data.issues && data.issues.length) {
                    html += '<ul class="aiwb-simple-list">';
                    data.issues.forEach(function (item) {
                        html += '<li>' + escapeHtml(item) + '</li>';
                    });
                    html += '</ul>';
                }
            safeSetHtml($('#aiwb-integrity-result'), html);
                showToast('Integrity scan completed.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-integrity-result'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $('.aiwb-module-export-csv').on('click', function () {
            var moduleKey = $(this).data('module');
            var url = aiwbData.ajaxUrl + '?action=aiwb_export_module_csv&nonce=' + encodeURIComponent(aiwbData.nonce) + '&module=' + encodeURIComponent(moduleKey || '');
            safeNavigate(url, aiwbData.ajaxUrl);
        });

        $('.aiwb-module-export-pdf').on('click', function () {
            var moduleKey = $(this).data('module');
            var url = aiwbData.ajaxUrl + '?action=aiwb_export_module_pdf&nonce=' + encodeURIComponent(aiwbData.nonce) + '&module=' + encodeURIComponent(moduleKey || '');
            window.open(url, '_blank', 'noopener,noreferrer');
        });

        $('.aiwb-save-scan-schedule').on('click', function () {
            var enabled = $('#aiwb-scan-enabled').is(':checked') ? '1' : '0';
            var frequency = $('#aiwb-scan-frequency').val();
            var hour = $('#aiwb-scan-hour').val();
            apiPost('aiwb_save_security_schedule', { enabled: enabled, frequency: frequency, hour: hour }, function (data) {
                showToast(data.message || 'Schedule saved.', 'success');
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $('#aiwb-rewrite-posts').on('click', function () {
            var ids = [];
            $('.aiwb-post-checkbox:checked').each(function () {
                ids.push($(this).val());
            });
            if (!ids.length) {
                safeSetHtml($('#aiwb-posts-list'), '<p>Select at least one post to rewrite.</p>');
                return;
            }
            $('#aiwb-posts-list').append('<p>Rewriting selected posts...</p>');
            apiPost('aiwb_rewrite_posts', {
                ids: ids,
                tone: $('#aiwb-update-tone').val()
            }, function (data) {
                var html = '<p>Updated ' + escapeHtml(data.updated.length) + ' posts.</p>';
                html += renderList(data.updated, function (item) {
                    return escapeHtml(item.title) + ' <span class="aiwb-muted">(#' + escapeHtml(item.id) + ')</span>';
                });
            safeSetHtml($('#aiwb-posts-list'), html);
                showToast('Selected posts rewritten.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-posts-list'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $('#aiwb-updater-scan').on('click', function () {
            safeSetHtml($('#aiwb-updater-table'), '<div class="aiwb-table-row aiwb-table-header"><span>Post Title</span><span>Publish Date</span><span>SEO Score</span><span>Actions</span></div>');
            apiPost('aiwb_scan_old_posts', {
                age_days: $('#aiwb-updater-age').val(),
                limit: $('#aiwb-updater-limit').val()
            }, function (data) {
                var html = '<div class="aiwb-table-row aiwb-table-header"><span>Post Title</span><span>Publish Date</span><span>SEO Score</span><span>Actions</span></div>';
                if (!data.posts || !data.posts.length) {
            safeSetHtml($('#aiwb-updater-table'), html);
                    return;
                }
                data.posts.forEach(function (post) {
                    html += '<div class="aiwb-table-row">';
                    html += '<span>' + escapeHtml(post.title) + '</span>';
                    html += '<span>' + escapeHtml(post.date) + '</span>';
                    html += '<span>' + escapeHtml(post.seo_score || 0) + '%</span>';
                    html += '<span class="aiwb-action-row">';
                    html += '<button class="button aiwb-view" data-id="' + escapeHtml(post.id) + '" data-edit="' + escapeHtml(post.edit_url || '') + '">View</button>';
                    html += '<button class="button aiwb-preview" data-id="' + escapeHtml(post.id) + '">Preview</button>';
                    html += '<button class="button button-primary aiwb-rewrite" data-id="' + escapeHtml(post.id) + '">Rewrite</button>';
                    html += '</span></div>';
                });
            safeSetHtml($('#aiwb-updater-table'), html);
                showToast('Content list updated.', 'success');
            }, function (message) {
                safeSetHtml($('#aiwb-updater-table'), '<p>' + escapeHtml(message) + '</p>');
                showToast(message, 'error');
            });
        });

        $(document).on('click', '.aiwb-view', function () {
            var editUrl = $(this).data('edit');
            if (editUrl) {
                window.open(editUrl, '_blank', 'noopener,noreferrer');
                return;
            }
            showToast('Edit link not available.', 'error');
        });

        $(document).on('click', '.aiwb-preview', function () {
            var postId = $(this).data('id');
            apiPost('aiwb_preview_update', { post_id: postId, tone: 'professional' }, function (data) {
                var diff = buildDiffHtml(data.original || '', data.updated || '');
            safeSetHtml($('#aiwb-compare-original'), diff.original);
            safeSetHtml($('#aiwb-compare-updated'), diff.updated);
                $('#aiwb-publish-update').data('post-id', null).data('content', null);
                apiPost('aiwb_version_list', { post_id: postId }, function (list) {
                    var html = '';
                    (list.versions || []).forEach(function (ver) {
                        html += '<div class="aiwb-version-item"><span>' + escapeHtml(ver.version_label) + ' (' + escapeHtml(ver.created_at) + ')</span><button class="button aiwb-rollback" data-id="' + escapeHtml(ver.id) + '">Rollback</button></div>';
                    });
            safeSetHtml($('#aiwb-version-list'), html);
                }, function () {});
                showToast('Preview generated. Click Rewrite to apply.', 'success');
            }, function (message) {
                $('#aiwb-compare-updated').text(message);
                showToast(message, 'error');
            });
        });

        $(document).on('click', '.aiwb-rewrite', function () {
            var postId = $(this).data('id');
            apiPost('aiwb_preview_update', { post_id: postId, tone: 'professional' }, function (data) {
                var diff = buildDiffHtml(data.original || '', data.updated || '');
            safeSetHtml($('#aiwb-compare-original'), diff.original);
            safeSetHtml($('#aiwb-compare-updated'), diff.updated);
                $('#aiwb-publish-update').data('post-id', postId).data('content', data.updated || '');
                showToast('Rewrite ready. Review and click Publish Update.', 'success');
            }, function (message) {
                $('#aiwb-compare-updated').text(message);
                showToast(message, 'error');
            });
        });

        $('#aiwb-publish-update').on('click', function () {
            var postId = $(this).data('post-id');
            var content = $(this).data('content');
            if (!postId || !content) {
                showToast('Use Preview or Rewrite before publishing.', 'error');
                return;
            }
            apiPost('aiwb_publish_update', { post_id: postId, content: content }, function () {
                $('#aiwb-compare-updated').text('Update published.');
                showToast('Update published.', 'success');
            }, function (message) {
                $('#aiwb-compare-updated').text(message);
                showToast(message, 'error');
            });
        });

        $(document).on('click', '.aiwb-rollback', function () {
            var versionId = $(this).data('id');
            apiPost('aiwb_version_rollback', { version_id: versionId }, function () {
                $('#aiwb-version-list').prepend('<p>Rollback complete.</p>');
                showToast('Rollback complete.', 'success');
            }, function (message) {
                $('#aiwb-version-list').prepend('<p>' + message + '</p>');
                showToast(message, 'error');
            });
        });

        $('#aiwb-generate-seo').on('click', function () {
            $('#aiwb-seo-score').text('...');
            var title = $('#aiwb-meta-title').val();
            if (!title && $('#aiwb-seo-post').val()) {
                title = $('#aiwb-seo-post option:selected').text().replace(/\s*\\(#\\d+\\)\\s*$/, '');
                $('#aiwb-meta-title').val(title);
            }
            restPost('/seo-meta', { title: title, keyword: $('#aiwb-meta-keyword').val() }, function (data) {
                $('#aiwb-meta-title').val(data.title || '');
                $('#aiwb-meta-description').val(data.description || '');
                if (data.faq) {
                    $('#aiwb-seo-faq').val(JSON.stringify(data.faq, null, 2));
                }
                computeSeoScore();
                showToast('SEO metadata generated.', 'success');
            }, function (message) {
                $('#aiwb-meta-description').val(message);
                showToast(message, 'error');
            });
        });

        $('#aiwb-seo-post').on('change', function () {
            var postId = $(this).val();
            if (!postId) {
                return;
            }
            apiPost('aiwb_get_seo_post', { post_id: postId }, function (data) {
                $('#aiwb-meta-title').val(data.title || '');
                $('#aiwb-meta-description').val(data.description || '');
                $('#aiwb-meta-keyword').val(data.keyword || '');
                $('#aiwb-seo-faq').val(data.faq || '');
                computeSeoScore();
                showToast('SEO fields loaded from selected post.', 'success');
            }, function (message) {
                showToast(message || 'Failed to load post data.', 'error');
            });
        });

        $('#aiwb-generate-faq').on('click', function () {
            var title = $('#aiwb-meta-title').val();
            if (!title && $('#aiwb-seo-post').val()) {
                title = $('#aiwb-seo-post option:selected').text().replace(/\s*\\(#\\d+\\)\\s*$/, '');
                $('#aiwb-meta-title').val(title);
            }
            restPost('/seo-meta', { title: title, keyword: $('#aiwb-meta-keyword').val() }, function (data) {
                $('#aiwb-seo-faq').val(JSON.stringify(data.faq || [], null, 2));
                showToast('FAQ schema generated.', 'success');
            }, function (message) {
                showToast(message || 'Failed to generate FAQ schema.', 'error');
            });
        });

        $('#aiwb-copy-seo').on('click', function (event) {
            event.preventDefault();
            var payload = getSeoPayload();
            if (!payload.meta_title && !payload.meta_desc && !payload.focus_keyword && !payload.faq_schema) {
                showToast('Enter SEO fields before copying.', 'error');
                return;
            }
            withButtonLoading($(this), 'Copying...', function (done) {
                copySeoMeta(payload).then(function () {
                    showToast('SEO metadata copied.', 'success');
                    done();
                }).catch(function () {
                    showToast('Copy failed. Please copy manually.', 'error');
                    done();
                });
            });
        });

        $('#aiwb-save-seo-meta').on('click', function (event) {
            event.preventDefault();
            var payload = getSeoPayload();
            if (!payload.post_id) {
                showToast('Select a target post first.', 'error');
                return;
            }
            withButtonLoading($(this), 'Saving...', function (done) {
                apiPost('aiwb_save_seo_meta', {
                    post_id: payload.post_id,
                    meta_title: payload.meta_title,
                    meta_desc: payload.meta_desc,
                    focus_keyword: payload.focus_keyword,
                    faq_schema: payload.faq_schema,
                    provider: 'aiwb'
                }, function (data) {
                    if (data && data.meta_title !== undefined) {
                        $('#aiwb-meta-title').val(data.meta_title || '');
                        $('#aiwb-meta-description').val(data.meta_desc || '');
                        $('#aiwb-meta-keyword').val(data.focus_keyword || '');
                        $('#aiwb-seo-faq').val(data.faq_schema || '');
                        computeSeoScore();
                    }
                    showToast('SEO meta saved to post.', 'success');
                    done();
                }, function (message) {
                    showToast(message, 'error');
                    done();
                });
            });
        });

        $('#aiwb-apply-yoast').on('click', function (event) {
            event.preventDefault();
            var payload = getSeoPayload();
            if (!payload.post_id) {
                showToast('Select a target post first.', 'error');
                return;
            }
            withButtonLoading($(this), 'Applying...', function (done) {
                apiPost('aiwb_save_seo_meta', {
                    post_id: payload.post_id,
                    meta_title: payload.meta_title,
                    meta_desc: payload.meta_desc,
                    focus_keyword: payload.focus_keyword,
                    faq_schema: payload.faq_schema,
                    provider: 'yoast'
                }, function () {
                    showToast('Applied to Yoast meta.', 'success');
                    done();
                }, function (message) {
                    showToast(message, 'error');
                    done();
                });
            });
        });

        $('#aiwb-apply-rankmath').on('click', function (event) {
            event.preventDefault();
            var payload = getSeoPayload();
            if (!payload.post_id) {
                showToast('Select a target post first.', 'error');
                return;
            }
            withButtonLoading($(this), 'Applying...', function (done) {
                apiPost('aiwb_save_seo_meta', {
                    post_id: payload.post_id,
                    meta_title: payload.meta_title,
                    meta_desc: payload.meta_desc,
                    focus_keyword: payload.focus_keyword,
                    faq_schema: payload.faq_schema,
                    provider: 'rankmath'
                }, function () {
                    showToast('Applied to Rank Math meta.', 'success');
                    done();
                }, function (message) {
                    showToast(message, 'error');
                    done();
                });
            });
        });

        $('#aiwb-seo-draft').on('click', function () {
            var payload = getSeoPayload();
            copySeoMeta(payload).then(function () {
                apiPost('aiwb_create_seo_draft', {
                    meta_title: payload.meta_title,
                    meta_desc: payload.meta_desc,
                    focus_keyword: payload.focus_keyword
                }, function (data) {
                    showToast('Draft created. Opening editor.', 'success');
                    if (data.edit_url) {
                        window.open(data.edit_url, '_blank', 'noopener,noreferrer');
                    }
                }, function (message) {
                    showToast(message, 'error');
                });
            }).catch(function () {
                showToast('Copy failed, draft not created.', 'error');
            });
        });

        var popupListMap = {};

        $('#aiwb-save-popup').on('click', function () {
            var payload = {
                popup_id: $('#aiwb-popup-select').val() || 0,
                title: $('#aiwb-popup-title').val(),
                popup_type: $('#aiwb-popup-type').val(),
                template: $('#aiwb-popup-template').val(),
                headline: $('#aiwb-popup-title').val(),
                message: $('#aiwb-popup-content').val(),
                button_text: $('#aiwb-popup-button-text').val(),
                button_url: $('#aiwb-popup-button-url').val(),
                set_active: $('#aiwb-popup-set-active').is(':checked') ? '1' : '0',
                enabled: $('#aiwb-popup-enabled').is(':checked') ? '1' : '0'
            };
            apiPost('aiwb_save_popup', payload, function (data) {
                $('#aiwb-popup-preview').text('Popup saved.');
                showToast('Popup saved.', 'success');
                if (data && data.popup_id) {
                    loadPopupList(data.popup_id);
                }
            }, function (message) {
                $('#aiwb-popup-preview').text(message);
                showToast(message, 'error');
            });
        });

        $('#aiwb-preview-popup').on('click', function () {
            var title = $('#aiwb-popup-title').val() || 'Popup Title';
            var content = $('#aiwb-popup-content').val() || 'Popup content preview.';
            var type = $('#aiwb-popup-type option:selected').text() || $('#aiwb-popup-type').val();
            var template = $('#aiwb-popup-template option:selected').text() || $('#aiwb-popup-template').val();
            var buttonText = $('#aiwb-popup-button-text').val() || 'Start Now';
            var buttonUrl = $('#aiwb-popup-button-url').val() || '#';
            var html = '<div class="aiwb-popup-preview-box">';
            html += '<h4>' + title + '</h4>';
            html += '<p>' + content + '</p>';
            html += '<p class="aiwb-muted">Type: ' + type + ' | Template: ' + template + '</p>';
            html += '<p><a class="button button-primary" href="' + buttonUrl + '">' + buttonText + '</a></p>';
            html += '</div>';
            safeSetHtml($('#aiwb-popup-preview'), html);
            showToast('Popup preview updated.', 'success');
        });

        function formatPopupDate(dateString) {
            if (!dateString) {
                return '-';
            }
            var normalized = dateString.replace(' ', 'T');
            var dateObj = new Date(normalized);
            if (isNaN(dateObj.getTime())) {
                return dateString;
            }
            return dateObj.toLocaleString();
        }

        function updatePopupUpdated(popupId) {
            if (!popupId || !popupListMap[popupId]) {
                $('#aiwb-popup-updated').text('Last updated: -');
                return;
            }
            $('#aiwb-popup-updated').text('Last updated: ' + formatPopupDate(popupListMap[popupId].updated_at));
        }

        function loadPopupList(selectId) {
            if (!$('#aiwb-popup-select').length) {
                return;
            }
            apiPost('aiwb_get_popups', {}, function (data) {
                var items = data.items || [];
                var $select = $('#aiwb-popup-select');
                popupListMap = {};
                $select.find('option:not(:first)').remove();
                if (!items.length) {
                    $select.append("<option value=\"\" disabled>No saved popups yet</option>");
                } else {
                    items.forEach(function (item) {
                        popupListMap[String(item.id)] = item;
                        $select.append("<option value=\"" + item.id + "\">" + item.title + "</option>");
                    });
                }
                if (data && typeof data.enabled !== 'undefined') {
                    $('#aiwb-popup-enabled').prop('checked', String(data.enabled) !== '0');
                }
                if (selectId) {
                    $select.val(String(selectId));
                }
                updatePopupUpdated($select.val());
            }, function (message) {
                showToast(message || 'Unable to load saved popups.', 'error');
            });
        }

        $('#aiwb-popup-load').on('click', function () {
            var popupId = $('#aiwb-popup-select').val();
            if (!popupId) {
                showToast('Select a saved popup first.', 'error');
                return;
            }
            apiPost('aiwb_get_popup', { popup_id: popupId }, function (data) {
                $('#aiwb-popup-type').val(data.popup_type || '');
                $('#aiwb-popup-template').val(data.template || 'template_1').trigger('change');
                $('#aiwb-popup-title').val(data.headline || data.title || '');
                $('#aiwb-popup-content').val(data.message || '');
                $('#aiwb-popup-button-text').val(data.button_text || '');
                $('#aiwb-popup-button-url').val(data.button_url || '');
                updatePopupUpdated(popupId);
                showToast('Popup loaded.', 'success');
            }, function (message) {
                showToast(message, 'error');
            });
        });

        $("#aiwb-popup-new").on("click", function () {
            $("#aiwb-popup-select").val("");
            $("#aiwb-popup-title").val("");
            $("#aiwb-popup-content").val("");
            $("#aiwb-popup-button-text").val("");
            $("#aiwb-popup-button-url").val("");
            $("#aiwb-popup-type").val("Lead Capture");
            $("#aiwb-popup-template").val("template_1").trigger("change");
            $("#aiwb-popup-enabled").prop("checked", true);
            showToast("Ready for a new popup.", "success");
        });

        $("#aiwb-popup-delete").on("click", function () {
            var popupId = $("#aiwb-popup-select").val();
            if (!popupId) {
                showToast("Select a popup to delete.", "error");
                return;
            }
            apiPost("aiwb_delete_popup", { popup_id: popupId }, function () {
                showToast("Popup deleted.", "success");
                $("#aiwb-popup-select").val("");
                loadPopupList();
            }, function (message) {
                showToast(message, "error");
            });
        });

        $("#aiwb-popup-select").on("change", function () {
            updatePopupUpdated($(this).val());
        });

        $("#aiwb-popup-dummy").on("click", function () {
            var type = $("#aiwb-popup-type").val();
            var siteUrl = (window.aiwbData && aiwbData.siteUrl) ? aiwbData.siteUrl : "#";
            var samples = {
                "Lead Capture": {
                    title: "Get the 7-Step Growth Checklist",
                    message: "Join 8,000+ teams and receive a concise playbook to improve conversions this week.",
                    button_text: "Get the Checklist",
                    button_url: siteUrl
                },
                "Discount Popup": {
                    title: "Unlock 15% Off This Week",
                    message: "Limited-time offer for new customers. Use code BOOST15 at checkout.",
                    button_text: "Claim My Discount",
                    button_url: siteUrl
                },
                "Exit Intent Popup": {
                    title: "Before You Go - Save Your Setup",
                    message: "Grab a free onboarding call and we will set up your first campaign in 15 minutes.",
                    button_text: "Book a Call",
                    button_url: siteUrl
                },
                "Announcement Bar": {
                    title: "New Feature: Instant Audit Reports",
                    message: "Generate a full SEO and performance audit in under 60 seconds.",
                    button_text: "See the Update",
                    button_url: siteUrl
                }
            };
            var sample = samples[type] || samples["Lead Capture"];
            $("#aiwb-popup-title").val(sample.title);
            $("#aiwb-popup-content").val(sample.message);
            $("#aiwb-popup-button-text").val(sample.button_text);
            $("#aiwb-popup-button-url").val(sample.button_url);
            showToast("Sample content inserted.", "success");
        });

        $('.aiwb-preview-tab').on('click', function () {
            var target = $(this).data('preview');
            $('.aiwb-preview-tab').removeClass('is-active');
            $(this).addClass('is-active');
            $('#aiwb-popup-preview')
                .removeClass('aiwb-preview--desktop aiwb-preview--tablet aiwb-preview--mobile')
                .addClass('aiwb-preview--' + target);
        });

        loadPopupList();

        function applyFeaturedImageSelection(attachment) {
            if (bulkImageTarget && bulkImageTarget.length) {
                bulkImageTarget.find('.aiwb-bulk-featured-id').val(attachment.id || 0);
                safeSetHtml(bulkImageTarget.find('.aiwb-bulk-image-preview'), '<img src="' + escapeHtml(attachment.url) + '" style="max-width:100%;height:auto;">');
                bulkImageTarget = null;
                return;
            }
            $('#aiwb-featured-id').val(attachment.id || 0);
            safeSetHtml($('#aiwb-image-preview'), '<img src="' + escapeHtml(attachment.url) + '" style="max-width:100%;height:auto;">');
        }

        var fileFrame;
        function openMediaFrame() {
            if (!(window.wp && wp.media)) {
                showToast('Media library not available.', 'error');
                return;
            }
            if (fileFrame) {
                fileFrame.open();
                return;
            }
            fileFrame = wp.media({
                title: 'Select Featured Image',
                button: { text: 'Use this image' },
                multiple: false
            });
            fileFrame.on('select', function () {
                var attachment = fileFrame.state().get('selection').first().toJSON();
                applyFeaturedImageSelection(attachment);
            });
            fileFrame.open();
        }

        if (window.wp && wp.media) {
            $('#aiwb-upload-image').on('click', function (event) {
                event.preventDefault();
                openMediaFrame();
            });
        }

        function generateFeaturedImage() {
            var prompt = $('#aiwb-topic').val() || $('#aiwb-post-title').val() || '';
            openImageModal(prompt);
        }

        $('#aiwb-generate-image').on('click', generateFeaturedImage);
        $('#aiwb-regenerate-image').on('click', generateFeaturedImage);

        var imageState = { provider: '', page: 1, query: '' };

        function openImageModal(query) {
            imageState.query = query || '';
            imageState.page = 1;
            $('#aiwb-image-query').val(imageState.query);
            $('#aiwb-image-grid').empty();
            $('#aiwb-image-modal').addClass('is-open').attr('aria-hidden', 'false');
            var defaultProvider = $('#aiwb-image-modal').data('default-provider');
            if (defaultProvider && !imageState.provider) {
                setProvider(defaultProvider);
                return;
            }
            if (imageState.provider) {
                fetchImages(true);
            }
        }

        function closeImageModal() {
            $('#aiwb-image-modal').removeClass('is-open').attr('aria-hidden', 'true');
            bulkImageTarget = null;
        }

        function setProvider(provider) {
            imageState.provider = provider;
            $('.aiwb-provider-btn').removeClass('aiwb-provider-btn--active');
            $('.aiwb-provider-btn[data-provider="' + provider + '"]').addClass('aiwb-provider-btn--active');
            imageState.page = 1;
            $('#aiwb-image-grid').empty();
            fetchImages(true);
        }

        function fetchImages(reset) {
            var query = $('#aiwb-image-query').val().trim();
            if (!query) {
                showToast('Enter a search term.', 'error');
                return;
            }
            if (!imageState.provider) {
                showToast('Select an image provider.', 'error');
                return;
            }
            var page = imageState.page;
            $('#aiwb-image-load-more').prop('disabled', true).text('Loading...');
            apiPost('aiwb_image_search', {
                provider: imageState.provider,
                query: query,
                page: page,
                per_page: 12
            }, function (data) {
                var items = data.items || [];
                if (!items.length && page === 1) {
                    safeSetHtml($('#aiwb-image-grid'), '<p class="aiwb-muted">No images found.</p>');
                } else {
                    var html = '';
                    items.forEach(function (item) {
                        var thumb = escapeHtml(item.thumb);
                        var full = escapeHtml(item.full);
                        var author = escapeHtml(item.author || '');
                        var source = escapeHtml(item.source || '');
                        var pageLink = escapeHtml(item.page_url || '');
                        html += '<div class="aiwb-image-item">';
                        html += '<img src="' + thumb + '" alt="">';
                        html += '<button class="button aiwb-use-image" data-full="' + full + '" data-author="' + author + '" data-source="' + source + '" data-page="' + pageLink + '">Use</button>';
                        html += '</div>';
                    });
                    if (reset) {
            safeSetHtml($('#aiwb-image-grid'), html);
                    } else {
                        $('#aiwb-image-grid').append(sanitizeHtml(html));
                    }
                }
                $('#aiwb-image-load-more').prop('disabled', false).text('Load More');
            }, function (message) {
                showToast(message, 'error');
                $('#aiwb-image-load-more').prop('disabled', false).text('Load More');
            });
        }

        $(document).on('click', '.aiwb-provider-btn', function () {
            setProvider($(this).data('provider'));
        });

        $('#aiwb-image-search').on('click', function () {
            imageState.page = 1;
            $('#aiwb-image-grid').empty();
            fetchImages(true);
        });

        $('#aiwb-image-load-more').on('click', function () {
            imageState.page += 1;
            fetchImages(false);
        });

        $('#aiwb-image-close, .aiwb-modal__overlay').on('click', function () {
            closeImageModal();
        });

        $(document).on('click', '.aiwb-use-image', function () {
            var full = $(this).data('full');
            var author = $(this).data('author') || '';
            var source = $(this).data('source') || '';
            var pageUrl = $(this).data('page') || '';
            var title = 'Featured Image';
            if (bulkImageTarget && bulkImageTarget.length) {
                title = bulkImageTarget.find('.aiwb-bulk-title').val() || bulkImageTarget.data('keyword') || title;
            } else {
                title = $('#aiwb-topic').val() || $('#aiwb-post-title').val() || title;
            }
            apiPost('aiwb_image_attach', {
                url: full,
                title: title,
                source: source,
                author: author,
                page_url: pageUrl
            }, function (data) {
                if (data && data.url) {
                    applyFeaturedImageSelection(data);
                    showToast('Featured image selected.', 'success');
                    closeImageModal();
                } else {
                    showToast('Unable to attach image.', 'error');
                }
            }, function (message) {
                showToast(message, 'error');
            });
        });

        if ($('#aiwb-post-category').length) {
            apiPost('aiwb_get_categories', {}, function (data) {
                var $select = $('#aiwb-post-category');
                $select.find('option:not([value=\"0\"])').remove();
                (data.items || []).forEach(function (cat) {
                    $select.append('<option value="' + cat.id + '">' + cat.name + '</option>');
                });
            }, function () {});
        }

        $('#aiwb-add-category').on('click', function () {
            var name = $('#aiwb-new-category').val().trim();
            if (!name) {
                showToast('Enter a category name.', 'error');
                return;
            }
            apiPost('aiwb_create_category', { name: name }, function (data) {
                if (data && data.id) {
                    $('#aiwb-post-category').append('<option value="' + data.id + '">' + data.name + '</option>').val(data.id);
                    $('#aiwb-new-category').val('');
                    showToast('Category added.', 'success');
                }
            }, function (message) {
                showToast(message, 'error');
            });
        });

        if ($('#aiwb-topic').length) {
            var storedIdea = localStorage.getItem('aiwb_selected_idea');
            if (storedIdea) {
                localStorage.removeItem('aiwb_selected_idea');
                $('#aiwb-topic').val(storedIdea);
                $('#aiwb-content-result').val('Generating content...');
                restPostWithFallback('/generate-content', { topic: storedIdea, tone: 'professional', length: 'medium', keyword: '' }, 'aiwb_generate_content', function (response) {
                    $('#aiwb-content-result').val(formatGeneratedContent(response.content || ''));
                    showToast('Content generated from idea.', 'success');
                    $('.aiwb-copy-row').css('display', 'flex');
                }, function (message) {
                    $('#aiwb-content-result').val(message);
                    showToast(message, 'error');
                    $('.aiwb-copy-row').hide();
                });
            }
        }

        function computeSeoScore() {
            var title = ($('#aiwb-meta-title').val() || '').toLowerCase();
            var keyword = ($('#aiwb-meta-keyword').val() || '').toLowerCase();
            var description = ($('#aiwb-meta-description').val() || '').toLowerCase();
            var score = 0;
            if (keyword && title.indexOf(keyword) !== -1) {
                score += 20;
            }
            if (keyword && description.indexOf(keyword) !== -1) {
                score += 20;
            }
            if (title.length >= 30 && title.length <= 60) {
                score += 20;
            }
            if (description.length >= 120 && description.length <= 160) {
                score += 20;
            }
            if (keyword.length >= 3) {
                score += 20;
            }
            $('#aiwb-seo-score').text(score + '%');
            $('#aiwb-seo-progress').css('width', score + '%');
            updateSeoChecklist({
                keywordInTitle: keyword && title.indexOf(keyword) !== -1,
                keywordInDesc: keyword && description.indexOf(keyword) !== -1,
                titleLengthOk: title.length >= 30 && title.length <= 60,
                descLengthOk: description.length >= 120 && description.length <= 160,
                keywordOk: keyword.length >= 3
            });
        }

        function updateSeoChecklist(flags) {
            var $items = $('#aiwb-seo-checklist li');
            $items.removeClass('is-done');
            if ($items.length >= 5) {
                if (flags.keywordInTitle) { $items.eq(0).addClass('is-done'); }
                if (flags.keywordInDesc) { $items.eq(1).addClass('is-done'); }
                if (flags.titleLengthOk) { $items.eq(2).addClass('is-done'); }
                if (flags.descLengthOk) { $items.eq(3).addClass('is-done'); }
                if (flags.keywordOk) { $items.eq(4).addClass('is-done'); }
            }
        }

        $('#aiwb-meta-title, #aiwb-meta-keyword, #aiwb-meta-description').on('input', computeSeoScore);
        $('#aiwb-seo-faq').on('input', function () {
            if (!$('#aiwb-meta-description').val()) {
                computeSeoScore();
            }
        });
        computeSeoScore();
    });
})(jQuery);

